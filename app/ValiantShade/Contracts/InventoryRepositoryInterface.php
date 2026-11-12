<?php

namespace App\ValiantShade\Contracts;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface
{
    /**
     * Retrieve all inventory items with optional filtering.
     *
     * @param array $filters
     * @return Collection
     */
    public function all(array $filters = []): Collection;

    /**
     * Find an item by ID.
     *
     * @param int $id
     * @return InventoryItem|null
     */
    public function find(int $id): ?InventoryItem;

    /**
     * Create a new inventory item.
     *
     * @param array $data
     * @return InventoryItem
     */
    public function create(array $data): InventoryItem;

    /**
     * Update an existing inventory item.
     *
     * @param int $id
     * @param array $data
     * @return InventoryItem
     */
    public function update(int $id, array $data): InventoryItem;

    /**
     * Delete an inventory item.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    public function deleteMany(array $ids): int;

    public function findManyForUpdate(array $ids): Collection;

    /**
     * Truncate all items.
     *
     * @return void
     */
    public function truncate(): void;

    /**
     * Count total items.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get distinct item types.
     *
     * @return array
     */
    public function getDistinctTypes(): array;
}
