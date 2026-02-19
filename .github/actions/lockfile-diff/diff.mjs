#!/usr/bin/env node
/**
 * lockfile-diff/diff.mjs
 *
 * Parses two lockfiles (base vs head), computes added/removed/updated packages,
 * and prints a Markdown summary to stdout.
 *
 * Supported formats:
 *   - yarn.lock v1 (classic) via @yarnpkg/lockfile
 *   - package-lock.json v1/v2/v3 (npm 5+) via JSON.parse
 *
 * CLI args:
 *   --type               yarn | npm
 *   --base               path to base lockfile
 *   --head               path to head lockfile
 *   --collapse-threshold number (default: 25)
 *   --deps-dir           path where @yarnpkg/lockfile was npm-installed
 */

import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';
import { createRequire } from 'module';
import { env } from 'process';
import { parseArgs } from 'util';

// Argument parsing
const { values: args } = parseArgs({
  options: {
    type: { type: 'string' },
    base: { type: 'string' },
    head: { type: 'string' },
    'collapse-threshold': { type: 'string' },
    'deps-dir': { type: 'string' },
  },
});

const TYPE               = args['type']               ?? 'yarn';
const BASE_PATH          = args['base'];
const HEAD_PATH          = args['head'];
const COLLAPSE_THRESHOLD = parseInt(args['collapse-threshold'] ?? '25', 10);
const DEPS_DIR           = args['deps-dir'];

if (!BASE_PATH || !HEAD_PATH) {
  console.error('Usage: diff.mjs --type yarn|npm --base <path> --head <path> [--collapse-threshold N] [--deps-dir <path>]');
  process.exit(1);
}

// Parsers — each returns Map<packageName, resolvedVersion>

/**
 * Yarn classic v1.
 *
 * A createRequire() anchored inside DEPS_DIR ensures @yarnpkg/lockfile and
 * its own transitive requires resolve correctly regardless of where this
 * script lives in the repository.
 *
 * Key format examples:
 *   react@^18.0.0:
 *   "react@^18.0.0, react@^18.2.0":
 */
function parseYarn(content) {
  if (!DEPS_DIR) throw new Error('--deps-dir is required for yarn lockfile parsing');

  const depsRequire   = createRequire(new URL(`file://${resolve(DEPS_DIR)}/sentinel.js`));
  const classicParser = depsRequire('@yarnpkg/lockfile');

  const result = classicParser.parse(content);
  if (result.type !== 'success') throw new Error('Failed to parse yarn.lock');

  const map = new Map();
  for (const [key, entry] of Object.entries(result.object)) {
    const firstName = key.split(',')[0].trim();
    const atIndex   = firstName.lastIndexOf('@');
    const name      = atIndex > 0 ? firstName.slice(0, atIndex) : firstName;
    // Keep the highest version if the same package appears under multiple specifier groups.
    if (!map.has(name) || compareVersions(entry.version, map.get(name)) > 0) {
      map.set(name, entry.version);
    }
  }
  return map;
}

/**
 * package-lock.json (npm 5+).
 * v2/v3 uses `packages` (keys prefixed "node_modules/…").
 * v1 uses `dependencies`. v2 includes both; we prefer `packages`.
 */
