"""Page table: (source file, slug, parent slug). Order matters: parents before children."""
PAGES = [
    ('index.html', 'home', None),
    ('services.html', 'services', None),
    ('service-claims.html', 'claims', 'services'),
    ('service-appeals.html', 'appeals', 'services'),
    ('service-reassessment.html', 'reassessment', 'services'),
    ('service-support.html', 'support', 'services'),
    ('how-it-works.html', 'how-it-works', None),
    ('about.html', 'about', None),
    ('resources.html', 'resources', None),
    ('stories.html', 'stories', 'resources'),
    ('guides.html', 'guides', 'resources'),
    ('news.html', 'news', 'resources'),
    ('article.html', 'vac-benefits-programs-guide', 'resources'),
    ('contact.html', 'contact', None),
    ('faq.html', 'faq', None),
    ('vac-status-checker.html', 'vac-status-checker', None),
    ('consult.html', 'book-a-consult', None),
    ('privacy.html', 'privacy', None),
    ('terms.html', 'terms', None),
    ('accessibility.html', 'accessibility', None),
]

# Advertising landing pages. They carry their own header and footer instead of the site nav,
# are noindex, and are deliberately not linked from any menu — traffic arrives from paid ads.
# (source file, slug)
LANDING_PAGES = [
    ('lp-claims.html', 'vac-claim-help'),
    ('lp-awareness.html', 'vac-benefits-simplified'),
    ('lp-what-to-expect.html', 'what-to-expect'),
]

TITLE_SUFFIX = ' — 270 West Consulting'


def path_for(slug):
    """Site-relative path for a slug, e.g. 'claims' -> '/services/claims/'."""
    if slug == 'home':
        return '/'
    parents = {s: p for _, s, p in PAGES}
    parents.update({s: None for _, s in LANDING_PAGES})
    parts = []
    while slug:
        parts.append(slug)
        slug = parents[slug]
    return '/' + '/'.join(reversed(parts)) + '/'


# Prototype files that aren't pages of their own in WordPress.
# story.html is the sample veteran story: it becomes a `story` post once that post type
# exists, so for now its links point at the Stories archive.
EXTRA_LINKS = {
    'story.html': '/resources/stories/',
}

# prototype file -> site path
LINK_MAP = {src: path_for(slug) for src, slug, _ in PAGES}
# The article became the featured Guide post (seeded by import.php --resources); prototype links still point at it.
LINK_MAP['article.html'] = '/resources/guides/vac-benefits-programs-guide/'
LINK_MAP.update(EXTRA_LINKS)
