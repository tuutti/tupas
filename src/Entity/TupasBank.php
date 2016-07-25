<?php

namespace Drupal\tupas\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Tupas bank entity.
 *
 * @ConfigEntityType(
 *   id = "tupas_bank",
 *   label = @Translation("Tupas bank"),
 *   handlers = {
 *     "storage" = "Drupal\tupas\TupasBankStorage",
 *     "list_builder" = "Drupal\tupas\TupasBankListBuilder",
 *     "form" = {
 *       "add" = "Drupal\tupas\Form\TupasBankForm",
 *       "edit" = "Drupal\tupas\Form\TupasBankForm",
 *       "delete" = "Drupal\tupas\Form\TupasBankDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\tupas\TupasBankHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "tupas_bank",
 *   admin_permission = "administer tupas",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/people/tupas/banks/{tupas_bank}",
 *     "add-form" = "/admin/config/people/tupas/banks/add",
 *     "edit-form" = "/admin/config/people/tupas/banks/{tupas_bank}/edit",
 *     "delete-form" = "/admin/config/people/tupas/banks/{tupas_bank}/delete",
 *     "collection" = "/admin/config/people/tupas/banks"
 *   }
 * )
 */
class TupasBank extends ConfigEntityBase implements TupasBankInterface, ConfigEntityInterface {

  /**
   * The Tupas bank ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Tupas bank label.
   *
   * @var string
   */
  protected $label;


  /**
   * The Tupas bank status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The tupas bank form action URL.
   *
   * @var string
   */
  protected $action_url;

  /**
   * The tupas bank certification version.
   *
   * @var int
   */
  protected $cert_version;

  /**
   * Receiver id.
   *
   * @var string
   */
  protected $rcv_id;

  /**
   * Receiver key.
   *
   * @var string
   */
  protected $rcv_key;

  /**
   * Key version.
   *
   * @var string
   */
  protected $key_version;

  /**
   * Encryption algorithm.
   *
   * @var string
   */
  protected $encryption_alg;

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return (bool) $this->get('status');
  }

  /**
   * {@inheritdoc}
   */
  public function getActionUrl() {
    return $this->get('action_url');
  }

  /**
   * {@inheritdoc}
   */
  public function getCertVersion() {
    return $this->get('cert_version');
  }

  /**
   * {@inheritdoc}
   */
  public function getRcvId() {
    return $this->get('rcv_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getRcvKey() {
    return $this->get('rcv_key');
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyVersion() {
    return $this->get('key_version');
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionAlg() {
    return $this->get('encryption_alg');
  }

}
