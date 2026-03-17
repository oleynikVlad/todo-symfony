<?php

namespace App\Controller;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * AuthController — Handles user registration and JWT login.
 *
 * LARAVEL vs SYMFONY — Controllers:
 * - Laravel: class AuthController extends Controller { ... }
 *   Uses Request $request dependency injection or FormRequest.
 *   Returns response()->json([...]) or new JsonResponse.
 *
 * - Symfony: class AuthController extends AbstractController { ... }
 *   Uses Request $request from HttpFoundation.
 *   Returns new JsonResponse([...]).
 *
 * ROUTING:
 * - Laravel: Route::post('/api/register', [AuthController::class, 'register']);
 *   Defined in routes/api.php
 * - Symfony: #[Route('/api/register', methods: ['POST'])] attribute on method.
 *   Routes are defined directly on controller methods (or in YAML/XML).
 *
 * AbstractController provides helpers like:
 * - $this->json() — returns JsonResponse
 * - $this->getUser() — gets authenticated user (like auth()->user() in Laravel)
 * - $this->denyAccessUnlessGranted() — authorization check
 */
#[Route('/api')]
#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ValidatorInterface $validator,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Register a new user',
        description: 'Creates a new user account with email and password.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123', minLength: 6),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully.'),
                        new OA\Property(property: 'user', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                        ], type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Validation error or user already exists'),
        ],
    )]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new RegisterRequest();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->authService->register($dto);

        return $this->json([
            'message' => 'User registered successfully.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        summary: 'Login and get JWT token',
        description: 'Authenticates user and returns a JWT token for API access.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Invalid credentials'),
        ],
    )]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new LoginRequest();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->authService->validateCredentials($dto);
        $token = $this->jwtManager->create($user);

        return $this->json(['token' => $token]);
    }
}
