<?php

namespace App\Services\Magento;

use Tops\Magento\Magento\MagentoApi;

class MagentoBaseService extends MagentoApi
{
    public function __construct()
    {
        parent::__construct();

        // Setting Magento token and end point
        $_SESSION['magento_token'] = config('services.magento.token');
        $this->TOKEN = config('services.magento.token');
        $this->ENDPOINT = config('services.magento.end_point');
    }
}
