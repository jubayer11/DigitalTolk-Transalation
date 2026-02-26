<?php

namespace App\Contracts\Services;

use App\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TranslationServiceInterface
{
    public function create(array $payload): TranslationKey;

    public function update(string $key, array $payload): TranslationKey;

    public function show(string $key): TranslationKey;

    public function search(array $filters): LengthAwarePaginator;

    public function delete(string $key): void;
}
