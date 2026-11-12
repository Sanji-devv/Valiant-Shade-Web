<?php

namespace App\ValiantShade\Repositories;

use App\Models\InventoryItem;
use App\ValiantShade\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        $query = InventoryItem::query();

        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $sortCol = $filters['sort_by'] ?? 'id';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $allowedCols = ['id', 'name', 'quality', 'type', 'quantity', 'unit_price', 'total_price', 'market_value', 'buy_price', 'sell_price'];

        if (in_array($sortCol, $allowedCols, true)) {
            $query->orderBy($sortCol, strtolower($sortDir) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query->get();
    }

    public function find(int $id): ?InventoryItem
    {
        return InventoryItem::find($id);
    }

    public function create(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function update(int $id, array $data): InventoryItem
    {
        $item = InventoryItem::findOrFail($id);
        $item->update($data);
        return $item;
    }

    public function delete(int $id): bool
    {
        $item = InventoryItem::find($id);
        return $item ? (bool) $item->delete() : false;
    }

    public function deleteMany(array $ids): int
    {
        return InventoryItem::query()->whereIn('id', $ids)->delete();
    }

    public function findManyForUpdate(array $ids): Collection
    {
        return InventoryItem::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
    }

    public function truncate(): void
    {
        InventoryItem::truncate();
    }

    public function count(): int
    {
        return InventoryItem::count();
    }

    public function getDistinctTypes(): array
    {
        return InventoryItem::query()
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values()
            ->toArray();
    }
}
