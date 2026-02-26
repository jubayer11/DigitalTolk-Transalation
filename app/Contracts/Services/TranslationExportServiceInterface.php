<?php

namespace App\Contracts\Services;

interface TranslationExportServiceInterface
{
    public function export(string $locale, array $tags = []): array;

    public function clearExportCache(): void;
}
