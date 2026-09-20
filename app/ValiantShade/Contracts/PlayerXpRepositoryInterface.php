<?php

namespace App\ValiantShade\Contracts;

use App\Models\PlayerXp;
use Illuminate\Database\Eloquent\Collection;

interface PlayerXpRepositoryInterface
{
    public function all(array $filters = []): Collection;

    public function find(int $id): ?PlayerXp;

    public function create(array $data): PlayerXp;

    public function update(int $id, array $data): PlayerXp;

    public function delete(int $id): bool;

    public function count(array $filters = []): int;

    public function truncate(): void;

    public function getStatistics(): array;
}
