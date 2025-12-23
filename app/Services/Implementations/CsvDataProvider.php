<?php

namespace App\Services\Implementations;

use App\Services\Interfaces\DataProviderInterface;

class CsvDataProvider implements DataProviderInterface
{
    public function getData(string $source): array
    {
        $handle = $this->openFile($source);
        $columnMap = $this->buildColumnMap($handle);
        $rows = $this->parseRows($handle, $columnMap);
        fclose($handle);

        return $rows;
    }

    /**
     * Open file and return handle
     */
    private function openFile(string $filePath)
    {
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \RuntimeException('Could not open file');
        }

        return $handle;
    }

    /**
     * Build column mapping from header or use defaults
     */
    private function buildColumnMap($handle): array
    {
        $header = fgetcsv($handle);
        
        if ($header && count($header) >= 4) {
            $map = $this->mapHeaderColumns($header);
            if (!empty($map)) {
                return $map;
            }
        }

        return $this->getDefaultColumnMap();
    }

    /**
     * Map header column names to indices
     */
    private function mapHeaderColumns(array $header): array
    {
        $columnMap = [];

        foreach ($header as $index => $colName) {
            $colNameLower = strtolower(trim($colName));
            
            if (in_array($colNameLower, ['name', 'employee', 'employee name'])) {
                $columnMap['name'] = $index;
            } elseif (in_array($colNameLower, ['email', 'e-mail'])) {
                $columnMap['email'] = $index;
            } elseif (in_array($colNameLower, ['choice', 'meal', 'meal choice'])) {
                $columnMap['choice'] = $index;
            } elseif (in_array($colNameLower, ['date'])) {
                $columnMap['date'] = $index;
            } elseif (in_array($colNameLower, ['slack_id', 'slack id', 'slack', 'slack_user_id'])) {
                $columnMap['slack_id'] = $index;
            }
        }

        return $columnMap;
    }

    /**
     * Get default column mapping
     */
    private function getDefaultColumnMap(): array
    {
        return [
            'name' => 0,
            'email' => 1,
            'choice' => 2,
            'date' => 3,
            'slack_id' => 4,
        ];
    }

    /**
     * Parse CSV rows and return validated data
     */
    private function parseRows($handle, array $columnMap): array
    {
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 4) {
                continue;
            }

            $row = $this->mapRowData($data, $columnMap);

            if (!$this->isValidRow($row)) {
                continue;
            }

            $row['date'] = $this->normalizeDate($row['date']);
            
            if ($row['date'] === null) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Map CSV row data to structured array
     */
    private function mapRowData(array $data, array $columnMap): array
    {
        return [
            'name' => trim($data[$columnMap['name']] ?? $data[0] ?? ''),
            'email' => trim($data[$columnMap['email']] ?? $data[1] ?? ''),
            'choice' => trim($data[$columnMap['choice']] ?? $data[2] ?? ''),
            'date' => trim($data[$columnMap['date']] ?? $data[3] ?? ''),
            'slack_id' => isset($columnMap['slack_id']) && isset($data[$columnMap['slack_id']]) 
                ? trim($data[$columnMap['slack_id']]) 
                : null,
        ];
    }

    /**
     * Validate row has all required fields
     */
    private function isValidRow(array $row): bool
    {
        return !empty($row['email']) 
            && !empty($row['name']) 
            && !empty($row['choice']) 
            && !empty($row['date']);
    }

    /**
     * Normalize date to Y-m-d format
     */
    private function normalizeDate(string $date): ?string
    {
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}

