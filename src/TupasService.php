<?php

namespace Drupal\tupas;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\tupas\Entity\TupasBankInterface;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\Exception\TupasHashMatchException;

/**
 * Class TupasService.
 *
 * @package Drupal\tupas
 */
class TupasService implements TupasServiceInterface {

  const A01Y_ACTION_ID = 701;

  /**
   * TupasBank object.
   *
   * @var \Drupal\tupas\Entity\TupasBankInterface
   */
  protected $bank;

  /**
   * Return url after succesfull TUPAS authentication.
   *
   * @var string
   */
  protected $returnUrl;

  /**
   * Url to return after cancel event.
   *
   * @var string
   */
  protected $cancelUrl;

  /**
   * Url to return after rejected event.
   *
   * @var string
   */
  protected $rejectedUrl;

  /**
   * Tupas language.
   *
   * @var string
   */
  protected $language;

  /**
   * Transaction id.
   *
   * @var int
   */
  protected $transactionId;

  /**
   * List of allowed languages.
   *
   * @var array
   */
  protected $allowedLanguages = ['FI', 'EN', 'SV'];

  /**
   * Constructor.
   *
   * @param \Drupal\tupas\Entity\TupasBankInterface $bank
   *   TupasBank object.
   * @param array $settings
   *   List of required settings.
   */
  public function __construct(TupasBankInterface $bank, array $settings = []) {
    $this->bank = $bank;

    foreach ($settings as $key => $setting) {
      $this->set($key, $setting);
    }
    // Fallback to english.
    if (isset($settings['language'])) {
      $language = strtoupper($settings['language']);

      if (!in_array($language, $this->allowedLanguages)) {
        $this->set('language', 'EN');
      }
    }
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
  public function set($key, $value) {
    if (property_exists($this, $key)) {
      $this->{$key} = $value;
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
  public function get($key) {
    if (property_exists($this, $key)) {
      return $this->{$key};
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBank() {
    return $this->bank;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return strtoupper($this->language);
  }

  /**
   * {@inheritdoc}
   */
  public function getReturnUrl() {
    return $this->fromRoute($this->returnUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->fromRoute($this->cancelUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function getRejectedUrl() {
    return $this->fromRoute($this->rejectedUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId() {
    return $this->transactionId;
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
      'bank_id' => $this->bank->id(),
      'transaction_id' => $this->transactionId,
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
    if ($this->bank->getEncryptionAlg() === '01') {
      $mac = md5($mac);
    }
    elseif ($this->bank->getEncryptionAlg() === '03') {
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
    // Append rcv key.
    $parts[] = $this->bank->getRcvKey();

    if (!$this->hashMatch($values['B02K_MAC'], $parts)) {
      throw new TupasHashMatchException('Mac hash does not match with B02K_MAC.');
    }
    return TRUE;
  }

  /**
   * Hash SSN.
   *
   * @param string $payload
   *   The value SSN to be hashed that must contain sign of century (-, +, or A).
   *
   * @return string
   *   Hashed payload.
   *
   * @throws \Drupal\tupas\Exception\TupasGenericException
   */
  public static function hashSsn($payload) {
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

}
