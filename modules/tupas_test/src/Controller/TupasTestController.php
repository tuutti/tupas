<?php
namespace Drupal\tupas_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas\TupasServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class TupasTestController.
 *
 * @package Drupal\tupas_test\Controller
 */
class TupasTestController extends ControllerBase {

  /**
   * @var \Drupal\tupas\TupasServiceInterface
   */
  protected $tupas;

  /**
   * {@inheritdoc}
   */
  public function __construct(TupasServiceInterface $tupas) {
    $this->tupas = $tupas;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tupas')
    );
  }

  /**
   * Hello.
   *
   * @return string
   */
  public function front() {
    $banks = $this->entityManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

    $markup = [];
    foreach ($banks as $bank) {
      $markup[] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasForm', $bank);
    }
    return $markup;
  }

  /**
   * /tupas_test_return.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return array
   */
  public function returnTo(Request $request) {
    $bank = $this->entityManager()
      ->getStorage('tupas_bank')
      ->load($request->query->get('bank_id'));

    if (!$bank instanceof TupasBank) {
      throw new HttpException(502, 'Bank not found');
    }
    $hash_match = $this->tupas->validateMac($bank, $request);

    if (!$hash_match) {
      throw new HttpException(502, 'Hash validation failed');
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: /tupas_test_return')
    ];
  }

  /**
   * Cancel.
   */
  public function cancel() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: cancel')
    ];
  }

  /**
   * Rejected.
   */
  public function rejected() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: rejected')
    ];
  }

  /**
   * Test.
   */
  public function test() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: test')
    ];
  }

}
