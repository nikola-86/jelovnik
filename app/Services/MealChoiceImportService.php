<?php

namespace App\Services;

use App\Services\MealChoiceProcessor;

class MealChoiceImportService
{
    public function __construct(
        private MealChoiceProcessor $processor
    ) {}

    /**
     * Import meal choices from a file
     *
     * @param string $filePath Path to the uploaded file
     * @return array Statistics: ['created' => int, 'updated' => int, 'total' => int]
     * @throws \Exception
     */
    public function importFromFile(string $filePath): array
    {
        return $this->processor->process($filePath);
    }
}

