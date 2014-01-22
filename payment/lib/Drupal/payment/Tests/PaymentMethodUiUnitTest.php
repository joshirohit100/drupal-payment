<?php

/**
 * @file
 * Contains \Drupal\payment\Tests\PaymentMethodUiUnitTest.
 */

namespace Drupal\payment\Tests;

use Drupal\Core\Access\AccessInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests \Drupal\payment\PaymentMethodUi.
 */
class PaymentMethodUiUnitTest extends UnitTestCase {

  /**
   * The current user used for testing.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The form builder used for testing.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formBuilder;

  /**
   * The payment method configuration plugin manager used for testing.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The payment method plugin manager used for testing.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $paymentMethodManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\PaymentMethodUi|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $paymentMethodUi;

  /**
   * The URL generator used for testing.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'group' => 'Payment',
      'name' => '\Drupal\payment\PaymentMethodUi unit test',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->currentUser = $this->getMock('\Drupal\Core\Session\AccountInterface');

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $this->formBuilder = $this->getMock('\Drupal\Core\Form\FormBuilderInterface');

    $this->paymentMethodConfigurationManager = $this->getMock('\Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface');

    $this->paymentMethodManager= $this->getMock('\Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface');

    $this->urlGenerator = $this->getMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValue('http://example.com'));

    $this->paymentMethodUi = $this->getMockBuilder('\Drupal\payment\PaymentMethodUi')
      ->setConstructorArgs(array($this->entityManager, $this->paymentMethodManager, $this->paymentMethodConfigurationManager, $this->formBuilder, $this->urlGenerator, $this->currentUser))
      ->setMethods(array('drupalGetPath', 't', 'theme'))
      ->getMock();
  }

  /**
   * Tests enable().
   */
  public function testEnable() {
    $payment_method = $this->getMock('\Drupal\payment\Entity\PaymentMethodInterface');
    $payment_method->expects($this->once())
      ->method('enable');
    $payment_method->expects($this->once())
      ->method('save');
    $this->paymentMethodUi->enable($payment_method);
  }

  /**
   * Tests disable().
   */
  public function testDisable() {
    $payment_method = $this->getMock('\Drupal\payment\Entity\PaymentMethodInterface');
    $payment_method->expects($this->once())
      ->method('disable');
    $payment_method->expects($this->once())
      ->method('save');
    $this->paymentMethodUi->disable($payment_method);
  }

  /**
   * Tests select().
   */
  public function testSelect() {
    $definitions = array(
      'payment_unavailable' => array(),
      'foo' => array(
        'description' => $this->randomName(),
        'label' => $this->randomName(),
      ),
      'bar' => array(
        'description' => $this->randomName(),
        'label' => $this->randomName(),
      ),
    );
    $this->paymentMethodConfigurationManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $access_controller = $this->getMock('\Drupal\Core\Entity\EntityAccessControllerInterface');
    $access_controller->expects($this->any())
      ->method('createAccess')
      ->will($this->returnValue(TRUE));

    $this->entityManager->expects($this->once())
      ->method('getAccessController')
      ->with('payment_method')
      ->will($this->returnValue($access_controller));

    $this->paymentMethodUi->select();
  }

  /**
   * Tests selectAccess().
   */
  public function testSelectAccess() {
    $definitions = array(
      'payment_unavailable' => array(),
      'foo' => array(
        'description' => $this->randomName(),
        'label' => $this->randomName(),
      ),
      'bar' => array(
        'description' => $this->randomName(),
        'label' => $this->randomName(),
      ),
    );
    $this->paymentMethodConfigurationManager->expects($this->exactly(2))
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $access_controller = $this->getMock('\Drupal\Core\Entity\EntityAccessControllerInterface');
    $access_controller->expects($this->at(0))
      ->method('createAccess')
      ->with('foo', $this->currentUser)
      ->will($this->returnValue(TRUE));
    $access_controller->expects($this->at(1))
      ->method('createAccess')
      ->with('foo', $this->currentUser)
      ->will($this->returnValue(FALSE));
    $access_controller->expects($this->at(2))
      ->method('createAccess')
      ->with('bar', $this->currentUser)
      ->will($this->returnValue(FALSE));

    $this->entityManager->expects($this->exactly(2))
      ->method('getAccessController')
      ->with('payment_method')
      ->will($this->returnValue($access_controller));

    $request = new Request();

    $this->assertSame(AccessInterface::ALLOW, $this->paymentMethodUi->selectAccess($request));
    $this->assertSame(AccessInterface::DENY, $this->paymentMethodUi->selectAccess($request));
  }

