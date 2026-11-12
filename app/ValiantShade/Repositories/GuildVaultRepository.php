<?php

namespace App\ValiantShade\Repositories;

use App\Models\GuildVault;
use App\ValiantShade\Contracts\GuildVaultRepositoryInterface;
use Illuminate\Support\Facades\DB;

class GuildVaultRepository implements GuildVaultRepositoryInterface
{
    public function getVault(): GuildVault
    {
        return GuildVault::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Valiant Shade Lonca Kasası',
                'balance_sp' => 30000.00,
                'notes' => 'Parti ve loncanın fiziki nakit kasası.',
            ]
        );
    }

    public function updateBalance(float $balanceSp, ?string $notes = null): GuildVault
    {
        $vault = $this->getVault();
        $vault->balance_sp = max(0, $balanceSp);
        if ($notes !== null) {
            $vault->notes = $notes;
        }
        $vault->save();

        return $vault;
    }

    public function adjustBalance(float $deltaSp, ?string $notes = null): GuildVault
    {
        return DB::transaction(function () use ($deltaSp, $notes) {
            $vault = $this->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            $vault->balance_sp = max(0, ((float) $vault->balance_sp) + $deltaSp);
            if ($notes !== null) {
                $vault->notes = $notes;
            }
            $vault->save();

            return $vault;
        });
    }

    public function resetToDefault(): GuildVault
    {
        return $this->updateBalance(30000.00, 'Parti ve loncanın fiziki nakit kasası.');
    }
}