function parseNpm(content) {
  const lock = JSON.parse(content);
  const map  = new Map();

  if (lock.packages) {
    for (const [key, entry] of Object.entries(lock.packages)) {
      if (!key) continue; // root entry has an empty string key in v2/v3
      const name = key.replace(/^node_modules\//, '');
      if (entry.version) map.set(name, entry.version);
    }
  } else if (lock.dependencies) {
    for (const [name, entry] of Object.entries(lock.dependencies)) {
      if (entry.version) map.set(name, entry.version);
    }
  }

  return map;
}

// Semver comparison (major.minor.patch — sufficient for diff purposes)
function compareVersions(a, b) {
  const nums = (v) => String(v).split('-')[0].replace(/^[^0-9]*/, '').split('.').map(Number);
  const [aMaj = 0, aMin = 0, aPat = 0] = nums(a);
  const [bMaj = 0, bMin = 0, bPat = 0] = nums(b);
  return aMaj !== bMaj ? aMaj - bMaj
       : aMin !== bMin ? aMin - bMin
       : aPat - bPat;
}

// File loading — gracefully handles missing or empty files
function parseFile(filePath) {
  if (!existsSync(filePath)) return new Map();
  const content = readFileSync(filePath, 'utf8').trim();
  if (!content) return new Map();
  return TYPE === 'npm' ? parseNpm(content) : parseYarn(content);
}

// Diff computation
const baseMap = parseFile(BASE_PATH);
const headMap = parseFile(HEAD_PATH);

const added   = [];
const removed = [];
const updated = [];

for (const name of [...new Set([...baseMap.keys(), ...headMap.keys()])].sort()) {
  const baseVer = baseMap.get(name);
  const headVer = headMap.get(name);

  if      (!baseVer && headVer)                       added.push({ name, version: headVer });
  else if (baseVer && !headVer)                       removed.push({ name, version: baseVer });
  else if (baseVer && headVer && baseVer !== headVer) updated.push({
    name, from: baseVer, to: headVer,
    downgrade: compareVersions(baseVer, headVer) > 0,
  });
}

const hasDowngrade  = updated.some((p) => p.downgrade);
const totalChanges  = added.length + removed.length + updated.length;
const lockfileLabel = TYPE === 'yarn' ? 'yarn.lock' : 'package-lock.json';
const shortSha      = (env.GITHUB_SHA ?? '').slice(0, 7) || 'unknown';
const timestamp     = new Date().toISOString();

// Markdown output
function buildTables() {
  let out = '';

  if (added.length) {
    out += `### ➕ Added (${added.length})\n\n`;
    out += `| Package | Version |\n|---|---|\n`;
    for (const { name, version } of added) out += `| \`${name}\` | \`${version}\` |\n`;
    out += '\n';
  }

  if (removed.length) {
    out += `### ➖ Removed (${removed.length})\n\n`;
    out += `| Package | Version |\n|---|---|\n`;
    for (const { name, version } of removed) out += `| \`${name}\` | \`${version}\` |\n`;
    out += '\n';
  }

  if (updated.length) {
    out += `### 🔄 Updated (${updated.length})\n\n`;
    out += `| Package | From | To | Note |\n|---|---|---|---|\n`;
    for (const { name, from, to, downgrade } of updated) {
      out += `| \`${name}\` | \`${from}\` | \`${to}\` | ${downgrade ? '⚠️ downgrade' : ''} |\n`;
    }
    out += '\n';
  }

  return out;
}

let body = `## 🔒 Lockfile Changes — \`${lockfileLabel}\`\n\n`;

if (totalChanges === 0) {
  body += `_No dependency changes detected._\n`;
} else {
  const summary = [
    added.length   && `**${added.length}** added`,
    removed.length && `**${removed.length}** removed`,
    updated.length && `**${updated.length}** updated`,
    hasDowngrade   && `⚠️ **includes downgrade(s)**`,
  ].filter(Boolean).join(' · ');

  body += `${summary}\n\n`;

  const tables = buildTables();
  if (totalChanges >= COLLAPSE_THRESHOLD) {
    body += `<details>\n<summary>Show all ${totalChanges} changes</summary>\n\n${tables}\n</details>\n`;
  } else {
    body += tables;
  }
}

body += `\n<sub>Updated at ${timestamp} · commit \`${shortSha}\`</sub>\n`;

process.stdout.write(body);

// Signal downgrade status to the shell via a temp file.
writeFileSync(
  `${env.RUNNER_TEMP ?? '/tmp'}/lockfile-diff-downgrade`,
  hasDowngrade ? 'true' : 'false',
  'utf8'
);
