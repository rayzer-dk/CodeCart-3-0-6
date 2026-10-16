"""Collect a sanitized usage snapshot from ONE explicitly selected Codex rollout.

No credentials, message content, API calls, model calls or project-state writes.
The snapshot is observed telemetry, never a monetary bill or unique token count.
"""
import argparse
import datetime as dt
import json
import math
import os
from pathlib import Path
import sys
import uuid

TOKEN_KEYS = ('input_tokens', 'cached_input_tokens', 'output_tokens', 'reasoning_output_tokens', 'total_tokens')
def numeric(value):
    return isinstance(value, (int, float)) and not isinstance(value, bool) and math.isfinite(value) and value >= 0

def normalize(event):
    payload = event.get('payload', {})
    if event.get('type') != 'event_msg' or payload.get('type') != 'token_count':
        raise ValueError('Expected a recorded token_count event')
    stamp = event.get('timestamp')
    parsed = dt.datetime.fromisoformat(str(stamp).replace('Z', '+00:00'))
    if parsed.tzinfo is None:
        raise ValueError('Timestamp must include a timezone')
    result = {'schemaVersion':1, 'source':'codex-rollout-token-count', 'observedAt':stamp,
              'collectedAt':dt.datetime.now(dt.timezone.utc).isoformat(), 'accountWindows':[],
              'tokens':{}, 'cost':None}
    limits = payload.get('rate_limits') or {}
    for key in ('primary', 'secondary'):
        window = limits.get(key)
        if not isinstance(window, dict):
            continue
        used = window.get('used_percent')
        duration = window.get('window_minutes')
        reset = window.get('resets_at')
        if numeric(used) and used <= 100 and numeric(duration) and duration > 0:
            result['accountWindows'].append({'kind':key, 'usedPercent':used, 'remainingPercent':100-used,
                                            'windowMinutes':duration, 'resetsAt':reset if numeric(reset) else None})
    info = payload.get('info') or {}
    for source, target in [('total_token_usage','cumulative'), ('last_token_usage','lastRequest')]:
        values = info.get(source)
        if isinstance(values, dict):
            result['tokens'][target] = {k:int(values[k]) for k in TOKEN_KEYS if numeric(values.get(k))}
    return result

def collect(rollout, project):
    dest = Path(project).expanduser().resolve() / '.autopilot'
    if not dest.is_dir():
        raise ValueError('Existing .autopilot directory is required')
    latest = None
    with Path(rollout).expanduser().open(encoding='utf-8') as file:
        for line in file:
            if 'token_count' not in line:
                continue
            try:
                event = json.loads(line)
            except ValueError:
                continue  # a rollout may end in an unfinished append
            if event.get('type') == 'event_msg' and event.get('payload', {}).get('type') == 'token_count':
                latest = event
    if latest is None:
        raise ValueError('No recorded token usage; existing snapshot preserved')
    snapshot = normalize(latest)
    target = dest / 'usage.json'
    temp = dest / ('usage.tmp-' + uuid.uuid4().hex)
    try:
        temp.write_text(json.dumps(snapshot, ensure_ascii=False, indent=2)+'\n',encoding='utf-8')
        os.replace(temp, target)
    finally:
        if temp.exists():
            temp.unlink()
    return snapshot

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--project', required=True)
    parser.add_argument('--rollout', required=True, help='Exact user-selected session JSONL path; no global scan')
    opts = parser.parse_args()
    try:
        collect(opts.rollout, opts.project)
        print('Usage snapshot written; project state unchanged. Account limits are shared across chats.')
        return 0
    except (OSError, ValueError, TypeError) as exc:
        print('Usage unavailable: '+str(exc),file=sys.stderr)
        return 1

if __name__ == '__main__':
    sys.exit(main())
