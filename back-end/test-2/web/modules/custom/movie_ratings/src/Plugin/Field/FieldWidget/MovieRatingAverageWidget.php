<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Placeholder widget for the 'movie_rating_average' field.
 *
 * The average is derived from submitted ratings, never typed in, so this widget
 * only shows guidance. Set the field to "Disabled" on the form display.
 *
 * @FieldWidget(
 *   id = "movie_rating_average_readonly",
 *   label = @Translation("Movie rating average (no editor input)"),
 *   field_types = {"movie_rating_average"}
 * )
 */
class MovieRatingAverageWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['average'] = [
      '#type' => 'item',
      '#markup' => $this->t('Calculated automatically from submitted ratings.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Take no value from the form. Without this, enabling the widget on a form
    // display would blank the stored average every time an editor saved a node.
  }

}
