<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function profit(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfMonth();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        $orders = Order::with('items.product')->whereBetween('created_at', [$start, $end])->get();
        $items = $orders->flatMap->items;

        $byProduct = $items->groupBy('product_id')->map(function ($group) {
            $product = $group->first()->product;
            return [
                'product_name' => $product->name,
                'product_size' => $product->size,
                'quantity_sold' => $group->sum('quantity'),
                'total_revenue' => $group->sum('total_price'),
                'total_profit' => $group->sum('total_profit'),
            ];
        })->values();

        return response()->json([
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'total_revenue' => $orders->sum('total_price'),
            'total_cost' => $items->sum(fn ($i) => $i->unit_cost * $i->quantity),
            'total_profit' => $orders->sum('total_profit'),
            'total_units_sold' => $items->sum('quantity'),
            'by_product' => $byProduct,
        ]);
    }

    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        $orders = Order::with('items.product')->whereBetween('created_at', [$start, $end])->get();
        $items = $orders->flatMap->items;

        $byProduct = $items->groupBy('product_id')->map(function ($group) {
            $product = $group->first()->product;
            return [
                'product_name' => $product->name,
                'product_size' => $product->size,
                'quantity_sold' => $group->sum('quantity'),
                'total_revenue' => $group->sum('total_price'),
                'total_profit' => $group->sum('total_profit'),
            ];
        })->values();

        $topByQuantity = $byProduct->sortByDesc('quantity_sold')->values();
        $topByProfit = $byProduct->sortByDesc('total_profit')->values();

        $weekdayNames = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $byWeekday = $orders->groupBy(fn ($o) => $o->created_at->dayOfWeek)
            ->map(function ($group, $day) use ($weekdayNames) {
                return [
                    'weekday' => $weekdayNames[$day],
                    'quantity_sold' => $group->sum(fn ($o) => $o->items->sum('quantity')),
                    'total_revenue' => $group->sum('total_price'),
                ];
            })->sortByDesc('total_revenue')->values();

        $byDay = $orders->groupBy(fn ($o) => $o->created_at->toDateString())
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'quantity_sold' => $group->sum(fn ($o) => $o->items->sum('quantity')),
                    'total_revenue' => $group->sum('total_price'),
                    'total_profit' => $group->sum('total_profit'),
                ];
            })->sortBy('date')->values();

        $bestDay = $byDay->sortByDesc('total_revenue')->first();

        $deliveries = $orders->where('delivery_type', 'entrega');
        $pickups = $orders->where('delivery_type', 'retirada');

        return response()->json([
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'top_products_by_quantity' => $topByQuantity->take(5),
            'top_products_by_profit' => $topByProfit->take(5),
            'sales_by_weekday' => $byWeekday,
            'sales_by_day' => $byDay,
            'best_day' => $bestDay,
            'delivery' => [
                'total_deliveries' => $deliveries->count(),
                'total_pickups' => $pickups->count(),
                'total_motoboy_fees' => $deliveries->sum('delivery_fee'),
            ],
        ]);
    }
}