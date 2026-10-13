<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditService
    ) {}

    /**
     * Display the Security Audit Log dashboard.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'action' => $request->get('action'),
            'actor' => $request->get('actor'),
            'search' => $request->get('search'),
        ];

        $logs = $this->auditService->getLogs($filters);
        $stats = $this->auditService->getStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'logs' => $logs,
                'stats' => $stats,
            ]);
        }

        return view('valiant-shade.content.apps.app-audit-logs', compact('logs', 'stats', 'filters'));
    }

    /**
     * Clear all audit logs (Admin only).
     */
    public function clear(Request $request): JsonResponse
    {
        $this->auditService->clearLogs();

        return response()->json([
            'success' => true,
            'message' => __('Audit log history was cleared successfully.'),
        ]);
    }
}
