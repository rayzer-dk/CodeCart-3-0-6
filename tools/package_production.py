"""Build the merchant distribution without developer tools or local runtime data."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess
import sys
import zipfile
import zlib


def build(root, output):
    tracked = subprocess.check_output(
        ['git', 'ls-files', '-z'], cwd=root).decode('utf-8').split('\0')
    selected = set()
    root_docs = {'LICENSE', 'README.md', 'README.uk.md', 'README_FIRST.txt',
                 'composer.json', 'composer.lock', 'THIRD_PARTY_DEPENDENCIES.json'}
    for name in tracked:
        if name and (name.startswith(('upload/', 'documentation/')) or name in root_docs):
            if (root / name).is_file():
                selected.add(name)
    # Include newly prepared documentation before committing a release.
    selected.update(p.relative_to(root).as_posix() for p in (root / 'documentation').rglob('*') if p.is_file())
    selected.update(n for n in root_docs if (root / n).is_file())
    vendor = root / 'upload/system/storage/vendor'
    if not (vendor / 'autoload.php').is_file():
        raise ValueError('Run composer install --no-dev before packaging')
    selected.update(p.relative_to(root).as_posix() for p in vendor.rglob('*') if p.is_file())
    bonuses = root / 'bonuses'
    if bonuses.is_dir():
        selected.update(p.relative_to(root).as_posix() for p in bonuses.glob('*.ocmod.zip'))
    forbidden = re.compile(r'(^|/)(\.env(?:\..*)?|[^/]*\.(?:bak|old|tmp))$')
    for name in selected:
        if name in {'upload/config.php', 'upload/admin/config.php'} or forbidden.search(name) or (root / name).is_symlink():
            raise ValueError('Unsafe package entry: ' + name)
        if name.startswith('upload/system/storage/') and not name.startswith('upload/system/storage/vendor/'):
            leaf = name.removeprefix('upload/system/storage/')
            if leaf.split('/')[0] in {'cache', 'logs', 'session', 'download', 'upload', 'modification'} and not leaf.endswith('/index.html'):
                raise ValueError('Runtime data in package: ' + name)
    source = (root / 'upload/index.php').read_text(encoding='utf-8')
    version = re.search(r"CODECART_PACKAGE_BUILD'\s*,\s*'([0-9]\.[0-9]\.[0-9])'", source)
    if not version:
        raise ValueError('Invalid package build')
    output.mkdir(parents=True, exist_ok=True)
    archive = output / ('CodeCart-3.0.6-production-Build-' + version[1] + '.zip')
    if archive.exists():
        raise ValueError('Refusing to overwrite an existing release: ' + str(archive))
    manifest = json.loads((root / 'upload/system/config/codecart_integrity.json').read_text(encoding='utf-8'))
    if manifest.get('package_build') != version[1]:
        raise ValueError('Integrity manifest package build is stale')
    for name, digest in manifest['files'].items():
        if 'upload/' + name not in selected or hashlib.sha256((root / 'upload' / name).read_bytes()).hexdigest() != digest:
            raise ValueError('Integrity mismatch: ' + name)
    source_commit = subprocess.check_output(
        ['git', 'rev-parse', 'HEAD'], cwd=root).decode('ascii').strip()
    entries = {}
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for name in sorted(selected):
            data = (root / name).read_bytes()
            digest = hashlib.sha256(data).hexdigest()
            relative = name.removeprefix('upload/')
            if name.startswith('upload/') and relative in manifest['files'] and digest != manifest['files'][relative]:
                raise ValueError('Source changed during packaging: ' + name)
            entry = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
            entry.create_system = 3
            entry.external_attr = 0o100644 << 16
            z.writestr(entry, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
            entries[name] = {'sha256': digest, 'size': len(data)}
    with zipfile.ZipFile(archive) as z:
        if z.testzip():
            raise ValueError('ZIP verification failed')
        if z.namelist() != sorted(entries):
            raise ValueError('ZIP entry manifest mismatch')
        for name, expected in entries.items():
            data = z.read(name)
            if len(data) != expected['size'] or hashlib.sha256(data).hexdigest() != expected['sha256']:
                raise ValueError('ZIP content mismatch: ' + name)
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    release_manifest = {
        'archive': archive.name,
        'archive_sha256': digest,
        'package_build': version[1],
        'source_commit': source_commit,
        'compression': {'method': 'deflate', 'level': 9, 'python': sys.version.split()[0], 'zlib': zlib.ZLIB_RUNTIME_VERSION},
        'files': entries,
    }
    archive.with_suffix('.zip.manifest.json').write_text(
        json.dumps(release_manifest, indent=2, ensure_ascii=False) + '\n', encoding='utf-8', newline='\n')
    archive.with_suffix('.zip.sha256').write_text(digest + '  ' + archive.name + '\n', encoding='utf-8', newline='\n')
    print(str(archive))
    print(str(len(selected)) + ' files; SHA256 ' + digest)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    build(Path(__file__).resolve().parent.parent, args.output.resolve())
