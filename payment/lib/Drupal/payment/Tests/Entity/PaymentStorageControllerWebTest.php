<?php

/**
 * @file
 * Contains class \Drupal\payment\Tests\Entity\PaymentStorageControllerWebTest.
 */

namespace Drupal\payment\Tests\Entity;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Payment;
use Drupal\payment\Plugin\payment\type\PaymentTypeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests \Drupal\payment\Entity\PaymentStorageController.
 */
class PaymentStorageControllerWebTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\payment\Entity\PaymentStorageController web test',
      'group' => 'Payment',
    );
  }

  /**
   * Tests CRUD();
   */
  protected function testCRUD() {
    $database = \Drupal::database();

    // Test creating a payment.
    $payment = entity_create('payment', array(
      'bundle' => 'payment_unavailable',
    ));
    $this->assertTrue($payment instanceof PaymentInterface);
    $this->assertTrue(is_int($payment->getOwnerId()));
    $this->assertEqual(count($payment->validate()), 0);
    $this->assertTrue($payment->getPaymentType() instanceof PaymentTypeInterface);

    // Test saving a payment.
    $this->assertFalse($payment->id());
    // Set an extra status, so we can test for status IDs later.
    $payment->setStatus(Payment::statusManager()->createInstance('payment_success'));
    $payment->save();
    // @todo The ID should be an integer, but for some reason the entity field
    //   API returns a string.
    $this->assertTrue(is_numeric($payment->id()));
    $this->assertTrue(strlen($payment->uuid()));
    $this->assertTrue(is_int($payment->getOwnerId()));
    // Check references to other tables.
    $payment_data = $database->select('payment', 'p')
      ->fields('p', array('first_payment_status_id', 'last_payment_status_id'))
      ->condition('id', $payment->id())
      ->execute()
      ->fetchAssoc();
    $this->assertEqual($payment_data['first_payment_status_id'], 1);
    $this->assertEqual($payment_data['last_payment_status_id'], 2);
    $payment_loaded = entity_load_unchanged('payment', $payment->id());
    $this->assertEqual(count($payment_loaded->getLineItems()), count($payment->getLineItems()));
    $this->assertEqual(count($payment_loaded->getStatuses()), count($payment->getStatuses()));

    // Test deleting a payment.
    $payment->delete();
    $this->assertFalse(entity_load('payment', $payment->id()));
    $line_items_exists = db_select('payment_line_item')
      ->condition('payment_id', $payment->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertFalse($line_items_exists);
    $statuses_exist = db_select('payment_status')
      ->condition('payment_id', $payment->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertFalse($statuses_exist);
  }
}