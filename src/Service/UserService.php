<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Money;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use DateTime;
use InvalidArgumentException;

class UserService
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function createUser(string $email, string $name, string $surname): ?User
    {
        if (
            filter_var($email, FILTER_VALIDATE_EMAIL)
            && strlen($email) <= 255
            && strlen($name) <= 255
            && strlen($surname) <= 255
        ) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setSurname($surname);

            $this->userRepository->save($user);

            return $user;
        }

        return null;
    }

    public function addMoneyToUser(int $userId, string $date, int $money): void
    {
        $user = $this->getUserById($userId);

        $moneyEntity = new Money();
        $moneyEntity->setDate(new DateTime($date));
        $moneyEntity->setMoney($money);

        $user->addMoney($moneyEntity);

        $this->userRepository->save($user);
    }

    /**
     * @return  array<int<0, max>, array<string, array<int<0, max>, array<string, int|string|null>>|int|string|null>>
     */
    public function getUsersWithMoney(int $page, int $itemsPerPage): array
    {
        return $this->userRepository->getUsersWithMoney($page, $itemsPerPage);
    }

    private function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user instanceof User) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        return $user;
    }
}
