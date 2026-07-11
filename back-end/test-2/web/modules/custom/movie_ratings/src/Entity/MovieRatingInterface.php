<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for the Movie rating entity.
 */
interface MovieRatingInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the submitted star rating value.
   *
   * @return int
   *   The rating value.
   */
  public function getRating(): int;

  /**
   * Gets the ID of the rated movie node.
   *
   * @return int|null
   *   The movie node ID, or NULL if not set.
   */
  public function getMovieId(): ?int;

  /**
   * Gets the IP address the rating was submitted from.
   *
   * @return string
   *   The IP address.
   */
  public function getIpAddress(): string;

  /**
   * Gets the time the rating was created.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTime(): int;

}
