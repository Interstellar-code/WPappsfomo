<?php declare(strict_types = 1);

namespace MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Payloads;

if (!defined('ABSPATH')) exit;


use MailPoet\Automation\Engine\Integration\Payload;

class WooCommerceBookingPayload implements Payload {

  private \WC_Booking $booking;

  public function __construct(
    \WC_Booking $booking
  ) {
    $this->booking = $booking;
  }

  public function getId(): int {
    return $this->booking->get_id();
  }

  public function getBooking(): \WC_Booking {
    return $this->booking;
  }
}
