<?php

declare(strict_types=1);

namespace papi\Resource;

use JsonException;
use papi\Database\Paginator\PaginatorFactory;
use papi\Database\PostgresDb;
use papi\Relation\ManyToMany;
use papi\Relation\ManyToManyValidator;
use papi\Response\ErrorResponse;
use papi\Response\JsonResponse;
use papi\Response\NotFoundResponse;
use papi\Response\OKResponse;
use papi\Response\ValidationErrorResponse;
use Workerman\Protocols\Http\Request;

/**
 * Handles Create Read & Delete operations on many to many relations
 */
class ManyToManyHandler
{
    /**
     * Creates many to many relation
     *
     * @param ManyToMany $relation
     * @param Request    $request
     *
     * @return JsonResponse
     * @throws JsonException
     */
    public static function createRelation(
        ManyToMany $relation,
        Request $request
    ): JsonResponse {
        try {
            $body = json_decode($request->rawBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new ValidationErrorResponse('Body cannot be empty');
        }

        if (($validationErrors = (new ManyToManyValidator())->getValidationErrors($relation, $body)) !== null) {
            return new ValidationErrorResponse($validationErrors);
        }

        $postgres = new PostgresDb();

        if ($relation->exists(
            $postgres,
            $body[$relation->rootResourceIdField],
            $body[$relation->relatedResourceIdField],
        )
        ) {
            return new ErrorResponse('Relation already exists');
        }

        $relation->create(
            $postgres,
            $body[$relation->rootResourceIdField],
            $body[$relation->relatedResourceIdField],
        );

        return new JsonResponse(
            201,
            $body
        );
    }

    /**
     * Deletes many to many relation
     *
     * @param ManyToMany $relation
     * @param string     $rootResourceId
     * @param string     $relatedResourceId
     *
     * @return JsonResponse
     * @throws JsonException
     */
    public static function deleteRelation(
        ManyToMany $relation,
        string $rootResourceId,
        string $relatedResourceId
    ): JsonResponse {
        $response = $relation->delete(
            new PostgresDb(),
            $rootResourceId,
            $relatedResourceId
        );

        if ($response === 0) {
            return new NotFoundResponse();
        }

        return new JsonResponse(204);
    }

    /**
     * Gets many to many relations
     *
     * @param ManyToMany $relation
     * @param Request    $request
     * @param bool       $pagination
     * @param int        $paginationItems
     *
     * @return JsonResponse
     */
    public static function getRelation(
        ManyToMany $relation,
        Request $request,
        bool $pagination = true,
        int $paginationItems = 10
    ): JsonResponse {
        $filters = [];

        if ($stringQuery = $request->queryString()) {
            parse_str($stringQuery, $filters);
        }

        $validationErrors = (new ManyToManyQueryValidator())->getValidationErrors($relation, $filters);

        if ($validationErrors !== null) {
            return new ValidationErrorResponse($validationErrors);
        }

        if ($pagination === true) {
            $paginator = PaginatorFactory::getCursorPaginator($filters, $paginationItems);
            $result = $paginator->getPaginatedManyToManyResults($relation, $filters);
        } else {
            $result = $relation->get(new PostgresDb(), $filters);
        }

        return new OKResponse($result);
    }
}
