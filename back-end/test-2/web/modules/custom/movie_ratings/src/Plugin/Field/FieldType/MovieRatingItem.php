<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'movie_rating' field type.
 *
 * This field carries no meaningful stored value: it exists so an interactive
 * rating control can be placed on a content type's display. Submitted votes are
 * stored as movie_rating content entities, not on the host entity.
 *
 * @FieldType(
 *   id = "movie_rating",
 *   label = @Translation("Movie rating"),
 *   description = @Translation("Renders an interactive star rating control on the display; votes are stored as movie_rating entities."),
 *   category = @Translation("Custom"),
 *   default_widget = "movie_rating_select",
 *   default_formatter = "movie_rating_form",
 *   cardinality = 1
 * )
 */
class MovieRatingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['max_stars' => 5] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Rating placeholder'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Nothing is stored on the host entity; ratings live in movie_rating
    // entities created from the display form.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['max_stars'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum stars'),
      '#description' => $this->t('The highest rating a user can choose.'),
      '#default_value' => $this->getSetting('max_stars'),
      '#min' => 2,
      '#max' => 10,
      '#required' => TRUE,
    ];
    return $element;
  }

}
