<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/api/users", methods={"POST"})
     */
    public function createUser(Request $request): JsonResponse
    {
        $jsonData = $request->getContent();
        $data = json_decode((string) $jsonData, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON data.');
        }

        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        $surname = $data['surname'] ?? '';

        $this->userService->createUser($email, $name, $surname);

        return new JsonResponse(['message' => 'User created'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/api/users/{id}/money", methods={"POST"})
     */
    public function addMoneyToUser(int $id, Request $request): JsonResponse
    {
        $jsonData = $request->getContent();
        $data = json_decode((string) $jsonData, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON data.');
        }

        $date = $data['date'] ?? '';
        $money = (int) ($data['money'] ?? 0);

        $this->userService->addMoneyToUser($id, $date, $money);

        return new JsonResponse(['message' => 'Money added to user'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/api/users/{page}", methods={"GET"})
     */
    public function getUsersWithMoney(int $page): JsonResponse
    {
        $itemsPerPage = 30;

        $usersWithMoneyData = $this->userService->getUsersWithMoney($page, $itemsPerPage);

        return new JsonResponse(['items' => $usersWithMoneyData]);
    }
}
