<?php declare(strict_types = 1);

namespace MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Fields;

if (!defined('ABSPATH')) exit;


use DateTime;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Payloads\WooCommerceBookingPayload;
use MailPoet\WooCommerce\WooCommerceBookings\Helper as WCBookingsHelper;

class BookingFields {

  private WCBookingsHelper $wcBookings;

  public function __construct(
    WCBookingsHelper $wcBooking
  ) {
    $this->wcBookings = $wcBooking;
  }

  /**
   * @return Field[]
   */
  public function getFields(): array {
    return [
      new Field(
        'woocommerce-bookings:booking:id',
        Field::TYPE_INTEGER,
        __('Woo Booking ID', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          return $payload->getBooking()->get_id();
        }
      ),
      new Field(
        'woocommerce-bookings:booking:created',
        Field::TYPE_DATETIME,
        __('Woo Booking created', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          return $payload->getBooking()->get_date_created();
        }
      ),
      new Field(
        'woocommerce-bookings:booking:modified',
        Field::TYPE_DATETIME,
        __('Woo Booking modified date', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          return $payload->getBooking()->get_date_modified();
        }
      ),
      new Field(
        'woocommerce-bookings:booking:status',
        Field::TYPE_ENUM,
        __('Woo Booking status', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          return $payload->getBooking()->get_status();
        },
        [
          'options' => $this->wcBookings->getBookingStatuses(),
        ]
      ),
      new Field(
        'woocommerce-bookings:booking:persons',
        Field::TYPE_NUMBER,
        __('Woo Booking persons count', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          return $payload->getBooking()->get_persons();
        }
      ),
      new Field(
        'woocommerce-bookings:booking:all-day',
        Field::TYPE_BOOLEAN,
        __('Woo Booking all day', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          return $payload->getBooking()->get_all_day();
        }
      ),
      new Field(
        'woocommerce-bookings:booking:start',
        Field::TYPE_DATETIME,
        __('Woo Booking start', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          $timestamp = $payload->getBooking()->get_start();
          return $timestamp ? new DateTime('@' . $timestamp) : null;
        }
      ),
      new Field(
        'woocommerce-bookings:booking:end',
        Field::TYPE_DATETIME,
        __('Woo Booking end', 'mailpoet-premium'),
        function(WooCommerceBookingPayload $payload) {
          $timestamp = $payload->getBooking()->get_end();
          return $timestamp ? new DateTime('@' . $timestamp) : null;
        }
      ),
    ];
  }
}
