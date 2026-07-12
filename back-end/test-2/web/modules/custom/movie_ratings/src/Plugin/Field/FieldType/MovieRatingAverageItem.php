<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'movie_rating_average' field type.
 *
 * Holds the average rating and vote count for the host entity. The value is a
 * cache of the movie_rating entities, recomputed by the rating manager whenever
 * a rating is created, changed or removed; it is never entered by an editor.
 * Storing it on the node is what makes the average available to Views as a
 * field, filter and sort.
 *
 * @FieldType(
 *   id = "movie_rating_average",
 *   label = @Translation("Movie rating average"),
 *   description = @Translation("Stores the average rating and vote count for a movie, maintained automatically from submitted ratings."),
 *   category = @Translation("Custom"),
 *   default_widget = "movie_rating_average_readonly",
 *   default_formatter = "movie_rating_average",
 *   cardinality = 1
 * )
 */
class MovieRatingAverageItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'average';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Decimals are represented as strings in typed data, as core's DecimalItem
    // does, so no precision is lost in PHP floats.
    $properties['average'] = DataDefinition::create('string')
      ->setLabel(t('Average rating'));
    $properties['votes'] = DataDefinition::create('integer')
      ->setLabel(t('Number of votes'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'average' => [
          'type' => 'numeric',
          'precision' => 4,
          'scale' => 2,
          'not null' => FALSE,
        ],
        'votes' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
          'default' => 0,
        ],
      ],
      // Indexed so "highest rated" sorts and filters stay cheap in Views.
      'indexes' => [
        'average' => ['average'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $average = $this->get('average')->getValue();
    return $average === NULL || $average === '';
  }

}
