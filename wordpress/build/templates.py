"""Elementor Pro Theme Builder templates for resources: loop-item cards, the three singles, and the
content of the three archive pages.

These are built here, not converted from prototype HTML, because they are dynamic: Elementor dynamic
tags (post title, URL, excerpt, featured image) fill the widgets, and the theme's shortcodes
(inc/shortcodes.php) render the parts that are logic rather than layout. The class names are the
prototype's, so styles.css and resources.css keep styling them.

Placeholders the importer resolves:
  __W270_TPL__:<slug>            → the post ID of that saved template (loop grid template_id)
  __W270_TERM__:<taxonomy>:<slug> → the term ID (loop query include terms)
  conditions 'include/singular/in_resource_type:<slug>' → 'include/singular/in_resource_type/<term id>'
"""
import json
import os
from urllib.parse import quote


def _gen():
    import generate  # imported lazily: generate.py imports this module
    return generate


def new_id():
    return _gen().new_id()


def tag(name, settings=None):
    """An Elementor dynamic-tag token, as stored in an element's __dynamic__ settings."""
    return f'[elementor-tag id="{new_id()}" name="{name}" settings="{quote(json.dumps(settings or {}, separators=(",", ":")))}"]'


def con(classes, children, html_tag='div', link=None, extra=None):
    settings = {'content_width': 'full', 'html_tag': html_tag, 'css_classes': ('w-con ' + classes).strip()}
    if link == 'post':
        settings['link'] = {'url': '', 'is_external': '', 'nofollow': ''}
        settings['__dynamic__'] = {'link': tag('post-url')}
    if extra:
        settings.update(extra)
    return {'id': new_id(), 'elType': 'container', 'isInner': True, 'settings': settings, 'elements': children}


def widget(wtype, settings):
    return {'id': new_id(), 'elType': 'widget', 'widgetType': wtype, 'settings': settings, 'elements': []}


def heading(text, size, classes, dynamic=None, link=None):
    s = {'title': text, 'header_size': size, '_css_classes': ('w-heading ' + classes).strip()}
    dyn = {}
    if dynamic:
        dyn['title'] = dynamic
    if link == 'post':
        s['link'] = {'url': '', 'is_external': '', 'nofollow': ''}
        dyn['link'] = tag('post-url')
    if dyn:
        s['__dynamic__'] = dyn
    return widget('heading', s)


def text(html, classes, dynamic=None):
    s = {'editor': html, '_css_classes': ('w-text ' + classes).strip()}
    if dynamic:
        s['__dynamic__'] = {'editor': dynamic}
    return widget('text-editor', s)


def raw(markup, classes=''):
    return widget('html', {'html': markup, '_css_classes': ('w-html ' + classes).strip()})


def sc(shortcode):
    """A shortcode widget whose wrapper vanishes (w-html), so its markup sits directly in the layout."""
    return widget('shortcode', {'shortcode': shortcode, '_css_classes': 'w-html'})


def button(label, url, classes):
    return widget('button', {'text': label, 'link': {'url': url, 'is_external': '', 'nofollow': ''}, '_css_classes': ('w-btn ' + classes).strip()})


def featured_image(classes=''):
    return widget('image', {'image': {'url': '', 'id': ''}, 'image_size': 'large',
                            '__dynamic__': {'image': tag('post-featured-image')},
                            '_css_classes': ('w-image ' + classes).strip()})


def shortcode_tag(shortcode):
    return tag('shortcode', {'shortcode': shortcode})


PLAY = '<span class="story-card-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>'


# ── loop items (one card each) ──
def card_guide():
    return con('guide-card', [
        con('guide-card-link', [
            con('guide-card-meta', [sc('[w270_type_label class="guide-type"]'), sc('[w270_read_time class="guide-read" suffix=" min read"]')]),
            heading('', 'h3', 'guide-card-h', dynamic=tag('post-title')),
            text('', 'guide-card-p', dynamic=tag('post-excerpt')),
            text('Read →', 'guide-card-more'),
        ], html_tag='a', link='post'),
    ])


