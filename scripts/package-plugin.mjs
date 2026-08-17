import { cp, mkdir, rm } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const source = resolve(root, 'plugin/k8-canvas');
const dist = resolve(root, 'dist');
const staged = resolve(dist, 'k8-canvas');
const archive = resolve(dist, 'k8-canvas-v0.1.0.zip');

await rm(dist, { recursive: true, force: true });
await mkdir(dist, { recursive: true });
await cp(source, staged, { recursive: true });

const zip = spawnSync('zip', ['-qr', archive, 'k8-canvas'], {
  cwd: dist,
  encoding: 'utf8'
});

if (zip.status !== 0) {
  process.stderr.write(zip.stderr || 'Plugin packaging failed.\n');
  process.exit(zip.status || 1);
}

process.stdout.write(`${archive}\n`);

