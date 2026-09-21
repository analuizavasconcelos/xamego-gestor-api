<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CourierSettlementController extends Controller
{
    /**
     * Resumo diário de entregas e valores a acertar
     */
    public function dailySummary(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $courierName = $request->query('courier_name');

        $query = Order::query()
            ->whereDate('created_at', $date)
            ->where('status', 'entregue')
            ->when($courierName, fn($q) => $q->where('courier_name', $courierName));

        $orders = $query->get();

        $totalDeliveries = $orders->count();
        $totalFeesToPay = (float) $orders->sum('courier_fee');

        // Total de pedidos pagos em dinheiro diretamente para o motoboy
        $cashCollected = (float) $orders->where('payment_method', 'dinheiro')->sum(function ($order) {
            return $order->total_price; // Total do pedido (itens + taxa de entrega)
        });

        // Saldo líquido: se positivo, motoboy devolve dinheiro. Se negativo, a loja paga ao motoboy.
        $netBalance = $cashCollected - $totalFeesToPay;

        return response()->json([
            'date' => $date,
            'courier_name' => $courierName ?? 'Todos',
            'total_deliveries' => $totalDeliveries,
            'total_fees_to_pay' => $totalFeesToPay,
            'cash_collected' => $cashCollected,
            'net_balance' => $netBalance,
            'settled_all' => $orders->every(fn($o) => $o->courier_settled),
            'orders' => $orders
        ]);
    }

    /**
     * Marca todas as entregas do dia como acertadas/pagas
     */
    public function settle(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'courier_name' => 'nullable|string',
        ]);

        Order::query()
            ->whereDate('created_at', $request->date)
            ->where('status', 'entregue')
            ->when($request->courier_name, fn($q) => $q->where('courier_name', $request->courier_name))
            ->update(['courier_settled' => true]);

        return response()->json(['message' => 'Acerto realizado com sucesso!']);
    }
}