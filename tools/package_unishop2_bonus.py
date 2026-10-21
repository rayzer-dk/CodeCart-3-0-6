"""Build a maintained bonus OCMOD package with reproducible ZIP metadata."""
import argparse
import json
from pathlib import Path
import tempfile
import xml.etree.ElementTree as ET
import zipfile


PACKAGES = {
    'unishop2_compatibility': 'UniShop2_Compatibility_Pro',
    'monobank_payment': 'Monobank_Payment_Modern_Pro',
}


def build(root: Path, output: Path, module: str = 'unishop2_compatibility') -> Path:
    if module not in PACKAGES:
        raise ValueError('Unknown bonus module')
    source = root / 'bonus-source' / module
    metadata = ET.parse(source / 'install.xml').getroot()
    if metadata.tag != 'modification' or not metadata.findtext('code'):
        raise ValueError('Invalid installer metadata')
    version = metadata.findtext('version')
    if not version or len(version) != 5 or any(part not in '0123456789' for part in version.split('.')) or len(version.split('.')) != 3:
        raise ValueError('Invalid module version')
    if module == 'monobank_payment':
        manifest = json.loads((source / 'upload/system/extension/monobank_payment/manifest.json').read_text(encoding='utf-8'))
        if manifest.get('version') != version or manifest.get('code') != metadata.findtext('code'):
            raise ValueError('Bonus manifest does not match installer metadata')
    entries = {'install.xml': source / 'install.xml', 'README.md': source / 'README.md'}
    upload = source / 'upload'
    if upload.is_dir():
        for path in sorted(upload.rglob('*')):
            if path.is_file():
                if path.is_symlink():
                    raise ValueError('Symlinks are not accepted: ' + str(path))
                if path.parent == upload:
                    raise ValueError('Root upload files are not accepted by the installer: ' + path.name)
                entries[path.relative_to(source).as_posix()] = path
    output.mkdir(parents=True, exist_ok=True)
    archive = output / (PACKAGES[module] + '_v' + version + '.ocmod.zip')
    with tempfile.NamedTemporaryFile(dir=output, prefix='.bonus-', suffix='.zip', delete=False) as staged:
        temporary = Path(staged.name)
    try:
        with zipfile.ZipFile(temporary, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as package:
            directory = zipfile.ZipInfo('upload/', date_time=(1980, 1, 1, 0, 0, 0))
            directory.create_system = 3
            directory.external_attr = (0o40755 << 16) | 0x10
            package.writestr(directory, b'')
            for name, path in sorted(entries.items()):
                entry = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
                entry.create_system = 3
                entry.external_attr = 0o100644 << 16
                package.writestr(entry, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
        with zipfile.ZipFile(temporary) as package:
            if package.testzip() is not None:
                raise ValueError('Archive CRC verification failed')
        temporary.replace(archive)
    finally:
        temporary.unlink(missing_ok=True)
    return archive


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--module', choices=sorted(PACKAGES), default='unishop2_compatibility')
    args = parser.parse_args()
    root = Path(__file__).resolve().parent.parent
    print(build(root, args.output.resolve() if args.output else root / 'bonuses', args.module))
