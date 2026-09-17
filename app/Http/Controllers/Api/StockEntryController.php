<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockEntryController extends Controller
{
    public function index(Product $product)
    {
        return $product->stockEntries()->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $entry = StockEntry::create([
                ...$validated,
                'user_id' => $request->user()?->id,
            ]);

            Product::where('id', $validated['product_id'])
                ->increment('current_stock', $validated['quantity']);

            return response()->json($entry, 201);
        });
    }
}