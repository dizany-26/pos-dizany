<?php

namespace App\Services;

use App\Models\PedidoCatalogo;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class PedidoCatalogoNotificationService
{
    public function unreadFor(User $user): Collection
    {
        $notifications = $user->unreadNotifications()->latest()->get();
        $catalogNotifications = $notifications->filter(
            fn (DatabaseNotification $notification) => $this->isCatalogOrder($notification)
        );

        if ($catalogNotifications->isEmpty()) {
            return $notifications;
        }

        $pendingOrders = PedidoCatalogo::where('estado', 'pendiente')
            ->get(['id', 'codigo']);
        $pendingIds = $pendingOrders->pluck('id')->map(fn ($id) => (string) $id)->all();
        $pendingCodes = $pendingOrders->pluck('codigo')->all();

        $staleIds = $catalogNotifications
            ->reject(fn (DatabaseNotification $notification) => $this->matchesPendingOrder(
                $notification,
                $pendingIds,
                $pendingCodes
            ))
            ->pluck('id');

        if ($staleIds->isNotEmpty()) {
            $user->notifications()->whereIn('id', $staleIds)->update(['read_at' => now()]);
        }

        return $notifications->whereNotIn('id', $staleIds)->values();
    }

    public function markAsRead(PedidoCatalogo $order): void
    {
        DatabaseNotification::query()
            ->whereNull('read_at')
            ->get()
            ->filter(fn (DatabaseNotification $notification) => $this->matchesOrder($notification, $order))
            ->each->markAsRead();
    }

    private function isCatalogOrder(DatabaseNotification $notification): bool
    {
        return isset($notification->data['pedido_catalogo_id'])
            || ($notification->data['titulo'] ?? null) === 'Nuevo pedido del catálogo';
    }

    private function matchesPendingOrder(
        DatabaseNotification $notification,
        array $pendingIds,
        array $pendingCodes
    ): bool {
        $orderId = $notification->data['pedido_catalogo_id'] ?? null;
        if ($orderId !== null) {
            return in_array((string) $orderId, $pendingIds, true);
        }

        $message = (string) ($notification->data['mensaje'] ?? '');

        return collect($pendingCodes)->contains(
            fn (string $code) => str_contains($message, $code)
        );
    }

    private function matchesOrder(DatabaseNotification $notification, PedidoCatalogo $order): bool
    {
        if (! $this->isCatalogOrder($notification)) {
            return false;
        }

        $orderId = $notification->data['pedido_catalogo_id'] ?? null;

        return $orderId !== null
            ? (string) $orderId === (string) $order->id
            : str_contains((string) ($notification->data['mensaje'] ?? ''), $order->codigo);
    }
}
