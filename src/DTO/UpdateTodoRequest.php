<?php

namespace App\DTO;

use App\Entity\Todo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for updating a Todo.
 * All fields are optional — only provided fields will be updated.
 */
class UpdateTodoRequest
{
    #[Assert\Length(max: 255, maxMessage: 'Title cannot exceed {{ limit }} characters.')]
    public ?string $title = null;

    #[Assert\Length(max: 5000, maxMessage: 'Description cannot exceed {{ limit }} characters.')]
    public ?string $description = null;

    #[Assert\Choice(
        choices: Todo::VALID_STATUSES,
        message: 'Status must be one of: pending, in_progress, completed.',
    )]
    public ?string $status = null;

    /**
     * Track which fields were explicitly provided in the request.
     * This distinguishes "field not sent" (null, not provided) from
     * "field explicitly set to null" (null, provided) — allowing users
     * to clear a description by sending {"description": null}.
     */
    public bool $titleProvided = false;
    public bool $descriptionProvided = false;
    public bool $statusProvided = false;
}
