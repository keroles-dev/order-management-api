<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $orders = Order::offset($offset)->limit($limit)->get();

        $data = [
            'orders' => OrderResource::collection($orders),
            'total' => $orders->count(),
            'offset' => $offset,
            'limit' => $limit,
        ];

        return $this->sendResponse($data, 'Orders fetched successfully', 200);
    }

    public function store(Request $request)
    {
        // prevent xss attack
        $request->merge(['product_name' => strip_tags($request->product_name)]);

        // validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'product_name' => 'required|string|max:255|min:3',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        // create order
        $order = Order::create([
            'user_id' => $request->user_id,
            'product_name' => $request->product_name,
            'quantity' => $request->quantity,
            'price' => $request->price,
        ]);

        return $this->sendResponse(new OrderResource($order), 'Order created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . implode(',', Order::orderStatuses()),
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return $this->sendError('Order not found', [], 404);
        }

        $order->update([
            'status' => $request->status,
        ]);

        return $this->sendResponse(new OrderResource($order), 'Order updated successfully', 200);
    }

    public function stats()
    {
        $stats = Order::select(
            DB::raw('SUM(price) as revenue'),
            DB::raw('COUNT(*) as total_orders'),
            'status',
            DB::raw('COUNT(*) as status_count')
        )
            ->groupBy('status')
            ->get();

        $revenue = $stats->sum('revenue') / 100;
        $orders = $stats->map(function ($item) {
            return [
                'status' => $item->status,
                'total' => $item->status_count
            ];
        });

        return $this->sendResponse(
            ['revenue' => $revenue, 'orders' => $orders],
            'Order stats fetched successfully',
            200,
        );
    }
}
