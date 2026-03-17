<?php

namespace App\MessageHandler;

use App\Message\TodoCreatedNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for TodoCreatedNotification messages.
 *
 * LARAVEL vs SYMFONY — Event Listeners / Handlers:
 * - Laravel: class SendTodoNotification implements ShouldQueue {
 *       public function handle(TodoCreated $event) { ... }
 *   }
 *   Registered in EventServiceProvider: TodoCreated::class => [SendTodoNotification::class]
 *
 * - Symfony: Use #[AsMessageHandler] attribute. The handler is auto-discovered
 *   and registered by the service container (autowiring + autoconfigure).
 *   No manual registration needed — Symfony scans for the attribute.
 *
 * This handler:
 * - Logs the todo creation event (simulates sending an email notification)
 * - Runs asynchronously when Messenger is configured with an async transport
 * - To process: php bin/console messenger:consume async
 */
#[AsMessageHandler]
class TodoCreatedNotificationHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(TodoCreatedNotification $message): void
    {
        $this->logger->info('Todo created notification', [
            'todo_id' => $message->getTodoId(),
            'user_email' => $message->getUserEmail(),
            'message' => sprintf(
                'New todo #%d created by %s. Email notification would be sent here.',
                $message->getTodoId(),
                $message->getUserEmail(),
            ),
        ]);
    }
}
