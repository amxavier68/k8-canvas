(function (blocks, blockEditor, components, element, i18n) {
  const el = element.createElement;
  const Fragment = element.Fragment;
  const InspectorControls = blockEditor.InspectorControls;
  const RichText = blockEditor.RichText;
  const useBlockProps = blockEditor.useBlockProps;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const TextareaControl = components.TextareaControl;
  const __ = i18n.__;

  function safeDeclarations(value) {
    const blocked = /[{}]|@import|expression\s*\(|javascript\s*:|data\s*:|url\s*\(|-moz-binding/i;
    return String(value || '')
      .split(';')
      .map(function (item) { return item.trim(); })
      .filter(function (item) {
        return item && item.indexOf(':') > 0 && !blocked.test(item) && /^(--[a-z0-9-_]+|[a-z-]+)\s*:/i.test(item);
      })
      .join('; ');
  }

  blocks.registerBlockType('k8-canvas/responsive-panel', {
    edit: function (props) {
      const attributes = props.attributes;
      const setAttributes = props.setAttributes;
      const scope = 'k8c-editor-' + props.clientId.replace(/[^a-z0-9]/gi, '').slice(0, 12);
      const css = '.' + scope + '{' + safeDeclarations(attributes.baseCss) + '}' +
        '@media(max-width:1024px){.' + scope + '{' + safeDeclarations(attributes.tabletCss) + '}}' +
        '@media(max-width:767px){.' + scope + '{' + safeDeclarations(attributes.mobileCss) + '}}';
      const blockProps = useBlockProps({ className: 'k8-canvas-panel ' + scope });

      return el(Fragment, null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Responsive CSS', 'k8-canvas'), initialOpen: true },
            el(TextareaControl, {
              label: __('Desktop / base declarations', 'k8-canvas'),
              help: __('CSS declarations only. Selectors, braces and URLs are rejected.', 'k8-canvas'),
              value: attributes.baseCss,
              onChange: function (value) { setAttributes({ baseCss: value }); }
            }),
            el(TextareaControl, {
              label: __('Tablet — 1024px and below', 'k8-canvas'),
              value: attributes.tabletCss,
              onChange: function (value) { setAttributes({ tabletCss: value }); }
            }),
            el(TextareaControl, {
              label: __('Mobile — 767px and below', 'k8-canvas'),
              value: attributes.mobileCss,
              onChange: function (value) { setAttributes({ mobileCss: value }); }
            })
          ),
          el(PanelBody, { title: __('Call to action', 'k8-canvas'), initialOpen: false },
            el(TextControl, {
              label: __('Button label', 'k8-canvas'),
              value: attributes.ctaLabel,
              onChange: function (value) { setAttributes({ ctaLabel: value }); }
            }),
            el(TextControl, {
              label: __('Button URL', 'k8-canvas'),
              type: 'url',
              value: attributes.ctaUrl,
              onChange: function (value) { setAttributes({ ctaUrl: value }); }
            })
          )
        ),
        el('style', null, css),
        el('section', blockProps,
          el(RichText, {
            tagName: 'p', className: 'k8-canvas-panel__eyebrow',
            value: attributes.eyebrow,
            onChange: function (value) { setAttributes({ eyebrow: value }); },
            placeholder: __('Eyebrow', 'k8-canvas')
          }),
          el(RichText, {
            tagName: 'h2', className: 'k8-canvas-panel__heading',
            value: attributes.heading,
            onChange: function (value) { setAttributes({ heading: value }); },
            placeholder: __('Heading', 'k8-canvas')
          }),
          el(RichText, {
            tagName: 'p', className: 'k8-canvas-panel__body',
            value: attributes.body,
            onChange: function (value) { setAttributes({ body: value }); },
            placeholder: __('Supporting copy', 'k8-canvas')
          }),
          attributes.ctaLabel ? el('span', { className: 'k8-canvas-panel__cta' }, attributes.ctaLabel) : null
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n);

