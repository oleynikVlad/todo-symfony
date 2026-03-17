<?php

namespace App\Controller;

use App\DTO\CreateTodoRequest;
use App\DTO\PaginatedResponse;
use App\DTO\TodoResponse;
use App\DTO\UpdateTodoRequest;
use App\Entity\User;
use App\Service\TodoService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * TodoController — CRUD operations for Todo items.
 *
 * LARAVEL vs SYMFONY — Route Groups & Middleware:
 * - Laravel: Route::middleware('auth:sanctum')->prefix('api/todos')->group(function () { ... });
 * - Symfony: #[Route('/api/todos')] on the class, security via security.yaml firewalls & access_control.
 *
 * LARAVEL vs SYMFONY — Getting Auth User:
 * - Laravel: $request->user() or auth()->user()
 * - Symfony: $this->getUser() (from AbstractController) or inject Security service
 *
 * LARAVEL vs SYMFONY — Authorization:
 * - Laravel: Policies (TodoPolicy) + $this->authorize('update', $todo)
 * - Symfony: Voters + $this->denyAccessUnlessGranted('EDIT', $todo)
 *   For simplicity here, we check ownership in the service layer.
 */
#[Route('/api/todos')]
#[OA\Tag(name: 'Todos')]
class TodoController extends AbstractController
{
    public function __construct(
        private readonly TodoService $todoService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_todos_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'List all todos for the authenticated user',
        description: 'Returns a paginated list of todos. Supports filtering by status, date range, and sorting.',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'completed'])),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-12-31')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of todos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'Buy groceries'),
                                new OA\Property(property: 'description', type: 'string', example: 'Milk, eggs, bread'),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                            ],
                        )),
                        new OA\Property(property: 'total', type: 'integer', example: 25),
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'limit', type: 'integer', example: 10),
                        new OA\Property(property: 'totalPages', type: 'integer', example: 3),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 10)));
        $status = $request->query->get('status');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $sort = $request->query->get('sort', 'desc');

        $result = $this->todoService->listTodos($user, $page, $limit, $status, $dateFrom, $dateTo, $sort);

        $todoResponses = array_map(
            fn ($todo) => TodoResponse::fromEntity($todo),
            $result['items'],
        );

        $paginated = new PaginatedResponse($todoResponses, $result['total'], $result['page'], $result['limit']);

        return $this->json($paginated);
    }

    #[Route('', name: 'api_todos_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a new todo',
        description: 'Creates a new todo item for the authenticated user.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Buy groceries'),
                    new OA\Property(property: 'description', type: 'string', example: 'Milk, eggs, bread', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed'], example: 'pending'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Todo created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Todo created successfully.'),
                        new OA\Property(property: 'todo', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'title', type: 'string', example: 'Buy groceries'),
                            new OA\Property(property: 'description', type: 'string', example: 'Milk, eggs, bread'),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                        ], type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new CreateTodoRequest();
        $dto->title = $data['title'] ?? '';
        $dto->description = $data['description'] ?? null;
        $dto->status = $data['status'] ?? 'pending';

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $todo = $this->todoService->createTodo($dto, $user);

        return $this->json([
            'message' => 'Todo created successfully.',
            'todo' => TodoResponse::fromEntity($todo),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_todos_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a single todo',
        description: 'Returns a specific todo by ID (must belong to authenticated user).',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todo details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'todo', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'title', type: 'string', example: 'Buy groceries'),
                            new OA\Property(property: 'description', type: 'string', example: 'Milk, eggs, bread'),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                        ], type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — not your todo'),
            new OA\Response(response: 404, description: 'Todo not found'),
        ],
    )]
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $todo = $this->todoService->getTodo($id, $user);

        return $this->json(['todo' => TodoResponse::fromEntity($todo)]);
    }

    #[Route('/{id}', name: 'api_todos_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(
        summary: 'Update a todo',
        description: 'Updates an existing todo item. Only provided fields will be changed.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Updated title', nullable: true),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated description', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed'], nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todo updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Todo updated successfully.'),
                        new OA\Property(property: 'todo', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'description', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                        ], type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Todo not found'),
        ],
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new UpdateTodoRequest();
        $dto->title = $data['title'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->status = $data['status'] ?? null;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $todo = $this->todoService->updateTodo($id, $dto, $user);

        return $this->json([
            'message' => 'Todo updated successfully.',
            'todo' => TodoResponse::fromEntity($todo),
        ]);
    }

    #[Route('/{id}', name: 'api_todos_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete a todo',
        description: 'Deletes a todo item (must belong to authenticated user).',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todo deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Todo deleted successfully.'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Todo not found'),
        ],
    )]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->todoService->deleteTodo($id, $user);

        return $this->json(['message' => 'Todo deleted successfully.']);
    }
}
