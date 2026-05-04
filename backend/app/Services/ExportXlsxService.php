<?php

namespace App\Services;

use App\Http\Requests\Ticket\ExportXlsxRequest;
use Illuminate\Support\Facades\Http;

class ExportXlsxService
{
    const array FIELDS_MAPPING = [
        /*
         * '<поле из фронта>' переводим в '<поле для tracker query>'
         */
        'deadline' => 'Deadline',
        'evaluation' => 'TICKET.Evaluation',
        'actual_amount_of_hours' => 'TICKET.Actual_amount_of_hours',
    ];

    public function fetch(ExportXlsxRequest $request): array
    {
        return $this->export($request->validated());
    }

    private function export(array $filters): array
    {
        $response = Http::withToken(config('yandex-tracker.oauth_token'), 'OAuth')
            ->withHeaders([
                'X-Cloud-Org-Id' => config('yandex-tracker.x_cloud_org_id'),
            ])
            ->acceptJson()
            ->timeout(30)
            ->post(config('yandex-tracker.api_url') . '/v3/entities/report/', $this->formRequestBody($filters));

        $response->throw();

        return [
            'id' => $response->json()['id']
        ];
    }

    private function formRequestBody(array $filters): array
    {
        return [
            'fields' => [
                'summary' => 'Выгрузка задач A25',
                'parameters' => [
                    'type' => 'issueFilterExport',
                    'format' => 'xlsx',
                    'filter' => [
                        'query' => $this->formQuery($filters),
                        'sorts' => [
                            [
                                'orderBy' => 'updated',
                                'orderAsc' => false,
                            ],
                        ],
                    ],
                    'fields' => [
                        'priority',
                        'type',
                        'key',
                        'summary',
                        'assignee',
                        'status',
                        'updated',
                        '69f66b9ef4141b7ae87d4e6e--actualAmountOfHours',
                        '69f66b9ef4141b7ae87d4e6e--evaluation_custom',
                        'deadline'
                    ],
                ],
            ],
        ];
    }

    private function formQuery(array $filters): string
    {
        $conditions = [];

        if (!empty($filters['statuses'])) {
            $statuses = implode(',', array_map(
                static fn (string $status): string => '"' . $status . '"',
                $filters['statuses'],
            ));

            $conditions[] = '"Status": ' . $statuses;
        }

        if ($this->hasFilter($filters, 'deadline')) {
            $conditions[] = sprintf(
                '"%s": %s',
                self::FIELDS_MAPPING['deadline'],
                $filters['deadline'],
            );
        }

        if ($this->hasFilter($filters, 'evaluation_min') || $this->hasFilter($filters, 'evaluation_max')) {
            $evaluationMin = $this->hasFilter($filters, 'evaluation_min')
                ? $filters['evaluation_min']
                : 0;

            $evaluationMax = $this->hasFilter($filters, 'evaluation_max')
                ? $filters['evaluation_max']
                : 9999;

            $conditions[] = sprintf(
                '%s: %s .. %s',
                self::FIELDS_MAPPING['evaluation'],
                $evaluationMin,
                $evaluationMax,
            );
        }

        if ($this->hasFilter($filters, 'actual_amount_of_hours')) {
            $conditions[] = sprintf(
                '%s: %s',
                self::FIELDS_MAPPING['actual_amount_of_hours'],
                $filters['actual_amount_of_hours'],
            );
        }

        $query = collect($conditions)
            ->map(static fn (string $condition): string => "({$condition})")
            ->implode(' AND ');

        return trim($query . ' Queue: TICKET "Sort by": Updated DESC');
    }

    private function hasFilter(array $filters, string $key): bool
    {
        return array_key_exists($key, $filters)
            && $filters[$key] !== null
            && $filters[$key] !== '';
    }
}
