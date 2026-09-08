import unittest
from htmldom import parse

class HtmlDomTests(unittest.TestCase):
    def test_tree_classes_and_offsets(self):
        src = '<div class="a b"><p>Hi <em>x</em></p><img src="i.jpg"/></div>'
        doc = parse(src)
        div = doc.root.elements()[0]
        self.assertEqual(div.tag, 'div')
        self.assertEqual(div.classes, ['a', 'b'])
        self.assertEqual(doc.outer_html(div), src)
        p, img = div.elements()
        self.assertEqual(doc.inner_html(p), 'Hi <em>x</em>')
        self.assertEqual(doc.outer_html(img), '<img src="i.jpg"/>')
        self.assertEqual(img.attrs['src'], 'i.jpg')

    def test_entities_are_kept_raw_but_text_is_unescaped(self):
        doc = parse('<p>A &amp; B &#8217;s</p>')
        p = doc.root.elements()[0]
        self.assertEqual(doc.inner_html(p), 'A &amp; B &#8217;s')
        self.assertEqual(doc.text(p), 'A & B ’s')

    def test_svg_self_closing_and_nesting(self):
        src = '<section><svg viewBox="0 0 10 6"><path d="M1 1"/></svg><div></div></section>'
        doc = parse(src)
        sec = doc.root.elements()[0]
        svg, div = sec.elements()
        self.assertEqual(doc.outer_html(svg), '<svg viewBox="0 0 10 6"><path d="M1 1"/></svg>')
        self.assertEqual(div.elements(), [])
        self.assertEqual(doc.inner_html(div), '')

    def test_body_and_title(self):
        doc = parse('<html><head><title>T &amp; U</title></head><body><section id="s">x</section></body></html>')
        self.assertEqual(doc.title(), 'T & U')
        self.assertEqual(doc.body().elements()[0].attrs['id'], 's')

if __name__ == '__main__':
    unittest.main()
