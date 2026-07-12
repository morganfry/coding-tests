<?php

declare(strict_types=1);

namespace Drupal\movie_ratings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default implementation of the rating manager.
 */
class RatingManager implements RatingManagerInterface {

  /**
   * ID of the node currently being re-saved to refresh its average, if any.
   *
   * @var int|null
   */
  protected ?int $resavingNid = NULL;

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

  /**
   * {@inheritdoc}
   *
   * Checks Authenticated users by uid and Anonymous by ip address.
   */
  public function hasRated(int $nid): bool {
    $query = $this->entityTypeManager->getStorage('movie_rating')->getQuery()
      ->accessCheck(FALSE)
      ->condition('movie', $nid)
      ->range(0, 1);

    if ($this->currentUser->isAuthenticated()) {
      $query->condition('uid', $this->currentUser->id());
    }
    else {
      $request = $this->requestStack->getCurrentRequest();
      $query->condition('uid', 0);
      $query->condition('ip_address', $request !== NULL ? (string) $request->getClientIp() : '');
    }

    return (bool) $query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getAverage(int $nid): array {
    $query = $this->entityTypeManager->getStorage('movie_rating')
      ->getAggregateQuery()
      ->accessCheck(FALSE)
      ->condition('movie', $nid)
      ->aggregate('rating', 'AVG', NULL, $average_alias)
      ->aggregate('rating', 'COUNT', NULL, $count_alias);

    $result = $query->execute();
    $row = $result[0] ?? [];
    $votes = (int) ($row[$count_alias] ?? 0);

    return [
      'average' => $votes > 0 ? round((float) $row[$average_alias], 1) : NULL,
      'votes' => $votes,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function updateAverage(int $nid): void {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node instanceof FieldableEntityInterface) {
      return;
    }

    $field_name = $this->getAverageFieldName($node);
    if ($field_name === NULL) {
      // Content types other than Movie carry no average rating field.
      return;
    }

    $average = $this->getAverage($nid);

    $node->set($field_name, [
      'average' => $average['average'],
      'votes' => $average['votes'],
    ]);

    if ($node instanceof RevisionableInterface) {
      // A visitor's vote is not an editorial revision of the movie.
      $node->setNewRevision(FALSE);
    }

    // Flagged so movie_ratings_node_presave() can tell this save apart from an
    // editor's and leave the movie's changed timestamp alone.
    $this->resavingNid = $nid;
    try {
      $node->save();
    }
    finally {
      $this->resavingNid = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isAverageResave(FieldableEntityInterface $entity): bool {
    return $this->resavingNid !== NULL
      && $entity->getEntityTypeId() === 'node'
      && (int) $entity->id() === $this->resavingNid;
  }

  /**
   * {@inheritdoc}
   */
  public function getAverageFieldName(FieldableEntityInterface $entity): ?string {
    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() === 'movie_rating_average') {
        return $field_name;
      }
    }
    return NULL;
  }

}
