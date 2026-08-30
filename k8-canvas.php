<?php
/**
 * Plugin Name: K8 Canvas
 * Description: WordPress-native visual development and scoped custom CSS layer for Kollabor8 Web Collectives.
 * Version: 0.1.0-dev
 * Author: Kollabor8 Web Collectives
 * Text Domain: k8-canvas
 */

if (! defined('ABSPATH')) {
    exit;
}

define('K8_CANVAS_VERSION', '0.1.0-dev');
define('K8_CANVAS_FILE', __FILE__);
define('K8_CANVAS_DIR', plugin_dir_path(__FILE__));

require_once K8_CANVAS_DIR . 'src/Plugin.php';

K8\Canvas\Plugin::boot();
