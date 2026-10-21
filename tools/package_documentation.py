"""Refresh the bundled documentation from current sources, retaining notices."""
import argparse
from pathlib import Path
import tempfile
import zipfile


def build(root: Path, output: Path) -> Path:
    original = root / 'upload/admin/view/documentation/CodeCart_PRO_Documentation.zip'
    entries = {}
    with zipfile.ZipFile(original) as package:
        if package.testzip() is not None:
            raise ValueError('Existing documentation archive CRC failure')
        for name in package.namelist():
            if name.startswith(('LICENSE', 'NOTICE', 'THIRD_PARTY_')) and not name.endswith('/'):
                entries[name] = package.read(name)
    for name in ('LICENSE', 'README.md', 'README.uk.md', 'README_FIRST.txt', 'THIRD_PARTY_DEPENDENCIES.json'):
        entries[name] = (root / name).read_bytes()
    for path in sorted((root / 'documentation').rglob('*')):
        if path.is_file():
            if path.is_symlink():
                raise ValueError('Documentation symlink is not accepted: ' + str(path))
            entries[path.relative_to(root).as_posix()] = path.read_bytes()
    output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile(dir=output.parent, prefix='.documentation-', suffix='.zip', delete=False) as staged:
        temporary = Path(staged.name)
    try:
        with zipfile.ZipFile(temporary, 'w') as package:
            for name, data in sorted(entries.items()):
                entry = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
                entry.create_system = 3
                entry.external_attr = 0o100644 << 16
                package.writestr(entry, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
        with zipfile.ZipFile(temporary) as package:
            if package.testzip() is not None or package.namelist() != sorted(entries):
                raise ValueError('Documentation archive verification failed')
            for name, data in entries.items():
                if package.read(name) != data:
                    raise ValueError('Documentation entry changed: ' + name)
        temporary.replace(output)
    finally:
        temporary.unlink(missing_ok=True)
    return output


if __name__ == '__main__':
    root = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=root / 'upload/admin/view/documentation/CodeCart_PRO_Documentation.zip')
    args = parser.parse_args()
    print(build(root, args.output.resolve()))
