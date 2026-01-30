<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithHighCoupling extends Controller
{
    private InventoryService $inventoryService;
    private PaymentGateway $paymentGateway;
    private ShippingService $shippingService;
    private Logger $logger;
    private EmailService $emailService;
    private DiscountCalculator $discountCalculator;
    private CustomerService $customerService;

    public function __construct() {
        $this->inventoryService = new InventoryService();
        $this->paymentGateway = new PaymentGateway();
        $this->shippingService = new ShippingService();
        $this->logger = new Logger();
        $this->emailService = new EmailService();
        $this->discountCalculator = new DiscountCalculator();
        $this->customerService = new CustomerService();
    }

    public function process(Order $order) {
        $customer = $this->customerService->getCustomer($order->getCustomerId());
        $discount = $this->discountCalculator->calculate($customer, $order);
        $order->applyDiscount($discount);

        $this->inventoryService->reserveItems($order);
        $this->paymentGateway->charge($order->getTotal());
        $this->shippingService->ship($order);
        $this->emailService->sendConfirmation($customer->getEmail(), $order);
        $this->logger->log("Order processed: " . $order->getId());
    }
}
