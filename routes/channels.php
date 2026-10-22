<?php

use Illuminate\Support\Facades\Broadcast;

// O identificador privado do cliente só pode ser subscrito pelo próprio cliente autenticado.
Broadcast::channel('orders.{customerId}', function ($user, int $customerId): bool {
    return (int) $user->id === $customerId;
});

// A equipa só recebe eventos em tempo real do restaurante associado à sua conta.
Broadcast::channel('restaurant.{restaurantId}', function ($user, int $restaurantId): bool {
    return in_array($user->role, ['restaurant_admin', 'staff'], true)
        && (int) $user->restaurant_id === $restaurantId;
});

// Mantém também o canal de utilizador gerado pelo Laravel para funcionalidades do starter kit.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
