<?php

namespace Drupal\Tests\tupas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\Exception\TupasHashMatchException;
use Drupal\tupas\TupasService;

/**
 * TupasService unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas\TupasService
 */
class TupasServiceTest extends UnitTestCase {

  /**
   * The mocket Tupas bank entity.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $bank;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->bank = $this->getMockBuilder('\Drupal\tupas\Entity\TupasBankInterface')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test language methods.
   *
   * @covers ::getLanguage
   * @covers ::get
   * @covers ::set
   * @covers ::__construct
   * @covers ::getBank
   * @covers ::getDefaults
   */
  public function testLanguage() {
    $tupas = new TupasService($this->bank);

    // Test language fallback.
    $this->assertEquals($tupas->getLanguage(), 'EN');
    // Test invalid language fallback.
    $tupas->set('language', 'en_US');
    $this->assertEquals($tupas->getLanguage(), 'EN');
    // Test correct language and autocapitalize.
    $tupas->set('language', 'fi');
    $this->assertEquals($tupas->getLanguage(), 'FI');
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
    $tupas = new TupasService($this->bank);

    $this->bank->expects($this->any())
      ->method('getEncryptionAlg')
      ->will($this->returnValue('01'));
    $this->bank->expects($this->any())
      ->method('getRcvKey')
      ->will($this->returnValue('1234567'));

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
    $tupas->validate($values);

    $values['B02K_MAC'] = $tupas->checksum($values);

    // Hashes does not match.
    $this->setExpectedException(TupasHashMatchException::class);
    $tupas->validate($values);

    $values[] = $this->bank->getRcvKey();

    $this->assertTrue($tupas->validate($values));
  }

  /**
   * Test transaction id parsing.
   *
   * @covers ::parseTransactionId
   */
  public function testParseTransactionId() {
    $tupas = new TupasService($this->bank);

    for ($i = 0; $i < 3; $i++) {
      $transaction_id = random_int(123456, 234567);
      $combined = date('YdmHis') . $transaction_id;
      $this->assertEquals($transaction_id, $tupas->parseTransactionId($combined));
    }
  }

  /**
   * Test hashResponseId() method.
   *
   * @covers ::hashResponseId
   * @covers ::getHashableTypes
   * @covers ::validateIdType
   */
  public function testHashResponseId() {
    $invalid = '1234567';

    $this->setExpectedException(TupasGenericException::class);
    TupasService::hashResponseId($invalid, '02');

    // Test invalid id.
    $response = TupasService::hashResponseId($invalid, '01');
    $this->assertEquals($invalid, $response);

    TupasService::hashResponseId('123456-123A');
  }

}
