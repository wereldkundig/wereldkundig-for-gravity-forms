#!/usr/bin/env python3
"""
Looks up how a Dutch WordPress admin shows a Gravity Forms label, from the installed Gravity Forms translation
(wp-content/languages/plugins/gravityforms-nl_NL.po, next to this plugin).

    python3 gf_label.py "Form Settings" "System Status"

Prints "English → Dutch" per label, or "(not found)". Only for Gravity Forms' own labels: the add-on's labels
(Themes, Map Fields, Checked by default, ...) are in its own translation, languages/wereldkundig-for-gravity-forms-nl_NL.po.
"""

import re
import sys
from pathlib import Path

PO = Path(__file__).resolve().parents[4].parent.parent / 'languages/plugins/gravityforms-nl_NL.po'


def translations():
    found = {}
    text = PO.read_text(encoding='utf-8')
    for msgid, msgstr in re.findall(r'^msgid "(.*)"\nmsgstr "(.*)"$', text, re.MULTILINE):
        if msgid and msgstr:
            found.setdefault(msgid, msgstr)
    return found


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    if not PO.exists():
        sys.exit(f'Not found: {PO}. Install the Dutch language pack for Gravity Forms on the local site first.')
    known = translations()
    for label in sys.argv[1:]:
        print(f'{label} → {known.get(label, "(not found)")}')


if __name__ == '__main__':
    main()
