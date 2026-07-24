#!/usr/bin/env node
/**
 * Links the local `syn` CLI into node_modules/.bin so `npx syn` works from the project root.
 * No-ops when syn.mjs is absent (e.g. partial Docker layer) so installs never hard-fail.
 */
import { existsSync, mkdirSync, writeFileSync, chmodSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const BIN_DIR = join(ROOT, 'node_modules', '.bin');
const SCRIPT = join(ROOT, 'syn.mjs');

if (!existsSync(SCRIPT)) {
  process.exit(0);
}

mkdirSync(BIN_DIR, { recursive: true });

writeFileSync(
  join(BIN_DIR, 'syn.cmd'),
  `@ECHO off\r\nnode "${SCRIPT.replace(/\\/g, '\\\\')}" %*\r\n`,
);

const unixShim = `#!/usr/bin/env sh\nnode "${SCRIPT}" "$@"\n`;
writeFileSync(join(BIN_DIR, 'syn'), unixShim);
chmodSync(join(BIN_DIR, 'syn'), 0o755);
