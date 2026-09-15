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
    ('contact.html', 'contact', None),
    ('faq.html', 'faq', None),
    ('eligibility.html', 'eligibility', None),
    ('consult.html', 'book-a-consult', None),
    ('privacy.html', 'privacy', None),
    ('terms.html', 'terms', None),
    ('accessibility.html', 'accessibility', None),
]

TITLE_SUFFIX = ' — 270 West Consulting'


def path_for(slug):
    """Site-relative path for a slug, e.g. 'claims' -> '/services/claims/'."""
    if slug == 'home':
        return '/'
    parents = {s: p for _, s, p in PAGES}
    parts = []
    while slug:
        parts.append(slug)
        slug = parents[slug]
    return '/' + '/'.join(reversed(parts)) + '/'


# prototype file -> site path
LINK_MAP = {src: path_for(slug) for src, slug, _ in PAGES}
# The article became the featured Guide post (seeded by import.php --resources); prototype links still point at it.
LINK_MAP['article.html'] = '/resources/guides/vac-benefits-programs-guide/'
