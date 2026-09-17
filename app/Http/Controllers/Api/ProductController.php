<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::where('is_active', true)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'size' => 'required|string|max:255',
            'current_cost' => 'required|numeric|min:0',
            'current_price' => 'required|numeric|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product = Product::create($validated);

        // já registra o primeiro histórico de preço
        ProductPriceHistory::create([
            'product_id' => $product->id,
            'cost' => $product->current_cost,
            'price' => $product->current_price,
            'changed_at' => now(),
        ]);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product->load('priceHistory');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'current_cost' => 'sometimes|numeric|min:0',
            'current_price' => 'sometimes|numeric|min:0',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        // se custo ou preço mudaram, registra no histórico
        $costChanged = isset($validated['current_cost']) && $validated['current_cost'] != $product->current_cost;
        $priceChanged = isset($validated['current_price']) && $validated['current_price'] != $product->current_price;

        $product->update($validated);

        if ($costChanged || $priceChanged) {
            ProductPriceHistory::create([
                'product_id' => $product->id,
                'cost' => $product->current_cost,
                'price' => $product->current_price,
                'changed_at' => now(),
            ]);
        }

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);
        return response()->json(['message' => 'Produto desativado']);
    }
}