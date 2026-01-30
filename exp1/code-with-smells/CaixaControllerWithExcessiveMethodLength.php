<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caixa;
use App\Models\Entrada_caixa;
use App\Models\Estoque;
use App\Models\Sangria;
use App\Models\Sistema;
use App\Models\Transacoes;
use App\Models\Venda;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaixaControllerWithExcessiveMethodLength extends Controller
{
    public function __construct(
        private readonly Caixa $caixaModel,
        private readonly Transacoes $transacoesModel,
        private readonly Sistema $sistemaModel,
        private readonly Sangria $sangriaModel,
        private readonly Entrada_caixa $entradaCaixaModel,
        private readonly Venda $vendaModel,
        private readonly Estoque $estoqueModel
    ) {
    }

    public function iniciarCaixaView(): Factory|Application|View
    {
        $caixa = $this->caixaModel->today();

        return view('admin.caixa.abrir', [
            'aberto' => $this->caixaModel->checkOpen(),
            'caixa' => $caixa
        ]);
    }

    public function iniciarCaixa(Request $request)
    {
        $caixa = $this->caixaModel;

        if (!$caixa->checkOpen() && $caixa == null) {
            try {
                $caixa = $this->caixaModel;
                $caixa->data = date('Y-m-d');
                $caixa->inicial = str_replace(['.', ','], ['', '.'], $request->valor);
                $caixa->valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $this->sistemaModel->setVal('caixa_aberto', true);
                $caixa->save();
                return response()->json([
                    'success' => 'true',
                    'message' => 'Caixa foi aberto'
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'success' => 'false',
                    'message' => $e->errorInfo[2]
                ]);
            }
        }

        return view('admin.caixa.abrir', ['aberto' => $caixa->checkOpen()]);
    }

    public function fecharCaixaView()
    {

        $transacoes = $this->transacoesModeltoday();
        $sangria = $this->sangriaModel->today();
        $entradas = $this->entradaCaixaModel->today();
        foreach ($sangria as $sang) {
            $sang->valor = number_format($sang->valor, 2, ',', '.');
        }
        foreach ($entradas as $entrada) {
            $entrada->valor = number_format($entrada->valor, 2, ',', '.');
        }
        $detalhes = '';

        foreach ($transacoes as $value) {
            $transacoesId = $value->id;
            $venda = $this->vendaModel->where('transacao', '=', $transacoesId)->get();
            $value->pagamento = str_replace(
                ['DI', 'CR', 'DE'],
                ['Dinheiro', 'Cartão de Crédito', 'Débito'],
                $value->pagamento
            );

            $value->desconto = $value->desconto . '%';
            $value->total = number_format($value->total, 2, ',', '.');
            foreach ($venda as $val) {
                $codigo = $val->codigo_estoque;
                $estoque = $this->estoqueModel->where('codigo', '=', $codigo)->first();
                $detalhes = $estoque->nome . ' | ' . $detalhes;
            }
            $value->detalhes = $detalhes;
            $detalhes = '';
        }

        $caixaValor = $this->caixaModel->today() ?: (object)[
            'valor' => '0,00',
            'inicial' => '0,00',
            'totalCredito' => '0,00',
            'totalDebito' => '0,00',
            'totalC' => '0,00'
        ];

        if ($caixaValor !== (object)[/* ... */]) {  // Se não for o objeto padrão
            $caixaValor->valor = number_format($caixaValor->valor, 2, ',', '.');
            $caixaValor->inicial = number_format($caixaValor->inicial, 2, ',', '.');
            $caixaValor->totalCredito = number_format($this->transacoesModel->totalCreditoDay(), 2, ',', '.');
            $caixaValor->totalDebito = number_format($this->transacoesModel->totalDebitoDay(), 2, ',', '.');
            $caixaValor->totalC = number_format(
                $this->transacoesModel->totalDebitoDay() + $this->transacoesModel->totalCreditoDay(),
                2,
                ',',
                '.'
            );
        }

        return view(
            'admin.caixa.fechar',
            [
                'aberto' => $this->caixaModel->checkOpen(),
                'caixaValor' => $caixaValor,
                'transacoes' => $transacoes,
                'sangria' => $sangria,
                'entrada' => $entradas
            ]
        );
    }

    public function sangriaView()
    {
        return view('admin.caixa.sangria', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    public function sangriaPost(Request $request)
    {
        if ($this->caixaModel->checkOpen()) {
            $sangria = new Sangria();
            $sangria->data = $this->data();
            $sangria->descricao = $request->descricao;
            $sangria->valor = str_replace(['.', ','], ['', '.'], $request->valor);
            try {
                if ($sangria->save()) {
                    $this->caixaModel->getOff($sangria->valor);
                    return response()->json([
                        'success' => 'true',
                        'message' => 'Retirado com sucesso, o saldo do caixa é de R$' . number_format(
                            $this->caixaModel->today()->valor,
                            2,
                            ',',
                            '.'
                        )
                    ]);
                }
            } catch (QueryException $e) {
                return $e->errorInfo[2];
            }
        }

        return false;
    }

    protected function data()
    {
        return date('Y-m-d');
    }

    public function addCaixaView($order)
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
                throw new Exception('Produto não encontrado: ' . $item->product_id);
            }

            if ($product->stock < $item->quantity) {
                throw new Exception('Estoque insuficiente para o produto: ' . $product->name);
            }

            // Atualizar estoque
            $product->stock -= $item->quantity;
            $product->save();

            // Registrar movimento de estoque
            $stockLog = new StockLog();
            $stockLog->product_id = $product->id;
            $stockLog->quantity = -$item->quantity;
            $stockLog->reason = 'Venda - Pedido #' . $order->id;
            $stockLog->save();
        }

        // Processar informações de envio
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            throw new Exception('Endereço de entrega não fornecido');
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
                    throw new Exception('Falha no processamento do cartão de crédito: ' . $result->message);
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
                    'description' => 'Pedido #' . $order->id
                ]);

                $order->setPixCode($result->code);
                $order->setPixQrCode($result->qr_code);
                $order->setPaymentStatus('pending');
                break;

            default:
                throw new Exception('Método de pagamento não suportado: ' . $paymentMethod->type);
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
        $orderHistory->comment = 'Pedido criado e processado automaticamente';
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

        return view('admin.caixa.adicionar', ['aberto' => $this->caixaModel->checkOpen()]);
    }
}
