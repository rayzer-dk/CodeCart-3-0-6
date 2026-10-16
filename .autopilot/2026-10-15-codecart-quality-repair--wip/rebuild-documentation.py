from pathlib import Path
import hashlib
import json
import zipfile

root = Path(__file__).resolve().parents[2]
target = root / 'upload/admin/view/documentation/CodeCart_PRO_Documentation.zip'
with zipfile.ZipFile(target) as archive:
    entries = {info.filename: archive.read(info) for info in archive.infolist() if not info.is_dir()}
for path in (root / 'documentation').rglob('*'):
    if path.is_file():
        entries[path.relative_to(root).as_posix()] = path.read_bytes()
for name in ('README.md', 'README.uk.md', 'README_FIRST.txt', 'LICENSE'):
    entries[name] = (root / name).read_bytes()
temporary = target.with_suffix('.rebuilding.zip')
with zipfile.ZipFile(temporary, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for name, content in sorted(entries.items()):
        info = zipfile.ZipInfo(name, date_time=(2026, 10, 16, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        archive.writestr(info, content)
with zipfile.ZipFile(temporary) as archive:
    assert archive.testzip() is None
    assert all(archive.read(path.relative_to(root).as_posix()) == path.read_bytes()
               for path in (root / 'documentation').rglob('*') if path.is_file())
temporary.replace(target)
evidence = Path(__file__).resolve().parent / 'evidence/final/documentation-zip.json'
evidence.write_text(json.dumps({'entries': len(entries), 'crc': 'PASS', 'current_documentation_matches': True,
    'sha256': hashlib.sha256(target.read_bytes()).hexdigest()}, indent=2), encoding='utf-8')
print(evidence.read_text(encoding='utf-8'))