  /**
   * Tests add().
   */
  public function testAdd() {
    $plugin_id = $this->randomName();

    $payment_method = $this->getMock('\Drupal\payment\Entity\PaymentMethodInterface');

    $storage_controller = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $storage_controller->expects($this->once())
      ->method('create')
      ->will($this->returnValue($payment_method));

    $form_controller = $this->getMock('\Drupal\Core\Entity\EntityFormControllerInterface');
    $form_controller->expects($this->once())
      ->method('setEntity')
      ->will($this->returnSelf());

    $this->entityManager->expects($this->once())
      ->method('getStorageController')
      ->with('payment_method')
      ->will($this->returnValue($storage_controller));

    $this->entityManager->expects($this->once())
      ->method('getFormController')
      ->with('payment_method', 'default')
      ->will($this->returnValue($form_controller));

    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with($form_controller);

    $this->paymentMethodUi->add($plugin_id);
  }

  /**
   * Tests addAccess().
   */
  public function testAddAccess() {
    $plugin_id = $this->randomName();
    $request = new Request();
    $request->attributes->set('plugin_id', $plugin_id);

    $access_controller = $this->getMock('\Drupal\Core\Entity\EntityAccessControllerInterface');
    $access_controller->expects($this->at(0))
      ->method('createAccess')
      ->with($plugin_id, $this->currentUser)
      ->will($this->returnValue(TRUE));
    $access_controller->expects($this->at(1))
      ->method('createAccess')
      ->with($plugin_id, $this->currentUser)
      ->will($this->returnValue(FALSE));

    $this->entityManager->expects($this->exactly(2))
      ->method('getAccessController')
      ->with('payment_method')
      ->will($this->returnValue($access_controller));

    $this->assertSame(AccessInterface::ALLOW, $this->paymentMethodUi->addAccess($request));
    $this->assertSame(AccessInterface::DENY, $this->paymentMethodUi->addAccess($request));
  }

  /**
   * Tests duplicate().
   */
  public function testDuplicate() {
    $payment_method = $this->getMock('\Drupal\payment\Entity\PaymentMethodInterface');
    $payment_method->expects($this->once())
      ->method('createDuplicate')
      ->will($this->returnSelf());
    $payment_method->expects($this->once())
      ->method('setLabel')
      ->will($this->returnSelf());

    $form_controller = $this->getMock('\Drupal\Core\Entity\EntityFormControllerInterface');
    $form_controller->expects($this->once())
      ->method('setEntity')
      ->will($this->returnSelf());

    $this->entityManager->expects($this->once())
      ->method('getFormController')
      ->with('payment_method', 'default')
      ->will($this->returnValue($form_controller));

    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with($form_controller);

    $this->paymentMethodUi->duplicate($payment_method);
  }

  /**
   * Tests listPlugins().
   */
  public function testListPlugins() {
    $plugin_id = $this->randomName();
    $definitions = array(
      $plugin_id => array(
        'active' => TRUE,
        'class' => '\Drupal\payment\Tests\PaymentMethodUiUnitTestDummyPaymentMethodPlugin',
        'label' => $this->randomName(),
      ),
    );

    $this->paymentMethodManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $build = $this->paymentMethodUi->listPlugins();
    $this->assertInternalType('array', $build);
  }
}

/**
 * Fakes a payment method plugin.
 */
class PaymentMethodUiUnitTestDummyPaymentMethodPlugin {

  /**
   * Fakes \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface::getOperations().
   */
  public static function getOperations($plugin_id) {
    return array();
  }
}