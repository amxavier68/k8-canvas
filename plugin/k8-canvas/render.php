<?php
/**
 * Server rendering for the K8 Responsive Panel block.
 *
 * @var array $attributes Block attributes.
 */

if (!defined('ABSPATH')) {
    exit;
}

$k8_canvas_sanitize_declarations = static function ($value): string {
    $value = is_string($value) ? $value : '';
    $blocked = '/[{}]|@import|expression\s*\(|javascript\s*:|data\s*:|url\s*\(|-moz-binding/i';
    $safe = [];

    foreach (explode(';', $value) as $declaration) {
        $declaration = trim($declaration);
        if ($declaration === '' || strpos($declaration, ':') === false) {
            continue;
        }
        if (preg_match($blocked, $declaration)) {
            continue;
        }
        if (!preg_match('/^(--[a-z0-9-_]+|[a-z-]+)\s*:/i', $declaration)) {
            continue;
        }
        $safe[] = $declaration;
    }

    return implode('; ', $safe) . ($safe ? ';' : '');
};

$scope = 'k8c-' . substr(md5(wp_json_encode($attributes)), 0, 12);
$base_css = $k8_canvas_sanitize_declarations($attributes['baseCss'] ?? '');
$tablet_css = $k8_canvas_sanitize_declarations($attributes['tabletCss'] ?? '');
$mobile_css = $k8_canvas_sanitize_declarations($attributes['mobileCss'] ?? '');
$style = '';

if ($base_css !== '') {
    $style .= '.' . $scope . '{' . $base_css . '}';
}
if ($tablet_css !== '') {
    $style .= '@media (max-width:1024px){.' . $scope . '{' . $tablet_css . '}}';
}
if ($mobile_css !== '') {
    $style .= '@media (max-width:767px){.' . $scope . '{' . $mobile_css . '}}';
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => "k8-canvas-panel {$scope}",
]);
?>
<?php if ($style !== '') : ?>
    <style id="<?php echo esc_attr("{$scope}-styles"); ?>"><?php echo wp_strip_all_tags($style); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<?php endif; ?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if (!empty($attributes['eyebrow'])) : ?>
        <p class="k8-canvas-panel__eyebrow"><?php echo wp_kses_post($attributes['eyebrow']); ?></p>
    <?php endif; ?>
    <h2 class="k8-canvas-panel__heading"><?php echo wp_kses_post($attributes['heading'] ?? ''); ?></h2>
    <?php if (!empty($attributes['body'])) : ?>
        <p class="k8-canvas-panel__body"><?php echo wp_kses_post($attributes['body']); ?></p>
    <?php endif; ?>
    <?php if (!empty($attributes['ctaLabel'])) : ?>
        <a class="k8-canvas-panel__cta" href="<?php echo esc_url($attributes['ctaUrl'] ?? '#'); ?>">
            <?php echo esc_html($attributes['ctaLabel']); ?>
        </a>
    <?php endif; ?>
</section>
