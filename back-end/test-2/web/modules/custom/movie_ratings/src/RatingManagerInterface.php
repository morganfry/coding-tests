<?php

declare(strict_types=1);

namespace Drupal\movie_ratings;

/**
 * Records movie ratings.
 */
interface RatingManagerInterface {

  /**
   * Records a single rating vote for a movie.
   *
   * Creates a movie_rating entity capturing the rating value, the current
   * user, and the request IP address.
   *
   * @param int $nid
   *   The movie node ID being rated.
   * @param int $rating
   *   The star rating value.
   */
  public function recordRating(int $nid, int $rating): void;

  /**
   * Determines whether the current user has already rated a movie.
   *
   * Authenticated users are matched by user ID; anonymous users are matched by
   * the request IP address.
   *
   * @param int $nid
   *   The movie node ID.
   *
   * @return bool
   *   TRUE if a rating already exists for the current user, FALSE otherwise.
   */
  public function hasRated(int $nid): bool;

}
