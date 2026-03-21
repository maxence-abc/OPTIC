<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée ou met à jour un compte administrateur plateforme',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du compte admin')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe du compte admin')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'Prénom', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Nom', 'Plateforme')
            ->addOption('phone', null, InputOption::VALUE_OPTIONAL, 'Téléphone', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $plainPassword = (string) $input->getArgument('password');
        $firstName = trim((string) $input->getOption('first-name'));
        $lastName = trim((string) $input->getOption('last-name'));
        $phone = $input->getOption('phone');

        if ($email === '' || $plainPassword === '' || $firstName === '' || $lastName === '') {
            $io->error('Email, mot de passe, prénom et nom sont requis.');

            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        $isNewUser = $user === null;

        if ($user === null) {
            $user = new User();
            $user->setEmail($email);
            $user->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($user);
        }

        $storedRoles = array_values(array_unique(array_filter(
            $user->getRoles(),
            static fn (string $role): bool => $role !== 'ROLE_CLIENT'
        )));
        $storedRoles[] = 'ROLE_ADMIN';

        $user
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPhone($phone !== null ? trim((string) $phone) ?: null : null)
            ->setRoles(array_values(array_unique($storedRoles)))
            ->setIsActive(true)
            ->setPassword($this->passwordHasher->hashPassword($user, $plainPassword))
            ->setUpdateAt(new \DateTime());

        $this->entityManager->flush();

        $io->success(sprintf(
            '%s admin prêt: %s',
            $isNewUser ? 'Compte' : 'Compte mis à jour, accès admin',
            $email
        ));
        $io->listing([
            'Rôle: ROLE_ADMIN',
            'Prénom: '.$firstName,
            'Nom: '.$lastName,
            'Actif: oui',
        ]);

        return Command::SUCCESS;
    }
}