def card_story():
    return con('story-card', [
        con('story-card-media', [featured_image(), raw(PLAY)]),
        con('story-card-body', [
            sc('[w270_story_meta]'),
            sc('[w270_field name="pull_quote" wrap="blockquote" class="story-card-quote"]'),
            con('story-card-cite', [
                sc('[w270_field name="veteran_name" wrap="span" class="story-card-name"]'),
                sc('[w270_field name="veteran_role" wrap="span" class="story-card-role" default="Canadian Armed Forces Veteran"]'),
            ]),
            sc('[w270_story_link]'),
        ]),
    ], html_tag='a', link='post')


def card_news():
    return con('news-entry', [
        con('', [
            sc('[w270_news_meta]'),
            heading('', 'h3', 'news-entry-h', dynamic=tag('post-title'), link='post'),
            text('', 'news-entry-p', dynamic=tag('post-excerpt')),
            sc('[w270_news_link]'),
        ], html_tag='article'),
    ])


# ── singles ──
def hero_kicker(label):
    return raw(f'<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>{label}</div>')


def single_library():
    return con('site-main', [
        con('res-header', [
            con('res-crumb', [text('<a href="/resources/">Resources</a>', ''), sc('[w270_topic_crumb]')]),
            heading('', 'h1', 'res-title', dynamic=shortcode_tag('[w270_seo_h1]')),
            heading('', 'h2', 'page-hero-lead', dynamic=shortcode_tag('[w270_marketing_h2]')),
            sc('[w270_hero_meta]'),
        ], html_tag='section'),
        con('article-layout', [
            sc('[w270_toc]'),
            con('article-body', [
                text('', 'article-intro', dynamic=tag('post-excerpt')),
                sc('[w270_checklist]'),
                widget('theme-post-content', {'_css_classes': 'w-html'}),
                sc('[w270_callout]'),
            ], html_tag='article'),
            con('article-rail', [
                con('article-rail-cta', [
                    text('Need help?', 'article-rail-label'),
                    heading('Talk to an advisor — free.', 'h3', 'article-rail-h'),
                    text('Two-minute VAC status check. No pressure, no obligation.', 'article-rail-p'),
                    button('Check your VAC status →', '/vac-status-checker/', 'article-rail-btn'),
                ]),
                sc('[w270_related]'),
            ], html_tag='aside'),
        ], html_tag='section'),
    ], html_tag='main', extra={'_element_id': 'content'})


def single_story():
    return con('site-main', [
        con('page-hero', [
            hero_kicker('Veteran story'),
            heading('', 'h1', 'page-hero-h1', dynamic=shortcode_tag('[w270_seo_h1]')),
        ], html_tag='section'),
        con('story-single', [
            sc('[w270_story_media]'),
            sc('[w270_field name="pull_quote" wrap="blockquote" class="story-single-quote"]'),
            con('story-single-cite', [
                sc('[w270_field name="veteran_name" wrap="span" class="story-card-name"]'),
                sc('[w270_field name="veteran_role" wrap="span" class="story-card-role" default="Canadian Armed Forces Veteran"]'),
            ]),
            con('article-body', [widget('theme-post-content', {'_css_classes': 'w-html'})]),
        ], html_tag='section'),
    ], html_tag='main', extra={'_element_id': 'content'})


def single_news():
    return con('site-main', [
        con('page-hero', [
            hero_kicker('News'),
            heading('', 'h1', 'page-hero-h1', dynamic=shortcode_tag('[w270_seo_h1]')),
            sc('[w270_news_meta]'),
        ], html_tag='section'),
        con('article-layout article-layout-narrow', [
            con('article-body', [widget('theme-post-content', {'_css_classes': 'w-html'})]),
        ], html_tag='section'),
    ], html_tag='main', extra={'_element_id': 'content'})


# ── archive pages ──
ARCHIVES = {
    'guides': dict(
        h1='VAC guides and checklists for Canadian veterans', lead='Know what VAC needs before you apply.',
        sub='Free explainers and checklists on Veterans Affairs Canada programs, paperwork and timelines, whether you work with us or not.',
        types=['guides', 'checklists', 'explainers'], card='w270-card-guide', section='archive archive-library',
        filter_tax='resource_topic', first='All', count='resources', columns=(3, 2, 1), gap=20,
        orderby='menu_order', order='ASC',
    ),
    'stories': dict(
        h1='Canadian veteran stories about the VAC claims process', lead='Real veterans. Real stories.',
        sub='Veterans share what their service meant, what the VAC process was like, and what changed once someone was in their corner. Each story is shared with their permission.',
        types=['stories'], card='w270-card-story', section='archive archive-stories',
        filter_tax=None, first=None, count=None, columns=(2, 2, 1), gap=24,
        orderby='menu_order', order='ASC',
    ),
    'news': dict(
        h1='270 West news and updates for Canadian veterans', lead="What we're working on.",
        sub="Where you'll find us, new guides as they're published, and updates from the team.",
        types=['news'], card='w270-card-news', section='archive archive-news',
        filter_tax='news_category', first='All updates', count='Latest updates from the team', columns=(1, 1, 1), gap=0,
        orderby='post_date', order='DESC',
    ),
}


