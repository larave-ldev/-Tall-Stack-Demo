<?php

namespace App\Services;

use Exception;

class FileService
{
    public function downloadImages(string $url, $storageDisk, string $storeDir, int $maxAttempts): ?string
    {
        $try = 1;
        $storagePath = null;
        while ($try <= $maxAttempts) {
            try {//try to get url multiple times
                $file_data = file_get_contents($url, false, stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]));
                $fileName = basename($url); //base name from the url
                $path = $storeDir . $fileName;

                if ($storageDisk->put($path, $file_data)) {
                    $storagePath = $storageDisk->url($path);
                }
            } catch (Exception $exception) {
                //not throwing  error when exception occurs
                return $storagePath;
            }
            $try++;
        }

        return $storagePath;
    }
}
