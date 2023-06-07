<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImportSalariesCommand extends Command
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    private string $password = "interview-test";

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:import-salaries')
            ->setDescription('Import user salary for a given month')
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('month', InputArgument::REQUIRED, 'Month (1-12)')
            ->addArgument('year', InputArgument::REQUIRED, 'Year (e.g., 2023)');
    }

    /**
     * @throws ExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('userId');
        $month = $input->getArgument('month');
        $year = $input->getArgument('year');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($userId);

        if (!$user) {
            $output->writeln('User not found.');

            return Command::FAILURE;
        }

        if (!is_numeric($userId)) {
            $output->writeln('User ID must be a valid integer.');

            return Command::FAILURE;
        }

        if (!is_numeric($month) || (int)$month < 1 || (int)$month > 12) {
            $output->writeln('Month must be a valid integer between 1 and 12.');

            return Command::FAILURE;
        }

        if (!is_numeric($year)) {
            $output->writeln('Year must be a valid integer.');

            return Command::FAILURE;
        }

        $token = $user->getToken();

        if (empty($token)) {
            $output->writeln('Token is empty. Fetching a new one...');
            $output->writeln($user->getEmail());

            // Fetch the token
            $token = $this->fetchToken($user->getEmail(), $this->password);

            if (empty($token)) {
                $output->writeln('Failed to fetch JWT token.');

                return Command::FAILURE;
            }

            $user->setToken($token);
            $this->entityManager->flush();
        }

        $salaryData = [
            'user' => $user->getExternalApiId(),
            'money' => $this->getUserSalaryForMonth($user, (int) $month, (int) $year),
            'year' => $year,
            'month' => $month,
        ];

       // $output->writeln('Salary Data: ' . json_encode($salaryData));

        $response = $this->httpClient->request('POST', 'https://interview-test.digital.cz/api/salaries', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => $salaryData,
        ]);

        if ($response->getStatusCode() === 401) {
            $output->writeln('Token may have expired. Fetching a new one...');
            $token = $this->fetchToken($user->getEmail(), $this->password);

            if (empty($token)) {
                $output->writeln('Failed to fetch JWT token.');

                return Command::FAILURE;
            }

            $user->setToken($token);
            $this->entityManager->flush();

            $response = $this->httpClient->request('POST', 'https://interview-test.digital.cz/api/salaries', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $salaryData,
            ]);
        }

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        $output->writeln(['Status code: ' . $statusCode, 'Response: ' . $content]);

        return Command::SUCCESS;
    }

    private function fetchToken(string $email, string $password): ?string
    {
        $response = $this->httpClient->request('POST', 'https://interview-test.digital.cz/api/auth-token', [
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $content = $response->toArray();

            return $content['token'];
        }

        return null;
    }

    private function getUserSalaryForMonth(User $user, int $month, int $year): int
    {
        $salaries = $user->getMoney();

        foreach ($salaries as $money) {
            $moneyDate = $money->getDate();

            if ($moneyDate instanceof DateTimeInterface) {
                $moneyYear = (int) $moneyDate->format('Y');
                $moneyMonth = (int) $moneyDate->format('m');

                if ($moneyYear === $year && $moneyMonth === $month) {
                    return $money->getMoney();
                }
            }
        }

        return 0;
    }
}
