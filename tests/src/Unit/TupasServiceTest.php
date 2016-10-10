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
   * The mocked Tupas Bank entity.
   *
   * @var \Drupal\tupas\Entity\TupasBankInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $bank;

  /**
   * The Tupas Service.
   *
   * @var \Drupal\tupas\TupasServiceInterface
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->bank = $this->getMock('\Drupal\tupas\Entity\TupasBankInterface');
    $this->sut = new TupasService($this->bank);
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
    // Test language fallback.
    $this->assertEquals($this->sut->getLanguage(), 'EN');
    // Test invalid language fallback.
    $this->sut->set('language', 'en_US');
    $this->assertEquals($this->sut->getLanguage(), 'EN');
    // Test correct language and autocapitalize.
    $this->sut->set('language', 'fi');
    $this->assertEquals($this->sut->getLanguage(), 'FI');
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
    $this->sut->validate($values);

    $values['B02K_MAC'] = $this->sut->checksum($values);

    // Hashes does not match.
    $this->setExpectedException(TupasHashMatchException::class);
    $this->sut->validate($values);

    $values[] = $this->bank->getRcvKey();

    $this->assertTrue($this->sut->validate($values));
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
      $this->assertEquals($transaction_id, $this->sut->parseTransactionId($combined));
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
