<?php

namespace App\Http\Controllers\Api\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\ExportXlsxRequest;
use App\Services\ExportXlsxService;
use OpenApi\Attributes as OA;

class ExportXlsxController extends Controller
{
    public function __construct(
        private readonly ExportXlsxService $exportXlsxService,
    ) {}

    #[OA\Get(
        path: '/api/ticket/export-xlsx',
        operationId: 'ticket.export.index',
        description: 'Выгрузка задач в Excel с фильтрацией.',
        summary: 'Выгрузить задачи',
        tags: ['Ticket'],
        parameters: [
            new OA\Parameter(
                name: 'statuses[]',
                description: 'Статусы',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(
                        type: 'string',
                        enum: ['backlog', 'open', 'closed', 'inProgress', 'cancelled'],
                    ),
                ),
            ),
            new OA\Parameter(
                name: 'deadline',
                description: 'Дедлайн',
                in: 'query',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-05-03'),
            ),
            new OA\Parameter(
                name: 'evaluation_min',
                description: 'оценка от',
                in: 'query',
                schema: new OA\Schema(type: 'number', format: 'float', example: 0, minimum: 0),
            ),
            new OA\Parameter(
                name: 'evaluation_max',
                description: 'оценка до',
                in: 'query',
                schema: new OA\Schema(type: 'number', format: 'float', example: 5, minimum: 0),
            ),
            new OA\Parameter(
                name: 'actual_amount_of_hours',
                description: 'Реально затраченное время',
                in: 'query',
                schema: new OA\Schema(type: 'number', format: 'float', example: 1, minimum: 0),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'id отчета из трекера',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: '123a456b789c')
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function index(ExportXlsxRequest $request)
    {
        return $this->exportXlsxService->fetch($request);
    }
}
