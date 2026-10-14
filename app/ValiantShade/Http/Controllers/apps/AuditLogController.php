<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\ActivityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected ActivityAuditService $auditService
    ) {}

    /**
     * Display the Security Audit Log dashboard (Spatie activity_log).
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'event' => $request->get('event') ?: $request->get('action'),
            'log_name' => $request->get('log_name'),
            'subject_type' => $request->get('subject_type'),
            'subject_id' => $request->get('subject_id'),
            'causer_id' => $request->get('causer_id'),
            'actor' => $request->get('actor'),
            'search' => $request->get('search'),
            'period' => $request->get('period'),
            'from' => $request->get('from'),
            'to' => $request->get('to'),
        ];

        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

        $activities = $this->auditService->getActivities($filters, (int) $request->get('limit', 250));
        $logs = $activities->map(fn ($a) => $this->auditService->formatForUi($a));
        $stats = $this->auditService->getStats($filters);

        $reportPeriod = $request->get('report_period', 'daily');
        $periodReport = $this->auditService->getPeriodReport(
            is_string($reportPeriod) ? $reportPeriod : 'daily',
            (int) $request->get('report_buckets', 14)
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'logs' => $logs,
                'stats' => $stats,
                'period_report' => $periodReport,
            ]);
        }

        return view('valiant-shade.content.apps.app-audit-logs', compact(
            'logs',
            'stats',
            'filters',
            'periodReport',
            'reportPeriod'
        ));
    }

    /**
     * Clear all activity log entries (Admin only).
     */
    public function clear(Request $request): JsonResponse
    {
        $this->auditService->clearAll();

        return response()->json([
            'success' => true,
            'message' => __('Activity log history was cleared successfully.'),
        ]);
    }
}
