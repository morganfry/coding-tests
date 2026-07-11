<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\movie_ratings\Form\RatingForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the interactive star rating form on the entity display.
 *
 * The rating form is rendered regardless of stored field value (the field
 * carries none), so this formatter overrides view() rather than viewElements().
 *
 * @FieldFormatter(
 *   id = "movie_rating_form",
 *   label = @Translation("Interactive star rating form"),
 *   field_types = {"movie_rating"}
 * )
 */
final class MovieRatingFormFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a MovieRatingFormFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected readonly FormBuilderInterface $formBuilder,
    protected readonly AccountInterface $currentUser,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('form_builder'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $entity = $items->getEntity();

    // Only rate saved entities, and only for users allowed to submit ratings.
    if ($entity->isNew() || !$this->currentUser->hasPermission('submit movie ratings')) {
      $build = [];
      $this->addCacheability($build);
      return $build;
    }

    $max_stars = (int) $this->getFieldSetting('max_stars') ?: 5;
    $build = $this->formBuilder->getForm(RatingForm::class, (int) $entity->id(), $max_stars);
    $this->addCacheability($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Rendering is handled entirely in view(); there is no per-item output.
    return [];
  }

  /**
   * Adds cache metadata that varies the output by the current user.
   *
   * @param array $build
   *   The render array to attach cache metadata to.
   */
  protected function addCacheability(array &$build): void {
    $build['#cache']['contexts'][] = 'user.permissions';
  }

}