def archive_page(slug, a):
    grid_id = new_id()
    grid = {
        'id': grid_id, 'elType': 'widget', 'widgetType': 'loop-grid', 'elements': [],
        'settings': {
            '_skin': 'post',
            'template_id': f'__W270_TPL__:{a["card"]}',
            'columns': str(a['columns'][0]), 'columns_tablet': str(a['columns'][1]), 'columns_mobile': str(a['columns'][2]),
            'posts_per_page': 60,
            'column_gap': {'unit': 'px', 'size': a['gap'], 'sizes': []},
            'row_gap': {'unit': 'px', 'size': a['gap'], 'sizes': []},
            'post_query_post_type': 'resource',
            'post_query_include': ['terms'],
            'post_query_include_term_ids': [f'__W270_TERM__:resource_type:{t}' for t in a['types']],
            'post_query_orderby': a['orderby'],
            'post_query_order': a['order'],
            'post_query_ignore_sticky_posts': 'yes',
            '_css_classes': 'w-loop',
        },
    }
    toolbar = []
    if a['count']:
        if a['filter_tax'] == 'resource_topic':
            toolbar.append(sc(f'[w270_archive_count types="{",".join(a["types"])}" label="{a["count"]}"]'))
        else:
            toolbar.append(text(a['count'], 'archive-count'))
    if a['filter_tax']:
        toolbar.append(widget('taxonomy-filter', {
            'selected_element': grid_id, 'taxonomy': a['filter_tax'], 'direction': 'horizontal',
            'show_first_item': 'yes', 'first_item_title': a['first'], 'multiple_selection': '',
            '_css_classes': 'w-html archive-filters',
        }))
    body = ([con('archive-toolbar', toolbar)] if toolbar else []) + [grid]
    return [
        con('page-hero', [
            hero_kicker('Resources'),
            heading(a['h1'], 'h1', 'page-hero-h1'),
            text(a['lead'], 'page-hero-lead'),
            text(a['sub'], 'page-hero-sub'),
        ], html_tag='section'),
        con(a['section'], body, html_tag='section'),
    ]


def build(out_dir):
    """Writes out/tpl-*.json for the Pro templates and out/archive-*.json for the archive pages."""
    templates = [
        ('loop-item', 'w270-card-guide', 'Resource card: guide', [card_guide()], []),
        ('loop-item', 'w270-card-story', 'Resource card: story', [card_story()], []),
        ('loop-item', 'w270-card-news', 'Resource card: news', [card_news()], []),
        ('single', 'w270-single-library', 'Resource: guide, checklist, explainer', [single_library()], ['include/singular/resource']),
        # Pro resolves sub-conditions from a flat registry: include/singular/in_resource_type/<term id>.
        ('single', 'w270-single-story', 'Resource: story', [single_story()], ['include/singular/in_resource_type:stories']),
        ('single', 'w270-single-news', 'Resource: news', [single_news()], ['include/singular/in_resource_type:news']),
    ]
    written = []
    for kind, slug, title, elements, conditions in templates:
        for el in elements:
            el['isInner'] = False
        path = os.path.join(out_dir, f'tpl-{slug}.json')
        with open(path, 'w', encoding='utf-8') as f:
            json.dump({'type': kind, 'slug': slug, 'title': title, 'conditions': conditions, 'media': [], 'elements': elements}, f, ensure_ascii=False, indent=1)
        written.append(slug)
    for slug, a in ARCHIVES.items():
        elements = archive_page(slug, a)
        for el in elements:
            el['isInner'] = False
        with open(os.path.join(out_dir, f'archive-{slug}.json'), 'w', encoding='utf-8') as f:
            json.dump({'slug': slug, 'elements': elements}, f, ensure_ascii=False, indent=1)
        written.append(f'archive-{slug}')
    return written
