<?php

namespace App\Http\Traits;

trait DecorationCacheKeys
{
    protected array $keys = ['edit', 'enable', 'disable'];

    public function getDecorationCacheKeys(): array
    {
        return $this->keys;
    }
}
