<?php

declare(strict_types=1);

namespace Drupal\movie_ratings;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Records movie ratings and maintains the cached average on the movie.
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

  /**
   * Calculates a movie's average rating from its rating entities.
   *
   * @param int $nid
   *   The movie node ID.
   *
   * @return array
   *   An array with two keys: 'average', the mean rating as a float or NULL
   *   when the movie has no ratings, and 'votes', the number of ratings.
   */
  public function getAverage(int $nid): array;

  /**
   * Recalculates and stores a movie's average rating.
   *
   * The average is a cache of the rating entities, so it is recomputed from
   * them in full rather than adjusted incrementally, and cannot drift. Does
   * nothing if the node carries no average rating field.
   *
   * @param int $nid
   *   The movie node ID.
   */
  public function updateAverage(int $nid): void;

  /**
   * Recalculates the stored average of every movie that has been rated.
   *
   * Used to backfill averages for ratings submitted before the average rating
   * field was added to the content type.
   */
  public function updateAllAverages(): void;

  /**
   * Determines whether an entity is mid-save purely to refresh its average.
   *
   * Lets hook implementations tell a visitor's vote apart from an editor
   * changing the content.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being saved.
   *
   * @return bool
   *   TRUE if this save was triggered by updateAverage(), FALSE otherwise.
   */
  public function isAverageResave(FieldableEntityInterface $entity): bool;

  /**
   * Finds the entity's field that stores an average rating, if it has one.
   *
   * The field is matched on the field type this module defines, not on a field
   * machine name, so a site builder can name the field whatever they like and
   * add it to any content type without touching this code.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to inspect.
   *
   * @return string|null
   *   The machine name of the average rating field, or NULL if the entity has
   *   none.
   */
  public function getAverageFieldName(FieldableEntityInterface $entity): ?string;

}
