<?php

namespace App\Helpers;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (! function_exists('isCommandExecuted')) {
    function isCommandExecuted(string $commandName): bool
    {
        // Check if the flag exists in the database
        return Schema::hasTable('command_executions') && DB::table('command_executions')->where('command_name', $commandName)->exists();
    }
}

if (! function_exists('markCommandAsExecuted')) {
    function markCommandAsExecuted(string $commandName): void
    {
        // Create the flag in the database to indicate that the command has been executed
        if (Schema::hasTable('command_executions')) {
            DB::table('command_executions')->insert(['command_name' => $commandName]);
        }
    }
}
if (! function_exists('checkQueueExist')) {
    function checkQueueProcessExist(string $key, string $action): bool
    {
        $value = Cache()->get($key);
        $batch = $value ? Bus::findBatch($value) : null;
        if ($batch && $batch->processedJobs() !== $batch->totalJobs) {
            Notification::make($key)
                ->warning()
                ->title('Already one ' . Str::lower($action) . ' queue is running. Please wait until completed.')
                ->send();

            return true;
        } else {
            return false;
        }
    }
}
if (! function_exists('cleanJsonString')) {
    // Helper function to clean JSON string
    function cleanJsonString($jsonString)
    {
        if (! empty($jsonString)) {
            $jsonString = str_replace('\n', '\\\\n', $jsonString);

            return str_replace('\t', '', trim($jsonString));
        }

        return null;
    }
}
// Helper function to clean string
if (! function_exists('cleanString')) {
    function cleanString($inputString)
    {
        return str_replace('\t', '', trim($inputString) ?? null);
    }
}

// Helper function to compare two images
if (! function_exists('compareImages')) {
    function compareImages(?string $url1, ?string $url2): string
    {
        $result = 'Invalid image URL';

        if (! $url1 || ! $url2) {
            return $result;
        }

        // Fetch the images
        $image1 = Http::get($url1);
        $image2 = Http::get($url2);

        if ($image1->ok() && $image2->ok()) {
            // Convert images to binary data
            $binaryImage1 = $image1->body();
            $binaryImage2 = $image2->body();

            // Compare images
            if ($binaryImage1 === $binaryImage2) {
                $result = 'Identical';
            } else {
                $result = 'Different';
            }
        } else {
            $result = 'Failed to fetch one or both images.';
        }

        return $result;
    }
}
