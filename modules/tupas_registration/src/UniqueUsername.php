<?php

namespace Drupal\tupas_registration;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class UniqueUsername.
 *
 * @package Drupal\tupas_registration
 */
class UniqueUsername implements UniqueUsernameInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * UniqueUsername constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function userExists($name) {
    $users = $this->entityManager->getStorage('user')
      ->loadByProperties(['name' => $name]);
    return $users ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName($name = NULL) {
    if (!$name) {
      // @todo Generate human readable username?
      $random = new Random();
      // Generate unique username.
      while (TRUE) {
        $name = $random->name(10);

        if (!$this->userExists($name)) {
          break;
        }
      }
      return $name;
    }
    $parts = explode(' ', strtolower($name));

    if (isset($parts[1])) {
      // Name is uppercase by default. Convert to lowercase and
      // capitalize first letter.
      list($first, $last) = $parts;

      $name = sprintf('%s %s', ucfirst($first), ucfirst($last));
    }
    $i = 1;
    // Generate unique username, by incrementing suffix.
    $original = $name;

    while (TRUE) {
      if (!$this->userExists($name)) {
        break;
      }
      $name = sprintf('%s %d', $original, $i++);
    }
    return $name;
  }

}