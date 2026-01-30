<?php

class ExcessiveInlineCode
{
    public function checkout(Request $request) {
        $items = $request->input('items');
        $total = 0;

        foreach ($items as $item) {
            $price = $item['price'];
            $quantity = $item['quantity'];
            $discount = 0;

            if ($quantity > 10) {
                $discount = 0.1;
            } elseif ($quantity > 5) {
                $discount = 0.05;
            }

            $subtotal = ($price * $quantity) * (1 - $discount);
            $total += $subtotal;
        }

        // salva pedido
        DB::table('orders')->insert([
            'user_id' => auth()->id(),
            'total' => $total,
            'created_at' => now()
        ]);

        // envia email
        Mail::to(auth()->user()->email)->send(new OrderConfirmed($total));
    }
}
