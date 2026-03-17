<?php

namespace App\DTO;

use App\Entity\Todo;

/**
 * Response DTO for Todo entity.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: API Resources (TodoResource extends JsonResource)
 *   Transforms model: return ['id' => $this->id, 'title' => $this->title, ...]
 * - Symfony: Plain DTO class with a static factory method
 *
 * Both approaches serve the same purpose:
 * Control what data is exposed to the API consumer.
 * Never return raw entities/models in API responses.
 */
class TodoResponse
{
    public ?int $id;
    public string $title;
    public ?string $description;
    public string $status;
    public string $createdAt;
    public string $updatedAt;

    public static function fromEntity(Todo $todo): self
    {
        $dto = new self();
        $dto->id = $todo->getId();
        $dto->title = $todo->getTitle();
        $dto->description = $todo->getDescription();
        $dto->status = $todo->getStatus();
        $dto->createdAt = $todo->getCreatedAt()->format('c');
        $dto->updatedAt = $todo->getUpdatedAt()->format('c');

        return $dto;
    }
}
