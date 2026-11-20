#!/usr/bin/env python3
"""Keep the Ukrainian admin list action heading consistently «Дії».

Only the column_action language key is normalized. Button captions and other
context-specific uses of «Дія» are intentionally not changed.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1] / "upload/admin/language/uk-ua"
ACTION = re.compile(
    r"(^\s*\$_\['column_action'\]\s*=\s*)'([^']*)'(\s*;)",
    re.MULTILINE,
)
FORMER = {"Дія", "Дії", "Операція", "Операції", "Action", "Actions"}
STANDARD = "Дії"


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true", help="report inconsistent labels without changing files")
    args = parser.parse_args()
    inconsistent = []
    updated = 0
    reviewed = 0
    for path in sorted(ROOT.rglob("*.php")):
        content = path.read_text(encoding="utf-8")
        occurrences = list(ACTION.finditer(content))
        if not occurrences:
            continue
        reviewed += 1
        replacements = [match for match in occurrences if match.group(2) in FORMER and match.group(2) != STANDARD]
        if replacements:
            inconsistent.append(path.relative_to(ROOT).as_posix())
            if not args.check:
                content = ACTION.sub(
                    lambda match: (match.group(1) + "'" + STANDARD + "'" + match.group(3))
                    if match.group(2) in FORMER else match.group(0),
                    content,
                )
                path.write_text(content, encoding="utf-8", newline="\n")
                updated += 1
    print(f"Ukrainian admin action columns: {reviewed} files checked, {len(inconsistent)} inconsistent, {updated} normalized")
    if args.check and inconsistent:
        print("\n".join(inconsistent))
        raise SystemExit(1)


if __name__ == "__main__":
    main()
