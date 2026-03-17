<?php

namespace App\Repository;

use App\Entity\Todo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TodoRepository — Handles all database queries for Todo entities.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: Todo::where('user_id', $user->id)->paginate(10)
 * - Symfony: $todoRepository->findPaginatedByUser($user, 1, 10)
 *
 * - Laravel pagination returns LengthAwarePaginator (auto JSON in API)
 * - Symfony uses Doctrine Paginator or manual offset/limit
 *
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    /**
     * Find todos for a user with pagination, filtering, and sorting.
     *
     * Laravel equivalent:
     *   Todo::where('user_id', $userId)
     *       ->when($status, fn($q) => $q->where('status', $status))
     *       ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
     *       ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
     *       ->orderBy('created_at', $sortDirection)
     *       ->paginate($limit, ['*'], 'page', $page);
     *
     * @return array{items: list<Todo>, total: int, page: int, limit: int}
     */
    public function findPaginatedByUser(
        User $user,
        int $page = 1,
        int $limit = 10,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortDirection = 'DESC',
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :user')
            ->setParameter('user', $user);

        if ($status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('t.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTimeImmutable($dateFrom));
        }

        if ($dateTo !== null) {
            $qb->andWhere('t.createdAt <= :dateTo')
                ->setParameter('dateTo', new \DateTimeImmutable($dateTo . ' 23:59:59'));
        }

        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy('t.createdAt', $sortDirection);

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total = count($paginator);

        $items = [];
        foreach ($paginator as $item) {
            $items[] = $item;
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }
}
