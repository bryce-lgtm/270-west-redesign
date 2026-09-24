# Unified Resource post type — design

**Date:** 2026-09-24
**Status:** Implemented 2026-09-24 (plan `2026-09-24-unified-resource-post-type.md`, Tasks 1–11) and verified on SiteGround: one `resource` type, 5 sub-types, 3 taxonomies, all 22 permalinks preserved, the three archives template-driven.
**Supersedes parts of:** `2026-09-15-resource-post-types-design.md`

## Problem

Three separate post types (`guide`, `checklist`, `explainer`) each take a slot in the
wp-admin menu, which is more menu than the content justifies. Stories and News have no post
type at all — they exist only as static Elementor pages generated from the prototype, so the
team cannot add or edit them. And the resource importer re-seeds on every deploy, so anything
edited in wp-admin is overwritten the next time the site is built.

## Decisions taken

1. **One post type with sub-types.** A single `resource` type; Guide / Checklist / Explainer /
   Story / News become terms on a `resource_type` taxonomy used for labelling and filtering.
2. **Archives list what is published.** Publishing a Story makes it appear on
   `/resources/stories/` without anyone editing a page.
3. **Content is admin-owned.** The importer seeds once and then never touches those posts.
   Edits, additions and deletions in wp-admin always win.

Structure stays code-owned: field definitions, sub-types, templates and the archive layout are
changed in the repo, not in wp-admin.

## Data model

Post type `resource`, label "Resources", `show_in_rest` on, supports title/editor/excerpt/
thumbnail/revisions.

| Taxonomy | Label | Terms | Applies to |
|---|---|---|---|
| `resource_type` | Types | Guide, Checklist, Explainer, Story, News | all |
| `resource_topic` | Topics | VAC eligibility & programs, Filing your first VAC claim, After a VAC decision | guides/checklists/explainers |
| `news_category` | News categories | Campaign, Community, Sponsorship, New guide, Team | News |

`news_category` is separate rather than folded into Topics: the News filter chips use a
different vocabulary, and merging them would put Campaign and Community into the topic filter
on the guides archive.

`resource_type` is non-hierarchical, one term per post. Term **slugs are plural**
(`guides`, `checklists`, `explainers`, `stories`, `news`) so permalinks are unchanged; the
term **names** are singular because they render as a label on each card ("Checklist", "Explainer").

## URLs

Permalink base `resources/%resource_type%/%postname%/`, giving exactly today's paths:

```
/resources/guides/<slug>/        /resources/stories/<slug>/
/resources/checklists/<slug>/    /resources/news/<slug>/
/resources/explainers/<slug>/
```

No redirects are required — every existing resource URL is preserved.

`%resource_type%` is registered as a rewrite tag and resolved in `post_type_link`. A post with
no Type term falls back to `guides` so a permalink is never malformed.

The single-post rule matches `resources/([^/]+)/([^/]+)/?$` and the archive pages sit at
`resources/([^/]+)/?$`, so the two do not collide.

## Archive pages

`/resources/stories/`, `/resources/guides/` and `/resources/news/` stay **pages**, not term
archives. `/resources/guides/` is not a guides archive: it lists all three of guides,
checklists and explainers together, filtered by Topic. Term archives cannot express that.

One page template, `template-resource-archive.php`, serves all three. Per-page ACF settings:

- **hero** — h1, lead, sub (the existing "Resources landing" group, repointed to this template
  rather than deleted)
- **types listed** — multi-select of `resource_type` terms
- **filter vocabulary** — none | Topics | News categories
- **layout** — library cards / story cards / news list

`/resources/checklists/` and `/resources/explainers/` get no archive page; those items appear
on the guides page, as they do in the prototype.

## Fields per sub-type

ACF location rules key off the Type term, so an editor sees only what is relevant.

| Sub-type | Fields |
|---|---|
| all | summary, featured, related resources, SEO H1 |
| Guide | read time, show TOC, callout (label + text) |
| Checklist | read time, checklist items (repeater), download PDF |
| Explainer | read time |
| Story | veteran name, rank/branch, pull-quote, video URL, duration, story number |
| News | category (taxonomy), optional external link; date is the native post date |

Existing groups are re-pointed rather than rebuilt: "Resource details" moves from
`post_type == guide|checklist|explainer` to `post_type == resource`, and "Guide extras" /
"Checklist extras" gain a Type term rule.

## Templates

`single-resource.php` branches on the Type term and reuses the existing
`template-parts/resource/*` partials, so guides, checklists and explainers render exactly as
they do today. New partials for Story (built from `story.html`) and News.

**News single:** no prototype exists — every "Read update →" in `news.html` points at `#`. The
single is built from the existing article prose styles (serif headline, date, category, body).
The optional external-link field lets an entry point elsewhere instead, in which case the card
links out and no single is generated.

## Migration

The 15 existing posts convert **in place**: `post_type` is updated and the matching Type term
assigned. Post IDs do not change, so ACF meta and the `related_resources` references (which
store post IDs) survive untouched.

Stories and News are seeded from the prototype as real entries — 2 stories from `stories.html`
and 5 news items from `news.html`, so the site keeps the content it shows today.

Order matters: migrate existing rows **before** the old types are unregistered, and make the
migration idempotent so a re-run is a no-op.

## Ownership and deploy changes

- The resource importer becomes seed-once: create when missing, never update or delete.
- `acf-json` is no longer `rsync --delete`d, so field groups edited in wp-admin survive; the
  groups are synced into the database so they appear in the Field Groups list rather than
  only under "Sync available".
- `stories`, `guides` and `news` leave the generated page set in `pages.py`; they become
  template-driven pages whose hero copy is editable.

## Checks

`check-coverage.py` stops comparing the three archive pages word-for-word, since their card
lists are now dynamic. It gains a lighter assertion instead: each archive renders at least the
number of items seeded, and the single templates render for one post of each sub-type.

`render-check.php` gains a permalink assertion per sub-type, so a broken rewrite fails the
deploy rather than reaching the site.

## Risks

| Risk | Handling |
|---|---|
| Rewrite rules collide or 404 after the change | `flush_rewrite_rules()` on import; render-check asserts a permalink per sub-type |
| Migration runs twice and duplicates terms | Keyed on post ID, idempotent, no-ops on second run |
| A post ends up with no Type term | Permalink falls back to `guides`; migration assigns a term to every row |
| Seed-once hides a content fix made in the repo | Accepted and explicit — after seeding, content lives in wp-admin |
| Team edits a field group, next deploy reverts it | `acf-json` no longer deleted on deploy |

## Out of scope

Redesigning the archive card layouts, adding sub-types beyond the five, and moving the
main marketing pages (home, services, about) to admin ownership — those stay code-owned.
