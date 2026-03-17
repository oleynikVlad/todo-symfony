<?php

namespace App\DTO;

use App\Entity\Todo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for creating a new Todo.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: $request->validate(['title' => 'required|string|max:255', ...])
 * - Symfony: Validation attributes on DTO properties, validated via ValidatorInterface
 */
class CreateTodoRequest
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Title cannot exceed {{ limit }} characters.')]
    public string $title = '';

    #[Assert\Length(max: 5000, maxMessage: 'Description cannot exceed {{ limit }} characters.')]
    public ?string $description = null;

    #[Assert\Choice(
        choices: Todo::VALID_STATUSES,
        message: 'Status must be one of: pending, in_progress, completed.',
    )]
    public string $status = Todo::STATUS_PENDING;
}
