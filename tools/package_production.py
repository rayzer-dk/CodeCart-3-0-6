"""Build the merchant distribution without developer tools or local runtime data."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess
import zipfile


def build(root, output):
    tracked = subprocess.check_output(
        ['git', 'ls-files', '-z'], cwd=root).decode('utf-8').split('\0')
    selected = set()
    root_docs = {'README.md', 'README.uk.md', 'README_FIRST.txt',
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
    for name, digest in manifest['files'].items():
        if 'upload/' + name not in selected or hashlib.sha256((root / 'upload' / name).read_bytes()).hexdigest() != digest:
            raise ValueError('Integrity mismatch: ' + name)
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for name in sorted(selected):
            z.write(root / name, name)
    with zipfile.ZipFile(archive) as z:
        if z.testzip():
            raise ValueError('ZIP verification failed')
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    archive.with_suffix('.zip.sha256').write_text(digest + '  ' + archive.name + '\n', encoding='utf-8')
    print(str(archive))
    print(str(len(selected)) + ' files; SHA256 ' + digest)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    build(Path(__file__).resolve().parent.parent, args.output.resolve())
