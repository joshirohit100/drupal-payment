<?php

/**
 * @file
 * Contains \Drupal\payment\Tests\Entity\Payment\PaymentListBuilderUnitTest.
 */

namespace Drupal\payment\Tests\Entity\Payment {

use Drupal\Core\Url;
use Drupal\payment\Entity\Payment\PaymentListBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentListBuilder
 */
class PaymentListBuilderUnitTest extends UnitTestCase {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currencyStorage;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\Date|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $date;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The list builder under test.
   *
   * @var \Drupal\payment\Entity\Payment\PaymentListBuilder
   */
  protected $listBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\payment\Entity\Payment\PaymentListBuilder unit test',
      'group' => 'Payment',
    );
  }

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  public function setUp() {
    $this->currencyStorage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');

    $this->date = $this->getMockBuilder('\Drupal\Core\Datetime\Date')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityStorage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');

    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');

    $this->requestStack = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
      ->disableOriginalConstructor()
      ->getMock();

    $this->stringTranslation = $this->getMock('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->stringTranslation->expects($this->any())
      ->method('translate')
      ->will($this->returnArgument(0));

    $this->listBuilder = new PaymentListBuilder($this->entityType, $this->entityStorage, $this->stringTranslation, $this->moduleHandler, $this->requestStack, $this->date, $this->currencyStorage);
  }

  /**
   * @covers ::createInstance
   */
  function testCreateInstance() {
    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $map = array(
      array('currency', $this->currencyStorage),
      array('payment', $this->entityStorage),
    );
    $entity_manager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValueMap($map));

    $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
    $map = array(
      array('date', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->date),
      array('entity.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_manager),
      array('module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler),
      array('request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->requestStack),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($map));

    $form = PaymentListBuilder::createInstance($container, $this->entityType);
    $this->assertInstanceOf('\Drupal\payment\Entity\Payment\PaymentListBuilder', $form);
  }

  /**
   * @covers ::buildHeader
   */
  function testBuildHeader() {
    $header = $this->listBuilder->buildHeader();
    $expected = array(
      'updated' => 'Last updated',
      'status' => 'Status',
      'amount' => 'Amount',
      'payment_method' => array(
        'data' => 'Payment method',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'owner' => array(
        'data' => 'Payer',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'operations' => 'Operations',
    );
    $this->assertSame($expected, $header);
  }

  /**
   * @covers ::buildOperations
   */
  public function testBuildOperations() {
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValue(array()));

    $payment = $this->getMockBuilder('\Drupal\payment\Entity\Payment')
      ->disableOriginalConstructor()
      ->getMock();

    $expected_build = array(
      '#type' => 'operations',
      '#links' => array(),
      '#attached' => array(
        'library' => array('core/drupal.ajax'),
      )
    );
    $this->assertSame($expected_build, $this->listBuilder->buildOperations($payment));
  }

  /**
   * @covers ::buildRow
   *
   * @depends testBuildOperations
   */
  function testBuildRow() {
    $payment_changed_time = time();
    $payment_changed_time_formatted = $this->randomName();
    $payment_currency_code = $this->randomName();
    $payment_amount = mt_rand();
    $payment_amount_formatted = $this->randomName();

    $payment_status_definition = array(
      'label' => $this->randomName(),
    );

    $payment_status = $this->getMock('\Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface');
    $payment_status->expects($this->any())
      ->method('getPluginDefinition')
      ->will($this->returnValue($payment_status_definition));

    $owner = $this->getMockBuilder('\Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $payment_method_label = $this->randomName();
    $payment_method = $this->getMock('\Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface');
    $payment_method->expects($this->atLeastOnce())
      ->method('getPluginLabel')
      ->will($this->returnValue($payment_method_label));

    $payment = $this->getMockBuilder('\Drupal\payment\Entity\Payment')
      ->disableOriginalConstructor()
      ->getMock();
    $payment->expects($this->any())
      ->method('getAmount')
      ->will($this->returnValue($payment_amount));
    $payment->expects($this->any())
      ->method('getChangedTime')
      ->will($this->returnValue($payment_changed_time));
    $payment->expects($this->any())
      ->method('getCurrencyCode')
      ->will($this->returnValue($payment_currency_code));
    $payment->expects($this->any())
      ->method('getOwner')
      ->will($this->returnValue($owner));
    $payment->expects($this->any())
      ->method('getPaymentMethod')
      ->will($this->returnValue($payment_method));
    $payment->expects($this->any())
      ->method('getStatus')
      ->will($this->returnValue($payment_status));

    $currency = $this->getMockBuilder('\Drupal\currency\Entity\Currency')
      ->disableOriginalConstructor()
      ->getMock();
    $currency->expects($this->once())
      ->method('formatAmount')
      ->with($payment_amount)
      ->will($this->returnValue($payment_amount_formatted));

    $this->currencyStorage->expects($this->once())
      ->method('load')
      ->with($payment_currency_code)
      ->will($this->returnValue($currency));

    $this->date->expects($this->once())
      ->method('format')
      ->with($payment_changed_time)
      ->will($this->returnValue($payment_changed_time_formatted));

    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValue(array()));

    $build = $this->listBuilder->buildRow($payment);
    unset($build['data']['operations']['data']['#attached']);
    $expected_build = array(
      'data' => array(
        'updated' => $payment_changed_time_formatted,
        'status' => $payment_status_definition['label'],
        'amount' => $payment_amount_formatted,
        'payment_method' => $payment_method_label,
        'owner' => array(
          'data' => array(
            '#theme' => 'username',
            '#account' => $owner,
          )
        ),
        'operations' => array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(),
          ),
        ),
      ),
    );
    $this->assertSame($expected_build, $build);
  }

  /**
   * @covers ::render
   *
   * @depends testBuildHeader
   */
  public function testRender() {
    $this->entityStorage->expects($this->once())
      ->method('loadMultiple')
      ->will($this->returnValue(array()));

    $build = $this->listBuilder->render();
    unset($build['#header']);
    $expected_build = array(
      '#type' => 'table',
      '#title' => NULL,
      '#rows' => array(),
      '#empty' => 'There are no payments yet.',
    );
    $this->assertSame($expected_build, $build);
  }

  /**
   * @covers ::getDefaultOperations
   */
  public function testGetDefaultOperationsWithoutAccess() {
    $method = new \ReflectionMethod($this->listBuilder, 'getDefaultOperations');
    $method->setAccessible(TRUE);

    $payment = $this->getMockBuilder('\Drupal\payment\Entity\Payment')
      ->disableOriginalConstructor()
      ->getMock();

    $operations = $method->invoke($this->listBuilder, $payment);
    $this->assertEmpty($operations);
  }

  /**
   * @covers ::getDefaultOperations
   */
  public function testGetDefaultOperationsWithAccess() {
    $method = new \ReflectionMethod($this->listBuilder, 'getDefaultOperations');
    $method->setAccessible(TRUE);

    $url_canonical = new Url($this->randomName());
    $url_update_status_form = new Url($this->randomName());
    $url_capture_form = new Url($this->randomName());

    $payment = $this->getMockBuilder('\Drupal\payment\Entity\Payment')
      ->disableOriginalConstructor()
      ->getMock();
    $map = array(
      array('view', NULL, TRUE),
      array('update_status', NULL, TRUE),
      array('capture', NULL, TRUE),
    );
    $payment->expects($this->any())
      ->method('access')
      ->will($this->returnValueMap($map));
    $map = array(
      array('canonical', $url_canonical),
      array('update-status-form', $url_update_status_form),
      array('capture-form', $url_capture_form),
    );
    $payment->expects($this->any())
      ->method('urlInfo')
      ->will($this->returnValueMap($map));

    $destination = $this->randomName();
    /** @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject $request */
    $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
    $request->attributes = new ParameterBag();
    $request->attributes->set('_system_path', $destination);
    $this->requestStack->expects($this->atLeastOnce())
      ->method('getCurrentRequest')
      ->will($this->returnValue($request));

    $operations = $method->invoke($this->listBuilder, $payment);
    $expected_operations = array(
      'view' => array(
        'title' => 'View',
        'weight' => -10,
        'route_name' => $url_canonical->getRouteName(),
        'route_parameters' => array(),
        'options' => array(),
      ),
      'update_status' => array(
        'title' => 'Update status',
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
        ),
        'query' => array(
          'destination' => $destination,
        ),
        'route_name' => $url_update_status_form->getRouteName(),
        'route_parameters' => array(),
        'options' => array(),
      ),
      'capture' => array(
        'title' => 'Capture',
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-accepts' => 'application/vnd.drupal-modal',
        ),
        'query' => array(
          'destination' => $destination,
        ),
        'route_name' => $url_capture_form->getRouteName(),
        'route_parameters' => array(),
        'options' => array(),
      ),
    );
    $this->assertSame($expected_operations, $operations);
  }

}

}

namespace {

  if (!defined('RESPONSIVE_PRIORITY_LOW')) {
    define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
  }
  if (!defined('RESPONSIVE_PRIORITY_MEDIUM')) {
    define('RESPONSIVE_PRIORITY_MEDIUM', 'priority-medium');
  }

}