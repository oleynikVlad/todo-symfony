<?php

namespace App\Tests\Unit;

use App\DTO\TodoResponse;
use App\Entity\Todo;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit Test for TodoResponse DTO.
 *
 * LARAVEL vs SYMFONY — Testing:
 * - Laravel: php artisan test, extends TestCase from PHPUnit
 *   Uses RefreshDatabase trait, factories, and assertions like assertJson()
 * - Symfony: php bin/phpunit, same PHPUnit base
 *   Uses WebTestCase for functional tests, KernelTestCase for integration
 *
 * Unit tests are identical in both frameworks — pure PHPUnit.
 */
class TodoResponseTest extends TestCase
{
    public function testFromEntityCreatesCorrectDto(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $todo = new Todo();
        $todo->setTitle('Test Todo');
        $todo->setDescription('Test Description');
        $todo->setStatus(Todo::STATUS_PENDING);
        $todo->setOwner($user);

        $response = TodoResponse::fromEntity($todo);

        $this->assertSame('Test Todo', $response->title);
        $this->assertSame('Test Description', $response->description);
        $this->assertSame('pending', $response->status);
        $this->assertNotEmpty($response->createdAt);
        $this->assertNotEmpty($response->updatedAt);
    }

    public function testFromEntityWithNullDescription(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $todo = new Todo();
        $todo->setTitle('No Description Todo');
        $todo->setDescription(null);
        $todo->setStatus(Todo::STATUS_IN_PROGRESS);
        $todo->setOwner($user);

        $response = TodoResponse::fromEntity($todo);

        $this->assertSame('No Description Todo', $response->title);
        $this->assertNull($response->description);
        $this->assertSame('in_progress', $response->status);
    }

    public function testTodoStatusConstants(): void
    {
        $this->assertSame('pending', Todo::STATUS_PENDING);
        $this->assertSame('in_progress', Todo::STATUS_IN_PROGRESS);
        $this->assertSame('completed', Todo::STATUS_COMPLETED);
        $this->assertCount(3, Todo::VALID_STATUSES);
    }
}
