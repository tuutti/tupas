<?php

namespace Drupal\Tests\tupas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\Exception\TupasHashMatchException;

/**
 * TupasBank unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas\Entity\TupasBank
 */
class TupasBankTest extends UnitTestCase {

  /**
   * The mocked Tupas Bank entity.
   *
   * @var \Drupal\tupas\Entity\TupasBankInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $bank;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $values = [
      'encryption_alg' => '01',
      'id_type' => '02',
    ];
    $this->bank = new TupasBank($values, 'tupas_bank');
  }

  /**
   * Test language methods.
   *
   * @covers ::getLanguage
   * @covers ::get
   * @covers ::set
   * @covers ::__construct
   * @covers ::getDefaultSettings
   */
  public function testLanguage() {
    // Test language fallback.
    $this->assertEquals($this->bank->getLanguage(), 'EN');
    // Test invalid language fallback.
    $this->bank->setSetting('language', 'en_US');
    $this->assertEquals($this->bank->getLanguage(), 'EN');
    // Test correct language and autocapitalize.
    $this->bank->setSetting('language', 'fi');
    $this->assertEquals($this->bank->getLanguage(), 'FI');
  }

  /**
   * Test encryption methods.
   *
   * @covers ::hashMac
   * @covers ::checksum
   * @covers ::hashMatch
   * @covers ::validate
   */
  public function testEncryption() {
    $values = [
      'B02K_VERS' => 1,
      'B02K_TIMESTMP' => 123456,
      'B02K_IDNBR' => 123456,
      'B02K_STAMP' => date('YdmHis') . 123456,
      'B02K_CUSTNAME' => 'Test Name',
      'B02K_KEYVERS' => '03',
      'B02K_ALG' => $this->bank->getEncryptionAlg(),
      'B02K_CUSTID' => '123456-123A',
      'B02K_CUSTTYPE' => '01',
    ];
    // B02K_MAC missing.
    $this->setExpectedException(TupasGenericException::class);
    $this->bank->validate($values);

    $values['B02K_MAC'] = $this->bank->checksum($values);

    // Hashes does not match.
    $this->setExpectedException(TupasHashMatchException::class);
    $this->bank->validate($values);

    $values[] = $this->bank->getRcvKey();

    $this->assertTrue($this->bank->validate($values));
  }

  /**
   * Test transaction id parsing.
   *
   * @covers ::parseTransactionId
   */
  public function testParseTransactionId() {
    for ($i = 0; $i < 3; $i++) {
      $transaction_id = random_int(123456, 234567);
      $combined = date('YdmHis') . $transaction_id;
      $this->assertEquals($transaction_id, $this->bank->parseTransactionId($combined));
    }
  }

  /**
   * Test hashResponseId() method.
   *
   * @covers ::hashResponseId
   * @covers ::getHashableTypes
   * @covers ::validIdType
   */
  public function testHashResponseId() {
    $invalid = '1234567';

    $this->setExpectedException(TupasGenericException::class);
    $this->bank->hashResponseId($invalid);

    $this->bank->set('id_type', 02);
    // Test invalid id.
    $response = $this->bank->hashResponseId($invalid);
    $this->assertEquals($invalid, $response);

    $this->bank->hashResponseId('123456-123A');
  }

}
