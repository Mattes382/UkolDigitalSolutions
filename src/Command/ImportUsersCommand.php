<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class ImportUsersCommand extends Command
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:import-users')
        ->setDescription('Import users from the database to the API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        foreach ($users as $user) {
            $userData = [
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'plainPassword' => 'interview-test',
                'active' => true, /*Kdyz jsem nezadal tento parametr dostaval jsem 500 ackoli bylo pole podle
                dokumentace nepovinne*/
            ];

            try {
                $response = $this->httpClient->request('POST', 'https://interview-test.digital.cz/api/users', [
                    'json' => $userData,
                ]);

                $content = $response->getContent();
                $apiUser = json_decode($content, true);

                if (is_array($apiUser) && isset($apiUser['id'])) {
                    $externalApiId = (string) $apiUser['id'];
                    $user->setExternalApiId($externalApiId);
                    $this->entityManager->flush();

                    $output->writeln(
                        'User imported: ' . $user->getEmail() . ' (External API ID: ' . $externalApiId . ')',
                    );
                } else {
                    $output->writeln('Failed to import user: ' . $user->getEmail() . ' - Invalid API response');
                }
            } catch (Throwable $e) {
                $output->writeln('Failed to import user: ' . $user->getEmail() . ' - ' . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
