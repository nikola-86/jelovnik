<?php

namespace App\Services\Interfaces;

interface DataProviderInterface
{
    /**
     * Get data from a source
     *
     * @param string $source Source identifier (file path, date, API endpoint, etc.)
     * @return array Array of parsed rows, each row should contain: name, email, choice, date, slack_id (optional)
     * @throws \RuntimeException If data cannot be retrieved
     */
    public function getData(string $source): array;
}

