<?php

namespace K8\Canvas;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'loaded']);
    }

    public static function loaded(): void
    {
        /**
         * Fires once K8 Canvas has loaded.
         *
         * This is the extension point for future Elementor integration,
         * design tokens and bounded site-specific modules.
         */
        do_action('k8_canvas_loaded');
    }
}
