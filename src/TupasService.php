<?php
namespace Drupal\tupas;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\tupas\Entity\TupasBank;
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
    return $this->language;
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
   * Url helper to generate internal return uris.
   *
   * Url must contain bank_id and transaction_id arguments, like
   * /tupas/{bank_id}/{transaction_id}.
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
    return implode('&', $parts) . '&';
  }

  /**
   * Validate mac.
   *
   * @param $mac
   * @param $parts
   * @return bool
   */
  public function hashMatch($mac, $parts) {
    $generated_mac = $this->hashMac($this->checksum($parts));

    return $generated_mac === $mac;
  }

  /**
   * Validate mac from return parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return bool
   * @throws \Drupal\tupas\Exception\TupasGenericException
   * @throws \Drupal\tupas\Exception\TupasHashMatchException
   */
  public function isValid(Request $request) {
    $mac_order = [
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

    foreach ($mac_order as $key) {
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
}
