<?php
/**
 * Plugin Name: K8 Canvas
 * Description: Governed, visibility-first building for WordPress.
 * Version: 0.3.1-alpha.2
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Kollabor8 Web Collectives
 * License: Proprietary
 */

defined('ABSPATH') || exit;

define('K8_CANVAS_VERSION', '0.3.1-alpha.2');
define('K8_CANVAS_PLUGIN_FILE', __FILE__);
define('K8_CANVAS_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once K8_CANVAS_PLUGIN_DIR . 'includes/class-k8-canvas-schema.php';
require_once K8_CANVAS_PLUGIN_DIR . 'includes/class-k8-canvas-access.php';
require_once K8_CANVAS_PLUGIN_DIR . 'includes/class-k8-canvas-rest.php';
require_once K8_CANVAS_PLUGIN_DIR . 'includes/class-k8-canvas-admin.php';

register_activation_hook(__FILE__, ['K8_Canvas_Schema', 'install']);
add_action('plugins_loaded', static function (): void {
    if (get_option('k8_canvas_schema_version') !== K8_Canvas_Schema::VERSION) {
        K8_Canvas_Schema::install();
    }
});
add_action('rest_api_init', ['K8_Canvas_REST', 'register_routes']);
add_action('admin_menu', ['K8_Canvas_Admin', 'register_menu']);

/**
 * Stable operational metadata for diagnostics and contract checks.
 */
function k8_canvas_foundation_status(): array
{
    return [
        'name' => 'K8 Canvas',
        'version' => K8_CANVAS_VERSION,
        'phase' => 'multi-tenant-mvp',
    ];
}
