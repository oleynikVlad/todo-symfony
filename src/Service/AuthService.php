<?php

namespace App\Service;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * AuthService — Business logic for authentication.
 *
 * LARAVEL vs SYMFONY — Service Layer:
 * - Laravel: Often puts business logic directly in controllers or uses Action classes.
 *   Auth logic is typically handled by Laravel's built-in Auth facade.
 * - Symfony: Encourages a dedicated Service layer. Services are auto-wired by the container.
 *
 * LARAVEL vs SYMFONY — Dependency Injection:
 * - Laravel: Uses the Service Container (app()->make(), resolve(), or constructor injection in controllers)
 * - Symfony: Uses autowiring by default. Just type-hint the dependency in the constructor,
 *   and the DI container provides it automatically.
 *
 * LARAVEL vs SYMFONY — Password Hashing:
 * - Laravel: Hash::make($password), Hash::check($password, $hashed)
 * - Symfony: UserPasswordHasherInterface->hashPassword(), ->isPasswordValid()
 */
class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function register(RegisterRequest $dto): User
    {
        $existing = $this->userRepository->findOneBy(['email' => $dto->email]);
        if ($existing !== null) {
            throw new \InvalidArgumentException('A user with this email already exists.');
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function validateCredentials(LoginRequest $dto): User
    {
        $user = $this->userRepository->findOneBy(['email' => $dto->email]);

        if ($user === null || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        return $user;
    }
}
