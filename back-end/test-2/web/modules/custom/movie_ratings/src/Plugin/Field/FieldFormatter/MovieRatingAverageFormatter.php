<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Displays the average rating as a star graphic with the value and vote count.
 *
 * Overrides view() rather than viewElements() so the "no ratings yet" state is
 * still rendered for a movie nobody has voted on, where the field is empty and
 * viewElements() would never be called.
 *
 * @FieldFormatter(
 *   id = "movie_rating_average",
 *   label = @Translation("Average rating stars"),
 *   field_types = {"movie_rating_average"}
 * )
 */
final class MovieRatingAverageFormatter extends FormatterBase {

  /**
   * The star scale assumed when a bundle has no voting field to read it from.
   */
  private const DEFAULT_MAX_STARS = 5;

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $entity = $items->getEntity();
    $item = $items->first();

    $votes = $item !== NULL ? (int) $item->votes : 0;
    $average = $votes > 0 ? (float) $item->average : NULL;
    $max_stars = $this->maxStars($entity);

    return [
      '#theme' => 'movie_rating_average',
      '#average' => $average,
      '#average_display' => $average !== NULL ? number_format($average, 1) : '',
      '#votes' => $votes,
      '#max_stars' => $max_stars,
      '#percent' => $average !== NULL ? round($average / $max_stars * 100, 2) : 0,
      '#nid' => (int) $entity->id(),
      '#attached' => ['library' => ['movie_ratings/star_rating']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Rendering is handled entirely in view(); there is no per-item output.
    return [];
  }

  /**
   * Resolves the star scale the average should be presented against.
   *
   * The scale is read from the voting field on the same entity rather than
   * configured twice, so "out of N" can never disagree with the number of stars
   * visitors actually get to choose from.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being displayed.
   *
   * @return int
   *   The highest rating a visitor can give.
   */
  private function maxStars(FieldableEntityInterface $entity): int {
    foreach ($entity->getFieldDefinitions() as $definition) {
      if ($definition->getType() === 'movie_rating') {
        $max_stars = (int) $definition->getSetting('max_stars');
        if ($max_stars > 1) {
          return $max_stars;
        }
      }
    }
    return self::DEFAULT_MAX_STARS;
  }

}
