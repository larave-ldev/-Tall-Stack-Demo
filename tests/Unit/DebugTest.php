<?php

it('should not contain dd(,die,echo,print_r statements', function () {
    // Specify the root directories to start the search
    $rootDirectories = ['app/', 'routes/', 'database/'];

    foreach ($rootDirectories as $rootDirectory) {
        // Create a RecursiveIteratorIterator to iterate through files
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDirectory));

        foreach ($iterator as $file) {
            // Check if the file is a PHP file
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Read the content of the file
                $fileContent = file_get_contents($file->getPathname());

                $this->assertStringNotContainsString('dd(', $fileContent, "File {$file->getPathname()} contains 'dd()' statement.");
                $this->assertStringNotContainsString('die', $fileContent, "File {$file->getPathname()} contains 'die' statement.");
                $this->assertStringNotContainsString('echo', $fileContent, "File {$file->getPathname()} contains 'echo' statement.");
                $this->assertStringNotContainsString('print_r', $fileContent, "File {$file->getPathname()} contains 'print_r' statement.");
            }
        }
    }
});
