<?php

namespace App\Services;

use App\Models\ProductColourMapper;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CommonService
{
    public function isColourExistToProductColourMapper(string $colour): ?object
    {
        return ProductColourMapper::where('product_colour', $colour)->first();
    }

    public function sendFilamentNotification(array $notificationData): void
    {
        // Send notification
        $user = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->with('role')->first();
        if ($user) {
            // Send notification
            Notification::make('colour-intimation')
                ->title($notificationData['title'])
                ->info()
                ->send()
                ->sendToDatabase($user);
        }
    }

    public function sendCustomFilamentNotification(array $notificationData): void
    {
        // Send notification
        $user = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->with('role')->first();
        if ($user) {
            $data = [
                'actions' => [],
                'body' => null,
                'color' => null,
                'duration' => 'persistent',
                'icon' => null,
                'iconColor' => null,
                'title' => $notificationData['title'],
                'view' => 'filament-notifications::notification',
                'viewData' => [],
                'format' => 'filament',
            ];

            $insertNotification = [
                'id' => Str::orderedUuid(),
                'type' => 'Filament\Notifications\DatabaseNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode($data, true),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('notifications')->insert($insertNotification);
        }
    }

    public function createSKU(string $productCode, int $promodataId): string
    {
        $productCode = $productCode ? str_replace('/', '-', $productCode) : null;

        return $productCode ? $productCode . '-' . $promodataId : $promodataId;
    }
}
