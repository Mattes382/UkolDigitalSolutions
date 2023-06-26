<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Money;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findById(int $id): ?User
    {
        return parent::find($id);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return  array<User>
     */
    public function findAll(): array
    {
        return $this->findAll();
    }

    /**
     * @return  array<int<0, max>, array<string, array<int<0, max>, array<string, int|string|null>>|int|string|null>>
     */
    public function getUsersWithMoney(int $page, int $itemsPerPage): array
    {
        $offset = ($page - 1) * $itemsPerPage;

        $queryBuilder = $this->createQueryBuilder('u')
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
}
