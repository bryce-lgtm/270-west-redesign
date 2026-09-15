# Resource Post Types (ACF Pro) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Guide, Checklist and Explainer post types with ACF Pro fields, render them and the Resources landing page from the child theme using the prototype designs, and seed the prototype's 15 resources on Local and SiteGround.

**Architecture:** A small plugin (`wordpress/plugins/270west-content`) registers the post types, the Topic taxonomy and the ACF field groups (Local JSON). The child theme gains `inc/resources.php` helpers, `assets/css/resources.css`, three single templates, a "Resources landing" page template and shared template parts. The importer gets a `--resources` step that seeds topics/resources (the featured guide's body converted from `article.html`), assigns the page template and retires the old article page. Spec: `docs/superpowers/specs/2026-09-15-resource-post-types-design.md`.

**Tech Stack:** WordPress 7.1, ACF Pro (installed by the user; everything degrades gracefully without it), PHP 8.2 via Local's binary / SiteGround SSH, Python 3.9 stdlib (`unittest`), existing build scripts in `wordpress/build/`.

---

## Environment facts

```
ROOT=/Users/Bryce/270-west-redesign/.claude/worktrees/distracted-sutherland-766c39
PHP="/Users/Bryce/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
INI="/Users/Bryce/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini"
# Local site: http://270-west.local  SiteGround: https://brycec57.sg-host.com (deploy-siteground.sh)
```

- Existing helpers in `wordpress/build/import.php`: `w270_page_by_slug($slug)`, `w270_media_id($filename)`, `w270_page_paths()`, `$GLOBALS['w270_failed']`, flags parsed in `w270_main`.
- Existing generator helpers in `wordpress/build/generate.py`: `rewrite_links(html)`, `parse()` from `htmldom.py` (`doc.outer_html(node)`, `doc.inner_html(node)`, `node.find(pred)`, `node.classes`, `node.attrs`).
- Prototype CSS already defines `.page-hero*`, `.resources-featured`, `.resource-card-main*`, `.resource-card-sm*`, `.library-*`, `.article-*`, `.photo`, `.mono-label`, `.btn-ink`, `.section-pad`. Only the prototype's inline-styled bits need new classes (`resources.css`).
- ACF true/false fields store `'1'`/`'0'` in post meta under the field name, which is what the featured meta query relies on.

## File structure

```
wordpress/plugins/270west-content/
  270west-content.php                         post types, taxonomy, ACF JSON paths, type labels
  acf-json/group_270w_resource_details.json
  acf-json/group_270w_guide_extras.json
  acf-json/group_270w_checklist_extras.json
  acf-json/group_270w_resources_landing.json  hero fields on the Resources page
wordpress/theme/270west/
  functions.php                               + require inc/resources.php
  inc/resources.php                           helpers, content filter, enqueue, redirects
  assets/css/resources.css                    classes for the prototype's inline-styled article/library bits
  single-guide.php  single-checklist.php  single-explainer.php
  template-resources.php                      Template Name: Resources landing
  template-parts/resource/{single,hero,toc,body,callout,checklist-items,rail,related,card,library}.php
wordpress/build/
  seed-resources.json                         topics, 15 resources, landing hero copy
  seed_article.py                             article.html body → out/seed-article.json
  tests/test_resources.py
  import.php                                  + w270_import_resources(), --resources flag
  pages.py                                    article removed from PAGES; LINK_MAP keeps article.html
  check-coverage.py                           + resources landing + guide article-body checks
  render-check.php                            + resource permalinks/content and ACF group count
  deploy-theme.sh, deploy-siteground.sh       + plugin rsync
```

---

### Task 1: Plugin `270west-content` with post types, taxonomy and ACF field groups

**Files:**
- Create: `wordpress/plugins/270west-content/270west-content.php`
- Create: `wordpress/plugins/270west-content/acf-json/group_270w_resource_details.json`
- Create: `wordpress/plugins/270west-content/acf-json/group_270w_guide_extras.json`
- Create: `wordpress/plugins/270west-content/acf-json/group_270w_checklist_extras.json`
- Create: `wordpress/plugins/270west-content/acf-json/group_270w_resources_landing.json`
- Modify: `wordpress/build/deploy-theme.sh` (add plugin rsync)
- Modify: `wordpress/build/deploy-siteground.sh` (add plugin rsync + git add)

- [ ] **Step 1: Write the plugin**

```php
<?php
/**
 * Plugin Name: 270 West Content
 * Description: Resource post types (Guides, Checklists, Explainers), the Topic taxonomy and their ACF field groups.
 * Version: 1.0.0
 * Author: 270 West Consulting
 * Text Domain: 270west
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'W270C_DIR', plugin_dir_path( __FILE__ ) );

/** post type => [singular, plural, rewrite slug, menu icon] */
const W270C_TYPES = [
	'guide'     => [ 'Guide', 'Guides', 'resources/guides', 'dashicons-book' ],
	'checklist' => [ 'Checklist', 'Checklists', 'resources/checklists', 'dashicons-yes-alt' ],
	'explainer' => [ 'Explainer', 'Explainers', 'resources/explainers', 'dashicons-lightbulb' ],
];

function w270c_types() {
	return array_keys( W270C_TYPES );
}

/** "Guide" / "Checklist" / "Explainer"; the featured main card says "Featured guide". */
function w270c_type_label( $type, $featured = false ) {
	$label = W270C_TYPES[ $type ][0] ?? 'Resource';
	return ( $featured && 'guide' === $type ) ? 'Featured guide' : $label;
}

function w270c_register() {
	$position = 21;
	foreach ( W270C_TYPES as $type => [ $singular, $plural, $slug, $icon ] ) {
		register_post_type( $type, [
			'labels' => [
				'name' => $plural, 'singular_name' => $singular, 'add_new_item' => "Add New {$singular}",
				'edit_item' => "Edit {$singular}", 'new_item' => "New {$singular}", 'view_item' => "View {$singular}",
				'search_items' => "Search {$plural}", 'not_found' => "No {$plural} found", 'all_items' => "All {$plural}",
			],
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => [ 'slug' => $slug, 'with_front' => false ],
			'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
			'menu_icon'     => $icon,
			'menu_position' => $position++,
		] );
	}
	register_taxonomy( 'resource_topic', w270c_types(), [
		'labels' => [ 'name' => 'Topics', 'singular_name' => 'Topic', 'add_new_item' => 'Add New Topic', 'edit_item' => 'Edit Topic', 'search_items' => 'Search Topics' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'resources/topic', 'with_front' => false ],
	] );
}
add_action( 'init', 'w270c_register' );

// ACF Local JSON: field groups load from (and save back to) the plugin's acf-json folder.
add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = W270C_DIR . 'acf-json';
	return $paths;
} );
add_filter( 'acf/settings/save_json', fn() => W270C_DIR . 'acf-json' );

register_activation_hook( __FILE__, function () {
	w270c_register();
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
```

- [ ] **Step 2: Write the four ACF field group JSON files**

`acf-json/group_270w_resource_details.json`:

```json
{
  "key": "group_270w_resource_details",
  "title": "Resource details",
  "fields": [
    { "key": "field_270w_summary", "label": "Summary", "name": "summary", "type": "textarea", "required": 1, "rows": 3, "new_lines": "", "instructions": "One or two sentences. Shown on cards and as the intro paragraph of the article." },
    { "key": "field_270w_read_time", "label": "Read time (minutes)", "name": "read_time", "type": "number", "min": 1, "step": 1, "default_value": 5 },
    { "key": "field_270w_featured", "label": "Featured", "name": "featured", "type": "true_false", "ui": 1, "message": "Show in “Start here” on the Resources page" },
    { "key": "field_270w_related", "label": "Related resources", "name": "related_resources", "type": "relationship", "post_type": ["guide", "checklist", "explainer"], "filters": ["search", "post_type"], "max": 4, "return_format": "id", "instructions": "Up to four. Leave empty to show the newest resources in the same topic." },
    { "key": "field_270w_seo_h1", "label": "SEO H1", "name": "seo_h1", "type": "text", "instructions": "Keyword-led heading. When set it becomes the H1 and the post title renders as the H2 beneath it." }
  ],
  "location": [
    [ { "param": "post_type", "operator": "==", "value": "guide" } ],
    [ { "param": "post_type", "operator": "==", "value": "checklist" } ],
    [ { "param": "post_type", "operator": "==", "value": "explainer" } ]
  ],
  "menu_order": 0, "position": "normal", "style": "default", "label_placement": "top", "instruction_placement": "label", "active": true, "show_in_rest": 0
}
```

`acf-json/group_270w_guide_extras.json`:

```json
{
  "key": "group_270w_guide_extras",
  "title": "Guide extras",
  "fields": [
    { "key": "field_270w_show_toc", "label": "Show table of contents", "name": "show_toc", "type": "true_false", "ui": 1, "default_value": 1, "instructions": "Built from the H2 headings in the content. Hidden automatically when there are fewer than two." },
    { "key": "field_270w_callout", "label": "Callout box", "name": "callout", "type": "group", "layout": "block", "sub_fields": [
      { "key": "field_270w_callout_label", "label": "Label", "name": "label", "type": "text", "default_value": "Helpful tip" },
      { "key": "field_270w_callout_text", "label": "Text", "name": "text", "type": "textarea", "rows": 3, "new_lines": "" }
    ] }
  ],
  "location": [ [ { "param": "post_type", "operator": "==", "value": "guide" } ] ],
  "menu_order": 1, "position": "normal", "style": "default", "label_placement": "top", "instruction_placement": "label", "active": true, "show_in_rest": 0
}
```

`acf-json/group_270w_checklist_extras.json`:

```json
{
  "key": "group_270w_checklist_extras",
  "title": "Checklist extras",
  "fields": [
    { "key": "field_270w_items", "label": "Checklist items", "name": "items", "type": "repeater", "layout": "row", "button_label": "Add item", "sub_fields": [
      { "key": "field_270w_item", "label": "Item", "name": "item", "type": "text", "required": 1 },
      { "key": "field_270w_item_note", "label": "Note", "name": "note", "type": "text", "instructions": "Optional. Shown under the item in smaller text." }
    ] },
    { "key": "field_270w_download_pdf", "label": "PDF download", "name": "download_pdf", "type": "file", "return_format": "id", "mime_types": "pdf", "instructions": "Optional printable version of this checklist." }
  ],
  "location": [ [ { "param": "post_type", "operator": "==", "value": "checklist" } ] ],
  "menu_order": 1, "position": "normal", "style": "default", "label_placement": "top", "instruction_placement": "label", "active": true, "show_in_rest": 0
}
```

`acf-json/group_270w_resources_landing.json`:

```json
{
  "key": "group_270w_resources_landing",
  "title": "Resources landing",
  "fields": [
    { "key": "field_270w_hero_h1", "label": "Hero heading (H1)", "name": "hero_h1", "type": "text", "instructions": "Falls back to the page title." },
    { "key": "field_270w_hero_lead", "label": "Hero lead", "name": "hero_lead", "type": "text" },
    { "key": "field_270w_hero_sub", "label": "Hero sub-copy", "name": "hero_sub", "type": "textarea", "rows": 3, "new_lines": "" }
  ],
  "location": [ [ { "param": "page_template", "operator": "==", "value": "template-resources.php" } ] ],
  "menu_order": 0, "position": "normal", "style": "default", "label_placement": "top", "instruction_placement": "label", "active": true, "show_in_rest": 0
}
```

- [ ] **Step 3: Add the plugin to both deploy scripts**

In `wordpress/build/deploy-theme.sh`, after the theme rsync line add:

```bash
mkdir -p "$SITE/wp-content/plugins/270west-content"
rsync -a --delete "$ROOT/wordpress/plugins/270west-content/" "$SITE/wp-content/plugins/270west-content/"
echo "plugin deployed to $SITE/wp-content/plugins/270west-content"
```

In `wordpress/build/deploy-siteground.sh`, after the `rsync … "$DEST/build/"` line and before `git -C "$CLONE" add -A wp-content/themes/270west`, add:

```bash
PDEST="$CLONE/wp-content/plugins/270west-content"
mkdir -p "$PDEST"
rsync -a --delete "$ROOT/wordpress/plugins/270west-content/" "$PDEST/"
git -C "$CLONE" add -A wp-content/plugins/270west-content
```

- [ ] **Step 4: Deploy locally, activate, and verify registration and rewrites**

```bash
wordpress/build/deploy-theme.sh
"$PHP" -c "$INI" -r 'if(PHP_SAPI!=="cli")exit;define("WP_USE_THEMES",false);$_SERVER["HTTP_HOST"]="270-west.local";require "/Users/Bryce/Local Sites/270-west/app/public/wp-load.php";require_once ABSPATH."wp-admin/includes/plugin.php";$p="270west-content/270west-content.php";if(!is_plugin_active($p)){$r=activate_plugin($p);echo is_wp_error($r)?"ERR ".$r->get_error_message():"activated";}else echo "already active";echo "\n";flush_rewrite_rules();foreach(["guide","checklist","explainer"] as $t)echo $t,": ",post_type_exists($t)?"ok":"MISSING"," ",get_post_type_object($t)->rewrite["slug"],"\n";echo "taxonomy: ",taxonomy_exists("resource_topic")?"ok":"MISSING","\n";echo "acf: ",function_exists("acf_get_field_groups")?count(acf_get_field_groups())." groups":"not installed","\n";'
```
Expected: `activated`, three `ok` lines with slugs `resources/guides` etc., `taxonomy: ok`, and `acf: 4 groups` once ACF Pro is installed (or `acf: not installed` before it is).

- [ ] **Step 5: Commit**

```bash
git add wordpress/plugins wordpress/build/deploy-theme.sh wordpress/build/deploy-siteground.sh
git commit -m "Add 270west-content plugin: resource post types, Topic taxonomy, ACF field groups"
```

---

### Task 2: Seed data — `seed-resources.json`, `seed_article.py`, tests

**Files:**
- Create: `wordpress/build/seed-resources.json`
- Create: `wordpress/build/seed_article.py`
- Create: `wordpress/build/tests/test_resources.py`
- Modify: `wordpress/build/pages.py` (drop the article page; keep its link mapping)

- [ ] **Step 1: Write `seed-resources.json`**

Titles, types, topics and read times mirror `resources.html`; `order` is the prototype's display order (featured 0–2, library 10+). Slug of the featured guide must stay `vac-benefits-programs-guide` (old URL redirects to it).

```json
{
  "topics": ["VAC eligibility & programs", "Filing your first VAC claim", "After a VAC decision"],
  "landing": {
    "hero_h1": "VAC benefits resources, guides, and checklists for Canadian veterans.",
    "hero_lead": "Plain-language guides for navigating VAC.",
    "hero_sub": "Free, no-jargon explainers and checklists to help you understand Veterans Affairs Canada programs, paperwork, and timelines — whether you choose to work with us or not."
  },
  "resources": [
    { "slug": "vac-benefits-programs-guide", "type": "guide", "title": "A plain-language guide to VAC's main benefits programs", "topic": "VAC eligibility & programs", "read_time": 18, "featured": true, "image": "mountain-valley.jpg", "order": 0,
      "summary": "Veterans Affairs Canada offers more programs than most veterans realize. This guide walks through the main ones — what they're for, who qualifies, and how to apply — in plain language.",
      "related": ["vac-application-checklist", "how-vac-disability-ratings-work", "common-reasons-applications-come-back"] },
    { "slug": "vac-application-checklist", "type": "checklist", "title": "VAC application checklist: documents to gather", "topic": "Filing your first VAC claim", "read_time": 5, "featured": true, "image": "forest-fog.jpg", "order": 1,
      "summary": "The documents to gather before you start a VAC application, so it is complete the first time.",
      "items": ["Proof of service (release or service record)", "Current diagnosis from a treating physician", "Service health records covering the condition", "Any earlier VAC decision letters", "Supporting statements from family, peers or supervisors"] },
    { "slug": "how-vac-disability-ratings-work", "type": "explainer", "title": "How VAC disability ratings actually work", "topic": "After a VAC decision", "read_time": 8, "featured": true, "image": "hills-road.jpg", "order": 2,
      "summary": "What a disability assessment measures, how the percentage is set, and what it means for your benefits." },

    { "slug": "disability-benefits-what-they-cover", "type": "explainer", "title": "Disability Benefits — what they cover", "topic": "VAC eligibility & programs", "read_time": 6, "order": 10, "summary": "Coming soon: a plain-language explainer from 270 West." },
    { "slug": "income-replacement-benefit-explained", "type": "explainer", "title": "Income Replacement Benefit explained", "topic": "VAC eligibility & programs", "read_time": 6, "order": 11, "summary": "Coming soon: a plain-language explainer from 270 West." },
    { "slug": "career-transition-services", "type": "explainer", "title": "Career Transition Services", "topic": "VAC eligibility & programs", "read_time": 5, "order": 12, "summary": "Coming soon: a plain-language explainer from 270 West." },
    { "slug": "family-caregiver-support", "type": "explainer", "title": "Family caregiver support", "topic": "VAC eligibility & programs", "read_time": 5, "order": 13, "summary": "Coming soon: a plain-language explainer from 270 West." },

    { "slug": "what-to-expect-when-you-apply", "type": "guide", "title": "What to expect when you apply", "topic": "Filing your first VAC claim", "read_time": 8, "order": 20, "summary": "Coming soon: a plain-language guide from 270 West." },
    { "slug": "building-your-service-related-health-record", "type": "checklist", "title": "Building your service-related health record", "topic": "Filing your first VAC claim", "read_time": 6, "order": 21, "summary": "Coming soon: a checklist from 270 West." },
    { "slug": "getting-medical-documentation-right", "type": "guide", "title": "Getting medical documentation right", "topic": "Filing your first VAC claim", "read_time": 7, "order": 22, "summary": "Coming soon: a plain-language guide from 270 West." },
    { "slug": "common-reasons-applications-come-back", "type": "explainer", "title": "Common reasons applications come back", "topic": "Filing your first VAC claim", "read_time": 5, "order": 23, "summary": "Coming soon: a plain-language explainer from 270 West." },

    { "slug": "understanding-your-decision-letter", "type": "explainer", "title": "Understanding your decision letter", "topic": "After a VAC decision", "read_time": 6, "order": 30, "summary": "Coming soon: a plain-language explainer from 270 West." },
    { "slug": "when-to-consider-a-re-assessment", "type": "guide", "title": "When to consider a re-assessment", "topic": "After a VAC decision", "read_time": 7, "order": 31, "summary": "Coming soon: a plain-language guide from 270 West." },
    { "slug": "how-vac-appeals-work", "type": "guide", "title": "How VAC appeals work", "topic": "After a VAC decision", "read_time": 9, "order": 32, "summary": "Coming soon: a plain-language guide from 270 West." },
    { "slug": "mental-health-wellness-resources", "type": "guide", "title": "Mental health & wellness resources", "topic": "After a VAC decision", "read_time": 6, "order": 33, "summary": "Coming soon: a plain-language guide from 270 West." }
  ]
}
```

- [ ] **Step 2: Write the failing tests**

```python
import json
import os
import unittest

from seed_article import convert_article_body

BUILD = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


class SeedResourcesTests(unittest.TestCase):
    def setUp(self):
        with open(os.path.join(BUILD, 'seed-resources.json'), encoding='utf-8') as f:
            self.seed = json.load(f)

    def test_seed_has_15_resources_with_unique_slugs_and_valid_types_topics(self):
        res = self.seed['resources']
        self.assertEqual(len(res), 15)
        self.assertEqual(len({r['slug'] for r in res}), 15)
        for r in res:
            self.assertIn(r['type'], ('guide', 'checklist', 'explainer'), r['slug'])
            self.assertIn(r['topic'], self.seed['topics'], r['slug'])
            self.assertGreater(r['read_time'], 0)
            self.assertTrue(r['summary'])

    def test_three_featured_and_related_slugs_exist(self):
        res = self.seed['resources']
        self.assertEqual(sum(1 for r in res if r.get('featured')), 3)
        slugs = {r['slug'] for r in res}
        for r in res:
            for s in r.get('related', []):
                self.assertIn(s, slugs)
        self.assertEqual(res[0]['slug'], 'vac-benefits-programs-guide')


class SeedArticleTests(unittest.TestCase):
    SRC = ('<article class="article-body">'
           '<p class="article-intro">Intro text.</p>'
           '<div data-photo="forest-fog" data-aspect="16/9" data-pos="center 62%" data-alt="Ridge"></div>'
           '<h2 id="h-0" class="article-h2">First</h2><p class="article-p">Body <a href="faq.html">FAQ</a>.</p>'
           '<div class="article-callout"><div class="article-callout-label">Helpful tip</div><p class="article-callout-p">Keep copies.</p></div>'
           '<h2 id="h-1" class="article-h2">Second</h2><p class="article-p">More.</p>'
           '</article>')

    def test_intro_and_callout_are_extracted_and_photos_become_figures(self):
        out = convert_article_body(self.SRC)
        self.assertEqual(out['summary'], 'Intro text.')
        self.assertEqual(out['callout_label'], 'Helpful tip')
        self.assertEqual(out['callout_text'], 'Keep copies.')
        self.assertNotIn('article-intro', out['content'])
        self.assertNotIn('article-callout', out['content'])
        self.assertIn('<figure class="photo" style="aspect-ratio:16/9"><img src="__W270_MEDIA__:forest-fog.jpg" alt="Ridge" style="object-position:center 62%"></figure>', out['content'])
        self.assertIn('href="/faq/"', out['content'])
        self.assertIn('<h2 id="h-0" class="article-h2">First</h2>', out['content'])


if __name__ == '__main__':
    unittest.main()
```

- [ ] **Step 3: Run to verify failure**

```bash
cd wordpress/build && python3 -m unittest tests.test_resources 2>&1 | tail -2; cd -
```
Expected: `ModuleNotFoundError: No module named 'seed_article'`.

- [ ] **Step 4: Write `seed_article.py`**

```python
"""Converts the prototype article body (article.html) into seed content for the featured guide.
Writes out/seed-article.json: {slug, summary, content, callout_label, callout_text}.
Inline photos become <figure class="photo"> with a __W270_MEDIA__:<file> marker the importer resolves."""
import json
import os
import re

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
```

- [ ] **Step 5: Update `pages.py` — the article is no longer an Elementor page, but links to it must still resolve**

Remove the line `('article.html', 'vac-benefits-programs-guide', 'resources'),` from `PAGES`, and after `LINK_MAP = {...}` add:

```python
# The article became the featured Guide post (seeded by import.php --resources); prototype links still point at it.
LINK_MAP['article.html'] = '/resources/guides/vac-benefits-programs-guide/'
```

- [ ] **Step 6: Run tests, then generate the seed**

```bash
cd wordpress/build && python3 -m unittest discover -s tests -q 2>&1 | tail -1 && python3 seed_article.py && python3 generate.py | tail -1; cd -
```
Expected: `OK`, `seed-article.json: … chars, callout=yes`, and generate prints 16 pages (no `vac-benefits-programs-guide` line).

- [ ] **Step 7: Commit**

```bash
git add wordpress/build/seed-resources.json wordpress/build/seed_article.py wordpress/build/tests/test_resources.py wordpress/build/pages.py
git commit -m "Seed data for resource posts; article page becomes the featured guide"
```

---

### Task 3: Importer `--resources` step, coverage and render checks

**Files:**
- Modify: `wordpress/build/import.php`
- Modify: `wordpress/build/check-coverage.py`
- Modify: `wordpress/build/render-check.php`

- [ ] **Step 1: Remove the article page from `w270_page_paths()`**

In `import.php`, delete `'vac-benefits-programs-guide' => '/resources/vac-benefits-programs-guide/',` from the array returned by `w270_page_paths()`.

- [ ] **Step 2: Add the resources importer (insert above `function w270_main(`)**

```php
/** Replace __W270_MEDIA__:<file> markers with Media Library URLs. */
function w270_media_urls( $html ) {
	return preg_replace_callback( '/__W270_MEDIA__:([\w.-]+)/', function ( $m ) {
		$id = w270_media_id( $m[1] );
		if ( ! $id ) { throw new RuntimeException( "media not imported: {$m[1]}" ); }
		return wp_get_attachment_url( $id );
	}, $html );
}

function w270_import_resources() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugin = '270west-content/270west-content.php';
	if ( ! is_plugin_active( $plugin ) ) {
		$r = activate_plugin( $plugin );
		if ( is_wp_error( $r ) ) { echo "resources: cannot activate plugin: " . $r->get_error_message() . "\n"; $GLOBALS['w270_failed'] = true; return; }
		echo "plugin 270west-content: activated\n";
	}
	$acf = function_exists( 'update_field' );
	if ( ! $acf ) { echo "acf: missing — fields skipped (install ACF Pro and re-run --resources)\n"; }
	$seed    = json_decode( file_get_contents( __DIR__ . '/seed-resources.json' ), true, 512, JSON_THROW_ON_ERROR );
	$article = json_decode( file_get_contents( W270_OUT . '/seed-article.json' ), true, 512, JSON_THROW_ON_ERROR );

	$topics = [];
	foreach ( $seed['topics'] as $name ) {
		$t = term_exists( $name, 'resource_topic' );
		if ( ! $t ) { $t = wp_insert_term( $name, 'resource_topic' ); if ( is_wp_error( $t ) ) { throw new RuntimeException( $t->get_error_message() ); } }
		$topics[ $name ] = (int) $t['term_id'];
	}
	echo 'topics: ' . implode( ', ', array_keys( $topics ) ) . "\n";

	$ids = [];
	foreach ( $seed['resources'] as $r ) {
		try {
			$is_article = $r['slug'] === $article['slug'];
			$existing   = get_page_by_path( $r['slug'], OBJECT, $r['type'] );
			$post = [
				'post_type' => $r['type'], 'post_status' => 'publish', 'post_title' => $r['title'], 'post_name' => $r['slug'],
				'post_excerpt' => $r['summary'], 'menu_order' => (int) $r['order'],
				'post_content' => $is_article ? w270_media_urls( $article['content'] ) : '<p>Content coming soon.</p>',
			];
			$pid = $existing ? wp_update_post( $post + [ 'ID' => $existing->ID ], true ) : wp_insert_post( $post, true );
			if ( is_wp_error( $pid ) ) { throw new RuntimeException( $pid->get_error_message() ); }
			wp_set_object_terms( $pid, [ $topics[ $r['topic'] ] ], 'resource_topic' );
			if ( ! empty( $r['image'] ) && ( $mid = w270_media_id( $r['image'] ) ) ) { set_post_thumbnail( $pid, $mid ); }
			if ( $acf ) {
				update_field( 'field_270w_summary', $r['summary'], $pid );
				update_field( 'field_270w_read_time', (int) $r['read_time'], $pid );
				update_field( 'field_270w_featured', empty( $r['featured'] ) ? 0 : 1, $pid );
				update_field( 'field_270w_seo_h1', $r['seo_h1'] ?? '', $pid );
				if ( 'guide' === $r['type'] ) {
					update_field( 'field_270w_show_toc', 1, $pid );
					if ( $is_article ) {
						update_field( 'field_270w_callout', [ 'field_270w_callout_label' => $article['callout_label'], 'field_270w_callout_text' => $article['callout_text'] ], $pid );
					}
				}
				if ( 'checklist' === $r['type'] && ! empty( $r['items'] ) ) {
					update_field( 'field_270w_items', array_map( fn( $i ) => [ 'field_270w_item' => $i, 'field_270w_item_note' => '' ], $r['items'] ), $pid );
				}
			}
			$ids[ $r['slug'] ] = $pid;
			echo "resource {$r['type']}/{$r['slug']}: #{$pid}\n";
		} catch ( Throwable $e ) {
			echo "resource {$r['slug']}: ERROR " . $e->getMessage() . "\n";
			$GLOBALS['w270_failed'] = true;
		}
	}
	if ( $acf ) {
		foreach ( $seed['resources'] as $r ) {
			if ( empty( $r['related'] ) || empty( $ids[ $r['slug'] ] ) ) { continue; }
			$related = array_values( array_filter( array_map( fn( $s ) => $ids[ $s ] ?? 0, $r['related'] ) ) );
			update_field( 'field_270w_related', $related, $ids[ $r['slug'] ] );
		}
	}

	$page = w270_page_by_slug( 'resources' );
	if ( $page ) {
		update_post_meta( $page->ID, '_wp_page_template', 'template-resources.php' );
		if ( $acf ) {
			update_field( 'field_270w_hero_h1', $seed['landing']['hero_h1'], $page->ID );
			update_field( 'field_270w_hero_lead', $seed['landing']['hero_lead'], $page->ID );
			update_field( 'field_270w_hero_sub', $seed['landing']['hero_sub'], $page->ID );
		}
		echo "resources page: template assigned\n";
	}
	$old = get_page_by_path( 'resources/vac-benefits-programs-guide', OBJECT, 'page' );
	if ( $old ) { wp_trash_post( $old->ID ); echo "old article page #{$old->ID}: trashed\n"; }
	flush_rewrite_rules();
}
```

- [ ] **Step 3: Wire the flag into `w270_main`**

After the `menus` line inside `w270_main`, add:

```php
	if ( $all || isset( $flags['resources'] ) ) { w270_import_resources(); }
```

- [ ] **Step 4: Extend `check-coverage.py`**

Add after the `QUOTES` dict:

```python
# (prototype file, live path, scope). 'main' = whole <main>; 'article' = <article class="article-body"> only,
# compared as a bag of words (the callout moves to the end of the article in WordPress).
EXTRA_CHECKS = [('article.html', '/resources/guides/vac-benefits-programs-guide/', 'article')]
```

Add these functions before `main`:

```python
def article_words(html_text):
    m = re.search(r'<article class="article-body"[\s\S]*?</article>', html_text)
    if not m:
        raise RuntimeError('no <article class="article-body">')
    return norm(m.group(0))


def fetch(path):
    req = urllib.request.Request(BASE + path, headers={'User-Agent': 'Mozilla/5.0 (270west coverage check)'})
    with urllib.request.urlopen(req, timeout=30) as r:
        return r.read().decode('utf-8')


def check_extra(src, path, scope):
    from collections import Counter
    with open(os.path.join(ROOT, src), encoding='utf-8') as f:
        proto = f.read()
    live = fetch(path)
    if scope == 'article':
        a, b = article_words(proto), article_words(live)
        missing = list((Counter(a) - Counter(b)).elements())
        extra = list((Counter(b) - Counter(a)).elements())
        ok = not missing and not extra
        detail = f'missing={missing[:20]} extra={extra[:20]}' if not ok else ''
    else:
        a, b = prototype_words(src), norm(re.search(r'<main\b[\s\S]*?</main>', live).group(0))
        ok, detail = a == b, ''
    print(f'{"OK  " if ok else "DIFF"} {path:45s} {len(a):5d} words {detail}')
    return ok
```

In `main`, before `sys.exit(...)`, add:

```python
    if not only:
        for src, path, scope in EXTRA_CHECKS:
            failed = (not check_extra(src, path, scope)) or failed
```

- [ ] **Step 5: Extend `render-check.php`**

Before `exit( $fail ? 1 : 0 );` add:

```php
// Resource posts: permalinks must resolve through the rewrite rules and content must filter cleanly.
$types = function_exists( 'w270c_types' ) ? w270c_types() : [];
foreach ( get_posts( [ 'post_type' => $types ?: 'nonexistent', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] ) as $p ) {
	try {
		$url = get_permalink( $p );
		if ( url_to_postid( $url ) !== $p->ID ) { throw new RuntimeException( "permalink does not resolve: {$url}" ); }
		$html = apply_filters( 'the_content', $p->post_content );
		if ( str_contains( $html, '__W270_MEDIA__' ) ) { throw new RuntimeException( 'unresolved media marker' ); }
		$terms = wp_get_object_terms( $p->ID, 'resource_topic', [ 'fields' => 'names' ] );
		printf( "OK   %-45s %s topic=%s\n", $p->post_type . '/' . $p->post_name, parse_url( $url, PHP_URL_PATH ), $terms ? $terms[0] : '-' );
	} catch ( Throwable $e ) { $fail = true; printf( "FAIL %s: %s\n", $p->post_name, $e->getMessage() ); }
}
if ( function_exists( 'acf_get_field_groups' ) ) {
	$n = count( acf_get_field_groups() );
	printf( "%s acf field groups: %d\n", 4 === $n ? 'OK  ' : 'FAIL', $n );
	if ( 4 !== $n ) { $fail = true; }
} else {
	echo "WARN acf not installed: field groups not checked\n";
}
```

- [ ] **Step 6: Run the importer step and the render check (templates come in Task 4, so pages still 404 until then)**

```bash
"$PHP" -c "$INI" wordpress/build/import.php --resources; echo "exit $?"
"$PHP" -c "$INI" wordpress/build/render-check.php | tail -20
```
Expected: `topics: …`, 15 `resource …: #N` lines, `resources page: template assigned`, `old article page #56: trashed`, exit 0; render check shows 15 `OK guide/…` style lines with paths under `/resources/guides/` etc.

- [ ] **Step 7: Commit**

```bash
git add wordpress/build/import.php wordpress/build/check-coverage.py wordpress/build/render-check.php
git commit -m "Importer: seed resource posts, assign Resources template, retire article page; checks"
```

---

### Task 4: Theme — helpers, stylesheet, templates

**Files:**
- Create: `wordpress/theme/270west/inc/resources.php`
- Create: `wordpress/theme/270west/assets/css/resources.css`
- Create: `wordpress/theme/270west/single-guide.php`, `single-checklist.php`, `single-explainer.php`
- Create: `wordpress/theme/270west/template-resources.php`
- Create: `wordpress/theme/270west/template-parts/resource/{single,hero,toc,body,callout,checklist-items,rail,related,card,library}.php`
- Modify: `wordpress/theme/270west/functions.php` (require the helpers)

- [ ] **Step 1: `inc/resources.php`**

```php
<?php
/**
 * Resource post types: helpers, content filter, stylesheet, legacy redirects.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270_resource_types() {
	return function_exists( 'w270c_types' ) ? w270c_types() : [ 'guide', 'checklist', 'explainer' ];
}

/** ACF field value, or null when ACF is not installed. */
function w270_field( $name, $post_id = null ) {
	return function_exists( 'get_field' ) ? get_field( $name, $post_id ) : null;
}

function w270_type_label( $type, $featured = false ) {
	if ( function_exists( 'w270c_type_label' ) ) { return w270c_type_label( $type, $featured ); }
	$labels = [ 'guide' => 'Guide', 'checklist' => 'Checklist', 'explainer' => 'Explainer' ];
	return ( $featured && 'guide' === $type ) ? 'Featured guide' : ( $labels[ $type ] ?? 'Resource' );
}

function w270_read_time( $post_id ) {
	return max( 0, (int) w270_field( 'read_time', $post_id ) );
}

function w270_topic( $post_id ) {
	$terms = get_the_terms( $post_id, 'resource_topic' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/** Related resources: the ACF selection, else up to 3 newest resources in the same topic. */
function w270_related( $post_id ) {
	$ids = w270_field( 'related_resources', $post_id );
	if ( $ids ) { return array_filter( array_map( 'get_post', array_map( 'intval', (array) $ids ) ) ); }
	$topic = w270_topic( $post_id );
	$args  = [ 'post_type' => w270_resource_types(), 'numberposts' => 3, 'post__not_in' => [ $post_id ] ];
	if ( $topic ) { $args['tax_query'] = [ [ 'taxonomy' => 'resource_topic', 'terms' => $topic->term_id ] ]; }
	return get_posts( $args );
}

/** Adds the prototype's article classes and h-N ids to resource content (runs after wpautop). */
function w270_article_classes( $content ) {
	if ( ! is_singular( w270_resource_types() ) || ! in_the_loop() ) { return $content; }
	$i = 0;
	$content = preg_replace_callback( '/<h2\b([^>]*)>/i', function ( $m ) use ( &$i ) {
		$attrs = $m[1];
		if ( ! preg_match( '/\bid=/', $attrs ) ) { $attrs .= ' id="h-' . $i . '"'; }
		$i++;
		if ( ! preg_match( '/\bclass=/', $attrs ) ) { $attrs .= ' class="article-h2"'; }
		return "<h2{$attrs}>";
	}, $content );
	foreach ( [ 'h3' => 'article-h3', 'p' => 'article-p', 'blockquote' => 'article-blockquote' ] as $tag => $class ) {
		$content = preg_replace( '/<' . $tag . '(?![^>]*\bclass=)([^>]*)>/i', '<' . $tag . ' class="' . $class . '"$1>', $content );
	}
	return $content;
}
add_filter( 'the_content', 'w270_article_classes', 20 );

/** [['id' => 'h-0', 'text' => 'Heading'], …] from filtered content; empty if fewer than two H2s. */
function w270_toc( $content ) {
	preg_match_all( '/<h2\b[^>]*\bid="([^"]+)"[^>]*>([\s\S]*?)<\/h2>/i', $content, $m, PREG_SET_ORDER );
	$toc = array_map( fn( $h ) => [ 'id' => $h[1], 'text' => wp_strip_all_tags( $h[2] ) ], $m );
	return count( $toc ) >= 2 ? $toc : [];
}

function w270_is_resource_view() {
	return is_singular( w270_resource_types() ) || is_page_template( 'template-resources.php' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! w270_is_resource_view() ) { return; }
	$dir = get_stylesheet_directory();
	wp_enqueue_style( 'w270-resources', W270_ASSETS . '/css/resources.css', [ 'w270-styles' ], filemtime( $dir . '/assets/css/resources.css' ) );
}, 25 );

// The article used to be a page under /resources/; it is now the featured guide.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) { return; }
	$map  = [ '/resources/vac-benefits-programs-guide/' => '/resources/guides/vac-benefits-programs-guide/' ];
	$path = trailingslashit( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
	if ( isset( $map[ $path ] ) ) { wp_redirect( home_url( $map[ $path ] ), 301 ); exit; }
} );
```

In `functions.php`, after the walker `require_once`, add:

```php
require_once get_stylesheet_directory() . '/inc/resources.php';
```

- [ ] **Step 2: `assets/css/resources.css`** (the prototype's inline styles on article/resources pages, as classes)

```css
/* Resource singles + Resources landing: classes for what the prototype styled inline. */
.res-header { padding: 60px 80px 40px; border-bottom: 1px solid var(--rule); }
.res-crumb { display: flex; gap: 8px; font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin-bottom: 24px; }
.res-crumb a { color: inherit; text-decoration: none; }
.res-crumb-current { color: var(--accent); }
.res-title { font-family: var(--font-serif); font-size: 64px; font-weight: 500; line-height: 1.05; letter-spacing: -0.025em; margin: 0 0 24px; max-width: 1100px; }
.res-meta { display: flex; gap: 32px; font-family: var(--font-mono); font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); }
.article-body figure.photo { margin: 0 0 28px; }
.res-checklist { list-style: none; padding: 0; margin: 0 0 32px; }
.res-checklist li { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--rule); font-size: 17px; line-height: 1.55; }
.res-check { width: 22px; height: 22px; border: 1.5px solid var(--ink); border-radius: 4px; flex: none; margin-top: 3px; }
.res-checklist-note { display: block; font-size: 15px; color: var(--muted); margin-top: 4px; }
.res-pdf { margin: 0 0 32px; }
.res-start-h { font-family: var(--font-mono); font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--accent); margin: 0 0 24px; font-weight: 500; }
.res-library { background: var(--bg2); border-top: 1px solid var(--rule); }
.res-library-head { display: grid; grid-template-columns: 1fr 2fr; gap: 64px; margin-bottom: 56px; }
.res-library-h { font-family: var(--font-serif); font-size: 44px; font-weight: 500; line-height: 1.1; letter-spacing: -0.02em; margin: 0; max-width: 720px; }
```

- [ ] **Step 3: Single templates and the shared single part**

`single-guide.php`, `single-checklist.php`, `single-explainer.php` are identical:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/resource/single' );
}
get_footer();
```

`template-parts/resource/single.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$content  = apply_filters( 'the_content', get_the_content() );
$show_toc = w270_field( 'show_toc' );
$show_toc = null === $show_toc ? true : (bool) $show_toc;
$toc      = ( 'guide' === get_post_type() && $show_toc ) ? w270_toc( $content ) : [];
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<?php get_template_part( 'template-parts/resource/hero' ); ?>
	<section class="article-layout">
		<?php get_template_part( 'template-parts/resource/toc', null, [ 'toc' => $toc ] ); ?>
		<?php get_template_part( 'template-parts/resource/body', null, [ 'content' => $content ] ); ?>
		<?php get_template_part( 'template-parts/resource/rail' ); ?>
	</section>
</main>
```

`template-parts/resource/hero.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$topic   = w270_topic( get_the_ID() );
$seo_h1  = w270_field( 'seo_h1' );
$minutes = w270_read_time( get_the_ID() );
?>
<section class="res-header">
	<div class="res-crumb">
		<a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Resources</a>
		<?php if ( $topic ) : ?>
			<span>/</span>
			<span class="res-crumb-current"><a href="<?php echo esc_url( home_url( '/resources/#topic-' . $topic->slug ) ); ?>"><?php echo esc_html( $topic->name ); ?></a></span>
		<?php endif; ?>
	</div>
	<?php if ( $seo_h1 ) : ?>
		<h1 class="res-title"><?php echo esc_html( $seo_h1 ); ?></h1>
		<h2 class="page-hero-lead"><?php the_title(); ?></h2>
	<?php else : ?>
		<h1 class="res-title"><?php the_title(); ?></h1>
	<?php endif; ?>
	<div class="res-meta">
		<?php if ( $minutes ) : ?><span>● <?php echo (int) $minutes; ?> min read</span><?php endif; ?>
		<span>Updated <?php echo esc_html( get_the_modified_date( 'F Y' ) ); ?></span>
		<span>By 270 West Consulting</span>
	</div>
</section>
```

`template-parts/resource/toc.php` (always renders the aside so the 3-column grid holds):

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$toc = $args['toc'] ?? [];
?>
<aside class="article-toc">
	<?php if ( $toc ) : ?>
		<div class="article-toc-label">Contents</div>
		<div class="article-toc-links">
			<?php foreach ( $toc as $i => $h ) : ?>
				<a href="#<?php echo esc_attr( $h['id'] ); ?>"<?php echo 0 === $i ? ' class="active"' : ''; ?>><?php echo esc_html( $h['text'] ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</aside>
```

`template-parts/resource/body.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$summary = w270_field( 'summary' ) ?: get_the_excerpt();
?>
<article class="article-body">
	<?php if ( $summary ) : ?><p class="article-intro"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
	<?php if ( 'checklist' === get_post_type() ) { get_template_part( 'template-parts/resource/checklist-items' ); } ?>
	<?php echo $args['content']; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content ?>
	<?php if ( 'guide' === get_post_type() ) { get_template_part( 'template-parts/resource/callout' ); } ?>
</article>
```

`template-parts/resource/callout.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$c = w270_field( 'callout' );
if ( ! $c || empty( $c['text'] ) ) { return; }
?>
<div class="article-callout">
	<div class="article-callout-label"><?php echo esc_html( $c['label'] ?: 'Helpful tip' ); ?></div>
	<p class="article-callout-p"><?php echo esc_html( $c['text'] ); ?></p>
</div>
```

`template-parts/resource/checklist-items.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = w270_field( 'items' );
$pdf   = w270_field( 'download_pdf' );
if ( $items ) : ?>
	<ol class="res-checklist">
		<?php foreach ( $items as $row ) : ?>
			<li><span class="res-check" aria-hidden="true"></span><span><?php echo esc_html( $row['item'] ); ?><?php if ( ! empty( $row['note'] ) ) : ?><span class="res-checklist-note"><?php echo esc_html( $row['note'] ); ?></span><?php endif; ?></span></li>
		<?php endforeach; ?>
	</ol>
<?php endif;
if ( $pdf && ( $url = wp_get_attachment_url( (int) $pdf ) ) ) : ?>
	<a href="<?php echo esc_url( $url ); ?>" class="btn-ink res-pdf" download>Download the PDF checklist</a>
<?php endif; ?>
```

`template-parts/resource/rail.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<aside class="article-rail">
	<div class="article-rail-cta">
		<div class="article-rail-label">Need help?</div>
		<h3 class="article-rail-h">Talk to an advisor — free.</h3>
		<p class="article-rail-p">Two-minute eligibility check. No pressure, no obligation.</p>
		<a href="<?php echo esc_url( home_url( '/eligibility/' ) ); ?>" class="article-rail-btn">Check eligibility →</a>
	</div>
	<?php get_template_part( 'template-parts/resource/related' ); ?>
</aside>
```

`template-parts/resource/related.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$related = w270_related( get_the_ID() );
if ( ! $related ) { return; }
?>
<div>
	<div class="article-related-label">Related guides</div>
	<div class="article-related-links">
		<?php foreach ( $related as $p ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?> →</a>
		<?php endforeach; ?>
	</div>
</div>
```

- [ ] **Step 4: Resources landing template and its parts**

`template-resources.php`:

```php
<?php
/**
 * Template Name: Resources landing
 * Lists Guides, Checklists and Explainers: "Start here" (featured) + Library grouped by Topic.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
the_post();
$hero_h1  = w270_field( 'hero_h1' ) ?: get_the_title();
$hero_lead = w270_field( 'hero_lead' ) ?: '';
$hero_sub  = w270_field( 'hero_sub' ) ?: get_the_excerpt();
$types     = w270_resource_types();
$featured  = get_posts( [ 'post_type' => $types, 'numberposts' => 3, 'meta_key' => 'featured', 'meta_value' => '1', 'orderby' => [ 'menu_order' => 'ASC', 'date' => 'DESC' ] ] );
if ( ! $featured ) { $featured = get_posts( [ 'post_type' => $types, 'numberposts' => 3, 'orderby' => 'date', 'order' => 'DESC' ] ); }
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Resources</div>
		<h1 class="page-hero-h1"><?php echo esc_html( $hero_h1 ); ?></h1>
		<?php if ( $hero_lead ) : ?><p class="page-hero-lead"><?php echo esc_html( $hero_lead ); ?></p><?php endif; ?>
		<?php if ( $hero_sub ) : ?><p class="page-hero-sub"><?php echo esc_html( $hero_sub ); ?></p><?php endif; ?>
	</section>
	<?php if ( $featured ) : ?>
	<section class="section-pad">
		<h2 class="res-start-h">Start here</h2>
		<div class="resources-featured">
			<?php foreach ( $featured as $i => $p ) { get_template_part( 'template-parts/resource/card', null, [ 'post' => $p, 'main' => 0 === $i ] ); } ?>
		</div>
	</section>
	<?php endif; ?>
	<?php get_template_part( 'template-parts/resource/library', null, [ 'exclude' => wp_list_pluck( $featured, 'ID' ) ] ); ?>
</main>
<?php
get_footer();
```

`template-parts/resource/card.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$p       = $args['post'];
$main    = ! empty( $args['main'] );
$type    = get_post_type( $p );
$minutes = w270_read_time( $p->ID );
$cls     = $main ? 'resource-card-main' : 'resource-card-sm';
$tag     = $main
	? w270_type_label( $type, true ) . ( $minutes ? " · {$minutes} min read" : '' )
	: w270_type_label( $type ) . ( $minutes ? " · {$minutes} min" : '' );
?>
<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="<?php echo esc_attr( $cls ); ?>">
	<?php if ( has_post_thumbnail( $p ) ) : ?>
		<div class="photo" style="aspect-ratio:16/10"><?php echo get_the_post_thumbnail( $p, 'large' ); ?></div>
	<?php endif; ?>
	<div class="<?php echo esc_attr( $cls ); ?>-body">
		<?php if ( $main ) : ?>
			<div class="resource-card-main-tag"><?php echo esc_html( $tag ); ?></div>
			<h3 class="resource-card-main-h"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
			<div class="resource-card-main-link">Read<?php echo 'guide' === $type ? ' guide' : ''; ?> →</div>
		<?php else : ?>
			<div>
				<div class="resource-card-sm-tag"><?php echo esc_html( $tag ); ?></div>
				<h3 class="resource-card-sm-h"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
			</div>
			<div class="resource-card-sm-link">Read →</div>
		<?php endif; ?>
	</div>
</a>
```

`template-parts/resource/library.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$exclude = array_map( 'intval', $args['exclude'] ?? [] );
$types   = w270_resource_types();
$groups  = [];
$terms   = get_terms( [ 'taxonomy' => 'resource_topic', 'hide_empty' => true, 'orderby' => 'id', 'order' => 'ASC' ] );
foreach ( is_wp_error( $terms ) ? [] : $terms as $term ) {
	$posts = get_posts( [
		'post_type' => $types, 'numberposts' => -1, 'post__not_in' => $exclude,
		'tax_query' => [ [ 'taxonomy' => 'resource_topic', 'terms' => $term->term_id ] ],
		'orderby' => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	] );
	if ( $posts ) { $groups[] = [ 'slug' => $term->slug, 'name' => $term->name, 'posts' => $posts ]; }
}
$orphans = get_posts( [
	'post_type' => $types, 'numberposts' => -1, 'post__not_in' => $exclude, 'orderby' => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	'tax_query' => [ [ 'taxonomy' => 'resource_topic', 'operator' => 'NOT EXISTS' ] ],
] );
if ( $orphans ) { $groups[] = [ 'slug' => 'other', 'name' => 'Other', 'posts' => $orphans ]; }
if ( ! $groups ) { return; }
?>
<section class="section-pad res-library">
	<div class="res-library-head">
		<div class="mono-label">Library</div>
		<h2 class="res-library-h">Browse VAC resources by topic</h2>
	</div>
	<?php foreach ( $groups as $g ) : ?>
		<div class="library-topic" id="topic-<?php echo esc_attr( $g['slug'] ); ?>">
			<div class="library-topic-grid">
				<h3 class="library-topic-h"><?php echo esc_html( $g['name'] ); ?></h3>
				<div class="library-items">
					<?php foreach ( $g['posts'] as $p ) : ?>
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="library-item"><?php echo esc_html( get_the_title( $p ) ); ?><span class="library-item-arrow">→</span></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</section>
```

- [ ] **Step 5: Deploy, re-run the resources import (assigns the template now that it exists) and check the pages**

```bash
wordpress/build/deploy-theme.sh
"$PHP" -c "$INI" wordpress/build/import.php --resources | tail -3
for p in /resources/ /resources/guides/vac-benefits-programs-guide/ /resources/checklists/vac-application-checklist/ /resources/explainers/how-vac-disability-ratings-work/ /resources/vac-benefits-programs-guide/; do printf '%-60s %s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' http://270-west.local$p)" "$(curl -s -o /dev/null -w '%{redirect_url}' http://270-west.local$p)"; done
curl -s http://270-west.local/resources/ | grep -c 'library-item'
curl -s http://270-west.local/resources/guides/vac-benefits-programs-guide/ | grep -o '<h1[^>]*>[^<]*\|article-toc-links\|article-callout\|Related guides' | sort | uniq -c
```
Expected: the four live paths `200`; the old path `301` with the new guide URL as redirect target; 12 library items; the guide shows its H1, TOC links, callout and Related guides.

- [ ] **Step 6: Run the full checks**

```bash
"$PHP" -c "$INI" wordpress/build/render-check.php | grep -vc "^OK"; "$PHP" -c "$INI" wordpress/build/render-check.php | tail -4
cd wordpress/build && python3 check-coverage.py; echo "exit $?"; cd -
```
Expected: render check has no FAIL lines and reports `OK acf field groups: 4` (or the WARN if ACF Pro isn't installed yet); coverage prints `OK` for all 16 pages plus `OK /resources/guides/vac-benefits-programs-guide/`. For a `DIFF` on `/resources/`, compare the printed word diff with the seed JSON (titles, read times, tag wording "· 5 min" vs "· 18 min read") and fix the seed or template. Note the hero copy comes from ACF; without ACF Pro the page title is used and `/resources/` will DIFF until ACF Pro is installed.

- [ ] **Step 7: Commit**

```bash
git add wordpress/theme/270west
git commit -m "Theme templates for Guides, Checklists, Explainers and the Resources landing page"
```

---

### Task 5: Visual pass and mobile check

- [ ] **Step 1: Deploy the prototype for diffing and run the layout diff in the browser**

```bash
wordpress/build/deploy-proto.sh
```
Open `http://270-west.local/resources/` in the in-app browser, run the contents of `wordpress/build/layout-diff.js` with `javascript_tool`, then:

```js
await w270diff([['/resources/', 'resources.html'], ['/resources/guides/vac-benefits-programs-guide/', 'article.html']], 1440)
await w270diff([['/resources/', 'resources.html'], ['/resources/guides/vac-benefits-programs-guide/', 'article.html']], 390)
```
Expected: `/resources/` reports no diffs except `extraInWp` rows for the new `res-*` classes (the prototype styled those elements inline, so they have no class there). The guide page shows `extraInWp` for `res-header`, `res-crumb`, `res-title`, `res-meta`, and `figure.photo` rows; all shared classes (`article-layout`, `article-toc*`, `article-body`, `article-h2`, `article-p`, `article-callout*`, `article-rail*`) must match in box size. Fix any height/width mismatch in `resources.css`.

- [ ] **Step 2: Screenshot both pages at desktop and mobile widths and compare with the prototype; fix in `resources.css` only.**

- [ ] **Step 3: Commit**

```bash
git add wordpress/theme/270west/assets/css/resources.css
git commit -m "Resource templates match the prototype at desktop and mobile widths"
```

---

### Task 6: Deploy to SiteGround, docs, memory

**Files:**
- Modify: `wordpress/README.md`
- Modify: memory `project_wordpress_build.md`

- [ ] **Step 1: Deploy**

```bash
W270_SG_CLONE=/private/tmp/claude-502/-Users-Bryce-270-west-redesign--claude-worktrees-distracted-sutherland-766c39/e06b678a-1a35-47b3-a7b7-c4df215da0f8/scratchpad/siteground-public_html wordpress/build/deploy-siteground.sh 2>&1 | grep -vE "^(media|page) .*: (exists|#)" | tail -40
cd wordpress/build && W270_URL=https://brycec57.sg-host.com python3 check-coverage.py | grep -v "^OK"; cd -
for p in /resources/ /resources/guides/vac-benefits-programs-guide/ /resources/checklists/vac-application-checklist/; do printf '%-60s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' https://brycec57.sg-host.com$p)"; done
```
Expected: `plugin 270west-content: activated`, 15 resource lines, `SITEGROUND DEPLOY OK`; coverage prints nothing but OK lines; all three paths 200. If SiteGround's cache serves a stale `/resources/`, purge it in Site Tools and re-check.

- [ ] **Step 2: README** — add a section "Resources (custom post types)" to `wordpress/README.md`:

```markdown
## Resources (custom post types)

`wordpress/plugins/270west-content` registers Guides, Checklists and Explainers (`/resources/guides/…`,
`/resources/checklists/…`, `/resources/explainers/…`), the Topic taxonomy and the ACF field groups
(`acf-json/`, loaded automatically; edits in the ACF UI write back to those files). ACF Pro is installed by
hand in wp-admin on each site; without it the post types still work and field-driven blocks are hidden.

The theme renders them: `single-{guide,checklist,explainer}.php`, `template-resources.php` (assigned to the
Resources page) and `template-parts/resource/*`. `import.php --resources` seeds the topics and the 15
prototype resources (`seed-resources.json`; the featured guide's body comes from `article.html` via
`seed_article.py`), assigns the template and retires the old article page (its URL redirects).
Re-running `--resources` overwrites the seeded posts by slug.
```

- [ ] **Step 3: Memory** — append to `project_wordpress_build.md` a bullet on the resource CPTs (plugin location, seeds overwrite by slug, ACF Pro installed by the user), and update the `MEMORY.md` hook line.

- [ ] **Step 4: Commit and push; report the PR is updated**

```bash
git add wordpress/README.md && git commit -m "Document resource post types" && git push
```

---

## Self-review

- **Spec coverage:** plugin + taxonomy + Local JSON (T1); four field groups incl. landing hero (T1); seeds as published with images, related, checklist items, callout (T2/T3); template assignment, old page retired, redirect (T3/T4); singles with hero/TOC/body/callout/items/rail/related and landing with Start here + Library excluding featured, orphan "Other" group (T4); graceful without ACF (helpers return null, fallbacks in T4); deploy scripts (T1), render/coverage checks (T3), visual diff (T5), SiteGround (T6), README/memory (T6).
- **Placeholders:** none; Task 5 Step 2 is a judgement step with a defined fix location.
- **Consistency:** field keys `field_270w_*` match between JSON and importer; helper names `w270_field`, `w270_read_time`, `w270_topic`, `w270_related`, `w270_toc`, `w270_type_label`, `w270_resource_types` match across `inc/resources.php` and the templates; markers `__W270_MEDIA__:` match `seed_article.py`, `import.php` and `render-check.php`; `template-resources.php` name matches the ACF location rule and the importer.
