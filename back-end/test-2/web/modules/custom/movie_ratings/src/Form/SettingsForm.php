<?php

declare(strict_types=1);

namespace Drupal\movie_ratings\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures flood control for the movie rating form.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Time windows offered for the flood limit, in seconds.
   */
  private const INTERVALS = [60, 300, 900, 1800, 3600, 21600, 86400];

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter, used to label the time windows.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected DateFormatterInterface $dateFormatter,
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_ratings_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['movie_ratings.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('movie_ratings.settings');

    $form['flood'] = [
      '#type' => 'details',
      '#title' => $this->t('Flood control'),
      '#description' => $this->t('Limits how many ratings a single visitor can submit, to keep bots from stuffing the ballot. Users with the "Administer movie ratings" permission are not limited.'),
      '#open' => TRUE,
    ];

    $form['flood']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Rating attempts allowed'),
      '#description' => $this->t('Every submission counts, including one rejected because the visitor already rated that movie.'),
      '#default_value' => $config->get('flood.limit'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    $intervals = [];
    foreach (self::INTERVALS as $interval) {
      $intervals[$interval] = $this->dateFormatter->formatInterval($interval);
    }
    $form['flood']['interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Per time window'),
      '#options' => $intervals,
      '#default_value' => $config->get('flood.interval'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('movie_ratings.settings')
      ->set('flood.limit', (int) $form_state->getValue('limit'))
      ->set('flood.interval', (int) $form_state->getValue('interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
