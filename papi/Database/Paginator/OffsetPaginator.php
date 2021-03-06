<?php

declare(strict_types=1);

namespace papi\Database\Paginator;

use papi\Database\PostgresDb;
use papi\Resource\Resource;

/**
 * Handles offset pagination
 */
class OffsetPaginator extends Paginator
{
    private ?string $offset;

    private int $limit;

    private string $column;

    private string $order;

    public function __construct(
        string $column,
        int $limit = 10,
        string $order = 'asc',
        ?string $offset = null
    ) {
        $this->limit = $limit;
        $this->column = $column;
        $this->order = $order;
        $this->offset = $offset;
    }

    public function getPaginatedResults(
        Resource $resource,
        array $filters,
        bool $cache = false,
        ?int $cacheTtl = 300
    ): array {
        return $this->addPaginationLinks(
            (new $resource())->get(
                new PostgresDb(),
                $filters,
                [],
                $this->column,
                $this->order,
                $this->limit,
                $this->offset,
                $cache,
                $cacheTtl
            )
        );
    }

    protected function addPaginationLinks(array $response): array
    {
        $offset = (int)$this->offset;

        return array_merge(
            $response,
            [
                '__pagination' => [
                    'type'          => 'OFFSET',
                    'next_page'     => [
                        'offset'  => $offset + $this->limit,
                        'order'   => $this->order,
                        'orderBy' => $this->column,
                    ],
                    'previous_page' => [
                        'offset'  => $offset > $this->limit ? $offset - $this->limit : 0,
                        'order'   => $this->order,
                        'orderBy' => $this->column,
                    ],
                ],
            ]
        );
    }
}
