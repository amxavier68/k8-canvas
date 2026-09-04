<?php

defined('ABSPATH') || exit;

final class K8_Canvas_Admin
{
    public static function register_menu(): void
    {
        add_menu_page('K8 Canvas', 'K8 Canvas', 'manage_options', 'k8-canvas', [self::class, 'render'], 'dashicons-layout', 58);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage K8 Canvas.', 'k8-canvas'));
        }
        global $wpdb;
        $tables = K8_Canvas_Schema::tables();
        $organisations = $wpdb->get_results("SELECT * FROM {$tables['organisations']} WHERE status = 'active' ORDER BY organisation_type, name");
        $sites = $wpdb->get_results("SELECT s.*, o.name AS owner_name FROM {$tables['sites']} s JOIN {$tables['organisations']} o ON o.id = s.owning_organisation_id WHERE s.status = 'active' ORDER BY o.name, s.name");
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('K8 Canvas Control', 'k8-canvas'); ?></h1>
            <p><?php echo esc_html__('Switch context from agency to client to site. All changes pass through the versioned REST API.', 'k8-canvas'); ?></p>
            <h2><?php echo esc_html__('Organisations', 'k8-canvas'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Name', 'k8-canvas'); ?></th><th><?php esc_html_e('Type', 'k8-canvas'); ?></th><th><?php esc_html_e('Status', 'k8-canvas'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($organisations as $organisation) : ?>
                    <tr><td><?php echo esc_html($organisation->name); ?></td><td><?php echo esc_html(ucfirst($organisation->organisation_type)); ?></td><td><?php echo esc_html($organisation->status); ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$organisations) : ?><tr><td colspan="3"><?php esc_html_e('No organisations yet. Use the API to create the platform and first agency.', 'k8-canvas'); ?></td></tr><?php endif; ?>
                </tbody>
            </table>
            <h2><?php echo esc_html__('Connected sites', 'k8-canvas'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Site', 'k8-canvas'); ?></th><th><?php esc_html_e('Owner', 'k8-canvas'); ?></th><th><?php esc_html_e('URL', 'k8-canvas'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($sites as $site) : ?>
                    <tr><td><?php echo esc_html($site->name); ?></td><td><?php echo esc_html($site->owner_name); ?></td><td><a href="<?php echo esc_url($site->canonical_url); ?>"><?php echo esc_html($site->canonical_url); ?></a></td></tr>
                <?php endforeach; ?>
                <?php if (!$sites) : ?><tr><td colspan="3"><?php esc_html_e('No sites registered.', 'k8-canvas'); ?></td></tr><?php endif; ?>
                </tbody>
            </table>
            <p><code><?php echo esc_html(rest_url('k8-canvas/v1')); ?></code></p>
        </div>
        <?php
    }
}
