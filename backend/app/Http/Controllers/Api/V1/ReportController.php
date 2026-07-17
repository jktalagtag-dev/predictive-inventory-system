<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Reporting\Services\ReportException;
use App\Domains\Reporting\Services\ReportRunner;
use App\Domains\Reporting\Support\ReportCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RunReportRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function __construct(private readonly ReportRunner $reportRunner)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('reports.read')) {
            throw new AuthorizationException;
        }

        $definitions = ReportCatalog::visibleTo($request->user())->map(fn ($definition) => $definition->toArray())->values();

        return response()->json([
            'data' => $definitions,
            'meta' => ['requestId' => $this->correlationId($request)],
        ]);
    }

    public function show(RunReportRequest $request, string $reportCode): JsonResponse
    {
        if (! $request->user()->hasPermission('reports.read')) {
            throw new AuthorizationException;
        }

        try {
            $result = $this->reportRunner->run($reportCode, $request->validated(), $request->user());
        } catch (ReportException $exception) {
            return response()->json([
                'error' => ['code' => $exception->errorCode(), 'message' => $exception->getMessage(), 'requestId' => $this->correlationId($request)],
            ], $exception->status());
        }

        return response()->json([
            'data' => ['columns' => $result['columns'], 'rows' => $result['rows'], 'aggregates' => $result['aggregates']],
            'meta' => [...$result['meta'], 'requestId' => $this->correlationId($request)],
        ]);
    }

    private function correlationId(Request $request): string
    {
        return $request->attributes->get('correlation_id') ?? (string) Str::uuid();
    }
}
