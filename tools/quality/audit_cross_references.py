#!/usr/bin/env python3
"""Conservative cross-file candidate inventory for CodeCart first-party sources.

Findings are advisory: OpenCart OCMOD, Events, dynamic templates and selectors
require reference tracing before deleting any code.
"""
import argparse
import collections
import json
import pathlib
import re

IGNORE_PARTS = {"vendor", "node_modules", "storage", "cache", "build", "dist"}
ROOTS = (
    "upload/admin/controller",
    "upload/admin/model",
    "upload/admin/view",
    "upload/catalog/controller",
    "upload/catalog/model",
    "upload/catalog/view",
    "upload/system/library/codecart/src",
)
CLASS_RE = re.compile(r"(?<![\w-])\.([a-zA-Z_][\w-]{3,})")
JS_FUNCTION_RE = re.compile(r"\b(?:async\s+)?function\s+([a-zA-Z_$][\w$]*)\s*\(")
SELECTOR_RE = re.compile(r"(?m)(?:^|\})\s*([^{}]+)\s*\{")
WORKAROUND_RE = re.compile(r"(?im)^\s*(?://|/\*|\*|#)\s*.*\b(?:workaround|temporary fix|hotfix|quick fix|compatibility hack)\b")
EXTENSIONS = {".php", ".twig", ".js", ".css", ".xml"}

def inspect(root):
    files = []
    for sub in ROOTS:
        base = root / sub
        if not base.is_dir():
            continue
        for path in base.rglob("*"):
            if not path.is_file() or path.suffix.lower() not in EXTENSIONS:
                continue
            if any(x in IGNORE_PARTS for x in path.relative_to(root).parts):
                continue
            if path.name.endswith(".min.js") or path.name.endswith(".min.css"):
                continue
            files.append(path)
    files.sort()
    texts = {}
    for path in files:
        try:
            texts[path] = path.read_text(encoding="utf-8")
        except (UnicodeError, OSError):
            continue
    consumer = "\n".join(text for path, text in texts.items() if path.suffix.lower() != ".css")
    consumer_symbols = set(re.findall(r"[a-zA-Z_$][\w$-]*", consumer))
    candidates = []
    for path, content in texts.items():
        relative = path.relative_to(root).as_posix()
        if path.suffix.lower() == ".css":
            classes = set()
            for selector in SELECTOR_RE.findall(content):
                if selector.lstrip().startswith("@"):
                    continue
                classes.update(CLASS_RE.findall(selector))
            for name in sorted(classes - consumer_symbols):
                candidates.append({"kind": "css-class-unreferenced", "path": relative, "symbol": name,
                                   "confidence": "low", "note": "Check dynamic class generation and themes"})
        if path.suffix.lower() == ".js":
            for name in sorted(set(JS_FUNCTION_RE.findall(content))):
                if name not in consumer_symbols:
                    continue
                # One occurrence in the entire corpus is usually just the declaration.
                hits = len(re.findall(r"(?<![\w$])" + re.escape(name) + r"(?![\w$])", consumer))
                if hits == 1:
                    candidates.append({"kind": "js-function-single-reference", "path": relative,
                                       "symbol": name, "confidence": "low",
                                       "note": "Check externally invoked handlers and scripts"})
        if path.suffix.lower() in {".php", ".js", ".twig"}:
            for match in WORKAROUND_RE.finditer(content):
                line = content.count("\n", 0, match.start()) + 1
                candidates.append({"kind": "workaround-comment-review", "path": relative,
                                   "line": line, "confidence": "low",
                                   "note": "Trace original cause; do not remove compatibility code blindly"})
    counts = collections.Counter(c["kind"] for c in candidates)
    return {"scanned_files": len(texts), "counts": dict(sorted(counts.items())),
            "candidates": candidates[:2000], "truncated": len(candidates) > 2000}

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=pathlib.Path, default=pathlib.Path("."))
    parser.add_argument("--out", type=pathlib.Path, default=pathlib.Path("build/quality/cross-references.json"))
    args = parser.parse_args()
    report = inspect(args.root.resolve())
    args.out.parent.mkdir(parents=True, exist_ok=True)
    args.out.write_text(json.dumps(report, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print("Scanned", report["scanned_files"], "first-party files.")
    for kind, count in report["counts"].items():
        print(kind + ":", count)
    print("Advisory only. Trace dynamic routes, themes, Events and OCMOD before changes.")

if __name__ == "__main__":
    main()
