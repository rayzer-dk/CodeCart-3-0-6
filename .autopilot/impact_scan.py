#!/usr/bin/env python3
"""Conservative dependency-impact explorer. Hints, never a complete call graph."""
import argparse
import json
import re
import subprocess
from pathlib import Path

EXT={'.php','.py','.js','.jsx','.ts','.tsx','.twig','.xml','.sql','.json','.css','.html','.md'}
RISK={'composer.json','package.json','package-lock.json','install.xml','AGENTS.md','CLAUDE.md','config.php','.env','schema.sql'}
PATTERNS=(r'\b(?:require|include)(?:_once)?\s*\(?\s*[\'\"]([^\'\"]+)',r'\b(?:from|import)\s+([a-zA-Z_][\w.]*)',r'\b(?:route|template|view|model|controller)\s*[=:>]\s*[\'\"]([^\'\"]+)',r'\b(?:fetch|url)\s*\(\s*[\'\"]([^\'\"]+)',r'\b(?:extends|include)\s+[\'\"]([^\'\"]+)',r'\b(?:class|function)\s+([a-zA-Z_][\w]*)')
TOKEN=re.compile('|'.join('(?:'+p+')' for p in PATTERNS),re.I)

def files(root):
    try:
        p=subprocess.run(['git','ls-files','-z'],cwd=root,capture_output=True,timeout=10)
        if p.returncode==0:
            return [root/x.decode('utf-8','replace') for x in p.stdout.split(b'\0') if x and (root/x.decode('utf-8','replace')).is_file()]
    except (OSError,subprocess.TimeoutExpired): pass
    return [p for p in root.rglob('*') if p.is_file() and not set(p.relative_to(root).parts)&{'.git','node_modules','vendor','.venv','dist','build','.autopilot'}]

def tokens(p):
    if p.suffix.lower() not in EXT: return set()
    try:
        if p.stat().st_size>2_000_000:return set()
        s=p.read_text(encoding='utf-8',errors='replace')
    except OSError:return set()
    found=set()
    for match in TOKEN.finditer(s):
        value=next((g for g in match.groups() if g),None)
        if value and len(value)>=3:found.add(value.lower())
    return found

def analyze(root,changed,max_nodes=200):
    root=root.resolve()
    indexed={str(p.relative_to(root)).replace('\\','/'):p for p in files(root)}
    unknown=[x for x in changed if x not in indexed]
    refs={k:tokens(v) for k,v in indexed.items()}
    # Match basename / path stem / selected declared symbols; avoid broad text search.
    names={k:{Path(k).name.lower(),Path(k).stem.lower(),k.lower(),k.rsplit('.',1)[0].lower()} | {x.replace('/','\\').lower() for x in [k]} for k in indexed}
    reverse={k:set() for k in indexed}
    for source,values in refs.items():
        for target,identifiers in names.items():
            if source==target:continue
            if any(len(s)>=3 and (s in values or any(v.endswith('/'+s) for v in values)) for s in identifiers):
                reverse[target].add(source)
    discovered=set(x for x in changed if x in indexed)
    frontier=set(discovered)
    for _ in range(2):
        frontier=set().union(*(reverse[x] for x in frontier)) -discovered if frontier else set()
        discovered.update(frontier)
        if len(discovered)>max_nodes:break
    high=bool(unknown or len(discovered)>max_nodes or any(Path(x).name in RISK or any(z in x.lower() for z in ('migration','auth','checkout','payment','license','schema','install.xml','security','database','core/')) for x in discovered))
    return {'changed':changed,'missing_or_untracked':unknown,'affected':sorted(discovered)[:max_nodes],'truncated':len(discovered)>max_nodes,'high_risk':high,'limitations':['Static heuristic only; dynamic routes, OCMOD, events, DI, reflection, SQL-driven calls, templates, runtime services and generated code may not be detected.','Review module boundaries, shared contracts, database effects and indirect workflows manually.','Re-run after edits and broaden when the graph is incomplete.'],'required_action':'Expand analysis to owner modules, caller/callee contracts and end-to-end flows; use DEEP for cross-system or security changes.' if high else 'Confirm outgoing dependencies and indirect flows manually; targeted integration and regression tests are still required.'}

def main():
    a=argparse.ArgumentParser()
    a.add_argument('root',type=Path);a.add_argument('changed',nargs='+');a.add_argument('--max-nodes',type=int,default=200)
    p=a.parse_args();root=p.root.resolve(); changed=[str(Path(x)).replace('\\','/') for x in p.changed]
    if not root.is_dir():a.error('root must exist')
    print(json.dumps(analyze(root,changed,p.max_nodes),ensure_ascii=False,indent=2))
if __name__=='__main__':main()
