/**
 * @file
 * Progressive enhancement: turn the rating select into clickable stars.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Builds an accessible star control mirroring a rating <select>.
   */
  Drupal.behaviors.movieRatingStars = {
    attach: function (context) {
      once('movie-rating-stars', '.movie-rating-form select', context).forEach(function (select) {
        var values = [];
        Array.prototype.forEach.call(select.options, function (option) {
          if (option.value !== '') {
            values.push(option.value);
          }
        });
        if (!values.length) {
          return;
        }

        var stars = document.createElement('div');
        stars.className = 'movie-rating-stars';

        var current = select.value ? parseInt(select.value, 10) : 0;

        function paint(count) {
          Array.prototype.forEach.call(stars.children, function (button, index) {
            button.textContent = index < count ? '★' : '☆';
            button.setAttribute('aria-checked', String((index + 1) === current));
          });
        }

        values.forEach(function (value, index) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'movie-rating-stars__star';
          button.textContent = '☆';
          button.setAttribute('aria-label', Drupal.formatPlural(index + 1, '@count star', '@count stars'));
          button.addEventListener('click', function () {
            select.value = value;
            current = index + 1;
            paint(current);
          });
          button.addEventListener('mouseenter', function () {
            paint(index + 1);
          });
          stars.appendChild(button);
        });

        stars.addEventListener('mouseleave', function () {
          paint(current);
        });

        // Keep the select in sync for keyboard/SR users.
        select.addEventListener('change', function () {
          current = select.value ? parseInt(select.value, 10) : 0;
          paint(current);
        });

        select.parentNode.insertBefore(stars, select.nextSibling);
        select.classList.add('visually-hidden');
        paint(current);
      });
    }
  };
})(Drupal, once);
