<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration Test for AuthController.
 *
 * LARAVEL vs SYMFONY — Integration/Feature Tests:
 * - Laravel: extends TestCase, uses RefreshDatabase
 *   $response = $this->postJson('/api/register', ['email' => '...', 'password' => '...']);
 *   $response->assertStatus(201)->assertJson(['message' => '...']);
 *
 * - Symfony: extends WebTestCase (boots the kernel + creates test HTTP client)
 *   $client->request('POST', '/api/register', [], [], [], json_encode([...]));
 *   $this->assertResponseStatusCodeSame(201);
 *
 * KEY DIFFERENCES:
 * - Laravel: $this->postJson() is a fluent helper on the test class
 * - Symfony: static::createClient() returns a test Client, then $client->request()
 *
 * - Laravel: assertJson(), assertJsonStructure()
 * - Symfony: json_decode($client->getResponse()->getContent(), true) + standard assertions
 *
 * NOTE: This test requires a database. For CI, use SQLite or configure a test database.
 * In .env.test: DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
 */
class AuthControllerTest extends WebTestCase
{
    public function testRegisterValidationErrors(): void
    {
        $client = static::createClient();

        // Test empty payload
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testRegisterInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'not-an-email',
            'password' => 'secret123',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    public function testRegisterPasswordTooShort(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => '12345',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('password', $data['errors']);
    }

    public function testLoginValidationErrors(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testTodoEndpointRequiresAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/todos');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
