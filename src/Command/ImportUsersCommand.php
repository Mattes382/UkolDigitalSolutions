<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    protected function configure() : void
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


            $response = $this->httpClient->request('POST', 'https://interview-test.digital.cz/api/users', [
                'json' => $userData,
            ]);


            $statusCode = $response->getStatusCode();
            $content = $response->getContent();


            $output->writeln('User imported: ' . $user->getEmail());
        }

        return Command::SUCCESS;
    }
}
