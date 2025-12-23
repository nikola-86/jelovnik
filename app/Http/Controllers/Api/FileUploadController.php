<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use App\Services\MealChoiceImportService;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    public function __construct(
        private MealChoiceImportService $importService
    ) {}

    /**
     * Handle file upload and import meal choices
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120', // 5MB max
        ]);

        try {
            $filePath = $this->getUploadedFilePath($request->file('file'));
            $stats = $this->importService->importFromFile($filePath);

            return response()->json([
                'message' => 'File processed successfully',
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'total' => $stats['total'],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('File upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error processing file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the file path from uploaded file
     */
    private function getUploadedFilePath(UploadedFile $file): string
    {
        $fullPath = $file->getRealPath();
        
        if (!$fullPath || !file_exists($fullPath)) {
            $fullPath = $file->path();
        }
        
        if (!$fullPath || !file_exists($fullPath)) {
            throw new \RuntimeException('Failed to access uploaded file');
        }

        return $fullPath;
    }
}


