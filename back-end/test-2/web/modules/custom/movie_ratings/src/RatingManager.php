<?php

declare(strict_types=1);

namespace Drupal\movie_ratings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default implementation of the rating manager.
 */
class RatingManager implements RatingManagerInterface {

  /**
   * Constructs a RatingManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack, used to resolve the client IP address.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RequestStack $requestStack,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function recordRating(int $nid, int $rating): void {
    $request = $this->requestStack->getCurrentRequest();
    $ip_address = $request !== NULL ? (string) $request->getClientIp() : '';

    $this->entityTypeManager->getStorage('movie_rating')->create([
      'movie' => $nid,
      'rating' => $rating,
      'ip_address' => $ip_address,
      'uid' => $this->currentUser->id(),
    ])->save();
  }

}
