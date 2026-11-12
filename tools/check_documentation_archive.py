"""Verify that the bundled documentation archive holds the current sources.

Members are compared by name and content. The compressed bytes depend on the zlib
implementation of the machine that built the archive, so they are not compared.
"""
import sys
from pathlib import Path
import zipfile

from package_documentation import collect_entries


def check(root: Path) -> list:
    archive = root / 'upload/admin/view/documentation/CodeCart_PRO_Documentation.zip'
    expected = collect_entries(root)
    with zipfile.ZipFile(archive) as package:
        if package.testzip() is not None:
            return ['Archive CRC failure']
        actual = {name: package.read(name) for name in package.namelist() if not name.endswith('/')}
    problems = ['Missing from archive: ' + name for name in sorted(expected.keys() - actual.keys())]
    problems += ['Unexpected in archive: ' + name for name in sorted(actual.keys() - expected.keys())]
    problems += ['Outdated in archive: ' + name for name in sorted(expected.keys() & actual.keys()) if expected[name] != actual[name]]
    return problems


if __name__ == '__main__':
    problems = check(Path(__file__).resolve().parent.parent)
    for problem in problems:
        print(problem, file=sys.stderr)
    sys.exit(1 if problems else 0)
