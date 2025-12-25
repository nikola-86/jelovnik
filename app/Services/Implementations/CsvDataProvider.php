<?php

namespace App\Services\Implementations;

use App\Services\Interfaces\DataProviderInterface;

class CsvDataProvider implements DataProviderInterface
{
    public function getData(string $source): array
    {
        $handle = $this->openFile($source);
        
        // Read first line to check if it's a header
        $firstLine = fgetcsv($handle);
        
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }
        
        $columnMap = $this->determineColumnMap($firstLine);
        $rows = [];
        
        // If first line was a header, parse remaining rows
        // If first line was data, include it in parsing
        if ($this->isHeader($firstLine)) {
            $rows = $this->parseRows($handle, $columnMap);
        } else {
            // First line is data, parse it and remaining rows
            $rows = $this->parseRowData($firstLine, $columnMap, $rows);
            $rows = $this->parseRows($handle, $columnMap, $rows);
        }
        
        fclose($handle);

        return $rows;
    }

    /**
     * Open file and return handle
     */
    private function openFile(string $filePath)
    {
        $handle = @fopen($filePath, 'r');
        
        if ($handle === false) {
            $error = error_get_last();
            throw new \RuntimeException('Could not open file' . ($error ? ': ' . $error['message'] : ''));
        }

        return $handle;
    }

    /**
     * Check if a line is a header row
     */
    private function isHeader(array $line): bool
    {
        if (count($line) < 4) {
            return false;
        }
        
        $map = $this->mapHeaderColumns($line);
        return !empty($map) && count($map) >= 4;
    }

    /**
     * Determine column mapping from first line or use defaults
     */
    private function determineColumnMap(array $firstLine): array
    {
        if ($this->isHeader($firstLine)) {
            return $this->mapHeaderColumns($firstLine);
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
    private function parseRows($handle, array $columnMap, array $existingRows = []): array
    {
        $rows = $existingRows;

        while (($data = fgetcsv($handle)) !== false) {
            $rows = $this->parseRowData($data, $columnMap, $rows);
        }

        return $rows;
    }

    /**
     * Parse a single row of data and add to rows array if valid
     */
    private function parseRowData(array $data, array $columnMap, array $rows): array
    {
        if (count($data) < 4) {
            return $rows;
        }

        $row = $this->mapRowData($data, $columnMap);

        if (!$this->isValidRow($row)) {
            return $rows;
        }

        $row['date'] = $this->normalizeDate($row['date']);
        
        if ($row['date'] === null) {
            return $rows;
        }

        $rows[] = $row;
        return $rows;
    }

    /**
     * Map CSV row data to structured array
     */
    private function mapRowData(array $data, array $columnMap): array
    {
        $slackId = null;
        if (isset($columnMap['slack_id']) && isset($data[$columnMap['slack_id']])) {
            $slackIdValue = trim($data[$columnMap['slack_id']]);
            $slackId = $slackIdValue !== '' ? $slackIdValue : null;
        }
        
        return [
            'name' => trim($data[$columnMap['name']] ?? $data[0] ?? ''),
            'email' => trim($data[$columnMap['email']] ?? $data[1] ?? ''),
            'choice' => trim($data[$columnMap['choice']] ?? $data[2] ?? ''),
            'date' => trim($data[$columnMap['date']] ?? $data[3] ?? ''),
            'slack_id' => $slackId,
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

