<?php

namespace dnj\AAA\Http\Resources\Concerns;

use Illuminate\Support\Arr;

/**
 * @property static $summaryMetaKeys
 */
trait HasMeta
{
    public static array $metaKeys = [];

    protected function exportMetaWithKeys(array $keys): array
    {
        $meta = $this->resource->getMeta();
        if (1 == count($keys) and '*' === $keys[0]) {
            return $meta;
        }

        return Arr::only($meta, $keys);
    }

    protected function exportMeta(): array
    {
        $keys = static::$metaKeys;
        if (property_exists($this, 'summary') and $this->summary) {
            if (property_exists(static::class, 'summaryMetaKeys')) {
                $keys = static::$summaryMetaKeys;
            }
        }
        return ['meta' => $this->exportMetaWithKeys($keys)];
    }
}
