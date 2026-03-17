<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository — Doctrine equivalent of Laravel's Eloquent query scopes + repository pattern.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: User::where('email', $email)->first()
 * - Symfony: $userRepository->findOneBy(['email' => $email])
 *
 * - Laravel: Query scopes (scopeActive) on models
 * - Symfony: Custom repository methods (findByActive) in repository class
 *
 * - Laravel: Eloquent Builder chainable queries
 * - Symfony: Doctrine QueryBuilder or DQL
 *
 * KEY DIFFERENCE:
 * In Laravel, the model IS the query builder (Active Record pattern).
 * In Symfony/Doctrine, the Entity is a plain PHP object (Data Mapper pattern).
 * Queries live in the Repository, not in the Entity.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * Similar to Laravel's Auth::logoutOtherDevices() rehashing mechanism.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
