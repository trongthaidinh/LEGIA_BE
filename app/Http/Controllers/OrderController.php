<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $status = $request->query('status');
            $orders = Order::when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
                ->orderBy('created_at', 'desc')
                ->get();

            return responseJson($orders, 200, 'Orders retrieved successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function showByKey($key)
    {
        try {
            $order = Order::with('items')->where('order_key', $key)->first();

            if (!$order) {
                return responseJson(null, 404, 'Order not found');
            }

            return responseJson($order, 200, 'Order found');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }



    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'city' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'note' => 'nullable|string',
                'payment_method' => 'required|in:atm,cod',
                'shipping_fee' => 'nullable|integer',
                'subtotal' => 'required|integer',
                'total' => 'required|integer',
                'items' => 'required|array',
                'items.*.name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|integer|min:0',
                'items.*.image' => 'nullable|string',
            ]);

            $orderKey = 'wc_order_' . Str::random(10);

            foreach ($validated['items'] as $item) {
                $product = Product::where('name', $item['name'])->first();

                if (!$product) {
                    return responseJson(null, 404, 'Product not found: ' . $item['name']);
                }

                try {
                    $product->reduceStock($item['quantity']);
                } catch (\Exception $e) {
                    return responseJson(null, 400, $e->getMessage());
                }
            }

            $order = Order::create(array_merge($validated, ['order_key' => $orderKey]));

            foreach ($validated['items'] as $item) {
                $order->items()->create($item);
            }

            return responseJson([
                'order' => $order,
                'order_key' => $orderKey
            ], 201, 'Order created successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return responseJson(null, 404, 'Order not found');
            }

            $order->items()->delete();

            $order->delete();

            return responseJson(null, 200, 'Order deleted successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,completed,canceled',
            ]);

            $order = Order::find($id);

            if (!$order) {
                return responseJson(null, 404, 'Order not found');
            }

            $order->status = $validated['status'];
            $order->save();

            return responseJson($order, 200, 'Order status updated successfully');
        } catch (Exception $e) {
            return responseJson(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
