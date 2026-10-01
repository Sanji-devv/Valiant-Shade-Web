<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialLedger;

class CleanQuestPayouts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quest:payouts:clean {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Remove all quest payout related financial ledger entries (character shares and guild cuts) from the database.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will permanently delete all quest payout ledger entries. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        DB::transaction(function () {
            // Delete character share entries
            DB::table('financial_ledger')
                ->where('entity_type', FinancialLedger::ENTITY_CHARACTER)
                ->where('description', 'like', '%Quest share payout%')
                ->delete();

            // Delete guild cut entries
            DB::table('financial_ledger')
                ->where('entity_type', FinancialLedger::ENTITY_GUILD_RESERVE)
                ->where('description', 'like', 'Guild cut%')
                ->delete();
        });

        $this->info('All quest payout related ledger entries have been removed.');
        return 0;
    }
}
?>
