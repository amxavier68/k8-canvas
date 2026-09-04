<?php

defined('ABSPATH') || exit(1);

/**
 * Disposable WordPress/MySQL contract canary for KC-002.
 *
 * Run only against a fresh wp-env instance. It intentionally creates users and
 * tenant records so the server-side boundary is tested through the REST router.
 */

function k8_test_fail(string $message): void
{
    throw new RuntimeException($message);
}

function k8_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        k8_test_fail($message);
    }
}

function k8_test_request(string $method, string $path, array $parameters = []): WP_REST_Response
{
    $request = new WP_REST_Request($method, '/k8-canvas/v1' . $path);
    $request->set_body_params($parameters);
    $request->set_query_params($parameters);
    return rest_do_request($request);
}

function k8_test_expect_status(WP_REST_Response $response, int $expected, string $label): array
{
    $actual = $response->get_status();
    k8_test_assert($actual === $expected, sprintf('%s: expected HTTP %d, received %d (%s)', $label, $expected, $actual, wp_json_encode($response->get_data())));
    return (array) $response->get_data();
}

function k8_test_create_user(string $login): int
{
    $existing = get_user_by('login', $login);
    if ($existing) {
        return (int) $existing->ID;
    }
    $id = wp_insert_user([
        'user_login' => $login,
        'user_email' => $login . '@example.test',
        'user_pass' => wp_generate_password(24, true, true),
        'role' => 'subscriber',
    ]);
    k8_test_assert(!is_wp_error($id), 'Could not create disposable user ' . $login);
    return (int) $id;
}

function k8_test_ids(array $response_data): array
{
    return array_map('intval', wp_list_pluck($response_data['data'] ?? [], 'id'));
}

global $wpdb;

K8_Canvas_Schema::install();
$first_health = K8_Canvas_Schema::health();
k8_test_assert($first_health['ok'], 'Fresh schema health failed: ' . implode('; ', $first_health['errors']));
K8_Canvas_Schema::install();
$upgrade_health = K8_Canvas_Schema::health();
k8_test_assert($upgrade_health['ok'], 'Idempotent schema upgrade failed: ' . implode('; ', $upgrade_health['errors']));
k8_test_assert(get_option('k8_canvas_schema_version') === K8_Canvas_Schema::VERSION, 'Schema version was not locked after installation');

// Build the REST server; WordPress fires rest_api_init and registers plugin routes.
rest_get_server();

$admin = get_user_by('login', 'admin');
k8_test_assert($admin instanceof WP_User, 'wp-env administrator was not found');
wp_set_current_user($admin->ID);

$organisation_a = k8_test_expect_status(k8_test_request('POST', '/organisations', ['name' => 'Organisation A', 'organisation_type' => 'agency']), 201, 'create Organisation A')['id'];
$organisation_b = k8_test_expect_status(k8_test_request('POST', '/organisations', ['name' => 'Organisation B', 'organisation_type' => 'agency']), 201, 'create Organisation B')['id'];

$owner_a = k8_test_create_user('k8-owner-a');
$viewer_a = k8_test_create_user('k8-viewer-a');
$owner_b = k8_test_create_user('k8-owner-b');

$membership_a = k8_test_expect_status(k8_test_request('POST', '/memberships', ['user' => 'k8-owner-a', 'organisation_id' => $organisation_a, 'profile_key' => 'owner']), 201, 'grant Owner A')['id'];
$viewer_membership = k8_test_expect_status(k8_test_request('POST', '/memberships', ['user' => 'k8-viewer-a', 'organisation_id' => $organisation_a, 'profile_key' => 'viewer']), 201, 'grant Viewer A')['id'];
$membership_b = k8_test_expect_status(k8_test_request('POST', '/memberships', ['user' => 'k8-owner-b', 'organisation_id' => $organisation_b, 'profile_key' => 'owner']), 201, 'grant Owner B')['id'];

$site_a = k8_test_expect_status(k8_test_request('POST', '/sites', ['owning_organisation_id' => $organisation_a, 'name' => 'Site A', 'canonical_url' => 'https://a.example.test']), 201, 'create Site A')['id'];
$site_b = k8_test_expect_status(k8_test_request('POST', '/sites', ['owning_organisation_id' => $organisation_b, 'name' => 'Site B', 'canonical_url' => 'https://b.example.test']), 201, 'create Site B')['id'];

// Owner A can see and mutate A, but cannot see or mutate B even with guessed IDs.
wp_set_current_user($owner_a);
k8_test_assert(K8_Canvas_REST::can_manage(), 'Owner A did not pass the REST grant boundary');
$owner_a_orgs = k8_test_expect_status(k8_test_request('GET', '/organisations'), 200, 'Owner A organisation list');
k8_test_assert(k8_test_ids($owner_a_orgs) === [(int) $organisation_a], 'Owner A organisation list crossed tenant boundaries');
k8_test_expect_status(k8_test_request('POST', '/sites/' . $site_a, ['name' => 'Site A — Owner Updated']), 200, 'Owner A updates Site A');
k8_test_expect_status(k8_test_request('GET', '/sites', ['owning_organisation_id' => $organisation_b]), 403, 'Owner A guessed Organisation B read');
k8_test_expect_status(k8_test_request('POST', '/sites/' . $site_b, ['name' => 'Cross-tenant mutation']), 403, 'Owner A guessed Site B mutation');

