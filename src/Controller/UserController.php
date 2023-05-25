<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Money;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users", methods={"POST"})
     */
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $jsonData = $request->getContent();
        $data = json_decode((string)$jsonData, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON data.');
        }

        $user = new User();
        $user->setEmail((string) ($data['email'] ?? ''));
        $user->setName((string) ($data['name'] ?? ''));
        $user->setSurname((string) ($data['surname'] ?? ''));

        // Persist the user entity to the database
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User created'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/api/users/{id}/money", methods={"POST"})
     */
    public function addMoneyToUser(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $jsonData = $request->getContent();
        $data = json_decode((string) $jsonData, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON data.');
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        $money = new Money();
        $money->setDate(new DateTime((string) ($data['date'] ?? '')));
        $money->setMoney((int) ($data['money'] ?? 0));

        if ($user instanceof User) {
            $user->addMoney($money);

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Money added to user'], JsonResponse::HTTP_CREATED);
        }

        throw new InvalidArgumentException('Invalid user ID.');
    }

    /**
     * @Route("/api/users/{page}", methods={"GET"})
     */
    public function getUsersWithMoney(int $page, EntityManagerInterface $entityManager): JsonResponse
    {
        $itemsPerPage = 30;
        $offset = ($page - 1) * $itemsPerPage;

        /** @var EntityRepository<User> $userRepository */
        $userRepository = $entityManager->getRepository(User::class);
        $queryBuilder = $userRepository->createQueryBuilder('u')
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

        return new JsonResponse(['items' => $usersWithMoneyData]);
    }
}
