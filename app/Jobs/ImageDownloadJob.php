<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductColourList;
use App\Models\ProductMedia;
use App\Services\FileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ImageDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $model;
    private string $dir;
    private string $url;

    /**
     * Create a new job instance.
     */
    public function __construct($model, $url, $dir)
    {
        $this->model = $model;
        $this->dir = $dir;
        $this->url = $url;
    }

    /**
     * Execute the job.
     */
    public function handle(FileService $fileService): void
    {
        $path = $fileService->downloadImages($this->url, Storage::disk('products'), $this->dir, 3);
        if ($this->model instanceof Product) {
            $this->model->update(['downloaded_hero_image' => $path]);
        }
        if ($this->model instanceof ProductMedia) {
            $this->model->update(['downloaded_url' => $path]);
        }
        if ($this->model instanceof ProductColourList) {
            $this->model->update(['downloaded_image' => $path]);
        }

        if (file_exists($path)) {
            // Set permissions for a file 0644
            chmod($path, 0644);
        }
    }
}
