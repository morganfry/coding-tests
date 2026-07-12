# Movie Ratings

## INTRODUCTION

The Movie Ratings module lets site visitors rate movies from 1 to 5 stars. A **Movie
ratings block** shows the movie's average rating as stars and offers the rating form
directly beneath it. Each vote is stored as a `movie_rating` **content entity** together
with the submitter's IP address, so the complete voting history is retained and is exposed
to Views.

How it fits together:

- **Movie ratings block** — the rating UI. It reads the movie from the current route,
  renders the average rating (stars plus the number and vote count, e.g.
  ★★★★☆ 4.2 out of 5 (12 votes)) and, for visitors with the *Submit movie ratings*
  permission, the rating form (a select plus a **Rate** button). The star scale is a block
  setting, so it is configured where the block is placed rather than hard-coded.
- **`movie_rating` content entity** — one entity per vote, with base fields: the rated
  movie (entity reference to a node), the rating value, the IP address, the submitting
  user, and a timestamp. Its database table is created automatically when the module is
  installed (no `hook_schema`). Submitting the form creates one of these through the
  `movie_ratings.rating_manager` service, which stamps the current request's IP address.
- **`movie_rating_average` field type** — a field on the movie content type holding the
  movie's **average rating and vote count**. Nobody types this value in: the module
  recalculates it from the `movie_rating` entities whenever a rating is created, changed
  or deleted. It exists so the average is a **real column on the node**, which is what
  makes it usable in Views as a field, filter and sort — an average computed on the fly
  could be displayed but never filtered on, and items like "movies filtered by star
  rating" and "top 5 highest rated" depend on filtering and sorting it.
- **Star CSS/JS** — progressive enhancement turns the rating `<select>` into clickable
  stars. The select stays the source of truth, so rating still works with JavaScript
  disabled. Submits via AJAX (with a no-JS fallback), which also refreshes the average in
  place.

## REQUIREMENTS

Drupal core only.

## INSTALLATION

Install and enable as a typical custom module:

    ddev drush en movie_ratings -y
    ddev drush cr

## CONFIGURATION

The exported site configuration already has all of this in place. To set it up from scratch
on another content type:

1. At *Structure → Content types → … → Manage fields → Add field*, add a **Movie rating
   average** field (label it e.g. "Average rating"). This is where the calculated average
   is stored.
2. On **Manage form display**, set that field to **Disabled** — it is calculated, never
   entered by an editor.
3. On **Manage display**, set it to **Disabled** too. The block renders the average; leaving
   the field on the display as well would show it twice.
4. At *Structure → Block layout*, place the **Movie ratings** block in the **Content**
   region. In its settings choose *Maximum stars* (5), and under *Visibility → Content
   types* tick the movie content type so the block only appears on movie pages.
5. At *People → Permissions*, grant **Submit movie ratings** to the roles allowed to vote
   (anonymous and authenticated, to let any visitor rate).

The block owns the star scale, and passes it to the average display, so the number of stars
a visitor can pick can never disagree with the "out of N" shown alongside the average.

Export any configuration changes with `ddev drush cex`.

### Flood control (bot protection)

The rating form is public, so submissions are **rate limited per IP address** using Drupal
core's flood service. `hasRated()` stops a visitor voting twice on the *same* movie,
the flood limit caps how many ratings a single visitor can submit overall.

Configure it at *Configuration → Content authoring → Movie ratings*
(`/admin/config/content/movie-ratings`), or in `movie_ratings.settings`:

    flood:
      limit: 10        # ratings allowed per visitor…
      interval: 3600   # …per this many seconds

Notes:

- **Every submission counts**, including one turned away because the visitor had already rated
  that movie — a bot hammering the form is throttled whether or not its votes land.
- Users with the **Administer movie ratings** permission are not limited.
- The limit is keyed on the client IP, so the reverse-proxy settings matter (see below): behind
  DDEV's router without them, every visitor would look like the same IP and share one allowance.
- Expired flood entries are cleared by cron.

### Viewing ratings in Views

Because ratings are a content entity, they appear in Views as the **Movie rating** base
table. The `movie` reference provides a relationship to the movie node (and from there to
its category, actors and directors), and `rating`, `created`, `ip_address` and the author
are available as fields, filters and sorts.

The average field adds **Average rating** and its vote count to the ordinary **Content**
base table as a field, filter and sort — so a movie listing filtered or ordered by star
rating is built entirely in the Views UI, with no extra code. Build a listing at *Structure
→ Views* and export it with `ddev drush cex`.

### The left sidebar: "Most Popular" and "Top Rated"

The two top-5 lists are **Views blocks**, not module code — they are possible only because the
average and vote count are real columns on the node (see the field type above):

- `views.view.ratings_blocks` → `block_1` **Most Popular**, sorted by vote count descending
- `views.view.ratings_blocks` → `block_2` **Top Rated**, sorted by average rating descending

Both are capped at 5 rows and placed in the **Sidebar** region.

#### Why there is a custom theme

Olivero ships a **single** sidebar region and positions it to the **right** of the content
(`core/themes/olivero/css/layout/layout-sidebar.css` puts the content at grid column `3 / 11`
and the sidebar at `12 / 15`). There is no "left sidebar" region to choose in Block layout, so
the requirement for a *left* sidebar is met with a small Olivero subtheme:

    web/themes/custom/movies_theme/
    ├── movies_theme.info.yml          # base theme: olivero
    ├── movies_theme.libraries.yml
    └── css/layout/sidebar-left.css    # mirrors the grid: sidebar 3/6, content 7/15

The stylesheet overrides only the wide breakpoint (below it Olivero stacks both regions full
width, where "left" has no meaning). It also pins both regions to `grid-row: 1`, because the
sidebar markup comes *after* `<main>` in the page template — without that, CSS grid
auto-placement drops a left-hand sidebar onto a second row instead of seating it beside the
content.

### Notes

- The submitter IP is read from the incoming request (`Request::getClientIp()`). Behind a
  reverse proxy (including DDEV's router) configure Drupal's trusted reverse-proxy
  settings (`$settings['reverse_proxy']`) so the real client IP is recorded rather than
  the proxy's.
- A visitor may rate a given movie once — matched by user account when logged in, and by IP
  address when anonymous.
- Recording a vote re-saves the movie node to store the new average. That save deliberately
  creates **no new revision** and leaves the node's *changed* timestamp alone, so voting
  never looks like an editorial change or reorders "recently updated" listings.
