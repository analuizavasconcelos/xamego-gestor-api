<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return Order::with(['items.product', 'deliveryZone'])
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'     => 'nullable|string|max:255',
            'customer_phone'    => 'nullable|string|max:30',
            'delivery_address'  => 'nullable|string',
            'delivery_type'     => 'required|in:retirada,entrega',
            'delivery_zone_id'  => 'nullable|exists:delivery_zones,id',
            'courier_name'      => 'nullable|string|max:100',
            'payment_method'    => 'required|in:dinheiro,pix,cartao',
            'change_for'        => 'nullable|numeric|min:0',
            'status'            => 'nullable|in:pendente,em_preparo,saiu_para_entrega,entregue,cancelado',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $totalProductsPrice = 0;
            $totalProfit = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->current_stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Estoque insuficiente para {$product->name}. Disponível: {$product->current_stock}",
                    ]);
                }

                $unitPrice = $product->current_price;
                $unitCost = $product->current_cost;
                $quantity = $item['quantity'];
                $itemTotalPrice = $unitPrice * $quantity;
                $itemTotalProfit = ($unitPrice - $unitCost) * $quantity;

                $totalProductsPrice += $itemTotalPrice;
                $totalProfit += $itemTotalProfit;

                $itemsData[] = compact('product', 'quantity', 'unitPrice', 'unitCost', 'itemTotalPrice', 'itemTotalProfit');
            }

            // Definição das taxas de entrega a partir do bairro cadastrado
            $deliveryFee = 0;
            $courierFee = 0;
            $zoneId = null;

            if ($validated['delivery_type'] === 'entrega' && !empty($validated['delivery_zone_id'])) {
                $zone = DeliveryZone::find($validated['delivery_zone_id']);
                if ($zone) {
                    $zoneId = $zone->id;
                    $deliveryFee = (float) $zone->customer_fee;
                    $courierFee = (float) $zone->courier_fee;
                }
            }

            $order = Order::create([
                'customer_name'    => $validated['customer_name'] ?? null,
                'customer_phone'   => $validated['customer_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'delivery_type'    => $validated['delivery_type'],
                'delivery_zone_id' => $zoneId,
                'delivery_fee'     => $deliveryFee,
                'courier_fee'      => $courierFee,
                'courier_name'     => $validated['courier_name'] ?? null,
                'courier_settled'  => false,
                'payment_method'   => $validated['payment_method'],
                'change_for'       => $validated['payment_method'] === 'dinheiro' ? ($validated['change_for'] ?? null) : null,
                'total_price'      => $totalProductsPrice + $deliveryFee,
                'total_profit'     => $totalProfit,
                'status'           => $validated['status'] ?? 'pendente',
                'delivered_at'     => ($validated['status'] ?? '') === 'entregue' ? now() : null,
                'user_id'          => $request->user()?->id,
            ]);

            foreach ($itemsData as $data) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $data['product']->id,
                    'quantity'     => $data['quantity'],
                    'unit_price'   => $data['unitPrice'],
                    'unit_cost'    => $data['unitCost'],
                    'total_price'  => $data['itemTotalPrice'],
                    'total_profit' => $data['itemTotalProfit'],
                ]);

                $data['product']->decrement('current_stock', $data['quantity']);
            }

            return response()->json($order->load(['items.product', 'deliveryZone']), 201);
        });
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_name'     => 'nullable|string|max:255',
            'customer_phone'    => 'nullable|string|max:30',
            'delivery_address'  => 'nullable|string',
            'delivery_type'     => 'required|in:retirada,entrega',
            'delivery_zone_id'  => 'nullable|exists:delivery_zones,id',
            'courier_name'      => 'nullable|string|max:100',
            'payment_method'    => 'required|in:dinheiro,pix,cartao',
            'change_for'        => 'nullable|numeric|min:0',
            'status'            => 'required|in:pendente,em_preparo,saiu_para_entrega,entregue,cancelado',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $order) {
            // Devolve itens antigos ao estoque
            foreach ($order->items as $oldItem) {
                $product = Product::lockForUpdate()->find($oldItem->product_id);
                if ($product) {
                    $product->increment('current_stock', $oldItem->quantity);
                }
            }
            $order->items()->delete();

            // Valida novos itens e recalcula
            $totalProductsPrice = 0;
            $totalProfit = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->current_stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Estoque insuficiente para {$product->name}. Disponível: {$product->current_stock}",
                    ]);
                }

                $unitPrice = $product->current_price;
                $unitCost = $product->current_cost;
                $quantity = $item['quantity'];
                $itemTotalPrice = $unitPrice * $quantity;
                $itemTotalProfit = ($unitPrice - $unitCost) * $quantity;

                $totalProductsPrice += $itemTotalPrice;
                $totalProfit += $itemTotalProfit;

                $itemsData[] = compact('product', 'quantity', 'unitPrice', 'unitCost', 'itemTotalPrice', 'itemTotalProfit');
            }

            $deliveryFee = 0;
            $courierFee = 0;
            $zoneId = null;

            if ($validated['delivery_type'] === 'entrega' && !empty($validated['delivery_zone_id'])) {
                $zone = DeliveryZone::find($validated['delivery_zone_id']);
                if ($zone) {
                    $zoneId = $zone->id;
                    $deliveryFee = (float) $zone->customer_fee;
                    $courierFee = (float) $zone->courier_fee;
                }
            }

            $deliveredAt = $order->delivered_at;
            if ($validated['status'] === 'entregue' && !$deliveredAt) {
                $deliveredAt = now();
            } elseif ($validated['status'] !== 'entregue') {
                $deliveredAt = null;
            }

            $order->update([
                'customer_name'    => $validated['customer_name'] ?? null,
                'customer_phone'   => $validated['customer_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'delivery_type'    => $validated['delivery_type'],
                'delivery_zone_id' => $zoneId,
                'delivery_fee'     => $deliveryFee,
                'courier_fee'      => $courierFee,
                'courier_name'     => $validated['courier_name'] ?? null,
                'payment_method'   => $validated['payment_method'],
                'change_for'       => $validated['payment_method'] === 'dinheiro' ? ($validated['change_for'] ?? null) : null,
                'total_price'      => $totalProductsPrice + $deliveryFee,
                'total_profit'     => $totalProfit,
                'status'           => $validated['status'],
                'delivered_at'     => $deliveredAt,
            ]);

            foreach ($itemsData as $data) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $data['product']->id,
                    'quantity'     => $data['quantity'],
                    'unit_price'   => $data['unitPrice'],
                    'unit_cost'    => $data['unitCost'],
                    'total_price'  => $data['itemTotalPrice'],
                    'total_profit' => $data['itemTotalProfit'],
                ]);

                $data['product']->decrement('current_stock', $data['quantity']);
            }

            return response()->json($order->load(['items.product', 'deliveryZone']));
        });
    }

    public function destroy(Order $order)
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->increment('current_stock', $item->quantity);
                }
            }

            $order->delete();

            return response()->json(['message' => 'Pedido excluído e estoque estornado.']);
        });
    }
}