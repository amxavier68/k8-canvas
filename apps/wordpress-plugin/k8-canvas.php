<?php
/**
 * Plugin Name: K8 Canvas
 * Description: Governed, visibility-first building for WordPress.
 * Version: 0.1.0-alpha.1
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Kollabor8 Web Collectives
 * License: Proprietary
 */

defined('ABSPATH') || exit;

define('K8_CANVAS_VERSION', '0.1.0-alpha.1');
define('K8_CANVAS_PLUGIN_FILE', __FILE__);

/**
 * Foundation only. Product hooks enter through separately approved stories.
 */
function k8_canvas_foundation_status(): array
{
    return [
        'name' => 'K8 Canvas',
        'version' => K8_CANVAS_VERSION,
        'phase' => 'foundation',
    ];
}
