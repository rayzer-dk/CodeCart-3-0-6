#!/usr/bin/env python3
"""Audit CodeCart icon markup before migrating to Font Awesome 7 Regular.

Runs without external dependencies:
  python3 tools/audit_fontawesome7.py
  python3 tools/audit_fontawesome7.py --json
  python3 tools/audit_fontawesome7.py --strict

A Free Font Awesome 7 icon MUST NOT be forced to weight 400 unless its
codepoint exists in the bundled fa-regular-400 font. Solid-only icons
otherwise disappear. This tool inventories legacy and solid markup;
it does not silently rewrite potentially incompatible third-party code.
"""
import argparse
import json
import re
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCANNED = (ROOT / "upload" / "admin", ROOT / "upload" / "catalog")
EXCLUDE_DIRS = {"vendor", "node_modules", "cache", "font-awesome", "codemirror", "ckeditor", "summernote", "tinymce", "bootstrap", "jquery"}
SUFFIXES = {".php", ".twig", ".html", ".htm", ".js", ".css"}
CLASS_ATTR = re.compile(r"""\bclass\s*=\s*(['"])(.*?)\1""", re.I | re.S)
ICON_CLASS = re.compile(r"(?<![\w-])fa(?:s|r|b|-solid|-regular|-brands|-classic|-thin|-light)?(?![\w-])|(?<![\w-])fa-[a-z][a-z0-9-]*", re.I)
PREFIXES = {"fa", "fas", "far", "fab", "fa-solid", "fa-regular", "fa-brands", "fa-classic", "fa-light", "fa-thin"}
LEGACY = {"fa", "fas", "far", "fab"}
SOLID = {"fas", "fa-solid"}
REGULAR = {"far", "fa-regular"}
BRANDS = {"fab", "fa-brands"}

def scan():
    counts = Counter()
    examples = {}
    for base in SCANNED:
        for path in base.rglob("*"):
            if not path.is_file() or path.suffix.lower() not in SUFFIXES:
                continue
            if any(part.lower() in EXCLUDE_DIRS for part in path.relative_to(base).parts[:-1]):
                continue
            try:
                source = path.read_text(encoding="utf-8", errors="replace")
            except OSError:
                continue
            rel = path.relative_to(ROOT).as_posix()
            for match in CLASS_ATTR.finditer(source):
                classes = set(ICON_CLASS.findall(match.group(2)))
                icons = {name for name in classes if name.startswith("fa-") and name not in PREFIXES}
                if not icons or not classes.intersection(PREFIXES):
                    continue
                if classes.intersection(BRANDS):
                    kind = "brands_400"
                elif classes.intersection(REGULAR):
                    kind = "regular_400"
                elif classes.intersection(SOLID):
                    kind = "solid_900"
                elif "fa" in classes:
                    kind = "legacy_default_solid"
                else:
                    kind = "unclassified"
                counts[kind] += 1
                if len(examples.get(kind, [])) < 30:
                    examples.setdefault(kind, []).append({"file": rel, "icon": sorted(icons)[0]})
    return {"summary": dict(counts), "samples": examples, "note": "Markup counts, not visual-render verification. Free Regular glyph coverage needs separate font verification."}

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--json", action="store_true", help="Output machine-readable counts")
    parser.add_argument("--strict", action="store_true", help="Exit nonzero while legacy/solid markup remains")
    args = parser.parse_args()
    result = scan()
    if args.json:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print("CodeCart Font Awesome 7 markup audit")
        for kind, count in sorted(result["summary"].items()):
            print(f"{kind}: {count}")
        print(result["note"])
    if args.strict and any(result["summary"].get(k, 0) for k in ("solid_900", "legacy_default_solid")):
        raise SystemExit(1)

if __name__ == "__main__":
    main()
