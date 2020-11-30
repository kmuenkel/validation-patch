<?php

namespace ValidationPatch\Validation;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationRuleParser as BaseValidationRuleParser;

/**
 * Class ValidationRuleParser
 * @package ValidationPatch\Validation
 */
class ValidationRuleParser extends BaseValidationRuleParser
{
    /**
     * Correct the absence of the preg_quote() $delimiter
     * @inheritDoc
     */
    protected function explodeWildcardRules($results, $attribute, $rules)
    {
        $pattern = str_replace('\*', '[^\.]*', preg_quote($attribute, '/'));

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        foreach ($data as $key => $value) {
            if (Str::startsWith($key, $attribute) || (bool) preg_match('/^'.$pattern.'\z/', $key)) {
                foreach ((array) $rules as $rule) {
                    $this->implicitAttributes[$attribute][] = $key;

                    $results = $this->mergeRules($results, $key, $rule);
                }
            }
        }

        return $results;
    }
}
