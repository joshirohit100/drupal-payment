<?php

/**
 * @file
 * Contains \Drupal\Tests\payment\Unit\PaymentStatusesDisplayUnitTest.
 */

namespace Drupal\Tests\payment\Unit\Element;

use Drupal\payment\Element\PaymentStatusesDisplay;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Element\PaymentStatusesDisplay
 *
 * @group Payment
 */
class PaymentStatusesDisplayUnitTest extends UnitTestCase {

  /**
   * The fate formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dateFormatter;

  /**
   * The element under test.
   *
   * @var \Drupal\payment\Element\PaymentStatusesDisplay
   */
  protected $element;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->dateFormatter = $this->getMockBuilder('\Drupal\Core\Datetime\DateFormatter')
      ->disableOriginalConstructor()
      ->getMock();

    $this->stringTranslation = $this->getStringTranslationStub();

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->element = new PaymentStatusesDisplay($configuration, $plugin_id, $plugin_definition, $this->stringTranslation, $this->dateFormatter);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
    $map = array(
      array('date.formatter', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->dateFormatter),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($map));

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $element = PaymentStatusesDisplay::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf('\Drupal\payment\Element\PaymentStatusesDisplay', $element);
  }

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $info = $this->element->getInfo();
    $this->assertInternalType('array', $info);
    foreach ($info['#pre_render'] as $callback) {
      $this->assertTrue(is_callable($callback));
    }
  }

  /**
   * @covers ::preRender
   */
  public function testPreRender() {
    $payment_status_created = mt_rand();
    $payment_status = $this->getMock('\Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface');
    $payment_status->expects($this->atLeastOnce())
      ->method('getCreated')
      ->willReturn($payment_status_created);

    $this->dateFormatter->expects($this->once())
      ->method('format')
      ->with($payment_status_created);

    $element = array(
      '#payment_statuses' => [$payment_status],
    );

    $build = $this->element->preRender($element);
    $this->assertSame('table', $build['table']['#type']);
  }

  /**
   * @covers ::preRender
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPreRenderWithoutPayment() {
    $element = [];

    $this->element->preRender($element);
  }

}
