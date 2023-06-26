<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function save(User $user): void;

    public function delete(User $user): void;

    /**
     * @return  array<User>
     */
    public function findAll(): array;

    /**
     * @return  array<int<0, max>, array<string, array<int<0, max>, array<string, int|string|null>>|int|string|null>>
     */
    public function getUsersWithMoney(int $page, int $itemsPerPage): array;
}
