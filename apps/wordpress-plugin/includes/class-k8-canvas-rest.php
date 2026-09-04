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
        return self::update_response($ok, 'organisation');
    }

    public static function archive_organisation(WP_REST_Request $request)
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $ok = $wpdb->update(K8_Canvas_Schema::tables()['organisations'], ['status' => 'archived', 'archived_at' => $now, 'updated_at' => $now], ['id' => absint($request['id']), 'status' => 'active']);
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
        return self::update_response($ok, 'site');
    }

    public static function archive_site(WP_REST_Request $request)
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $ok = $wpdb->update(K8_Canvas_Schema::tables()['sites'], ['status' => 'archived', 'archived_at' => $now, 'updated_at' => $now], ['id' => absint($request['id']), 'status' => 'active']);
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
        return self::write_response($ok, $wpdb->insert_id, 'feature assignment');
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
