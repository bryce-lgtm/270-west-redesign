"""Converts the prototype article body (article.html) into seed content for the featured guide.
Writes out/seed-article.json: {slug, summary, content, callout_label, callout_text}.
Inline photos become <figure class="photo"> with a __W270_MEDIA__:<file> marker the importer resolves."""
import json
import os

from htmldom import parse
from generate import rewrite_links, ROOT, OUT

SLUG = 'vac-benefits-programs-guide'


def convert_article_body(html_text):
    doc = parse(html_text)
    body = doc.root.find(lambda n: n.tag == 'article' and 'article-body' in n.classes)[0]
    summary = callout_label = callout_text = ''
    parts = []
    for child in body.children:
        if child.is_text:
            continue
        if 'article-intro' in child.classes:
            summary = doc.text(child).strip()
            continue
        if 'article-callout' in child.classes:
            label = child.find(lambda n: 'article-callout-label' in n.classes)
            text = child.find(lambda n: 'article-callout-p' in n.classes)
            callout_label = doc.text(label[0]).strip() if label else ''
            callout_text = doc.text(text[0]).strip() if text else ''
            continue
        if 'data-photo' in child.attrs:
            d = child.attrs
            parts.append(f'<figure class="photo" style="aspect-ratio:{d.get("data-aspect", "16/9")}">'
                         f'<img src="__W270_MEDIA__:{d["data-photo"]}.jpg" alt="{d.get("data-alt", "")}" '
                         f'style="object-position:{d.get("data-pos", "center")}"></figure>')
            continue
        parts.append(doc.outer_html(child))
    content = rewrite_links('\n'.join(parts))
    return {'slug': SLUG, 'summary': summary, 'content': content, 'callout_label': callout_label, 'callout_text': callout_text}


def main():
    with open(os.path.join(ROOT, 'article.html'), encoding='utf-8') as f:
        out = convert_article_body(f.read())
    os.makedirs(OUT, exist_ok=True)
    with open(os.path.join(OUT, 'seed-article.json'), 'w', encoding='utf-8') as f:
        json.dump(out, f, ensure_ascii=False, indent=1)
    print(f'seed-article.json: {len(out["content"])} chars, callout={"yes" if out["callout_text"] else "no"}')


if __name__ == '__main__':
    main()
