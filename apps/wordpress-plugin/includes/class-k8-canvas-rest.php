<?php

defined('ABSPATH') || exit;

final class K8_Canvas_REST
{
    private const NAMESPACE = 'k8-canvas/v1';

    public static function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, '/organisations', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [self::class, 'list_organisations'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [self::class, 'create_organisation'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/organisations/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [self::class, 'update_organisation'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [self::class, 'archive_organisation'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/relationships', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [self::class, 'list_relationships'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [self::class, 'create_relationship'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/sites', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [self::class, 'list_sites'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [self::class, 'create_site'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/sites/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [self::class, 'update_site'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [self::class, 'archive_site'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/features', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'list_features'],
            'permission_callback' => [self::class, 'can_manage'],
        ]);
        register_rest_route(self::NAMESPACE, '/feature-assignments', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [self::class, 'set_feature_assignment'],
            'permission_callback' => [self::class, 'can_manage'],
        ]);
        register_rest_route(self::NAMESPACE, '/memberships', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [self::class, 'list_memberships'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [self::class, 'create_membership'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/memberships/(?P<id>\d+)', [
            ['methods' => WP_REST_Server::EDITABLE, 'callback' => [self::class, 'update_membership'], 'permission_callback' => [self::class, 'can_manage']],
            ['methods' => WP_REST_Server::DELETABLE, 'callback' => [self::class, 'revoke_membership'], 'permission_callback' => [self::class, 'can_manage']],
        ]);
        register_rest_route(self::NAMESPACE, '/audit-events', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'list_audit_events'],
            'permission_callback' => [self::class, 'can_manage'],
        ]);
    }

    public static function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public static function list_organisations(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = K8_Canvas_Schema::tables()['organisations'];
        $status = sanitize_key($request->get_param('status') ?: 'active');
        return self::response($wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE status = %s ORDER BY name", $status), ARRAY_A));
    }

    public static function create_organisation(WP_REST_Request $request)
    {
        global $wpdb;
        $name = sanitize_text_field((string) $request->get_param('name'));
        $type = sanitize_key((string) $request->get_param('organisation_type'));
        if ($name === '' || !in_array($type, ['platform', 'agency', 'client'], true)) {
            return new WP_Error('k8_invalid_organisation', 'A name and valid organisation type are required.', ['status' => 400]);
        }
        $now = current_time('mysql', true);
        $ok = $wpdb->insert(K8_Canvas_Schema::tables()['organisations'], [
            'name' => $name, 'slug' => sanitize_title($request->get_param('slug') ?: $name),
            'organisation_type' => $type, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ], ['%s', '%s', '%s', '%s', '%s', '%s']);
        if ($ok !== false) {
            K8_Canvas_Access::audit('organisation.create', 'organisation', (int) $wpdb->insert_id, (int) $wpdb->insert_id);
        }
        return self::write_response($ok, $wpdb->insert_id, 'organisation');
    }

    public static function update_organisation(WP_REST_Request $request)
    {
        global $wpdb;
        $data = ['updated_at' => current_time('mysql', true)];
        $formats = ['%s'];
        foreach (['name', 'slug'] as $field) {
            if ($request->has_param($field)) {
                $data[$field] = $field === 'slug' ? sanitize_title($request[$field]) : sanitize_text_field($request[$field]);
                $formats[] = '%s';
            }
        }
        $ok = $wpdb->update(K8_Canvas_Schema::tables()['organisations'], $data, ['id' => absint($request['id']), 'status' => 'active'], $formats, ['%d', '%s']);
        if ($ok > 0) {
            K8_Canvas_Access::audit('organisation.update', 'organisation', absint($request['id']), absint($request['id']));
        }
        return self::update_response($ok, 'organisation');
    }

    public static function archive_organisation(WP_REST_Request $request)
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $ok = $wpdb->update(K8_Canvas_Schema::tables()['organisations'], ['status' => 'archived', 'archived_at' => $now, 'updated_at' => $now], ['id' => absint($request['id']), 'status' => 'active']);
        if ($ok > 0) {
            K8_Canvas_Access::audit('organisation.archive', 'organisation', absint($request['id']), absint($request['id']));
        }
        return self::update_response($ok, 'organisation');
    }

    public static function list_relationships(): WP_REST_Response
    {
        global $wpdb;
        $table = K8_Canvas_Schema::tables()['relationships'];
        return self::response($wpdb->get_results("SELECT * FROM $table WHERE status = 'active' ORDER BY id", ARRAY_A));
    }

    public static function create_relationship(WP_REST_Request $request)
    {
        global $wpdb;
        $manager = absint($request->get_param('managing_organisation_id'));
        $managed = absint($request->get_param('managed_organisation_id'));
        if (!$manager || !$managed || $manager === $managed || !self::organisations_exist([$manager, $managed])) {
            return new WP_Error('k8_invalid_relationship', 'Two different, existing organisations are required.', ['status' => 400]);
        }
        $ok = $wpdb->insert(K8_Canvas_Schema::tables()['relationships'], [
            'managing_organisation_id' => $manager, 'managed_organisation_id' => $managed,
            'relationship_type' => 'agency_client', 'status' => 'active', 'starts_at' => current_time('mysql', true),
        ], ['%d', '%d', '%s', '%s', '%s']);
        if ($ok !== false) {
            K8_Canvas_Access::audit('relationship.create', 'relationship', (int) $wpdb->insert_id, $manager, ['managed_organisation_id' => $managed]);
        }
        return self::write_response($ok, $wpdb->insert_id, 'relationship');
    }

    public static function list_sites(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = K8_Canvas_Schema::tables()['sites'];
        $owner = absint($request->get_param('owning_organisation_id'));
        $rows = $owner
            ? $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE owning_organisation_id = %d AND status = 'active' ORDER BY name", $owner), ARRAY_A)
            : $wpdb->get_results("SELECT * FROM $table WHERE status = 'active' ORDER BY name", ARRAY_A);
        return self::response($rows);
    }

    public static function create_site(WP_REST_Request $request)
    {
        global $wpdb;
        $owner = absint($request->get_param('owning_organisation_id'));
        $name = sanitize_text_field((string) $request->get_param('name'));
        $url = esc_url_raw((string) $request->get_param('canonical_url'));
        if (!$owner || !self::organisations_exist([$owner]) || $name === '' || $url === '') {
            return new WP_Error('k8_invalid_site', 'An owning organisation, name and canonical URL are required.', ['status' => 400]);
        }
        $now = current_time('mysql', true);
        $ok = $wpdb->insert(K8_Canvas_Schema::tables()['sites'], [
            'owning_organisation_id' => $owner, 'name' => $name, 'canonical_url' => untrailingslashit($url),
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ], ['%d', '%s', '%s', '%s', '%s', '%s']);
        if ($ok !== false) {
            K8_Canvas_Access::audit('site.create', 'site', (int) $wpdb->insert_id, $owner);
        }
        return self::write_response($ok, $wpdb->insert_id, 'site');
    }

    public static function update_site(WP_REST_Request $request)
    {
        global $wpdb;
        $data = ['updated_at' => current_time('mysql', true)];
        foreach (['name', 'canonical_url'] as $field) {
            if ($request->has_param($field)) {
                $data[$field] = $field === 'canonical_url'
                    ? untrailingslashit(esc_url_raw((string) $request[$field]))
                    : sanitize_text_field((string) $request[$field]);
            }
        }
        $ok = $wpdb->update(K8_Canvas_Schema::tables()['sites'], $data, ['id' => absint($request['id']), 'status' => 'active']);
        if ($ok > 0) {
            $organisation = (int) $wpdb->get_var($wpdb->prepare("SELECT owning_organisation_id FROM " . K8_Canvas_Schema::tables()['sites'] . " WHERE id=%d", absint($request['id'])));
            K8_Canvas_Access::audit('site.update', 'site', absint($request['id']), $organisation);
        }
        return self::update_response($ok, 'site');
    }

    public static function archive_site(WP_REST_Request $request)
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $site_table = K8_Canvas_Schema::tables()['sites'];
        $organisation = (int) $wpdb->get_var($wpdb->prepare("SELECT owning_organisation_id FROM $site_table WHERE id=%d", absint($request['id'])));
        $ok = $wpdb->update($site_table, ['status' => 'archived', 'archived_at' => $now, 'updated_at' => $now], ['id' => absint($request['id']), 'status' => 'active']);
        if ($ok > 0) {
            K8_Canvas_Access::audit('site.archive', 'site', absint($request['id']), $organisation);
        }
        return self::update_response($ok, 'site');
    }

    public static function list_features(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $tables = K8_Canvas_Schema::tables();
        $organisation = absint($request->get_param('organisation_id'));
        $site = absint($request->get_param('site_id'));
        $boundary_type = $site ? 'site' : 'organisation';
        $boundary_id = $site ?: $organisation;
        $sql = "SELECT f.*, COALESCE(a.enabled, 0) AS enabled, a.configuration
                FROM {$tables['features']} f LEFT JOIN {$tables['feature_assignments']} a
                ON a.feature_id = f.id AND " . $wpdb->prepare('a.boundary_type = %s AND a.boundary_id = %d', $boundary_type, $boundary_id) . "
                WHERE f.lifecycle_status = 'active' ORDER BY f.name";
        return self::response($wpdb->get_results($sql, ARRAY_A));
    }

    public static function set_feature_assignment(WP_REST_Request $request)
    {
        global $wpdb;
        $feature = absint($request->get_param('feature_id'));
        $organisation = absint($request->get_param('organisation_id')) ?: null;
        $site = absint($request->get_param('site_id')) ?: null;
        if (!$feature || (!$organisation && !$site) || ($organisation && $site)) {
            return new WP_Error('k8_invalid_assignment', 'A feature and organisation or site boundary are required.', ['status' => 400]);
        }
        $boundary_type = $site ? 'site' : 'organisation';
        $boundary_id = $site ?: $organisation;
        $table = K8_Canvas_Schema::tables()['feature_assignments'];
        $configuration = $request->get_param('configuration');
        $configuration = is_array($configuration) ? wp_json_encode($configuration) : null;
        $ok = $wpdb->replace($table, [
            'feature_id' => $feature, 'boundary_type' => $boundary_type, 'boundary_id' => $boundary_id,
            'enabled' => rest_sanitize_boolean($request->get_param('enabled')) ? 1 : 0,
            'configuration' => $configuration, 'updated_at' => current_time('mysql', true),
        ], ['%d', '%s', '%d', '%d', '%s', '%s']);
        if ($ok !== false) {
            K8_Canvas_Access::audit('feature.assign', 'feature', $feature, $organisation, ['boundary_type' => $boundary_type, 'boundary_id' => $boundary_id, 'enabled' => rest_sanitize_boolean($request->get_param('enabled'))]);
        }
        return self::write_response($ok, $wpdb->insert_id, 'feature assignment');
    }

    public static function list_memberships(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $t = K8_Canvas_Schema::tables();
        $organisation = absint($request->get_param('organisation_id'));
        $where = $organisation ? $wpdb->prepare(' AND m.organisation_id=%d', $organisation) : '';
        $rows = $wpdb->get_results("SELECT m.id,m.user_id,u.user_login,u.user_email,m.organisation_id,o.name organisation_name,m.status,p.profile_key,p.name profile_name
            FROM {$t['memberships']} m JOIN {$wpdb->users} u ON u.ID=m.user_id JOIN {$t['organisations']} o ON o.id=m.organisation_id
            LEFT JOIN {$t['permission_grants']} g ON g.membership_id=m.id AND g.revoked_at IS NULL
            LEFT JOIN {$t['permission_profiles']} p ON p.id=g.permission_profile_id
            WHERE m.status='active'$where ORDER BY o.name,u.user_login", ARRAY_A);
        return self::response($rows);
    }

    public static function create_membership(WP_REST_Request $request)
    {
        global $wpdb;
        $identity = sanitize_text_field((string) $request->get_param('user'));
        $organisation = absint($request->get_param('organisation_id'));
        $profile_key = sanitize_key((string) $request->get_param('profile_key'));
        $user = is_email($identity) ? get_user_by('email', $identity) : get_user_by('login', $identity);
        $profile = $wpdb->get_row($wpdb->prepare("SELECT id FROM " . K8_Canvas_Schema::tables()['permission_profiles'] . " WHERE profile_key=%s AND status='active'", $profile_key));
        if (!$user || !$organisation || !$profile || !self::organisations_exist([$organisation])) {
            return new WP_Error('k8_invalid_membership', 'An existing WordPress user, organisation and permission profile are required.', ['status' => 400]);
        }
        $tables = K8_Canvas_Schema::tables();
        $membership_table = $tables['memberships'];
        $started = $wpdb->query('START TRANSACTION');
        $membership_write = $wpdb->query($wpdb->prepare("INSERT INTO $membership_table (user_id,organisation_id,status,created_at) VALUES (%d,%d,'active',%s) ON DUPLICATE KEY UPDATE status='active',ended_at=NULL", $user->ID, $organisation, current_time('mysql', true)));
        $membership_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM $membership_table WHERE user_id=%d AND organisation_id=%d", $user->ID, $organisation));
        $grant_revoke = $wpdb->update($tables['permission_grants'], ['revoked_at' => current_time('mysql', true)], ['membership_id' => $membership_id, 'boundary_type' => 'organisation', 'boundary_id' => $organisation, 'revoked_at' => null]);
        $ok = $wpdb->insert($tables['permission_grants'], [
            'membership_id' => $membership_id, 'permission_profile_id' => (int) $profile->id,
            'boundary_type' => 'organisation', 'boundary_id' => $organisation, 'created_at' => current_time('mysql', true),
        ], ['%d', '%d', '%s', '%d', '%s']);
        if ($started === false || $membership_write === false || !$membership_id || $grant_revoke === false || $ok === false || !K8_Canvas_Access::audit('membership.grant', 'membership', $membership_id, $organisation, ['profile_key' => $profile_key])) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('k8_membership_failed', 'The membership could not be saved safely. No access was granted.', ['status' => 500]);
        }
        if ($wpdb->query('COMMIT') === false) {
            return new WP_Error('k8_membership_commit_failed', 'The database could not confirm the membership change.', ['status' => 500]);
        }
        return self::write_response($ok, $membership_id, 'membership');
    }

    public static function update_membership(WP_REST_Request $request)
    {
        global $wpdb;
        $tables = K8_Canvas_Schema::tables();
        $membership_id = absint($request['id']);
        $profile_key = sanitize_key((string) $request->get_param('profile_key'));
        $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tables['memberships']} WHERE id=%d AND status='active'", $membership_id));
        $profile = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$tables['permission_profiles']} WHERE profile_key=%s AND status='active'", $profile_key));
        if (!$membership || !$profile) {
            return new WP_Error('k8_membership_not_found', 'The active membership or permission profile was not found.', ['status' => 404]);
        }
        $started = $wpdb->query('START TRANSACTION');
        $grant_revoke = $wpdb->query($wpdb->prepare("UPDATE {$tables['permission_grants']} SET revoked_at=%s WHERE membership_id=%d AND revoked_at IS NULL", current_time('mysql', true), $membership_id));
        $ok = $wpdb->insert($tables['permission_grants'], ['membership_id' => $membership_id, 'permission_profile_id' => (int) $profile->id, 'boundary_type' => 'organisation', 'boundary_id' => (int) $membership->organisation_id, 'created_at' => current_time('mysql', true)], ['%d', '%d', '%s', '%d', '%s']);
        if ($started === false || $grant_revoke === false || $ok === false || !K8_Canvas_Access::audit('membership.profile_change', 'membership', $membership_id, (int) $membership->organisation_id, ['profile_key' => $profile_key])) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('k8_membership_update_failed', 'The access profile could not be changed safely.', ['status' => 500]);
        }
        if ($wpdb->query('COMMIT') === false) {
            return new WP_Error('k8_membership_commit_failed', 'The database could not confirm the access-profile change.', ['status' => 500]);
        }
        return new WP_REST_Response(['updated' => true], 200);
    }

    public static function revoke_membership(WP_REST_Request $request)
    {
        global $wpdb;
        $tables = K8_Canvas_Schema::tables();
        $membership_id = absint($request['id']);
        $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tables['memberships']} WHERE id=%d AND status='active'", $membership_id));
        if (!$membership) {
            return new WP_Error('k8_membership_not_found', 'The active membership was not found.', ['status' => 404]);
        }
        $now = current_time('mysql', true);
        $started = $wpdb->query('START TRANSACTION');
        $grant_write = $wpdb->query($wpdb->prepare("UPDATE {$tables['permission_grants']} SET revoked_at=%s WHERE membership_id=%d AND revoked_at IS NULL", $now, $membership_id));
        $membership_write = $wpdb->update($tables['memberships'], ['status' => 'revoked', 'ended_at' => $now], ['id' => $membership_id, 'status' => 'active']);
        if ($started === false || $grant_write === false || $membership_write !== 1 || !K8_Canvas_Access::audit('membership.revoke', 'membership', $membership_id, (int) $membership->organisation_id)) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('k8_membership_revoke_failed', 'The membership could not be revoked safely.', ['status' => 500]);
        }
        if ($wpdb->query('COMMIT') === false) {
            return new WP_Error('k8_membership_commit_failed', 'The database could not confirm membership revocation.', ['status' => 500]);
        }
        return new WP_REST_Response(['revoked' => true], 200);
    }

    public static function list_audit_events(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $limit = min(100, max(1, absint($request->get_param('limit') ?: 50)));
        $t = K8_Canvas_Schema::tables();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT a.*,u.user_login FROM {$t['audit_events']} a LEFT JOIN {$wpdb->users} u ON u.ID=a.actor_user_id ORDER BY a.id DESC LIMIT %d", $limit), ARRAY_A);
        return self::response($rows);
    }

    private static function organisations_exist(array $ids): bool
    {
        global $wpdb;
        $ids = array_map('absint', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $table = K8_Canvas_Schema::tables()['organisations'];
        return count($ids) === (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id IN ($placeholders) AND status = 'active'", ...$ids));
    }

    private static function response(array $data): WP_REST_Response
    {
        return new WP_REST_Response(['data' => $data], 200);
    }

    private static function write_response($ok, int $id, string $resource)
    {
        global $wpdb;
        return $ok === false
            ? new WP_Error('k8_write_failed', sprintf('Unable to save %s: %s', $resource, $wpdb->last_error), ['status' => 500])
            : new WP_REST_Response(['id' => $id], 201);
    }

    private static function update_response($ok, string $resource)
    {
        if ($ok === false) {
            return new WP_Error('k8_update_failed', sprintf('Unable to update %s.', $resource), ['status' => 500]);
        }
        if ($ok === 0) {
            return new WP_Error('k8_not_found', sprintf('Active %s not found or no values changed.', $resource), ['status' => 404]);
        }
        return new WP_REST_Response(['updated' => true], 200);
    }
}
