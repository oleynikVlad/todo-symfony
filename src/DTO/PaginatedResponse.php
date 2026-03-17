<?php

namespace App\DTO;

/**
 * Generic paginated response DTO.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: ->paginate() returns LengthAwarePaginator that auto-serializes with
 *   data, current_page, last_page, per_page, total, links, etc.
 * - Symfony: No built-in paginator response. Build your own DTO (more control).
 */
class PaginatedResponse
{
    /** @var list<mixed> */
    public array $data;
    public int $total;
    public int $page;
    public int $limit;
    public int $totalPages;

    /**
     * @param list<mixed> $data
     */
    public function __construct(array $data, int $total, int $page, int $limit)
    {
        $this->data = $data;
        $this->total = $total;
        $this->page = $page;
        $this->limit = $limit;
        $this->totalPages = $limit > 0 ? (int) ceil($total / $limit) : 0;
    }
}
