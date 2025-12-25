<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Implementations\CsvDataProvider;
use Illuminate\Support\Facades\Storage;

class CsvDataProviderTest extends TestCase
{
    private CsvDataProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CsvDataProvider();
    }

    public function test_get_data_parses_csv_with_default_columns(): void
    {
        $csvContent = "John Doe,john@example.com,Pizza,2024-01-15,U12345\n";
        $csvContent .= "Jane Smith,jane@example.com,Burger,2024-01-16,U67890\n";
        
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->provider->getData($filePath);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('Pizza', $result[0]['choice']);
        $this->assertEquals('2024-01-15', $result[0]['date']);
        $this->assertEquals('U12345', $result[0]['slack_id']);
    }

    public function test_get_data_parses_csv_with_header(): void
    {
        $csvContent = "Name,Email,Meal Choice,Date,Slack ID\n";
        $csvContent .= "John Doe,john@example.com,Pizza,2024-01-15,U12345";
        
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->provider->getData($filePath);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_get_data_handles_different_header_variations(): void
    {
        $variations = [
            "Employee,Email,Choice,Date,Slack ID\n",
            "Employee Name,E-mail,Meal,Date,Slack\n",
            "name,email,meal choice,date,slack_user_id\n",
        ];

        foreach ($variations as $header) {
            $csvContent = $header . "John Doe,john@example.com,Pizza,2024-01-15,U12345";
            $filePath = $this->createTempCsvFile($csvContent);
            
            $result = $this->provider->getData($filePath);
            
            $this->assertCount(1, $result, "Failed for header: {$header}");
            $this->assertEquals('John Doe', $result[0]['name']);
        }
    }

    public function test_get_data_normalizes_dates(): void
    {
        $csvContent = "John Doe,john@example.com,Pizza,01/15/2024,U12345\n";
        $csvContent .= "Jane Smith,jane@example.com,Burger,2024-01-16,U67890\n";
        $csvContent .= "Bob Wilson,bob@example.com,Salad,2024-01-17,U11111\n";
        
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->provider->getData($filePath);
        
        $this->assertCount(3, $result);
        $this->assertEquals('2024-01-15', $result[0]['date']);
        $this->assertEquals('2024-01-16', $result[1]['date']);
        $this->assertEquals('2024-01-17', $result[2]['date']);
    }

    public function test_get_data_skips_invalid_rows(): void
    {
        $csvContent = "John Doe,john@example.com,Pizza,2024-01-15,U12345\n";
        $csvContent .= "Jane Smith,,Burger,2024-01-16,U67890\n"; // Missing email
        $csvContent .= ",jane@example.com,Salad,2024-01-17,U11111\n"; // Missing name
        $csvContent .= "Bob Wilson,bob@example.com,,2024-01-18,U22222\n"; // Missing choice
        $csvContent .= "Alice Brown,alice@example.com,Pasta,invalid-date,U33333\n"; // Invalid date
        $csvContent .= "Valid User,valid@example.com,Steak,2024-01-19,U44444";
        
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->provider->getData($filePath);
        
        $this->assertCount(2, $result); // Only valid rows
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Valid User', $result[1]['name']);
    }

    public function test_get_data_handles_optional_slack_id(): void
    {
        $csvContent = "John Doe,john@example.com,Pizza,2024-01-15,U12345\n";
        $csvContent .= "Jane Smith,jane@example.com,Burger,2024-01-16,\n"; // No Slack ID
        $csvContent .= "Bob Wilson,bob@example.com,Salad,2024-01-17,\n"; // No Slack ID column
        
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->provider->getData($filePath);
        
        $this->assertCount(3, $result);
        $this->assertEquals('U12345', $result[0]['slack_id']);
        $this->assertNull($result[1]['slack_id']);
        $this->assertNull($result[2]['slack_id']);
    }

    public function test_get_data_throws_exception_for_nonexistent_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not open file');
        
        $this->provider->getData('/nonexistent/file.csv');
    }

    public function test_get_data_handles_empty_file(): void
    {
        $filePath = $this->createTempCsvFile('');
        
        $result = $this->provider->getData($filePath);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_data_trims_whitespace(): void
    {
        $csvContent = "  John Doe  ,  john@example.com  ,  Pizza  ,  2024-01-15  ,  U12345  \n";
        
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->provider->getData($filePath);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('Pizza', $result[0]['choice']);
    }

    /**
     * Create a temporary CSV file for testing
     */
    private function createTempCsvFile(string $content): string
    {
        $filePath = sys_get_temp_dir() . '/' . uniqid('test_', true) . '.csv';
        file_put_contents($filePath, $content);
        
        return $filePath;
    }

    protected function tearDown(): void
    {
        // Clean up any temp files if needed
        parent::tearDown();
    }
}

