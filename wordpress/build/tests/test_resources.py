import json
import os
import unittest

from seed_article import convert_article_body

BUILD = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


class SeedResourcesTests(unittest.TestCase):
    def setUp(self):
        with open(os.path.join(BUILD, 'seed-resources.json'), encoding='utf-8') as f:
            self.seed = json.load(f)

    def test_seed_has_22_resources_with_unique_slugs_and_valid_subtypes(self):
        res = self.seed['resources']
        self.assertEqual(len(res), 22)
        self.assertEqual(len({r['slug'] for r in res}), 22)
        valid = ('guides', 'checklists', 'explainers', 'stories', 'news')
        for r in res:
            self.assertIn(r['type'], valid, r['slug'])
            self.assertTrue(r['summary'], r['slug'])
        articles = [r for r in res if r['type'] in ('guides', 'checklists', 'explainers')]
        self.assertEqual(len(articles), 15)
        for r in articles:
            self.assertIn(r['topic'], self.seed['topics'], r['slug'])
            self.assertGreater(r['read_time'], 0)

    def test_stories_and_news_carry_their_own_fields(self):
        by_type = {}
        for r in self.seed['resources']:
            by_type.setdefault(r['type'], []).append(r)
        self.assertEqual(len(by_type['stories']), 2)
        self.assertEqual(len(by_type['news']), 5)
        for s in by_type['stories']:
            self.assertTrue(s['pull_quote'], s['slug'])
            self.assertTrue(s['veteran_name'], s['slug'])
        cats = {'Campaign', 'Community', 'Sponsorship', 'New guide', 'Team'}
        for n in by_type['news']:
            self.assertIn(n['news_category'], cats, n['slug'])
            self.assertRegex(n['date'], r'^\d{4}-\d{2}-\d{2}$')

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
