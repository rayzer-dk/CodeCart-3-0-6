from pathlib import Path
import json
import subprocess
import urllib.request

sha = subprocess.check_output(['git', 'rev-parse', 'HEAD'], text=True).strip()
url = 'https://api.github.com/repos/rayzer-dk/CodeCart-3-0-6/actions/runs?head_sha=' + sha + '&per_page=100'
request = urllib.request.Request(url, headers={'User-Agent': 'CodeCart-local-QA'})
with urllib.request.urlopen(request, timeout=30) as response:
    data = json.load(response)
rows = [{'id': run['id'], 'name': run['name'], 'status': run['status'],
         'conclusion': run['conclusion'], 'url': run['html_url'], 'sha': run['head_sha']}
        for run in data['workflow_runs'] if run['head_sha'] == sha]
output = Path(__file__).resolve().parent / 'evidence/final/ci-snapshot.json'
output.write_text(json.dumps({'commit': sha, 'runs': rows}, indent=2), encoding='utf-8')
print(sha)
for run in rows:
    print(run['id'], run['name'], run['status'], run['conclusion'])
