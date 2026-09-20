<?php

namespace App\ValiantShade\Contracts;

use App\Models\GuildVault;

interface GuildVaultRepositoryInterface
{
    public function getVault(): GuildVault;

    public function updateBalance(float $balanceSp, ?string $notes = null): GuildVault;

    public function adjustBalance(float $deltaSp, ?string $notes = null): GuildVault;

    public function resetToDefault(): GuildVault;
}
