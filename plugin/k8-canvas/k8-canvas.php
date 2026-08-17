<?php
/**
 * Plugin Name: K8 Canvas
 * Description: WordPress-native responsive blocks and scoped custom CSS for the Kollabor8 ecosystem.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Kollabor8 Web Collectives
 * License: GPL-2.0-or-later
 * Text Domain: k8-canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

function k8_canvas_register_blocks(): void
{
    register_block_type(__DIR__);
}

add_action('init', 'k8_canvas_register_blocks');

