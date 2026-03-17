<?php

namespace App\Message;

/**
 * Message dispatched when a new Todo is created.
 *
 * LARAVEL vs SYMFONY — Events & Async:
 * - Laravel: class TodoCreated implements ShouldQueue { public $todo; }
 *   Dispatched via: event(new TodoCreated($todo)) or TodoCreated::dispatch($todo)
 * - Symfony: Plain PHP class (message). Dispatched via MessageBusInterface::dispatch().
 *   Routing to async transport is configured in messenger.yaml.
 *
 * KEY DIFFERENCE:
 * - Laravel: The event class itself declares if it should be queued (ShouldQueue interface)
 * - Symfony: The message class is transport-agnostic. Whether it runs sync or async
 *   is determined by config/packages/messenger.yaml routing rules.
 *   This gives you more flexibility to change behavior without touching code.
 */
class TodoCreatedNotification
{
    public function __construct(
        private readonly int $todoId,
        private readonly string $userEmail,
    ) {
    }

    public function getTodoId(): int
    {
        return $this->todoId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }
}
