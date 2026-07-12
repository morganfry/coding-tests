<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\movie_ratings\Form\RatingForm;
use Drupal\movie_ratings\RatingManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a movie's average rating together with the star rating form.
 *
 * Where the block appears is configuration, not code. It declares a required
 * node context, which Drupal satisfies from the route, so the block system
 * hides it automatically on any page that is not a node. Restricting it further
 * to movies is the block's "Content type" visibility condition, set when the
 * block is placed.
 *
 * @Block(
 *   id = "movie_ratings_block",
 *   admin_label = @Translation("Movie ratings"),
 *   category = @Translation("Custom"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Movie"))
 *   }
 * )
 */
final class MovieRatingsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a MovieRatingsBlock object.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\movie_ratings\RatingManagerInterface $ratingManager
   *   The rating manager, used to locate the average rating field.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FormBuilderInterface $formBuilder,
    protected AccountInterface $currentUser,
    protected RatingManagerInterface $ratingManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('movie_ratings.rating_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['max_stars' => 5];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['max_stars'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum stars'),
      '#description' => $this->t('The highest rating a visitor can give, and the scale the average is shown against.'),
      '#default_value' => $this->maxStars(),
      '#min' => 2,
      '#max' => 10,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['max_stars'] = (int) $form_state->getValue('max_stars');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if (!$node instanceof NodeInterface) {
      return [];
    }

    $field_name = $this->ratingManager->getAverageFieldName($node);
    if ($field_name === NULL) {
      // The content type carries no average rating field, so there is nothing
      // to show and nowhere for a vote to be recorded.
      return [];
    }

    $max_stars = $this->maxStars();

    // Render the field through its formatter with explicit display options,
    // rather than through a view mode. The field is hidden on Manage display
    // (this block is the one place the rating UI appears), so resolving it
    // through the view display would render nothing.
    $build['average'] = $node->get($field_name)->view([
      'type' => 'movie_rating_average',
      'label' => 'hidden',
      'settings' => ['max_stars' => $max_stars],
    ]);

    if ($this->currentUser->hasPermission('submit movie ratings')) {
      $build['form'] = $this->formBuilder->getForm(RatingForm::class, (int) $node->id(), $max_stars);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The node context contributes its own cache contexts (the route it was
    // resolved from). The form is only built for users allowed to vote, so the
    // output also varies by permission.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.permissions']);
  }

  /**
   * Resolves the configured star scale.
   *
   * @return int
   *   The highest rating a visitor can give.
   */
  private function maxStars(): int {
    $max_stars = (int) ($this->configuration['max_stars'] ?? 0);
    return $max_stars > 1 ? $max_stars : 5;
  }

}
