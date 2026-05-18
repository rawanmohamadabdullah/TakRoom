<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreOrderRequest;

class OrderController extends Controller
{

    public function customerIndex()
    {
        $user = Auth::user();

        $orders = Order::with(['items.product:id,name,price,description'])
            ->where('customer_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'state' => $order->state,
                    'total_price' => (float) $order->total_price,
                    'created_at' => $order->created_at,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'item_id' => $item->id,
                            'product_name' => $item->product ? $item->product->name : 'Product Deleted',
                            'quantity' => $item->quantity,
                            'price_per_item' => (float) $item->price,
                            'subtotal' => (float) ($item->price * $item->quantity)
                        ];
                    })
                ];
            });

        return response()->json([
            "status" => "success",
            "message" => "Orders retrieved successfully",
            "orders" => $orders
        ], 200);
    }

    public function supplierIndex()
    {
        $supplierId = Auth::id();

        $orders = Order::whereHas('items.product', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })
            ->with([
                'customer:id,name,building,room_number',
                'items' => function ($query) use ($supplierId) {
                    $query->whereHas('product', function ($q) use ($supplierId) {
                        $q->where('supplier_id', $supplierId);
                    })->with('product');
                }
            ])
            ->latest()
            ->get()
            ->map(function ($order) {
                $customerName = $order->customer ? $order->customer->name : 'Unknown Customer';
                $building = $order->customer ? $order->customer->building : 'N/A';
                $roomNumber = $order->customer ? $order->customer->room_number : 'N/A';

                return [
                    'order_id' => $order->id,
                    'state' => $order->state,
                    'customer_name' => $customerName,
                    'delivery_address' => [
                        'building' => $building,
                        'room_number' => $roomNumber
                    ],
                    'created_at' => $order->created_at,

                    'my_products_total' => (float) $order->items->sum(function ($item) {
                        return $item->price * $item->quantity;
                    }),

                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->product ? $item->product->name : 'Product Deleted',
                            'quantity' => $item->quantity,
                            'price' => (float) $item->price,
                            'subtotal' => (float) ($item->price * $item->quantity)
                        ];
                    })
                ];
            });

        return response()->json([
            "status" => "success",
            "message" => "Customer orders retrieved successfully for this supplier",
            "orders" => $orders
        ], 200);
    }

    public function create(StoreOrderRequest $request)
    {
        $user = Auth::user();

        $existingOrder = Order::where('customer_id', $user->id)
            ->where('state', 'pending')
            ->first();

        if ($existingOrder) {
            return response()->json([
                "status" => "error",
                "message" => "You already have a pending order; you cannot create a new order until the current order is processed."
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'customer_id' => $user->id,
                'state' => 'pending',
                'total_price' => 0.00
            ]);

            $totalPrice = 0;

            foreach ($request->validated()['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        "status" => "error",
                        "message" => "The requested quantity is not available for the product: " . $product->name
                    ], 422);
                }

                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ]);

                $totalPrice += $product->price * $item['quantity'];
            }

            $order->update(['total_price' => $totalPrice]);

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Order created successfully",
                "order_id" => $order->id,
                "total_price" => $totalPrice
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error",
                "message" => "An unexpected error occurred while processing the order."
            ], 500);
        }
    }

    public function accept($id)
    {
        $user = Auth::user();
        $order = Order::with(['customer', 'items.product'])->findOrFail($id);

        if ($order->state !== 'pending') {
            return response()->json([
                "status" => "error",
                "message" => "Cannot accept this order, the current state is: " . $order->state
            ], 400);
        }

        foreach ($order->items as $item) {
            if ($item->product->supplier_id !== $user->id) {
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized. This order contains products belonging to another supplier."
                ], 403);
            }
        }

        $customer = $order->customer;

        if ($customer->balance < $order->total_price) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to accept the order due to insufficient customer wallet balance."
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer->decrement('balance', $order->total_price);

            foreach ($order->items as $item) {
                $itemPriceSum = $item->price * $item->quantity;
                User::where('id', $item->product->supplier_id)->increment('balance', $itemPriceSum);
            }

            $order->update(['state' => 'accept']);

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Order accepted and balance transferred successfully."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error",
                "message" => "An error occurred while accepting the order."
            ], 500);
        }
    }

    public function reject($id)
    {
        $user = Auth::user();
        $order = Order::with('items.product')->findOrFail($id);

        if ($order->state !== 'pending') {
            return response()->json([
                "status" => "error",
                "message" => "Cannot reject this order, the current state is: " . $order->state
            ], 400);
        }

        foreach ($order->items as $item) {
            if ($item->product->supplier_id !== $user->id) {
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized to reject this order."
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->update(['state' => 'reject']);

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Order rejected successfully, stock returned to inventory."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error",
                "message" => "An error occurred while rejecting the order."
            ], 500);
        }
    }

    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::with('items.product')->where('customer_id', $user->id)->findOrFail($id);

        if ($order->state !== 'pending') {
            return response()->json([
                "status" => "error",
                "message" => "Cannot cancel this order, the current state is: " . $order->state
            ], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->update(['state' => 'cancelled']);

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Order cancelled successfully."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error",
                "message" => "An error occurred while cancelling the order."
            ], 500);
        }
    }


    public function showBalance()
    {
        $user = Auth::user();

        return response()->json([
            "status" => "success",
            "balance" => (float) $user->balance,
            "currency" => "USD"
        ], 200);
    }
}
