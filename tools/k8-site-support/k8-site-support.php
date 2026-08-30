<?php
/**
 * Plugin Name: K8 Site Support Bundle
 * Description: Exports a privacy-conscious technical diagnostic bundle for WordPress support and development triage.
 * Version: 0.1.0-dev
 * Author: Kollabor8 Web Collectives
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'k8_support_bundle_register_page');
add_action('admin_post_k8_support_bundle_export', 'k8_support_bundle_export');

function k8_support_bundle_register_page(): void
{
    add_management_page(
        'K8 Site Support Bundle',
        'K8 Support Bundle',
        'manage_options',
        'k8-site-support-bundle',
        'k8_support_bundle_render_page'
    );
}

function k8_support_bundle_render_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $export_url = wp_nonce_url(
        admin_url('admin-post.php?action=k8_support_bundle_export'),
        'k8_support_bundle_export'
    );

    echo '<div class="wrap">';
    echo '<h1>K8 Site Support Bundle</h1>';
    echo '<p>Exports technical environment data for debugging. It deliberately excludes users, orders, customer data, passwords, API keys and secret option values.</p>';
    echo '<p><a class="button button-primary" href="' . esc_url($export_url) . '">Download diagnostic JSON</a></p>';
    echo '</div>';
}

function k8_support_bundle_export(): void
{
    if (! current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }

    check_admin_referer('k8_support_bundle_export');

    $bundle = k8_support_bundle_collect();
    $filename = 'k8-support-bundle-' . gmdate('Ymd-His') . '.json';

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo wp_json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function k8_support_bundle_collect(): array
{
    if (! function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    global $wpdb;

    $theme = wp_get_theme();
    $parent = $theme->parent();
    $all_plugins = get_plugins();
    $active_plugins = (array) get_option('active_plugins', []);

    $plugins = [];
    foreach ($all_plugins as $file => $data) {
        $plugins[] = [
            'file' => $file,
            'name' => $data['Name'] ?? $file,
            'version' => $data['Version'] ?? '',
            'active' => in_array($file, $active_plugins, true),
        ];
    }

    usort($plugins, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));

    $constants = [
        'WP_MEMORY_LIMIT' => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : null,
        'WP_MAX_MEMORY_LIMIT' => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : null,
        'WP_DEBUG' => defined('WP_DEBUG') ? (bool) WP_DEBUG : null,
        'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : null,
        'DISABLE_WP_CRON' => defined('DISABLE_WP_CRON') ? (bool) DISABLE_WP_CRON : null,
    ];

    $scheduled_actions = null;
    if (class_exists('ActionScheduler_Store')) {
        try {
            $store = ActionScheduler_Store::instance();
            $scheduled_actions = [
                'pending' => count($store->query_actions(['status' => ActionScheduler_Store::STATUS_PENDING, 'per_page' => 1000])),
                'failed' => count($store->query_actions(['status' => ActionScheduler_Store::STATUS_FAILED, 'per_page' => 1000])),
            ];
        } catch (Throwable $e) {
            $scheduled_actions = ['error' => 'Unable to query Action Scheduler safely.'];
        }
    }

    return [
        'schema' => 'k8-site-support-v1',
        'generated_at_utc' => gmdate('c'),
        'wordpress' => [
            'version' => get_bloginfo('version'),
            'multisite' => is_multisite(),
            'locale' => get_locale(),
            'permalink_structure' => get_option('permalink_structure'),
            'home_host' => wp_parse_url(home_url('/'), PHP_URL_HOST),
            'site_host' => wp_parse_url(site_url('/'), PHP_URL_HOST),
        ],
        'runtime' => [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'database_server_info' => method_exists($wpdb, 'db_server_info') ? $wpdb->db_server_info() : null,
        ],
        'constants' => $constants,
        'theme' => [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'stylesheet' => $theme->get_stylesheet(),
            'template' => $theme->get_template(),
            'parent_name' => $parent ? $parent->get('Name') : null,
            'parent_version' => $parent ? $parent->get('Version') : null,
        ],
        'plugins' => $plugins,
        'known_components' => [
            'elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
            'elementor_pro' => defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : null,
            'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null,
        ],
        'scheduled_actions' => $scheduled_actions,
        'privacy' => [
            'excluded' => [
                'users',
                'orders',
                'customer records',
                'form submissions',
                'passwords',
                'API keys',
                'tokens',
                'secret option values',
                'database contents',
            ],
        ],
    ];
}
