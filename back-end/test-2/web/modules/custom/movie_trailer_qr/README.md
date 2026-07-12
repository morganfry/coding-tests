# Movie Trailer QR

## INTRODUCTION

The Movie Trailer QR module shows a scannable QR code on each movie page that takes the
visitor to the movie's trailer on YouTube, under the caption "Scan the QR code to watch the
trailer of this movie."

How it fits together:

- **`field_trailer`** — an ordinary core **link** field on the Movie content type holding the
  YouTube URL. Nothing about the trailer is stored by this module itself.
- **Trailer QR code formatter** — a display format for *any* link field. It renders the
  field's URL as a QR code with a caption. Its size, caption text and whether the code is
  also a clickable link are all display settings, so nothing is hard-coded.
- **`movie_trailer_qr.qr_code_generator`** — the service that produces the code, using
  [endroid/qr-code](https://github.com/endroid/qr-code). The library sits behind
  `QrCodeGeneratorInterface`, so it is used in exactly one class and could be swapped without
  touching the formatter.

The QR code is generated **on the server** and embedded in the page as an SVG data URI. That
means displaying it makes **no request to any external service** (no QR web API, so no movie
URL is handed to a third party), it needs no image library, it stays sharp at any size, and it
works with JavaScript disabled.

## REQUIREMENTS

- Drupal core's **Link** module (enabled automatically as a dependency).
- **endroid/qr-code ^5.1**, installed by Composer. It is already in the project's
  `composer.json` / `composer.lock`, so `ddev composer install` brings it in.

Note the version constraint is deliberate: endroid **6.x requires PHP 8.4**, and this project
runs PHP 8.2.

## INSTALLATION

    ddev composer install
    ddev drush en movie_trailer_qr -y
    ddev drush cr

## NOTES

- The field accepts any external URL, not only YouTube ones. No YouTube-specific validation is
  applied: `youtube.com/watch`, `youtu.be` short links and embed URLs are all legitimate, and a
  naive pattern check would reject some of them.
- The code is regenerated whenever the movie is rendered uncached. It is small and Drupal's
  render cache absorbs it, so no caching of our own is warranted; changing the trailer URL
  shows a new code on the next page load.
- The QR code is also wrapped in a link to the trailer by default, since a visitor already at a
  desktop cannot scan their own screen.
