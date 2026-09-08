"""Minimal DOM over html.parser that records source offsets, so any subtree can be
re-emitted verbatim. Assumes well-formed HTML (the prototype pages are)."""
import html
from html.parser import HTMLParser

VOID = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}


class Node:
    __slots__ = ('tag', 'attrs', 'children', 'parent', 'text', 'start', 'end', 'inner_start', 'inner_end')

    def __init__(self, tag, attrs=None, parent=None, text=None):
        self.tag = tag
        self.attrs = dict(attrs or {})
        self.children = []
        self.parent = parent
        self.text = text
        self.start = self.end = self.inner_start = self.inner_end = None

    @property
    def is_text(self):
        return self.tag is None

    @property
    def classes(self):
        return (self.attrs.get('class') or '').split()

    def elements(self):
        return [c for c in self.children if not c.is_text]

    def iter(self):
        yield self
        for c in self.children:
            yield from c.iter()

    def find(self, pred):
        return [n for n in self.iter() if not n.is_text and pred(n)]

    def __repr__(self):
        return f'<Node {self.tag} {self.attrs}>' if self.tag else f'<Text {self.text!r}>'


class Document:
    def __init__(self, source, root):
        self.source = source
        self.root = root

    def outer_html(self, node):
        return self.source[node.start:node.end]

    def inner_html(self, node):
        return self.source[node.inner_start:node.inner_end]

    def text(self, node):
        return html.unescape(''.join(n.text for n in node.iter() if n.is_text))

    def body(self):
        found = self.root.find(lambda n: n.tag == 'body')
        return found[0] if found else self.root

    def title(self):
        found = self.root.find(lambda n: n.tag == 'title')
        return self.text(found[0]).strip() if found else ''

    def meta(self, name):
        for n in self.root.find(lambda n: n.tag == 'meta' and n.attrs.get('name') == name):
            return html.unescape(n.attrs.get('content', ''))
        return ''


class _Builder(HTMLParser):
    def __init__(self, source):
        super().__init__(convert_charrefs=False)
        self.source = source
        self.line_starts = [0] + [i + 1 for i, ch in enumerate(source) if ch == '\n']
        self.root = Node('#root')
        self.cur = self.root

    def _pos(self):
        line, col = self.getpos()
        return self.line_starts[line - 1] + col

    def _open(self, tag, attrs, void):
        node = Node(tag, attrs, self.cur)
        node.start = self._pos()
        node.inner_start = node.start + len(self.get_starttag_text())
        self.cur.children.append(node)
        if void:
            node.inner_end = node.end = node.inner_start
        else:
            self.cur = node

    def handle_starttag(self, tag, attrs):
        self._open(tag, attrs, tag in VOID)

    def handle_startendtag(self, tag, attrs):
        self._open(tag, attrs, True)

    def handle_endtag(self, tag):
        node = self.cur
        while node is not self.root and node.tag != tag:
            node = node.parent
        if node is self.root:
            return  # stray end tag; ignore
        pos = self._pos()
        node.inner_end = pos
        node.end = self.source.index('>', pos) + 1
        self.cur = node.parent

    def _text(self, raw):
        node = Node(None, parent=self.cur, text=raw)
        node.start = self._pos()
        node.end = node.start + len(raw)
        self.cur.children.append(node)

    def handle_data(self, data):
        self._text(data)

    def handle_entityref(self, name):
        self._text(f'&{name};')

    def handle_charref(self, name):
        self._text(f'&#{name};')

    def handle_comment(self, data):
        pass


def parse(source):
    b = _Builder(source)
    b.feed(source)
    b.close()
    return Document(source, b.root)
