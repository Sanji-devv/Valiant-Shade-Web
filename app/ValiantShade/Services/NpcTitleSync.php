<?php

namespace App\ValiantShade\Services;

use App\Models\Npc;
use App\Models\NpcTitle;
use Illuminate\Support\Collection;

class NpcTitleSync
{
    /**
     * @param  list<string>|string|null  $titles
     */
    public function sync(Npc $npc, array|string|null $titles): void
    {
        $names = $this->normalizeTitleNames($titles);
        if ($names->isEmpty()) {
            $npc->titles()->detach();

            return;
        }

        $ids = $names
            ->map(static fn (string $name): int => NpcTitle::findOrCreateByName($name)->id)
            ->unique()
            ->values()
            ->all();

        $npc->titles()->sync($ids);
    }

    /**
     * @param  list<string>|string|null  $titles
     * @return Collection<int, string>
     */
    private function normalizeTitleNames(array|string|null $titles): Collection
    {
        if ($titles === null) {
            return collect();
        }

        if (is_string($titles)) {
            $decoded = json_decode($titles, true);
            if (is_array($decoded)) {
                $titles = $decoded;
            } else {
                $titles = preg_split('/[,;\n]+/', $titles) ?: [];
            }
        }

        return collect($titles)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values();
    }
}