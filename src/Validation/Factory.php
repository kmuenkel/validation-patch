<?php

namespace ValidationPatch\Validation;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\{Validator, Factory as BaseFactory};

/**
 * Allow for Illuminate\Contracts\Validation\Rule instances to support placeholders in their message() output.
 * Class Factory
 * @package ValidationPatch\Validation
 */
class Factory extends BaseFactory
{
    /**
     * @inheritDoc
     */
    public function extend($rule, $extension, $message = null)
    {
        if (is_string($extension) && is_null($message)) {
            $message = $this->generateMessageStruct();

            //Intercept the arguments passed to the Rule::passes() method
            $extension = function ($attr, $value, $parameters, Validator $validator) use ($rule, $extension, $message) {
                $message::$ruleName = $rule;
                $message::$rule = app($extension, compact('parameters', 'validator'));
                $message::$validator = $validator;
                $message::$attribute = $attr;
                $message::$parameters = $parameters;

                return $message::$rule->passes($attr, $value);
            };

            //ValidatorFacade::replacer() would result in infinite recursion when using Validator::makeReplacements()
            ValidatorFacade::extend($rule, $extension, $message);
        }

        parent::extend($rule, $extension, $message);
    }

    /**
     * @return object
     */
    protected function generateMessageStruct()
    {
        return new class {
            /**
             * @var Validator|null
             */
            public static $validator;

            /**
             * @var Rule|null
             */
            public static $rule;

            /**
             * @var string[]
             */
            public static $parameters = [];

            /**
             * @var mixed
             */
            public static $attribute = null;

            /**
             * @var string
             */
            public static $ruleName = '';

            /**
             * @return string
             */
            public function __toString()
            {
                return static::$validator->makeReplacements(
                    static::$rule->message(),
                    static::$attribute,
                    static::$ruleName,
                    static::$parameters
                );
            }
        };
    }
}
