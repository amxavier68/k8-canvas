<?php

defined('ABSPATH') || exit;

final class K8_Canvas_Schema
{
    public const VERSION = '3';

    public static function tables(): array
    {
        global $wpdb;

        return [
            'organisations' => $wpdb->prefix . 'k8_canvas_organisations',
            'relationships' => $wpdb->prefix . 'k8_canvas_organisation_relationships',
            'sites' => $wpdb->prefix . 'k8_canvas_sites',
            'features' => $wpdb->prefix . 'k8_canvas_features',
            'feature_assignments' => $wpdb->prefix . 'k8_canvas_feature_assignments',
            'memberships' => $wpdb->prefix . 'k8_canvas_memberships',
            'permission_profiles' => $wpdb->prefix . 'k8_canvas_permission_profiles',
            'permission_grants' => $wpdb->prefix . 'k8_canvas_permission_grants',
            'audit_events' => $wpdb->prefix . 'k8_canvas_audit_events',
        ];
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $tables = self::tables();
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$tables['organisations']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            slug varchar(190) NOT NULL,
            organisation_type varchar(32) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            archived_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY organisation_type (organisation_type),
            KEY status (status)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['relationships']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            managing_organisation_id bigint unsigned NOT NULL,
            managed_organisation_id bigint unsigned NOT NULL,
            relationship_type varchar(32) NOT NULL DEFAULT 'agency_client',
            status varchar(20) NOT NULL DEFAULT 'active',
            starts_at datetime NOT NULL,
            ends_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY relationship (managing_organisation_id,managed_organisation_id,relationship_type),
            KEY managed_organisation_id (managed_organisation_id),
            KEY status (status)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['sites']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            owning_organisation_id bigint unsigned NOT NULL,
            name varchar(190) NOT NULL,
            canonical_url varchar(500) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            archived_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY canonical_url (canonical_url(191)),
            KEY owning_organisation_id (owning_organisation_id),
            KEY status (status)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['features']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            feature_key varchar(100) NOT NULL,
            name varchar(190) NOT NULL,
            description text NULL,
            lifecycle_status varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY feature_key (feature_key)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['feature_assignments']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            feature_id bigint unsigned NOT NULL,
            boundary_type varchar(20) NOT NULL,
            boundary_id bigint unsigned NOT NULL,
            enabled tinyint(1) NOT NULL DEFAULT 0,
            configuration longtext NULL,
            effective_from datetime NULL,
            effective_until datetime NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY boundary_feature (feature_id,boundary_type,boundary_id),
            KEY boundary (boundary_type,boundary_id)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['memberships']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            organisation_id bigint unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            ended_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_organisation (user_id,organisation_id),
            KEY organisation_id (organisation_id),
            KEY status (status)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['permission_profiles']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            profile_key varchar(50) NOT NULL,
            name varchar(100) NOT NULL,
            permissions longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY profile_key (profile_key)
        ) $charset;");

        dbDelta("CREATE TABLE {$tables['permission_grants']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            membership_id bigint unsigned NOT NULL,
            permission_profile_id bigint unsigned NOT NULL,
            boundary_type varchar(20) NOT NULL DEFAULT 'organisation',
            boundary_id bigint unsigned NOT NULL,
            created_at datetime NOT NULL,
            revoked_at datetime NULL,
            PRIMARY KEY  (id),
            KEY membership_boundary (membership_id,permission_profile_id,boundary_type,boundary_id),
            KEY boundary (boundary_type,boundary_id),
            KEY revoked_at (revoked_at)
        ) $charset;");

        $grant_index = $wpdb->get_row("SHOW INDEX FROM {$tables['permission_grants']} WHERE Key_name='membership_boundary'");
        if ($grant_index && (int) $grant_index->Non_unique === 0) {
            $wpdb->query("ALTER TABLE {$tables['permission_grants']} DROP INDEX membership_boundary, ADD INDEX membership_boundary (membership_id,permission_profile_id,boundary_type,boundary_id)");
        }

        dbDelta("CREATE TABLE {$tables['audit_events']} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            request_id varchar(64) NOT NULL,
            actor_user_id bigint unsigned NOT NULL,
            action_key varchar(100) NOT NULL,
            resource_type varchar(50) NOT NULL,
            resource_id bigint unsigned NOT NULL,
            organisation_id bigint unsigned NULL,
            metadata longtext NULL,
            occurred_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY actor_user_id (actor_user_id),
            KEY resource (resource_type,resource_id),
            KEY organisation_id (organisation_id),
            KEY occurred_at (occurred_at)
        ) $charset;");

        self::seed_features();
        self::seed_permission_profiles();
        $health = self::health();
        if ($health['ok']) {
            update_option('k8_canvas_schema_version', self::VERSION, false);
        } else {
            delete_option('k8_canvas_schema_version');
            update_option('k8_canvas_schema_error', implode('; ', $health['errors']), false);
        }
    }

    private static function seed_features(): void
    {
        global $wpdb;
        $table = self::tables()['features'];
        $features = [
            ['page_builder', 'Canvas page building'],
            ['component_editor', 'Component editing'],
            ['design_system', 'Design-system controls'],
            ['publishing', 'Preview and publishing'],
            ['seo_metadata', 'SEO metadata'],
            ['woocommerce', 'WooCommerce controls'],
        ];

        foreach ($features as [$key, $name]) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $table (feature_key, name, lifecycle_status) VALUES (%s, %s, 'active')
                 ON DUPLICATE KEY UPDATE name = VALUES(name)",
                $key,
                $name
            ));
        }
    }

    private static function seed_permission_profiles(): void
    {
        global $wpdb;
        $table = self::tables()['permission_profiles'];
        $profiles = [
            'owner' => ['Owner', ['organisation.*', 'site.*', 'feature.*', 'membership.*', 'audit.read']],
            'editor' => ['Editor', ['organisation.read', 'site.read', 'site.update', 'feature.read', 'feature.update']],
            'viewer' => ['Viewer', ['organisation.read', 'site.read', 'feature.read']],
        ];
        foreach ($profiles as $key => [$name, $permissions]) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $table (profile_key,name,permissions,status) VALUES (%s,%s,%s,'active') ON DUPLICATE KEY UPDATE name=VALUES(name),permissions=VALUES(permissions)",
                $key,
                $name,
                wp_json_encode($permissions)
            ));
        }
    }

    public static function health(): array
    {
        global $wpdb;
        $tables = self::tables();
        $errors = [];
        foreach ($tables as $key => $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
                $errors[] = "Missing table: $key";
            }
        }
        if (!$errors) {
            $index = $wpdb->get_row("SHOW INDEX FROM {$tables['permission_grants']} WHERE Key_name='membership_boundary'");
            if (!$index || (int) $index->Non_unique !== 1) {
                $errors[] = 'Permission grant history index is not non-unique';
            }
            $profiles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['permission_profiles']} WHERE profile_key IN ('owner','editor','viewer') AND status='active'");
            if ($profiles !== 3) {
                $errors[] = 'Owner, Editor and Viewer profiles were not seeded';
            }
            $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $tables['permission_grants']));
            if (strtolower((string) $engine) !== 'innodb') {
                $errors[] = 'Permission grants require the InnoDB transaction engine';
            }
        }
        return ['ok' => !$errors, 'errors' => $errors];
    }
}
