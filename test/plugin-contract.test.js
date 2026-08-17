import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const read = (path) => readFile(new URL(`../${path}`, import.meta.url), 'utf8');

test('plugin metadata and block metadata agree on version', async () => {
  const [plugin, block] = await Promise.all([
    read('plugin/k8-canvas/k8-canvas.php'),
    read('plugin/k8-canvas/block.json').then(JSON.parse)
  ]);

  assert.match(plugin, /Plugin Name:\s*K8 Canvas/);
  assert.match(plugin, /Version:\s*0\.1\.0/);
  assert.equal(block.version, '0.1.0');
  assert.equal(block.apiVersion, 3);
  assert.equal(block.render, 'file:./render.php');
});

test('editor asset declares every WordPress global used by the block', async () => {
  const asset = await read('plugin/k8-canvas/editor.asset.php');
  for (const handle of ['wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n']) {
    assert.match(asset, new RegExp(`'${handle}'`));
  }
});

test('server rendering preserves the scoped CSS safety boundary', async () => {
  const render = await read('plugin/k8-canvas/render.php');
  assert.match(render, /@import/);
  assert.match(render, /javascript\\s\*:/);
  assert.match(render, /url\\s\*\\\(/);
  assert.match(render, /get_block_wrapper_attributes/);
  assert.match(render, /esc_url/);
  assert.match(render, /esc_html/);
});

