<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\movie_ratings\RatingManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the interactive star rating form embedded on a movie page.
 */
final class RatingForm extends FormBase {

  /**
   * Constructs a RatingForm object.
   *
   * @param \Drupal\movie_ratings\RatingManagerInterface $ratingManager
   *   The rating manager.
   */
  public function __construct(
    protected readonly RatingManagerInterface $ratingManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('movie_ratings.rating_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_ratings_rating_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param int|null $nid
   *   The movie node ID being rated.
   * @param int $max_stars
   *   The highest rating a user can choose.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?int $nid = NULL, int $max_stars = 5) {
    $max_stars = $max_stars > 1 ? $max_stars : 5;
    $form_state->set('movie_nid', $nid);
    $form_state->set('max_stars', $max_stars);

    $wrapper_id = 'movie-rating-form-' . (int) $nid;
    $form['#prefix'] = '<div id="' . $wrapper_id . '" class="movie-rating-form">';
    $form['#suffix'] = '</div>';

    if ($form_state->get('rated')) {
      $form['thanks'] = [
        '#markup' => '<p class="movie-rating-form__thanks">' . $this->t('Thanks for rating!') . '</p>',
      ];
    }

    $options = [];
    foreach (range(1, $max_stars) as $value) {
      $options[$value] = $this->formatPlural($value, '@count star', '@count stars');
    }

    $form['rating'] = [
      '#type' => 'select',
      '#title' => $this->t('Your rating'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rate'),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];

    $form['#attached']['library'][] = 'movie_ratings/star_rating';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rating = (int) $form_state->getValue('rating');
    $max = (int) $form_state->get('max_stars');
    if ($rating < 1 || $rating > $max) {
      $form_state->setErrorByName('rating', $this->t('Please choose a rating between 1 and @max.', ['@max' => $max]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = (int) $form_state->get('movie_nid');
    $rating = (int) $form_state->getValue('rating');
    if ($nid > 0) {
      $this->ratingManager->recordRating($nid, $rating);
      $form_state->set('rated', TRUE);
    }
    // Rebuild so the confirmation shows (and so no page reload is required when
    // JavaScript is enabled).
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback returning the refreshed form wrapper.
   *
   * @param array $form
   *   The rebuilt form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form render array to replace the wrapper with.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
