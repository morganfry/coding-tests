<?php

declare(strict_types=1);

namespace Drupal\movie_trailer_qr;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Generates QR codes with endroid/qr-code.
 *
 * The library is used only here, behind QrCodeGeneratorInterface, so nothing
 * else in the module depends on it and it can be swapped in one file.
 *
 * SVG is used rather than PNG: it stays sharp at any size, needs no image
 * extension, and is small enough to inline.
 */
class QrCodeGenerator implements QrCodeGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function generate(string $data, int $size): string {
    $qr_code = new QrCode(
      data: $data,
      // Medium tolerates a little damage or a poor camera without making the
      // code noticeably denser.
      errorCorrectionLevel: ErrorCorrectionLevel::Medium,
      size: $size,
    );

    return (new SvgWriter())->write($qr_code)->getDataUri();
  }

}
