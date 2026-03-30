<?php declare(strict_types = 1);

namespace MailPoet\Premium\Automation\Integrations\WooCommerceBookings;

if (!defined('ABSPATH')) exit;


use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Subjects\WooCommerceBookingStatusChangeSubject;
use MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Subjects\WooCommerceBookingSubject;
use MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Triggers\BookingCreatedTrigger;
use MailPoet\Premium\Automation\Integrations\WooCommerceBookings\Triggers\BookingStatusChangedTrigger;

class WooCommerceBookingsIntegration implements Integration {

  private BookingCreatedTrigger $bookingCreated;
  private BookingStatusChangedTrigger $bookingStatusChanged;
  private ContextFactory $contextFactory;
  private WooCommerceBookingSubject $wooCommerceBookingSubject;
  private WooCommerceBookingStatusChangeSubject $wooCommerceBookingStatusChangeSubject;

  public function __construct(
    ContextFactory $contextFactory,
    BookingCreatedTrigger $bookingCreated,
    BookingStatusChangedTrigger $bookingStatusChanged,
    WooCommerceBookingSubject $wooCommerceBookingSubject,
    WooCommerceBookingStatusChangeSubject $wooCommerceBookingStatusChangeSubject
  ) {
    $this->contextFactory = $contextFactory;
    $this->bookingCreated = $bookingCreated;
    $this->bookingStatusChanged = $bookingStatusChanged;
    $this->wooCommerceBookingSubject = $wooCommerceBookingSubject;
    $this->wooCommerceBookingStatusChangeSubject = $wooCommerceBookingStatusChangeSubject;
  }

  public function register(Registry $registry): void {
    $registry->addContextFactory('woocommerce-bookings', function () {
      return $this->contextFactory->getContextData();
    });

    $registry->addTrigger($this->bookingCreated);
    $registry->addTrigger($this->bookingStatusChanged);
    $registry->addSubject($this->wooCommerceBookingSubject);
    $registry->addSubject($this->wooCommerceBookingStatusChangeSubject);
  }
}
