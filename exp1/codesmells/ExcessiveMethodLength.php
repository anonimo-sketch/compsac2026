<?php

/**
 * Code Smell: Excessive Method Length
 * Descrição: Método com tamanho excessivo, indicando possível responsabilidade excessiva
 */

function processOrder($order)
{
    $items = $order->getItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item->price * $item->quantity;
    }
    $tax = $total * 0.1;
    $discount = $order->getDiscount();
    $finalTotal = $total + $tax - $discount;
    $order->setTotal($finalTotal);
    $order->save();

    // Verificar estoque para cada item
    foreach ($items as $item) {
        $product = Product::findById($item->product_id);
        if (!$product) {
            throw new Exception("Produto não encontrado: " . $item->product_id);
        }

        if ($product->stock < $item->quantity) {
            throw new Exception("Estoque insuficiente para o produto: " . $product->name);
        }

        // Atualizar estoque
        $product->stock -= $item->quantity;
        $product->save();

        // Registrar movimento de estoque
        $stockLog = new StockLog();
        $stockLog->product_id = $product->id;
        $stockLog->quantity = -$item->quantity;
        $stockLog->reason = "Venda - Pedido #" . $order->id;
        $stockLog->save();
    }

    // Processar informações de envio
    $shippingAddress = $order->getShippingAddress();
    if (!$shippingAddress) {
        throw new Exception("Endereço de entrega não fornecido");
    }

    // Calcular custo de envio baseado no CEP
    $zipCode = $shippingAddress->zip_code;
    $shippingCost = calculateShippingCost($zipCode, $total);
    $order->setShippingCost($shippingCost);
    $order->save();

    // Atualizar total com custo de envio
    $finalTotal += $shippingCost;
    $order->setTotal($finalTotal);
    $order->save();

    // Processar pagamento
    $paymentMethod = $order->getPaymentMethod();
    switch ($paymentMethod->type) {
        case 'credit_card':
            $paymentProcessor = new CreditCardProcessor();
            $result = $paymentProcessor->process([
                'card_number' => $paymentMethod->card_number,
                'expiry_date' => $paymentMethod->expiry_date,
                'cvv' => $paymentMethod->cvv,
                'name' => $paymentMethod->name,
                'amount' => $finalTotal
            ]);

            if (!$result->success) {
                throw new Exception("Falha no processamento do cartão de crédito: " . $result->message);
            }

            $order->setPaymentConfirmation($result->transaction_id);
            break;

        case 'bank_transfer':
            $bankProcessor = new BankTransferProcessor();
            $result = $bankProcessor->generateReference([
                'account' => $paymentMethod->account,
                'branch' => $paymentMethod->branch,
                'amount' => $finalTotal
            ]);

            $order->setPaymentReference($result->reference);
            $order->setPaymentStatus('pending');
            break;

        case 'pix':
            $pixProcessor = new PixProcessor();
            $result = $pixProcessor->generate([
                'amount' => $finalTotal,
                'description' => "Pedido #" . $order->id
            ]);

            $order->setPixCode($result->code);
            $order->setPixQrCode($result->qr_code);
            $order->setPaymentStatus('pending');
            break;

        default:
            throw new Exception("Método de pagamento não suportado: " . $paymentMethod->type);
    }

    // Atualizar status do pedido
    if ($order->getPaymentStatus() === 'pending') {
        $order->setStatus('awaiting_payment');
    } else {
        $order->setStatus('processing');
    }
    $order->save();

    // Registrar histórico do pedido
    $orderHistory = new OrderHistory();
    $orderHistory->order_id = $order->id;
    $orderHistory->status = $order->getStatus();
    $orderHistory->comment = "Pedido criado e processado automaticamente";
    $orderHistory->save();

    // Notificar cliente
    $customer = $order->getCustomer();
    $emailService = new EmailService();
    $emailService->sendOrderConfirmation(
        $customer->email,
        $order->id,
        $order->getItems(),
        $finalTotal,
        $order->getStatus(),
        $order->getExpectedDeliveryDate()
    );

    // Notificar departamento de vendas
    $emailService->sendInternalNotification(
        'vendas@empresa.com',
        'Novo Pedido - #' . $order->id,
        [
            'customer' => $customer->name,
            'total' => $finalTotal,
            'items' => count($items)
        ]
    );

    // Registrar atividade para análise de dados
    $analyticsService = new AnalyticsService();
    $analyticsService->trackOrder([
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'total' => $finalTotal,
        'items' => array_map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'price' => $item->price,
                'quantity' => $item->quantity
            ];
        }, $items),
        'payment_method' => $paymentMethod->type
    ]);

    // Verificar e processar cupons promocionais
    if ($order->hasCoupon()) {
        $coupon = $order->getCoupon();
        $coupon->incrementUsageCount();

        // Verificar limite de uso do cupom
        if ($coupon->hasReachedUsageLimit()) {
            $coupon->deactivate();
        }

        $coupon->save();
    }

    // Adicionar pontos de fidelidade ao cliente
    $loyaltyPoints = calculateLoyaltyPoints($finalTotal);
    $customer->addLoyaltyPoints($loyaltyPoints);
    $customer->save();

    // Registrar comissão para o vendedor, se aplicável
    if ($order->hasSalesperson()) {
        $salesperson = $order->getSalesperson();
        $commission = calculateCommission($finalTotal, $salesperson->commission_rate);

        $commissionEntry = new CommissionEntry();
        $commissionEntry->salesperson_id = $salesperson->id;
        $commissionEntry->order_id = $order->id;
        $commissionEntry->amount = $commission;
        $commissionEntry->status = 'pending';
        $commissionEntry->save();
    }

    return [
        'success' => true,
        'order_id' => $order->id,
        'status' => $order->getStatus(),
        'total' => $finalTotal
    ];
}
