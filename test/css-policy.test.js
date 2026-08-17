import test from 'node:test';
import assert from 'node:assert/strict';
import { compileScopedCss, sanitizeDeclarations } from '../plugin/k8-canvas/css-policy.js';

test('keeps ordinary and custom-property declarations', () => {
  assert.equal(
    sanitizeDeclarations('color: #fff; --k8-gap: 2rem; padding: 1rem'),
    'color: #fff; --k8-gap: 2rem; padding: 1rem;'
  );
});

test('rejects selectors, imports and URL-bearing declarations', () => {
  assert.equal(
    sanitizeDeclarations('color:red;} body{display:none; background:url(https://bad.test); @import:x; padding:1rem'),
    'color:red; padding:1rem;'
  );
});

test('returns no rule content when every declaration is blocked', () => {
  assert.equal(sanitizeDeclarations('background:url(https://bad.test); @import:x'), '');
});

test('compiles base, tablet and mobile rules beneath one scope', () => {
  assert.equal(
    compileScopedCss('k8c-a1', {
      baseCss: 'padding: 3rem',
      tabletCss: 'padding: 2rem',
      mobileCss: 'padding: 1rem'
    }),
    '.k8c-a1{padding: 3rem;}@media (max-width: 1024px){.k8c-a1{padding: 2rem;}}@media (max-width: 767px){.k8c-a1{padding: 1rem;}}'
  );
});

test('refuses an unsafe scope', () => {
  assert.equal(compileScopedCss('k8c-ok} body', { baseCss: 'color:red' }), '');
});
