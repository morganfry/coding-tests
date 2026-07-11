<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Placeholder widget for the 'movie_rating' field.
 *
 * The field is rated on the public display, not the entity edit form, so this
 * widget only shows guidance. Set the field to "Disabled" on the form display.
 *
 * @FieldWidget(
 *   id = "movie_rating_select",
 *   label = @Translation("Movie rating (no editor input)"),
 *   field_types = {"movie_rating"}
 * )
 */
class MovieRatingSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = [
      '#type' => 'item',
      '#markup' => $this->t('Ratings are collected on the movie page.'),
    ];
    return $element;
  }

}
