<?php

declare(strict_types=1);

namespace Drupal\movie_trailer_qr;

/**
 * Generates QR codes.
 */
interface QrCodeGeneratorInterface {

  /**
   * Encodes a string as a QR code image.
   *
   * @param string $data
   *   The data to encode, typically a URL.
   * @param int $size
   *   The width and height of the image, in pixels.
   *
   * @return string
   *   The QR code as a data URI, ready to use as an image source. Being a data
   *   URI rather than a remote URL, the page makes no external request to
   *   display it.
   */
  public function generate(string $data, int $size): string;

}
