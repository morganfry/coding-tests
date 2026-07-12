<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the average rating as a star graphic with the value and vote count.
 *
 * Overrides view() rather than viewElements() so the "no ratings yet" state is
 * still rendered for a movie nobody has voted on, where the field is empty and
 * viewElements() would never be called.
 *
 * The Movie ratings block renders the field through this formatter, passing its
 * own star scale in as a formatter setting, so the two always agree on the
 * scale the average is presented against.
 *
 * @FieldFormatter(
 *   id = "movie_rating_average",
 *   label = @Translation("Average rating stars"),
 *   field_types = {"movie_rating_average"}
 * )
 */
final class MovieRatingAverageFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['max_stars' => 5] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['max_stars'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum stars'),
      '#description' => $this->t('The scale the average is shown against, for example 4.2 out of 5.'),
      '#default_value' => $this->getSetting('max_stars'),
      '#min' => 2,
      '#max' => 10,
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Shown out of @max stars', ['@max' => $this->maxStars()]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $entity = $items->getEntity();
    $item = $items->first();

    $votes = $item !== NULL ? (int) $item->votes : 0;
    $average = $votes > 0 ? (float) $item->average : NULL;
    $max_stars = $this->maxStars();

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
   * Resolves the star scale the average is presented against.
   *
   * @return int
   *   The highest rating a visitor can give.
   */
  private function maxStars(): int {
    $max_stars = (int) $this->getSetting('max_stars');
    return $max_stars > 1 ? $max_stars : 5;
  }

}
