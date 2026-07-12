<?php

declare(strict_types=1);

namespace Drupal\movie_trailer_qr\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\movie_trailer_qr\QrCodeGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a link field as a scannable QR code with a caption.
 *
 * Used on a movie's trailer field so a visitor can scan the code and open the
 * trailer on their phone. The QR code is generated on the server and embedded
 * as a data URI, so displaying it makes no external request.
 *
 * @FieldFormatter(
 *   id = "trailer_qr_code",
 *   label = @Translation("Trailer QR code"),
 *   field_types = {"link"}
 * )
 */
final class TrailerQrCodeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a TrailerQrCodeFormatter object.
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
   * @param \Drupal\movie_trailer_qr\QrCodeGeneratorInterface $qrCodeGenerator
   *   The QR code generator.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected QrCodeGeneratorInterface $qrCodeGenerator,
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
      $container->get('movie_trailer_qr.qr_code_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 200,
      'caption' => 'Scan the QR code to watch the trailer of this movie.',
      'link_to_trailer' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size'),
      '#description' => $this->t('Width and height of the QR code, in pixels.'),
      '#default_value' => $this->getSetting('size'),
      '#min' => 50,
      '#max' => 600,
      '#required' => TRUE,
    ];
    $element['caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption'),
      '#description' => $this->t('Text shown with the QR code. Leave empty for no caption.'),
      '#default_value' => $this->getSetting('caption'),
      '#maxlength' => 255,
    ];
    $element['link_to_trailer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link the QR code to the trailer'),
      '#description' => $this->t('Lets visitors on a desktop, who cannot scan the code, click it instead.'),
      '#default_value' => $this->getSetting('link_to_trailer'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Size: @size pixels', ['@size' => $this->getSetting('size')]);
    $caption = $this->getSetting('caption');
    $summary[] = $caption !== ''
      ? $this->t('Caption: @caption', ['@caption' => $caption])
      : $this->t('No caption');
    if ($this->getSetting('link_to_trailer')) {
      $summary[] = $this->t('Linked to the trailer');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // A movie with no trailer has no items, so nothing is rendered — which is
    // what we want, rather than an empty QR frame.
    $elements = [];
    $size = (int) $this->getSetting('size');
    $title = $items->getEntity()->label();

    foreach ($items as $delta => $item) {
      $url = $item->getUrl()->toString();

      $elements[$delta] = [
        '#theme' => 'trailer_qr_code',
        '#uri' => $this->qrCodeGenerator->generate($url, $size),
        '#url' => $this->getSetting('link_to_trailer') ? $url : '',
        '#caption' => $this->getSetting('caption'),
        '#size' => $size,
        '#alt' => $this->t('QR code linking to the trailer of @title', ['@title' => $title]),
        '#attached' => ['library' => ['movie_trailer_qr/trailer_qr']],
      ];
    }

    return $elements;
  }

}
