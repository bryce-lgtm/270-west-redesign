import unittest
from generate import convert_fragment, rewrite_links, parse_decor, StyleRegistry


def first(html):
    els = convert_fragment(html)
    assert len(els) == 1, els
    return els[0]


class GenerateTests(unittest.TestCase):
    def test_section_becomes_full_width_container_with_classes(self):
        el = first('<section class="section-pad journey-section"><h2 class="display-lg">Four steps,<br>at your pace.</h2></section>')
        self.assertEqual(el['elType'], 'container')
        self.assertEqual(el['settings']['html_tag'], 'section')
        self.assertEqual(el['settings']['content_width'], 'full')
        self.assertEqual(el['settings']['css_classes'], 'w-con section-pad journey-section')
        h = el['elements'][0]
        self.assertEqual(h['widgetType'], 'heading')
        self.assertEqual(h['settings']['header_size'], 'h2')
        self.assertEqual(h['settings']['title'], 'Four steps,<br>at your pace.')
        self.assertEqual(h['settings']['_css_classes'], 'w-heading display-lg')

    def test_paragraph_and_inline_div_become_text_widgets(self):
        p = first('<p class="hero-lead">We help <em>you</em>.</p>')
        self.assertEqual(p['widgetType'], 'text-editor')
        self.assertEqual(p['settings']['editor'], '<p>We help <em>you</em>.</p>')
        self.assertEqual(p['settings']['_css_classes'], 'w-text hero-lead')
        d = first('<div class="journey-step-n">01</div>')
        self.assertEqual(d['settings']['editor'], '01')

    def test_button_widget(self):
        b = first('<a href="consult.html" class="btn-accent">Book a free consult →</a>')
        self.assertEqual(b['widgetType'], 'button')
        self.assertEqual(b['settings']['text'], 'Book a free consult →')
        self.assertEqual(b['settings']['link']['url'], '/book-a-consult/')
        self.assertEqual(b['settings']['_css_classes'], 'w-btn btn-accent')

    def test_image_widget_records_media_marker(self):
        i = first('<img src="img/veteran-sarah.jpg" alt="Capt. Sarah" style="object-position:center 30%"/>')
        self.assertEqual(i['widgetType'], 'image')
        self.assertEqual(i['settings']['image']['__media__'], 'veteran-sarah.jpg')
        self.assertEqual(i['settings']['image']['alt'], 'Capt. Sarah')
        self.assertEqual(i['settings']['image_size'], 'full')
        self.assertIn('w270-s', i['settings']['_css_classes'])

    def test_svg_empty_div_and_data_photo_become_html_widgets(self):
        s = first('<svg viewBox="0 0 10 6"><path d="M1 1"/></svg>')
        self.assertEqual(s['widgetType'], 'html')
        self.assertEqual(s['settings']['html'], '<svg viewBox="0 0 10 6"><path d="M1 1"/></svg>')
        e = first('<div class="hero-compass" aria-hidden="true"></div>')
        self.assertEqual(e['settings']['html'], '<div class="hero-compass" aria-hidden="true"></div>')
        ph = first('<div data-photo="shoreline" data-alt="Dock" data-aspect="16/7"></div>')
        self.assertEqual(ph['widgetType'], 'html')
        self.assertIn('data-photo="shoreline"', ph['settings']['html'])

    def test_linked_card_container(self):
        c = first('<a href="eligibility.html" class="lm-card lm-card-dark"><div class="lm-meta">2 min</div><h2 class="lm-h">Checker</h2></a>')
        self.assertEqual(c['elType'], 'container')
        self.assertEqual(c['settings']['html_tag'], 'a')
        self.assertEqual(c['settings']['link']['url'], '/eligibility/')
        self.assertEqual([e['widgetType'] for e in c['elements']], ['text-editor', 'heading'])

    def test_rich_block_is_one_text_widget(self):
        r = first('<div class="legal-body"><h2 id="sec-0">One</h2><p>a</p><p>b</p></div>')
        self.assertEqual(r['widgetType'], 'text-editor')
        self.assertEqual(r['settings']['_css_classes'], 'w-rich legal-body')
        self.assertEqual(r['settings']['editor'], '<h2 id="sec-0">One</h2><p>a</p><p>b</p>')

    def test_stray_text_inside_container_is_kept(self):
        c = first('<div class="x">Loose <svg></svg></div>')
        self.assertEqual([e['widgetType'] for e in c['elements']], ['text-editor', 'html'])
        self.assertEqual(c['elements'][0]['settings']['editor'], 'Loose')
        self.assertEqual(c['elements'][0]['settings']['_css_classes'], 'w-text w-bare w-raw')

    def test_bare_elements_get_markers_and_blockquote_keeps_class_on_wrapper(self):
        p = first('<p>Plain paragraph</p>')
        self.assertEqual(p['settings']['_css_classes'], 'w-text w-bare w-p')
        h = first('<h3>Plain heading</h3>')
        self.assertEqual(h['settings']['_css_classes'], 'w-heading w-bare w-h3')
        styled = first('<h2 style="font-size:52px">Styled</h2>')
        self.assertNotIn('w-bare', styled['settings']['_css_classes'])
        self.assertIn('w270-s', styled['settings']['_css_classes'])
        q = first('<blockquote class="hero-quote-q">I came.</blockquote>')
        self.assertEqual(q['settings']['editor'], 'I came.')
        self.assertEqual(q['settings']['_css_classes'], 'w-text hero-quote-q')

    def test_figure_maps_to_div_container_and_keeps_id(self):
        c = first('<figure class="hero-quote" id="fq"><img src="img/a.jpg" alt=""/><figcaption class="c">Q</figcaption></figure>')
        self.assertEqual(c['settings']['html_tag'], 'div')
        self.assertEqual(c['settings']['_element_id'], 'fq')

    def test_rewrite_links(self):
        self.assertEqual(rewrite_links('<a href="service-claims.html#x">c</a> <img src="img/a.svg">'),
                         '<a href="/services/claims/#x">c</a> <img src="__W270_ASSETS__/img/a.svg">')
        self.assertEqual(rewrite_links('<a href="index.html">h</a>'), '<a href="/">h</a>')
        self.assertEqual(rewrite_links('<a href="mailto:x@y.z">m</a>'), '<a href="mailto:x@y.z">m</a>')

    def test_parse_decor(self):
        js = ("document.getElementById('about-maple').innerHTML = brandMarkSVG(260, 'currentColor');"
              "var svc = document.getElementById('svc-grid'); if (svc) svc.innerHTML = mapGridSVG('#A1B6C2', 0.05, 48);"
              "document.getElementById('footer-topo').innerHTML = topoLinesSVG('#A1B6C2', 0.12);")
        self.assertEqual(parse_decor(js), {
            'about-maple': ('brandMarkSVG', '[260,"currentColor"]'),
            'svc-grid': ('mapGridSVG', '["#A1B6C2",0.05,48]'),
        })

    def test_decor_attrs_applied_to_html_widget(self):
        els = convert_fragment('<div id="cta-topo"></div>', decor={'cta-topo': ('topoLinesSVG', '["#A1B6C2",0.12]')})
        self.assertEqual(els[0]['settings']['html'], '<div id="cta-topo" data-decor="topoLinesSVG" data-decor-args=\'["#A1B6C2",0.12]\'></div>')

    def test_style_registry(self):
        reg = StyleRegistry()
        a = reg.cls('margin-top:26px')
        b = reg.cls('margin-top:26px')
        c = reg.cls('object-position:center 30%', kind='img')
        self.assertEqual(a, b)
        self.assertNotEqual(a, c)
        self.assertIn(f'.{a}.{a}.{a}.{a}{{margin-top:26px}}', reg.css())
        self.assertIn(f'.{c}.{c}.{c} img{{object-position:center 30%}}', reg.css())

if __name__ == '__main__':
    unittest.main()
