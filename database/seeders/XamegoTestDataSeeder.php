<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class XamegoTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa tabelas antigas e novas para evitar conflitos de integridade
        OrderItem::query()->delete();
        Order::query()->delete();
        Sale::query()->delete();
        ProductPriceHistory::query()->delete();
        Product::query()->delete();
        DeliveryZone::query()->delete();

        // 1. Zonas de Entrega (Bairros e Taxas Variáveis)
        $zonas = [
            ['name' => 'Centro',           'customer_fee' => 7.00,  'courier_fee' => 6.00],
            ['name' => 'Candeias',         'customer_fee' => 8.00,  'courier_fee' => 7.00],
            ['name' => 'Alto Maron',       'customer_fee' => 9.00,  'courier_fee' => 8.00],
            ['name' => 'Brasil',           'customer_fee' => 10.00, 'courier_fee' => 9.00],
            ['name' => 'Boa Vista',        'customer_fee' => 12.00, 'courier_fee' => 10.00],
        ];

        $deliveryZones = [];
        foreach ($zonas as $z) {
            $deliveryZones[$z['name']] = DeliveryZone::create($z);
        }

        // 2. Produtos
        $produtos = [
            [
                'name' => 'Pizza Brotinho',
                'size' => '15cm',
                'image_path' => 'brotinho.jpg',
                'current_cost' => 5.50,
                'current_price' => 15.00,
                'current_stock' => 50,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Pizza Média',
                'size' => '25cm',
                'image_path' => 'media.jpg',
                'current_cost' => 9.00,
                'current_price' => 28.00,
                'current_stock' => 40,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Pizza Grande',
                'size' => '30cm',
                'image_path' => 'grande.jpg',
                'current_cost' => 13.00,
                'current_price' => 38.00,
                'current_stock' => 30,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Pizza Família',
                'size' => '35cm',
                'image_path' => 'familia.jpg',
                'current_cost' => 17.00,
                'current_price' => 48.00,
                'current_stock' => 25,
                'low_stock_threshold' => 4,
            ],
        ];

        $created = [];
        foreach ($produtos as $dados) {
            $product = Product::create($dados);

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'cost' => $product->current_cost,
                'price' => $product->current_price,
                'changed_at' => now(),
            ]);

            $created[$product->name] = $product;
            $this->command->info("Produto criado: {$product->name}");
        }

        // 3. Pedidos estruturados (com múltiplos itens e acerto de motoboy)
        $pedidos = [
            [
                'customer_name' => 'Dona Marta',
                'customer_phone' => '77999990001',
                'delivery_address' => 'Rua das Flores, 120 - Candeias',
                'delivery_type' => 'entrega',
                'zone' => 'Candeias',
                'courier_name' => 'Marcos Motoboy',
                'payment_method' => 'pix',
                'change_for' => null,
                'status' => 'entregue',
                'courier_settled' => true,
                'days_ago' => 2,
                'items' => [
                    ['product' => $created['Pizza Brotinho'], 'quantity' => 2],
                    ['product' => $created['Pizza Média'],    'quantity' => 1],
                ]
            ],
            [
                'customer_name' => 'Seu João',
                'customer_phone' => '77999990002',
                'delivery_address' => null,
                'delivery_type' => 'retirada',
                'zone' => null,
                'courier_name' => null,
                'payment_method' => 'dinheiro',
                'change_for' => 50.00,
                'status' => 'entregue',
                'courier_settled' => true,
                'days_ago' => 1,
                'items' => [
                    ['product' => $created['Pizza Média'], 'quantity' => 1],
                ]
            ],
            [
                'customer_name' => 'Carla Mendes',
                'customer_phone' => '77999990003',
                'delivery_address' => 'Av. Olívia Flores, 450 - Candeias',
                'delivery_type' => 'entrega',
                'zone' => 'Candeias',
                'courier_name' => 'Marcos Motoboy',
                'payment_method' => 'dinheiro',
                'change_for' => 100.00,
                'status' => 'entregue',
                'courier_settled' => false, // Pedido de hoje: motoboy ainda tem que prestar contas
                'days_ago' => 0,
                'items' => [
                    ['product' => $created['Pizza Grande'], 'quantity' => 1],
                    ['product' => $created['Pizza Brotinho'], 'quantity' => 2],
                ]
            ],
            [
                'customer_name' => 'Rafael Silva',
                'customer_phone' => '77999990004',
                'delivery_address' => 'Rua Siqueira Campos, 89 - Centro',
                'delivery_type' => 'entrega',
                'zone' => 'Centro',
                'courier_name' => 'Marcos Motoboy',
                'payment_method' => 'cartao',
                'change_for' => null,
                'status' => 'entregue',
                'courier_settled' => false,
                'days_ago' => 0,
                'items' => [
                    ['product' => $created['Pizza Família'], 'quantity' => 1],
                ]
            ],
        ];

        foreach ($pedidos as $p) {
            $zone = $p['zone'] ? $deliveryZones[$p['zone']] : null;
            $deliveryFee = $zone ? $zone->customer_fee : 0;
            $courierFee = $zone ? $zone->courier_fee : 0;

            $itemsTotal = 0;
            $itemsProfit = 0;

            foreach ($p['items'] as $it) {
                $prod = $it['product'];
                $qty = $it['quantity'];
                $itemsTotal += $prod->current_price * $qty;
                $itemsProfit += ($prod->current_price - $prod->current_cost) * $qty;
            }

            $createdAt = now()->subDays($p['days_ago']);

            $order = Order::create([
                'customer_name'    => $p['customer_name'],
                'customer_phone'   => $p['customer_phone'],
                'delivery_address' => $p['delivery_address'],
                'delivery_type'    => $p['delivery_type'],
                'delivery_zone_id' => $zone?->id,
                'delivery_fee'     => $deliveryFee,
                'courier_fee'      => $courierFee,
                'courier_name'     => $p['courier_name'],
                'courier_settled'  => $p['courier_settled'],
                'payment_method'   => $p['payment_method'],
                'change_for'       => $p['change_for'],
                'total_price'      => $itemsTotal + $deliveryFee,
                'total_profit'     => $itemsProfit,
                'status'           => $p['status'],
                'delivered_at'     => $p['status'] === 'entregue' ? $createdAt : null,
            ]);

            $order->created_at = $createdAt;
            $order->updated_at = $createdAt;
            $order->saveQuietly();

            foreach ($p['items'] as $it) {
                $prod = $it['product'];
                $qty = $it['quantity'];

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $prod->id,
                    'quantity'   => $qty,
                    'unit_price' => $prod->current_price,
                    'unit_cost'  => $prod->current_cost,
                    'total'      => $prod->current_price * $qty,
                ]);

                $prod->decrement('current_stock', $qty);
            }

            $this->command->info("Pedido #{$order->id} criado para {$order->customer_name}");
        }

        $this->command->info('Dados de teste de pedidos e motoboy gerados com sucesso!');
    }
}