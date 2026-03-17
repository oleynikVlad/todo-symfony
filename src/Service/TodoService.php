<?php

namespace App\Service;

use App\DTO\CreateTodoRequest;
use App\DTO\UpdateTodoRequest;
use App\Entity\Todo;
use App\Entity\User;
use App\Message\TodoCreatedNotification;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * TodoService — All business logic for Todo CRUD operations.
 *
 * LARAVEL vs SYMFONY — Service Pattern:
 * - Laravel: Logic often in controllers or dedicated Action/Service classes.
 *   $todo = Todo::create($request->validated());
 * - Symfony: Service classes with injected dependencies.
 *   Entities are plain objects; persistence via EntityManager.
 *
 * LARAVEL vs SYMFONY — Events:
 * - Laravel: event(new TodoCreated($todo)) or Todo::observe(TodoObserver::class)
 * - Symfony: MessageBusInterface->dispatch(new TodoCreatedNotification($id))
 *   Messages are dispatched to a bus (can be sync or async via Messenger).
 */
class TodoService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TodoRepository $todoRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @return array{items: list<Todo>, total: int, page: int, limit: int}
     */
    public function listTodos(
        User $user,
        int $page = 1,
        int $limit = 10,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortDirection = 'DESC',
    ): array {
        return $this->todoRepository->findPaginatedByUser(
            $user,
            $page,
            $limit,
            $status,
            $dateFrom,
            $dateTo,
            $sortDirection,
        );
    }

    public function createTodo(CreateTodoRequest $dto, User $user): Todo
    {
        $todo = new Todo();
        $todo->setTitle($dto->title);
        $todo->setDescription($dto->description);
        $todo->setStatus($dto->status);
        $todo->setOwner($user);

        $this->entityManager->persist($todo);
        $this->entityManager->flush();

        // Dispatch async message — like Laravel's event(new TodoCreated($todo))
        $this->messageBus->dispatch(new TodoCreatedNotification($todo->getId(), $user->getEmail()));

        return $todo;
    }

    public function getTodo(int $id, User $user): Todo
    {
        $todo = $this->todoRepository->find($id);

        if ($todo === null) {
            throw new NotFoundHttpException('Todo not found.');
        }

        if ($todo->getOwner()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You do not own this todo.');
        }

        return $todo;
    }

    public function updateTodo(int $id, UpdateTodoRequest $dto, User $user): Todo
    {
        $todo = $this->getTodo($id, $user);

        if ($dto->titleProvided && $dto->title !== null) {
            $todo->setTitle($dto->title);
        }
        if ($dto->descriptionProvided) {
            $todo->setDescription($dto->description);
        }
        if ($dto->statusProvided && $dto->status !== null) {
            $todo->setStatus($dto->status);
        }

        $this->entityManager->flush();

        return $todo;
    }

    public function deleteTodo(int $id, User $user): void
    {
        $todo = $this->getTodo($id, $user);

        $this->entityManager->remove($todo);
        $this->entityManager->flush();
    }
}
