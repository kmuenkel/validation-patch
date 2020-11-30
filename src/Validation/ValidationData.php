<?php

namespace ValidationPatch\Validation;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationData as BaseValidationData;

/**
 * Class ValidationData
 * @package ValidationPatch\Validation
 */
class ValidationData extends BaseValidationData
{
    /**
     * Correct the absence of the preg_quote() $delimiter
     * @inheritDoc
     */
    protected static function extractValuesForWildcards($masterData, $data, $attribute)
    {
        $keys = [];

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute, '/'));

        foreach ($data as $key => $value) {
            try {
                if ((bool)preg_match('/^' . $pattern . '/', $key, $matches)) {
                    $keys[] = $matches[0];
                }
            } catch (\Exception $e) {
                dd($pattern);
            }
        }

        $keys = array_unique($keys);

        $data = [];

        foreach ($keys as $key) {
            $data[$key] = Arr::get($masterData, $key);
        }

        return $data;
    }
}
