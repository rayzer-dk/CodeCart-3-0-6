#!/usr/bin/env python3
"""Build a verified changed-files ZIP between two CodeCart full release archives."""
from __future__ import annotations

import argparse
import hashlib
import zipfile
from pathlib import Path


def digest(zf: zipfile.ZipFile, name: str) -> str:
    result = hashlib.sha256()
    with zf.open(name, "r") as entry:
        for chunk in iter(lambda: entry.read(1024 * 1024), b""):
            result.update(chunk)
    return result.hexdigest()


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base", type=Path, required=True)
    parser.add_argument("--target", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    with zipfile.ZipFile(args.base) as baseline, zipfile.ZipFile(args.target) as release:
        if baseline.testzip() is not None or release.testzip() is not None:
            raise ValueError("Corrupted base or release archive")
        old = {i.filename: i for i in baseline.infolist() if not i.is_dir()}
        new = {i.filename: i for i in release.infolist() if not i.is_dir()}
        changed = []
        for name, info in new.items():
            previous = old.get(name)
            if previous is None or info.file_size != previous.file_size or digest(release, name) != digest(baseline, name):
                changed.append(name)
        deleted = sorted(set(old) - set(new))
        args.output.parent.mkdir(parents=True, exist_ok=True)
        with zipfile.ZipFile(args.output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=8) as patch:
            for name in sorted(changed):
                patch.writestr(new[name], release.read(name))
            info = (
                "CodeCart PRO changed files for replacement\\n"
                f"Base: {args.base.name}\\n"
                f"Target: {args.target.name}\\n"
                f"Files to overwrite: {len(changed)}\\n"
                f"Files removed in target: {len(deleted)}\\n"
                "Copy the files with their relative paths; back up existing files and database before applying.\\n"
                "Only the full target archive is independently deployable.\\n"
            )
            patch.writestr("CHANGED_FILES_README.txt", info)
            if deleted:
                patch.writestr("REMOVED_FILES_REVIEW.txt", "\\n".join(deleted) + "\\n")
        with zipfile.ZipFile(args.output) as check:
            bad = check.testzip()
            if bad:
                raise ValueError("Patch archive failed validation: " + bad)
        print(f"Patch ready: {args.output.name}; changed={len(changed)}, removed={len(deleted)}")


if __name__ == "__main__":
    main()
