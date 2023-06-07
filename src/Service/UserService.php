<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Money;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;

class UserService
{
    private EntityManagerInterface $entityManager;

    /**
     * @var EntityRepository<User>
     */
    private EntityRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    public function createUser(string $email, string $name, string $surname): User
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setSurname($surname);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $user;
        }
    }

    public function addMoneyToUser(int $userId, string $date, int $money): void
    {
        $user = $this->getUserById($userId);

        $moneyEntity = new Money();
        $moneyEntity->setDate(new DateTime($date));
        $moneyEntity->setMoney($money);

        $user->addMoney($moneyEntity);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @return  array<int<0, max>, array<string, array<int<0, max>, array<string, int|string|null>>|int|string|null>>
     */
    public function getUsersWithMoney(int $page, int $itemsPerPage): array
    {
        $offset = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.money', 'm')
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage);

        $users = $queryBuilder->getQuery()->getResult();

        $usersWithMoneyData = [];

        if (is_iterable($users)) {
            foreach ($users as $user) {
                /** @var User $user */
                $userMoney = [];
                foreach ($user->getMoney() as $money) {
                    /** @var Money $money */
                    $userMoney[] = [
                        'id' => $money->getId(),
                        'date' => $money->getDate() ? $money->getDate()->format('Y-m-d\TH:i:s.u\Z') : null,
                        'money' => $money->getMoney(),
                    ];
                }

                $usersWithMoneyData[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'surname' => $user->getSurname(),
                    'money' => $userMoney,
                ];
            }
        }

        return $usersWithMoneyData;
    }

    private function getUserById(int $id): User
    {
        $user = $this->userRepository->find($id);

        if (!$user instanceof User) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        return $user;
    }
}
