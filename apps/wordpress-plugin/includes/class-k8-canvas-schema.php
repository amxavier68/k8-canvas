<?php

defined('ABSPATH') || exit;

final class K8_Canvas_Schema
{
    public const VERSION = '1';

    public static function tables(): array
    {
        global $wpdb;

        return [
            'organisations' => $wpdb->prefix . 'k8_canvas_organisations',
            'relationships' => $wpdb->prefix . 'k8_canvas_organisation_relationships',
            'sites' => $wpdb->prefix . 'k8_canvas_sites',
            'features' => $wpdb->prefix . 'k8_canvas_features',
            'feature_assignments' => $wpdb->prefix . 'k8_canvas_feature_assignments',
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

        update_option('k8_canvas_schema_version', self::VERSION, false);
        self::seed_features();
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
}
