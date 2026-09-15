"""Compares the visible text of each WordPress page with its prototype page.
Usage: python3 check-coverage.py [slug ...]   (run from wordpress/build; W270_URL overrides the site URL)"""
import difflib
import html
import os
import re
import sys
import urllib.request

from pages import PAGES, path_for

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
BASE = os.environ.get('W270_URL', 'http://270-west.local')
QUOTES = {'’': "'", '‘': "'", '“': '"', '”': '"', '—': '-', '–': '-', '…': '...', '\xa0': ' '}


def norm(s):
    s = re.sub(r'<(script|style|svg|noscript)\b[\s\S]*?</\1>', ' ', s)
    s = re.sub(r'<[^>]+>', ' ', s)
    s = html.unescape(s)
    for a, b in QUOTES.items():
        s = s.replace(a, b)
    return s.split()


def prototype_words(src_file):
    with open(os.path.join(ROOT, src_file), encoding='utf-8') as f:
        s = f.read()
    s = s[s.index('<body'):]
    s = re.sub(r'<header\b[\s\S]*?</header>', ' ', s, count=1)
    s = re.sub(r'<nav class="mobile-nav">[\s\S]*?</nav>', ' ', s, count=1)
    s = re.sub(r'<footer\b[\s\S]*?</footer>', ' ', s, count=1)
    return norm(s)


def wp_words(path):
    req = urllib.request.Request(BASE + path, headers={'User-Agent': 'Mozilla/5.0 (270west coverage check)'})  # hosts block the default UA
    with urllib.request.urlopen(req, timeout=30) as r:
        s = r.read().decode('utf-8')
    m = re.search(r'<main\b[\s\S]*?</main>', s)
    if not m:
        raise RuntimeError(f'no <main> in {path}')
    return norm(m.group(0))


def main(argv):
    only = set(argv)
    failed = False
    for src, slug, _ in PAGES:
        if only and slug not in only:
            continue
        a, b = prototype_words(src), wp_words(path_for(slug))
        if a == b:
            print(f'OK   {slug:30s} {len(a):5d} words')
            continue
        failed = True
        print(f'DIFF {slug:30s} prototype {len(a)} words, wordpress {len(b)} words')
        for line in difflib.unified_diff(a, b, 'prototype', 'wordpress', n=2, lineterm=''):
            print('   ', line)
    sys.exit(1 if failed else 0)


if __name__ == '__main__':
    main(sys.argv[1:])
