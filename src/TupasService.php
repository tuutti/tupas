<?php
namespace Drupal\tupas;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\tupas\Entity\TupasBankInterface;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\Exception\TupasHashMatchException;
use Symfony\Component\HttpFoundation\Request;

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
  protected $return_url;

  /**
   * Url to return after cancel event.
   *
   * @var string
   */
  protected $cancel_url;

  /**
   * Url to return after rejected event.
   *
   * @var string
   */
  protected $rejected_url;

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
  protected $transaction_id;

  /**
   * List of allowed languages.
   *
   * @var array
   */
  protected $allowed_languages = ['FI', 'EN', 'SV'];

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

      if (!in_array($language, $this->allowed_languages)) {
        $this->set('language', 'EN');
      }
    }
  }

  /**
   * Set property.
   *
   * @param $key
   * @param $value
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
   * @param $key
   * @return mixed|null
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
    return $this->fromRoute($this->return_url);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->fromRoute($this->cancel_url);
  }

  /**
   * {@inheritdoc}
   */
  public function getRejectedUrl() {
    return $this->fromRoute($this->rejected_url);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId() {
    return $this->transaction_id;
  }

  /**
   * Helper to generate (absolute) internal URLs.
   *
   * @param $key
   * @return \Drupal\Core\Url
   */
  public function fromRoute($key) {
    $arguments = [
      'bank_id' => $this->bank->id(),
      'transaction_id' => $this->transaction_id,
    ];
    $url = new Url($key, $arguments, ['absolute' => TRUE]);

    return $url->toString();
  }

  /**
   * Hash mac based on encryption algorithm.
   *
   * @param $mac
   * @return string
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
   * @param $parts
   * @return string
   */
  public function checksum(array $parts) {
    return $this->hashMac(implode('&', $parts) . '&');
  }

  /**
   * Validate mac.
   *
   * @param $mac
   * @param $parts
   * @return bool
   */
  public function hashMatch($mac, $parts) {
    return $this->checksum($parts) === $mac;
  }

  /**
   * Validate mac from return parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return bool
   * @throws \Drupal\tupas\Exception\TupasGenericException
   * @throws \Drupal\tupas\Exception\TupasHashMatchException
   */
  public function validate(Request $request) {
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
      if (!$request->query->get($key)) {
        throw new TupasGenericException(sprintf('Missing %s argument', $key));
      }
      $parts[] = $request->query->get($key);
    }
    // Append rcv key.
    $parts[] = $this->bank->getRcvKey();

    if (!$this->hashMatch($request->query->get('B02K_MAC'), $parts)) {
      throw new TupasHashMatchException('Mac hash does not match with B02K_MAC.');
    }
    return TRUE;
  }

  /**
   * Hash SSN.
   *
   * @param $payload
   *   The value SSN to be hashed that must contain sign of century (-, +, or A).
   * @param $salt
   * @return string
   * @throws \Drupal\tupas\Exception\TupasGenericException
   */
  public function hashSsn($payload, $salt) {
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
