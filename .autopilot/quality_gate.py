#!/usr/bin/env python3
"""Read-only validation ledger gate for Autopilot Quality PRO.

Input JSON: {"scenarios": [{"id":"R01:checkout", "mandatory":true,
"status":"PASS", "expected":"...", "observed":"...", "evidence":"..."}]}
Run: python quality_gate.py path/to/validation.json --profile STANDARD
Exit 0 only when mandatory coverage and evidence are sufficient.
"""
import argparse
import json
import pathlib
import sys

STATUSES = {"PASS", "FAIL", "BLOCKED", "NOT RUN", "N/A"}
PROFILES = {"FAST", "STANDARD", "DEEP"}


def validate(data, profile=None):
    errors = []
    if not isinstance(data, dict) or not isinstance(data.get("scenarios"), list) or not data["scenarios"]:
        return ["scenarios must be a nonempty array"]
    seen = set()
    for index, scenario in enumerate(data["scenarios"], 1):
        if not isinstance(scenario, dict):
            errors.append("scenario %s: expected object" % index)
            continue
        name = scenario.get("id")
        status = scenario.get("status")
        mandatory = scenario.get("mandatory", True)
        if not isinstance(name, str) or not name.strip():
            errors.append("scenario %s: missing id" % index)
        elif name in seen:
            errors.append("scenario %s: duplicate id %s" % (index, name))
        else:
            seen.add(name)
        if status not in STATUSES:
            errors.append("scenario %s: invalid status" % index)
        if not isinstance(mandatory, bool):
            errors.append("scenario %s: mandatory must be boolean" % index)
        if status == "PASS":
            for key in ("expected", "observed", "evidence"):
                if not isinstance(scenario.get(key), str) or not scenario[key].strip():
                    errors.append("scenario %s: PASS requires %s" % (index, key))
        if status == "N/A" and (not isinstance(scenario.get("reason"), str) or not scenario["reason"].strip()):
            errors.append("scenario %s: N/A requires reason" % index)
        if mandatory is True and status != "PASS":
            errors.append("scenario %s: mandatory scenario %s is %s" % (index, name, status))
    # Profile-specific coverage is enforced only when a profile is explicitly requested.
    # Evidence remains a statement supplied by the operator, not cryptographic proof.
    if profile in PROFILES:
        required = {
            "FAST": {"change", "regression"},
            "STANDARD": {"change", "regression", "security", "integration"},
            "DEEP": {"change", "regression", "security", "integration", "e2e", "rollback", "performance"},
        }[profile]
        categories = {s.get("category") for s in data["scenarios"]
                      if isinstance(s, dict) and s.get("mandatory", True) is True
                      and s.get("status") == "PASS"}
        for category in sorted(required - categories):
            errors.append("%s profile requires PASS scenario category %s" % (profile, category))
    return errors


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("ledger", type=pathlib.Path)
    parser.add_argument("--profile", choices=sorted(PROFILES), default="STANDARD")
    args = parser.parse_args(argv)
    try:
        data = json.loads(args.ledger.read_text(encoding="utf-8"))
    except (OSError, UnicodeError, ValueError) as exc:
        print("FAIL: cannot read ledger: %s" % exc, file=sys.stderr)
        return 2
    errors = validate(data, args.profile)
    if errors:
        for error in errors:
            print("FAIL: %s" % error)
        return 1
    print("PASS: %s validation ledger (%s scenarios); status is based on recorded evidence, not independent execution" % (args.profile, len(data["scenarios"])))
    return 0


if __name__ == "__main__":
    sys.exit(main())
