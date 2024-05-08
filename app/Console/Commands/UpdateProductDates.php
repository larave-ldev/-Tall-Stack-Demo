<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateProductDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-product-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dates for Product model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productsToUpdate = Product::whereNull('created_at')->orWhereNull('updated_at')->get();

        $now = Carbon::now();
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        // Update only null timestamps for each product
        $productsToUpdate->each(function ($product) use ($now, $threeMonthsAgo) {
            if ($product->created_at) {
                $product->created_at = $threeMonthsAgo;
            }
            if ($product->updated_at) {
                $product->updated_at = $now;
            }
            // Save changes
            $product->save();
        });

        $this->info('Product dates updated successfully.');
    }
}
