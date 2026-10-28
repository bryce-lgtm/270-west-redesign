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
    ('vac-status-checker.html', 'vac-status-checker', None),
    ('consult.html', 'book-a-consult', None),
    ('privacy.html', 'privacy', None),
    ('terms.html', 'terms', None),
    ('accessibility.html', 'accessibility', None),
    # Where Calendly redirects after a booking request. noindex (functions.php), not in any menu.
    ('call-request-received.html', 'call-request-received', None),
    # Reached only from the Resources page's story invitation. noindex, not in any menu.
    ('share-your-story.html', 'share-your-story', None),
    # Oct 26 copy deck: category page, brand page and Careers (under About).
    ('vac-benefits-support-options.html', 'vac-benefits-support-options', None),
    ('why-270-west.html', 'why-270-west', None),
    ('careers.html', 'careers', 'about'),
]

# Advertising landing pages. They carry their own header and footer instead of the site nav,
# are noindex, and are deliberately not linked from any menu — traffic arrives from paid ads.
# (source file, slug)
LANDING_PAGES = [
    ('lp-claims.html', 'vac-claim-help'),
    ('lp-awareness.html', 'vac-benefits-simplified'),
    ('lp-what-to-expect.html', 'what-to-expect'),
]

# Site sections switched off for now. Prototype elements marked data-feature="<name>" are left out of
# the generated pages while <name> is listed here, and elements marked data-feature-off="<name>" (the
# stand-in copy) are only included while it is. The importer passes the list to WordPress
# (option w270_hidden_features), which hides the matching menu items, rail boxes and archives.
#   library: Guides and News, held back until 270 West has reviewed them (Oct 2026).
#            Remove it from this set and redeploy to bring both sections back.
#   careers-stories:  Careers employee profiles, until real photos and approved quotes arrive.
#   careers-openings: Careers openings list and "View open roles", until there is a listing to link to
#                     (a "Contact our team" button stands in).
#   why-video:        the Why 270 West "Why experience matters" film, until the cut is final.
HIDDEN_FEATURES = {'library', 'careers-stories', 'careers-openings', 'why-video'}

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
# stories.html/guides.html/news.html are the three resource archives: template-driven pages
# created by the importer (w270_import_resources), not generated from PAGES.
EXTRA_LINKS = {
    'story.html': '/resources/stories/',
    'stories.html': '/resources/stories/',
    'guides.html': '/resources/guides/',
    'news.html': '/resources/news/',
}

# prototype file -> site path
LINK_MAP = {src: path_for(slug) for src, slug, _ in PAGES}
# article.html became the featured Guide resource (seeded once by import.php --resources), so it is
# not a page of its own; prototype links to it point at the resource.
LINK_MAP['article.html'] = '/resources/guides/vac-benefits-programs-guide/'
LINK_MAP.update(EXTRA_LINKS)
