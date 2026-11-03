#!/usr/bin/env python3
"""
Checks and updates docs/src/data/hooks-translations/nl.json against the generated docs/src/data/hooks/filters.json.

Every Dutch entry keeps the English it was translated from as `source`; HookList.astro fails the docs build when a
hook has no entry, when `source` differs from the current docblock text, or when an entry names a removed hook.

    python3 hook_translations.py                  # report; exit 1 when something needs translating
    python3 hook_translations.py --accept NAME..  # after translating: store the current English as `source`
    python3 hook_translations.py --accept --all   # the same for every missing or changed hook

--accept never translates. For a hook without an entry it creates one with the English text in the Dutch fields,
so the page never shows an empty cell; translate those fields right after.
"""

import argparse
import json
import sys
from pathlib import Path

REPO = Path(__file__).resolve().parents[4]
FILTERS = REPO / 'docs/src/data/hooks/filters.json'
DUTCH = REPO / 'docs/src/data/hooks-translations/nl.json'


def english(hook):
    """The hook's English texts, in the shape HookList.astro compares with `source`."""
    doc = hook['doc']
    return {
        'description': doc.get('description', ''),
        'long_description': doc.get('long_description', ''),
        'params': {t['variable']: t['content'] for t in doc['tags'] if t['name'] == 'param' and t.get('content')},
    }


def load():
    hooks = {hook['name']: english(hook) for hook in json.loads(FILTERS.read_text())['hooks']}
    dutch = json.loads(DUTCH.read_text()) if DUTCH.exists() else {}
    return hooks, dutch


def problems(hooks, dutch):
    missing = [name for name in hooks if name not in dutch]
    changed = [name for name in hooks if name in dutch and dutch[name]['source'] != hooks[name]]
    removed = [name for name in dutch if name not in hooks]
    return missing, changed, removed


def report(hooks, dutch):
    missing, changed, removed = problems(hooks, dutch)
    for name in missing:
        print(f'MISSING  {name}')
        print(json.dumps(hooks[name], indent=2, ensure_ascii=False))
    for name in changed:
        print(f'CHANGED  {name}')
        old, new, nl = dutch[name]['source'], hooks[name], dutch[name]
        for field in ('description', 'long_description'):
            if old[field] != new[field]:
                print(f'  {field}\n    was:   {old[field]!r}\n    now:   {new[field]!r}\n    Dutch: {nl[field]!r}')
        if old['params'] != new['params']:
            print(f'  params\n    was:   {old["params"]}\n    now:   {new["params"]}\n    Dutch: {nl["params"]}')
    for name in removed:
        print(f'REMOVED  {name} (delete its entry)')
    if not (missing or changed or removed):
        print(f'All {len(hooks)} hooks have an up-to-date Dutch translation.')
    return bool(missing or changed or removed)


def accept(hooks, dutch, names):
    for name in names:
        if name not in hooks:
            sys.exit(f'Unknown hook: {name}')
        entry = dutch.get(name) or {
            'description': hooks[name]['description'],
            'long_description': hooks[name]['long_description'],
            'params': dict(hooks[name]['params']),
        }
        dutch[name] = {'source': hooks[name], **{k: v for k, v in entry.items() if k != 'source'}}
        print(f'Accepted {name}')
    # Same order as filters.json, so the diff stays readable.
    ordered = {name: dutch[name] for name in hooks if name in dutch}
    ordered.update({name: entry for name, entry in dutch.items() if name not in hooks})
    DUTCH.parent.mkdir(parents=True, exist_ok=True)
    DUTCH.write_text(json.dumps(ordered, indent='\t', ensure_ascii=False) + '\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--accept', nargs='*', metavar='HOOK', help='store the current English as source for these hooks')
    parser.add_argument('--all', action='store_true', help='with --accept: every missing or changed hook')
    args = parser.parse_args()

    hooks, dutch = load()
    if args.accept is None:
        sys.exit(1 if report(hooks, dutch) else 0)

    missing, changed, _ = problems(hooks, dutch)
    names = missing + changed if args.all else args.accept
    if not names:
        sys.exit('Name the hooks to accept, or pass --all.')
    new = [name for name in names if name not in dutch]
    accept(hooks, dutch, names)
    if new:
        print('Created with English text, translate now: ' + ', '.join(new))


if __name__ == '__main__':
    main()
