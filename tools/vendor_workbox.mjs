/**
 * Download the pinned official Workbox release into the distributable upload tree.
 * Build-time only: storefront browsers never contact a third-party CDN.
 * Usage: node tools/vendor_workbox.mjs
 */
import { createHash } from 'node:crypto';
import { mkdir, readFile, rename, unlink, writeFile } from 'node:fs/promises';
import { join, resolve } from 'node:path';

const version = '7.4.1';
const baseUrl = 'https://storage.googleapis.com/workbox-cdn/releases/' + version + '/';
const directory = resolve('upload/catalog/view/javascript/codecart/workbox/v' + version);
const hashes = {
  'workbox-sw.js': 'a6e365b56c060e7cfb7f38bd4eaea77859ca1f20a632631af4dc72a42ccdb466',
  'workbox-core.prod.js': '471d0ba8d55a92e4dcf36c6ab90d0ec0a681e3e69defdefd01bd224136ecb4fa',
  'workbox-routing.prod.js': '1f97c426b1c9d60cd3d329912e329059a25f972833826d4ac5b735d2f05dbc37',
  'workbox-strategies.prod.js': '35acbee0671f1fe8b878ea15ae98b1d14588dbfdb6041d18254a6706ef6d9e84',
  'workbox-precaching.prod.js': '1987768fc35fd0f5bcd75f95070275569ee967fbf9eade7e07f7eea3c61ecb03',
  'workbox-cacheable-response.prod.js': '0e1b25d4eaafd7c81b197d572c413e5028bc416136568b908a5f07835c654d4f',
  'workbox-expiration.prod.js': 'b1ea28a4f1ad9d91f06ad2e0a74f9a79d53f0a3f96a9cd2d4b5f21cba5a8c001',
};
const digest = (bytes) => createHash('sha256').update(bytes).digest('hex');
await mkdir(directory, { recursive: true });
for (const [name, expected] of Object.entries(hashes)) {
  const output = join(directory, name);
  let bytes;
  try {
    bytes = await readFile(output);
    if (digest(bytes) !== expected) bytes = null;
  } catch { bytes = null; }
  if (!bytes) {
    const response = await fetch(baseUrl + name, {
      redirect: 'error',
      signal: AbortSignal.timeout(30000),
    });
    if (!response.ok) throw new Error('Failed to fetch ' + name + ': HTTP ' + response.status);
    bytes = Buffer.from(await response.arrayBuffer());
    if (digest(bytes) !== expected) throw new Error('Workbox checksum mismatch: ' + name);
    const tmp = output + '.' + process.pid + '.tmp';
    try {
      await writeFile(tmp, bytes);
      await rename(tmp, output);
    } finally {
      await unlink(tmp).catch(() => {});
    }
  }
}
const notice = resolve('tools/vendor/WORKBOX_LICENSE.txt');
const license = await readFile(notice, 'utf8');
await writeFile(join(directory, 'LICENSE'), license);
await writeFile(join(directory, 'SHA256.json'), JSON.stringify({ version, source: baseUrl, files: hashes }, null, 2) + '\n');
console.log('Verified local Workbox ' + version + ': ' + Object.keys(hashes).length + ' libraries');
