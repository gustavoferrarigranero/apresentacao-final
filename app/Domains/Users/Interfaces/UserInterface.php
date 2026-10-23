<?php

namespace App\Domains\Users\Interfaces;

use App\Domains\Users\Data\CreateStaffData;
use App\Domains\Users\Data\CustomerRegistrationData;
use App\Models\User;

interface UserInterface
{

    public function createCustomer(CustomerRegistrationData $data): User;

    public function findByEmail(string $email): ?User;

    public function createRestaurantStaff(int $restaurantId, CreateStaffData $data): User;

    public function updateProfile(User $user, array $attributes): User;

    public function updatePassword(User $user, string $password): void;

    public function delete(User $user): void;
}
