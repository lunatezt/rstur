<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\KitchenReadyNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;

class KDSController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['placed', 'confirmed', 'preparing'])
            ->with(['table', 'items' => function ($q) {
                $q->whereIn('item_status', ['pending', 'preparing'])
                  ->with('product');
            }])
            ->orderBy('created_at')
            ->get()
            ->filter(fn($order) => $order->items->isNotEmpty());

        return response()->json(['orders' => $orders->values()]);
    }

    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $order = $item->order;
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,served',
        ]);

        $wasReady = $item->item_status === 'ready';
        $updates = ['item_status' => $validated['status']];
        if ($validated['status'] === 'preparing') $updates['preparation_started_at'] = now();
        if ($validated['status'] === 'ready') {
            $updates['prepared_at'] = now();
            $updates['prepared_by'] = $request->user()->id;
            $prepTime = $item->preparation_started_at
                ? now()->diffInMinutes($item->preparation_started_at) : null;
            $updates['actual_preparation_time'] = $prepTime;
        }

        $item->update($updates);
        if ($validated['status'] === 'ready' && ! $wasReady) {
            $this->notifyAdmins($order, $item, $request->user(), 'item_ready');
        }

        $pendingItems = $order->items()->whereIn('item_status', ['pending', 'preparing'])->count();
        if ($pendingItems === 0 && $order->status === 'preparing') {
            $order->update(['status' => 'ready', 'ready_at' => now()]);
            $this->notifyAdmins($order, null, $request->user(), 'order_ready');
        } elseif ($validated['status'] === 'preparing' && $order->status === 'placed') {
            $order->update(['status' => 'preparing']);
        }

        return response()->json(['item' => $item, 'order_status' => $order->fresh()->status]);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate(['status' => 'required|in:preparing']);
        $order->update(['status' => $validated['status']]);
        $order->items()->whereIn('item_status', ['pending'])->update(['item_status' => 'preparing', 'preparation_started_at' => now()]);
        return response()->json(['order' => $order->load('items.product')]);
    }

    private function notifyAdmins(Order $order, ?OrderItem $item, User $readyBy, string $event): void
    {
        $order->loadMissing('table');
        $item?->loadMissing('product');

        $admins = User::query()
            ->where('restaurant_id', $order->restaurant_id)
            ->whereHas('role', fn ($query) => $query->where('slug', 'admin'))
            ->get();

        $readyItems = $item
            ? collect([$item])
            : $order->items()
                ->where('item_status', '!=', 'cancelled')
                ->with('product')
                ->get();
        $meals = $readyItems
            ->map(fn (OrderItem $readyItem) => [
                'name_en' => $readyItem->product?->name_en ?? $readyItem->product_name,
                'name_fa' => $readyItem->product?->name_fa,
            ])
            ->unique(fn (array $meal) => implode('|', $meal))
            ->values()
            ->all();

        Notification::send($admins, new KitchenReadyNotification([
            'event' => $event,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'table_id' => $order->table_id,
            'table_name' => $order->table?->name,
            'table_number' => $order->table?->table_number,
            'item_id' => $item?->id,
            'item_name_en' => $item?->product?->name_en ?? $item?->product_name,
            'item_name_fa' => $item?->product?->name_fa,
            'quantity' => $item?->quantity,
            'ready_by' => $readyBy->name,
            'meals' => $meals,
        ]));
    }
}
