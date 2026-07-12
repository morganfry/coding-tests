<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager, used to re-render the movie's average rating.
   */
  public function __construct(
    protected RatingManagerInterface $ratingManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('movie_ratings.rating_manager'),
      $container->get('entity_type.manager'),
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

    $wrapper_id = $this->wrapperId((int) $nid);
    $form['#prefix'] = '<div id="' . $wrapper_id . '" class="movie-rating-form">';
    $form['#suffix'] = '</div>';

    // Inline feedback shown after a submit. Kept as a form-state flag (rather
    // than the global messenger) so it renders inside the AJAX-replaced wrapper
    // and never captures unrelated page messages.
    $feedback = $form_state->get('feedback');
    if ($feedback === 'thanks') {
      $form['feedback'] = [
        '#markup' => '<p class="movie-rating-form__thanks">' . $this->t('Thanks for rating!') . '</p>',
      ];
    }
    elseif ($feedback === 'already') {
      $form['feedback'] = [
        '#markup' => '<p class="movie-rating-form__notice">' . $this->t('You have already rated this movie.') . '</p>',
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

    // The submit button is always present so the AJAX framework can always
    // resolve the triggering element, even on a repeat submit.
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
      // Only one rating per user (by account, or by IP when anonymous).
      if ($this->ratingManager->hasRated($nid)) {
        $form_state->set('feedback', 'already');
      }
      else {
        $this->ratingManager->recordRating($nid, $rating);
        $form_state->set('feedback', 'thanks');
      }
    }
    // Rebuild (rather than redirect) so the inline feedback shows without a
    // page reload; the stable form structure keeps the AJAX callback callable.
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback refreshing the form, and the average rating it just changed.
   *
   * @param array $form
   *   The rebuilt form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Commands replacing the form, and the movie's average rating display.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $nid = (int) $form_state->get('movie_nid');

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#' . $this->wrapperId($nid), $form));

    // A new vote moves the average, so refresh it too. Without this the star
    // graphic elsewhere on the page would keep showing the pre-vote figure
    // until the visitor reloaded.
    if ($form_state->get('feedback') === 'thanks') {
      $average = $this->buildAverage($nid, (int) $form_state->get('max_stars'));
      if ($average !== []) {
        $response->addCommand(new ReplaceCommand('[data-movie-rating-average="' . $nid . '"]', $average));
      }
    }

    return $response;
  }

  /**
   * Builds the DOM id of a movie's rating form wrapper.
   *
   * One movie page can hold several rating forms, so the id carries the node
   * ID to keep each form's AJAX replacement targeted at its own wrapper.
   *
   * @param int $nid
   *   The movie node ID.
   *
   * @return string
   *   The wrapper element id.
   */
  private function wrapperId(int $nid): string {
    return 'movie-rating-form-' . $nid;
  }

  /**
   * Re-renders a movie's average rating field.
   *
   * The field is rendered through its formatter rather than rebuilt by hand, so
   * the AJAX replacement matches what a page load produces. The display options
   * are passed explicitly rather than naming a view mode: the field is hidden
   * on the movie's display (the Movie ratings block is the one place the rating
   * UI appears), so resolving it through the view display would render nothing
   * and the star graphic would silently stop updating after a vote.
   *
   * @param int $nid
   *   The movie node ID.
   * @param int $max_stars
   *   The star scale the average is shown against.
   *
   * @return array
   *   The average rating render array, or an empty array if the movie has no
   *   average rating field.
   */
  private function buildAverage(int $nid, int $max_stars): array {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node instanceof FieldableEntityInterface) {
      return [];
    }

    $field_name = $this->ratingManager->getAverageFieldName($node);
    if ($field_name === NULL) {
      return [];
    }

    return $node->get($field_name)->view([
      'type' => 'movie_rating_average',
      'label' => 'hidden',
      'settings' => ['max_stars' => $max_stars],
    ]);
  }

}
