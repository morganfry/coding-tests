<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Movie rating content entity.
 *
 * Each entity is a single vote for a movie, capturing the star rating and the
 * submitter's IP address. The entity is exposed to Views via EntityViewsData.
 *
 * @ContentEntityType(
 *   id = "movie_rating",
 *   label = @Translation("Movie rating"),
 *   label_collection = @Translation("Movie ratings"),
 *   label_singular = @Translation("movie rating"),
 *   label_plural = @Translation("movie ratings"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "movie_rating",
 *   admin_permission = "administer movie ratings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 * )
 */
class MovieRating extends ContentEntityBase implements MovieRatingInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['movie'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Movie'))
      ->setDescription(t('The movie node this rating belongs to.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['movie' => 'movie']])
      ->setRequired(TRUE);

    $fields['rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rating'))
      ->setDescription(t('The submitted star rating value.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 1);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The IP address the rating was submitted from.'))
      ->setSetting('max_length', 128)
      ->setDefaultValue('');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the rating was submitted.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getRating(): int {
    return (int) $this->get('rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMovieId(): ?int {
    $target = $this->get('movie')->target_id;
    return $target !== NULL ? (int) $target : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIpAddress(): string {
    return (string) $this->get('ip_address')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

}