// Viewer A can read A. A denied mutation must change neither state nor audit.
wp_set_current_user($viewer_a);
$viewer_orgs = k8_test_expect_status(k8_test_request('GET', '/organisations'), 200, 'Viewer A organisation list');
k8_test_assert(k8_test_ids($viewer_orgs) === [(int) $organisation_a], 'Viewer A organisation list crossed tenant boundaries');
$tables = K8_Canvas_Schema::tables();
$before_name = (string) $wpdb->get_var($wpdb->prepare("SELECT name FROM {$tables['sites']} WHERE id=%d", $site_a));
$before_audit = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit_events']}");
k8_test_expect_status(k8_test_request('POST', '/sites/' . $site_a, ['name' => 'Viewer mutation']), 403, 'Viewer A mutation');
$after_name = (string) $wpdb->get_var($wpdb->prepare("SELECT name FROM {$tables['sites']} WHERE id=%d", $site_a));
$after_audit = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit_events']}");
k8_test_assert($after_name === $before_name, 'Denied Viewer A mutation changed Site A');
k8_test_assert($after_audit === $before_audit, 'Denied Viewer A mutation created an audit success event');
k8_test_expect_status(k8_test_request('GET', '/sites', ['owning_organisation_id' => $organisation_b]), 403, 'Viewer A guessed Organisation B read');

// Owner B is symmetrically isolated from A.
wp_set_current_user($owner_b);
$owner_b_orgs = k8_test_expect_status(k8_test_request('GET', '/organisations'), 200, 'Owner B organisation list');
k8_test_assert(k8_test_ids($owner_b_orgs) === [(int) $organisation_b], 'Owner B organisation list crossed tenant boundaries');
k8_test_expect_status(k8_test_request('POST', '/sites/' . $site_b, ['name' => 'Site B — Owner Updated']), 200, 'Owner B updates Site B');
k8_test_expect_status(k8_test_request('GET', '/sites', ['owning_organisation_id' => $organisation_a]), 403, 'Owner B guessed Organisation A read');
k8_test_expect_status(k8_test_request('POST', '/sites/' . $site_a, ['name' => 'Cross-tenant mutation']), 403, 'Owner B guessed Site A mutation');

// Profile replacement leaves one active grant and changes the next request.
wp_set_current_user($admin->ID);
k8_test_expect_status(k8_test_request('POST', '/memberships/' . $viewer_membership, ['profile_key' => 'editor']), 200, 'replace Viewer A profile');
$active_grants = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['permission_grants']} WHERE membership_id=%d AND revoked_at IS NULL", $viewer_membership));
k8_test_assert($active_grants === 1, 'Profile replacement did not leave exactly one active grant');
wp_set_current_user($viewer_a);
k8_test_expect_status(k8_test_request('POST', '/sites/' . $site_a, ['name' => 'Site A — Editor Updated']), 200, 'reprofiled Viewer A updates Site A');

// Revocation is enforced on the very next routed request.
wp_set_current_user($admin->ID);
k8_test_expect_status(k8_test_request('DELETE', '/memberships/' . $membership_a), 200, 'revoke Owner A');
wp_set_current_user($owner_a);
k8_test_assert(!K8_Canvas_REST::can_manage(), 'Revoked Owner A retained the REST grant boundary');
k8_test_expect_status(k8_test_request('GET', '/organisations'), 403, 'revoked Owner A next request');

// Unauthenticated requests are closed.
wp_set_current_user(0);
k8_test_assert(!K8_Canvas_REST::can_manage(), 'Unauthenticated caller passed can_manage');
k8_test_expect_status(k8_test_request('GET', '/organisations'), 401, 'unauthenticated organisation request');

// Consequential successful writes are visible in the append-only ledger.
wp_set_current_user($admin->ID);
$successful_updates = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['audit_events']} WHERE action_key='site.update' AND resource_id IN (%d,%d)", $site_a, $site_b));
k8_test_assert($successful_updates >= 3, 'Expected successful site updates were not written to the audit ledger');

echo "PBAC CONTRACT PASS\n";
echo wp_json_encode([
    'schema_version' => K8_Canvas_Schema::VERSION,
    'plugin_version' => K8_CANVAS_VERSION,
    'organisations' => [(int) $organisation_a, (int) $organisation_b],
    'users' => ['owner_a' => $owner_a, 'viewer_a' => $viewer_a, 'owner_b' => $owner_b],
    'memberships' => ['owner_a' => (int) $membership_a, 'viewer_a' => (int) $viewer_membership, 'owner_b' => (int) $membership_b],
    'audit_events' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit_events']}"),
], JSON_PRETTY_PRINT) . "\n";
