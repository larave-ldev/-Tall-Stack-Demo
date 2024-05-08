<?php

namespace App\Services\Magento;

use Tops\Magento\Magento\Attributes;

class AttributesService extends MagentoBaseService
{
    protected Attributes $attributes;

    public function __construct(Attributes $attributes)
    {
        parent::__construct();
        $this->attributes = $attributes;
    }

    public function getAttributeDetail(string $attributeCode): ?array
    {
        $this->attributes->ENDPOINT = config('services.magento.end_point');

        return $this->attributes->getAttributeDetail($attributeCode);
    }
}
