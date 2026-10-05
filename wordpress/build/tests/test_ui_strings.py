import os, re, unittest

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..'))


class UiStringsTests(unittest.TestCase):
    """main.js's English fallbacks and the theme's translatable wording block must stay in step."""

    def test_every_widget_string_is_in_the_theme_block_with_the_same_english(self):
        js = open(os.path.join(ROOT, 'js', 'main.js'), encoding='utf-8').read()
        php = open(os.path.join(ROOT, 'wordpress', 'theme', '270west', 'inc', 'checker.php'), encoding='utf-8').read()
        block = php[php.index('function w270_ui_strings'):php.index('add_action', php.index('function w270_ui_strings'))]
        theme = {k: v for k, v in re.findall(r'"([a-z0-9._]+)" => "((?:[^"\\]|\\.)*)"', block)}
        wanted = {k: v.replace("\\'", "'") for k, v in re.findall(r"t\('([a-z0-9._]+)',\s*'((?:[^'\\]|\\.)*)'", js)}
        for qid, q, opts in re.findall(r"\{ id:'(\w+)', q:'([^']*)', options:\[([^\]]*)\] \}", js):
            wanted[f'quiz.{qid}.q'] = q
            for i, o in enumerate(re.findall(r"'([^']*)'", opts)):
                wanted[f'quiz.{qid}.{i}'] = o
        wanted.pop('quiz.consent')  # comes from the Gravity Form's consent field
        self.assertIn('"quiz.consent" => w270_checker_consent_text()', block)
        self.assertEqual(sorted(wanted), sorted(theme))
        for k, v in wanted.items():
            self.assertEqual(theme[k], v, k)
