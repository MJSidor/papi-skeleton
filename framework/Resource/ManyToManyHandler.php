<?php
declare(strict_types=1);

namespace framework\Resource;

use framework\Database\MedooHandler;
use framework\Relation\ManyToMany;
use framework\Relation\ManyToManyValidator;
use framework\Response\JsonResponse;
use Workerman\Protocols\Http\Request;

class ManyToManyHandler
{
    public static function createRelation(
        ManyToMany $relation,
        Request $request
    ): JsonResponse {
        if (! RequestMethodChecker::isPost($request)) {
            return new JsonResponse(405, ['Method not allowed'], ['Allow' => 'POST']);
        }

        $stringBody = $request->rawBody();
        $body = json_decode($stringBody, true);

        $validationErrors = (new ManyToManyValidator())->getValidationErrors($relation, $body);

        if ($validationErrors) {
            return new JsonResponse(400, [$validationErrors]);
        }

        try {
            $handler = MedooHandler::getDbHandler();

            if ($handler->has(
                $relation->getTableNameWithoutDatabase(),
                [
                    $relation->rootResourceIdField    => $body[$relation->rootResourceIdField],
                    $relation->relatedResourceIdField => $body[$relation->relatedResourceIdField],
                ]
            )) {
                return new JsonResponse(400, ['Relation already exists']);
            }

            $result = $handler->insert(
                $relation->getTableNameWithoutDatabase(),
                [
                    $relation->rootResourceIdField    => $body[$relation->rootResourceIdField],
                    $relation->relatedResourceIdField => $body[$relation->relatedResourceIdField],
                ]
            );
            $errorCode = $result->errorCode();
        } catch (\PDOException $exception) {
            return new JsonResponse(500, [$exception->getMessage()]);
        }

        if ($errorCode === "00000") {
            return new JsonResponse(
                201,
                $body
            );
        }

        return new JsonResponse(500, ['Unknown database error']);
    }

    public static function deleteRelation(
        ManyToMany $relation,
        string $rootResourceId,
        string $relatedResourceId,
        Request $request
    ): JsonResponse {
        if (! RequestMethodChecker::isDelete($request)) {
            return new JsonResponse(405, ['Method not allowed'], ['Allow' => 'DELETE']);
        }

        try {
            $rowsAffected = MedooHandler::getDbHandler()
                                        ->delete(
                                            $relation->getTableNameWithoutDatabase(),
                                            [
                                                $relation->rootResourceIdField    => $rootResourceId,
                                                $relation->relatedResourceIdField => $relatedResourceId,
                                            ]
                                        )
                                        ->rowCount()
            ;
        } catch (\PDOException $exception) {
            return new JsonResponse(500, [$exception->getMessage()]);
        }

        if ($rowsAffected) {
            return new JsonResponse(204);
        }

        return new JsonResponse(404);
    }
}