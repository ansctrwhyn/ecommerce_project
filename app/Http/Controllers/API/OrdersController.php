<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $orders = Order::with('user:id,name,address')
            ->select('id', 'product_id', 'quantity', 'total_price', 'user_id', 'created_at', 'updated_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Orders retrieved successfully',
            'data' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'product_id' => $order->product_id,
                    'quantity' => $order->quantity,
                    'total_price' => $order->total_price,
                    'customer_name' => $order->user->name,
                    'customer_address' => $order->user->address,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        $user = auth()->user();
        $product = Product::find($request->input('product_id'));
        $totalPrice = $product->price * $request->input('quantity');

        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $request->input('product_id'),
            'quantity' => $request->input('quantity'),
            'total_price' => $totalPrice,
            'customer_name' => $user->name,
            'customer_address' => $user->address,
            'order_date' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => $order
        ], 200);
    }

    public function show($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        }

        $user = auth()->user();
        if ($order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to retrieve this order.'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order retrieved successfully',
            'data' => $order
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        $user = auth()->user();
        if ($order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to update this order.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        $product = Product::find($request->input('product_id'));
        $totalPrice = $product->price * $request->input('quantity');

        $order->update([
            'user_id' => $user->id,
            'product_id' => $request->input('product_id'),
            'quantity' => $request->input('quantity'),
            'total_price' => $totalPrice,
            'customer_name' => $user->name,
            'customer_address' => $user->address,
            'order_date' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => $order
        ], 200);
    }

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        }

        $user = auth()->user();
        if ($order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this order.'
            ], 403);
        }

        $order->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully',
        ], 200);
    }


    public function report()
    {
        $orders = Order::with(['product.category', 'user'])
            ->select('id', 'product_id', 'quantity', 'total_price', 'user_id', 'created_at')
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_price');

        $dataOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'product_name' => $order->product->name,
                'category_name' => $order->product->category->name,
                'quantity' => $order->quantity,
                'total_price' => $order->total_price,
                'customer_name' => $order->user->name,
                'order_date' => $order->created_at,
            ];
        });

        $data = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'orders' => $dataOrders,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Order report generated successfully',
            'data' => $data
        ], 200);
    }
}
