# Movie Migrate

## INTRODUCTION

The Movie Migrate module imports the demo catalogue — category terms, directors, actors,
movies and 100 ratings — as a single **migration group** called `movies`. Site
configuration (content types, fields, views, blocks) is exported to the config sync
directory, but *content* is not, so this module is what gives a freshly installed site
something to show.

The five migrations, in dependency order:

| Migration ID | Creates | Rows |
|---|---|---|
| `movie_categories` | `categories` taxonomy terms | 8 |
| `movie_directors` | Director nodes | 4 |
| `movie_actors` | Actor nodes | 8 |
| `movie_movies` | Movie nodes | 10 |
| `movie_ratings_data` | `movie_rating` entities | 100 |

`movie_movies` uses the `migration_lookup` process plugin to turn source references into
the real term and node IDs created by the earlier migrations, and `movie_ratings_data`
does the same to attach each rating to its movie. Declared `migration_dependencies` mean
the group runs in the right order on its own.

### Where the data lives

The rows are **JSON files in `data/`**, one per migration, read through migrate_plus's `url`
source (`data_fetcher_plugin: file`, `data_parser_plugin: json`). The YAML files describe only
the mapping; the content itself is data, and stays out of configuration:

    data/categories.json    8 terms
    data/directors.json     4 directors
    data/actors.json        8 actors
    data/movies.json       10 movies
    data/ratings.json     100 ratings

The migrations refer to these files **relative to the module** (`data/movies.json`).
`movie_migrate_migration_plugins_alter()` rewrites those into absolute paths at runtime,
because migrate_plus's file fetcher reads the source with a plain `file_get_contents()` — a
relative path would be resolved against the working directory, which differs between a Drush
run, a web request and cron, so the import would work in one context and fail in another.

## REQUIREMENTS

- `migrate` (core)
- `migrate_plus` — provides the migration **group**
- `migrate_tools` — provides the Drush commands below
- `movie_ratings` — supplies the `movie_rating` entity type the ratings migration writes to

Both contrib modules are in the project's `composer.json`, so `ddev composer install`
brings them in.

## USAGE

    ddev drush en movie_migrate -y
    ddev drush migrate:import --group=movies

Check what is registered and what has run:

    ddev drush migrate:status --group=movies

Undo everything the group created — content only; the migrations themselves stay:

    ddev drush migrate:rollback --group=movies

The import is idempotent: running it twice does not duplicate anything, because each row is
tracked in the migration map by its source ID.

## NOTES

- **Averages are not migrated, they are derived.** Creating each `movie_rating` entity fires
  `hook_ENTITY_TYPE_insert()` in `movie_ratings`, which recalculates the host movie's average
  and vote count. So the 100 imported ratings produce exactly the same averages a visitor
  voting 100 times would, and the "Most Popular" and "Top Rated" sidebar blocks are populated
  as a side effect. It also means the import does 100 node saves and is not instant.
- The ratings are attributed to anonymous (`uid: 0`) and carry the IP addresses from the
  documentation range `203.0.113.0/24` (RFC 5737), one per vote — so each looks like a
  separate visitor and the module's one-vote-per-visitor rule is not tripped.
- The migration writes `movie_rating` entities directly, bypassing `RatingForm`. The
  form's duplicate-vote check (`RatingManager::hasRated()`) therefore does not apply here;
  uniqueness is the source data's responsibility.
- Trailer URLs are plausible YouTube links for fictional films. They encode into valid QR
  codes but are not real trailers.

## MAINTAINERS

- Morgan Fry
