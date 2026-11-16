<?php

namespace App\ValiantShade\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LockQuestReportMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        $lock = fopen(storage_path('app/quest_reports.lock'), 'c');
        if ($lock === false) {
            throw new \RuntimeException('Görev raporları işlem kilidi açılamadı.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Görev raporları işlem kilidi alınamadı.');
            }

            return $next($request);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
