<?php

namespace Drupal\tupas\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\Exception\TupasHashMatchException;

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

  const A01Y_ACTION_ID = 701;

  /**
   * Array of tupas settings.
   *
   * @var array
   */
  protected $settings;

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
   * The Tupas bank id type.
   *
   * @var string
   */
  protected $id_type;

  /**
   * TupasBank constructor.
   *
   * @param array $values
   *   Initial values.
   * @param string $entity_type
   *   The entity type.
   * @param array $settings
   *   List of settings to set.
   */
  public function __construct(array $values, $entity_type, array $settings = []) {
    parent::__construct($values, $entity_type);

    $this->settings = $settings + $this->getDefaultSettings();
  }

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

  /**
   * {@inheritdoc}
   */
  public function getIdType() {
    return $this->get('id_type');
  }

  /**
   * Get defaults.
   *
   * @return array
   *   Default settings.
   */
  public function getDefaultSettings() {
    return [
      'allowed_languages' => ['FI', 'EN', 'SV'],
      'language' => 'EN',
    ];
  }

  /**
   * Set property.
   *
   * @param string $key
   *   Setting key.
   * @param mixed $value
   *   Setting value.
   *
   * @return $this
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;

    return $this;
  }

  /**
   * Set multiple settings.
   *
   * @param array $settings
   *   List of settings.
   *
   * @return $this
   */
  public function setSettings(array $settings) {
    foreach ($settings as $key => $value) {
      $this->setSetting($key, $value);
    }
    return $this;
  }

  /**
   * Get property.
   *
   * @param string $key
   *   Setting key.
   *
   * @return mixed|null
   *   Setting value or NULL if setting does not exists.
   */
  public function getSetting($key) {
    if (isset($this->settings[$key])) {
      return $this->settings[$key];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    $language = strtoupper($this->getSetting('language'));
    // Fallback to english.
    if (!in_array($language, $this->getSetting('allowed_languages'))) {
      return 'EN';
    }
    return $language;
  }

  /**
   * {@inheritdoc}
   */
  public function getReturnUrl() {
    return $this->fromRoute($this->getSetting('return_url'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->fromRoute($this->getSetting('cancel_url'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRejectedUrl() {
    return $this->fromRoute($this->getSetting('rejected_url'));
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId() {
    return $this->getSetting('transaction_id');
  }

  /**
   * Helper to generate (absolute) internal URLs.
   *
   * @param string $key
   *   Route.
   *
   * @return string
   *   Absolute url to given route.
   */
  public function fromRoute($key) {
    $arguments = [
      'bank_id' => $this->id(),
      'transaction_id' => $this->getTransactionId(),
    ];
    $url = new Url($key, $arguments, ['absolute' => TRUE]);

    return $url->toString();
  }

  /**
   * Hash mac based on encryption algorithm.
   *
   * @param string $mac
   *   Plaintext mac.
   *
   * @return string
   *   Hashed MAC.
   */
  public function hashMac($mac) {
    if ($this->getEncryptionAlg() === '01') {
      $mac = md5($mac);
    }
    elseif ($this->getEncryptionAlg() === '03') {
      $mac = hash('sha256', $mac);
    }
    else {
      $mac = sha1($mac);
    }
    return strtoupper($mac);
  }

  /**
   * Generate checksum.
   *
   * @param array $parts
   *   Parts used to generate checksum.
   *
   * @return string
   *   Hashed checksum.
   */
  public function checksum(array $parts) {
    return $this->hashMac(implode('&', $parts) . '&');
  }

  /**
   * Validate mac.
   *
   * @param string $mac
   *   Hash to compare with.
   * @param array $parts
   *   Parts to generate counterpart hash.
   *
   * @return bool
   *   TRUE if hashes matches.
   */
  public function hashMatch($mac, $parts) {
    return $this->checksum($parts) === $mac;
  }

  /**
   * Validate mac from return parameters.
   *
   * @param array $values
   *    Array of validation parameters.
   *
   * @return bool TRUE if validation passed.
   *    TRUE if validation passed.
   *
   * @throws \Drupal\tupas\Exception\TupasGenericException
   * @throws \Drupal\tupas\Exception\TupasHashMatchException
   */
  public function validate(array $values) {
    if (empty($values['B02K_MAC'])) {
      throw new TupasGenericException('Missing B02K_MAC argument.');
    }
    // Make sure url arguments are processed in correct order.
    // @see https://www.drupal.org/node/2669274 (tupas)
    // @see https://www.drupal.org/node/2374777 (tupas_registration)
    $parameters = [
      'B02K_VERS',
      'B02K_TIMESTMP',
      'B02K_IDNBR',
      'B02K_STAMP',
      'B02K_CUSTNAME',
      'B02K_KEYVERS',
      'B02K_ALG',
      'B02K_CUSTID',
      'B02K_CUSTTYPE',
    ];
    $parts = [];
    foreach ($parameters as $key) {
      if (!isset($values[$key])) {
        throw new TupasGenericException(sprintf('Missing %s argument', $key));
      }
      $parts[] = $values[$key];
    }
    // Validate customer type and append required values.
    if (in_array($values['B02K_CUSTTYPE'], ['08', '09'])) {
      foreach (['B02K_USRID', 'B02K_USERNAME'] as $key) {
        if (!isset($values[$key])) {
          throw new TupasGenericException(sprintf('Missing %s argument', $key));
        }
        $parts[] = $values[$key];
      }
    }
    // Append rcv key.
    $parts[] = $this->getRcvKey();

    if (!$this->hashMatch($values['B02K_MAC'], $parts)) {
      throw new TupasHashMatchException('Mac hash does not match with B02K_MAC.');
    }
    return TRUE;
  }

  /**
   * Parse transaction id from return timestamp.
   *
   * @param string $timestamp
   *   Timestamp.
   *
   * @return string
   *   Transaction id.
   */
  public function parseTransactionId($timestamp) {
    $timestamp = substr($timestamp, -6);

    if (!$this->getTransactionId()) {
      $this->setSetting('transaction_id', $timestamp);
    }
    return $timestamp;
  }

  /**
   * Get list of hashable return codes.
   *
   * @return array
   *   List of return codes.
   */
  public static function getHashableTypes() {
    $types = ['02', '12', '22', '32', '42'];

    // Create identical key for the value.
    return array_combine($types, $types);
  }

  /**
   * Validate id type.
   *
   * @return bool
   *    TRUE on success, FALSE on failure.
   */
  public function validIdType() {
    $hashable_types = static::getHashableTypes();

    if (!isset($hashable_types[$this->getIdType()])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Hash customer id.
   *
   * Hashing logic is copied directly from tupas_registration 7.x-1.x.
   *
   * @param string $payload
   *   The value SSN to be hashed that must contain sign
   *   of century (-, +, or A).
   *
   * @return string
   *   Hashed payload.
   *
   * @throws \Drupal\tupas\Exception\TupasGenericException
   */
  public function hashResponseId($payload) {
    // Response is already hashed. Nothing to do.
    // @note Hash generated by bank cannot be used with tupas_registration because
    // hash contains timestamp, thus making it unique for every request.
    if (!$this->validIdType()) {
      return $payload;
    }
    $pieces = preg_split("/(\+|\-|A)/", $payload);
    if (empty($pieces[1])) {
      throw new TupasGenericException('SSN must contain sign of century.');
    }
    $hashing_algorithm = '$2a$';
    $log2_level = 13;

    // Create salt specific for the SSN.
    $salt = hash_hmac('sha512', Settings::getHashSalt(), $pieces[0]);

    // SSN hashed with a salt generated from the site specific salt and the
    // birthdate.
    $hashed_ssn = crypt($payload, $hashing_algorithm . $log2_level . '$' . $salt . '$');

    return $hashed_ssn;
  }

  /**
   * Legacy hash SSN.
   *
   * @param string $payload
   *   The value SSN to be hashed.
   *
   * @return string
   *   Hashed payload.
   */
  public function legacyHash($payload) {
    $hashed_password = hash('sha512', Settings::getHashSalt() . $payload);

    return $hashed_password;
  }

}
