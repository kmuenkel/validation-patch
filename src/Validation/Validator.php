<?php

namespace ValidationPatch\Validation;

use DateTime;
use Illuminate\Validation\Validator as BaseValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Contracts\Validation\Rule as RuleContract;

/**
 * Class Validator
 * @package ValidationPatch\Validation
 */
class Validator extends BaseValidator
{
    /**
     * Leverage the ValidationData override
     * @inheritDoc
     */
    protected function passesOptionalCheck($attribute)
    {
        if (!$this->hasRule($attribute, ['Sometimes'])) {
            return true;
        }

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        return array_key_exists($attribute, $data) || array_key_exists($attribute, $this->data);
    }

    /**
     * Leverage the ValidationRuleParser override
     * @inheritDoc
     */
    public function addRules($rules)
    {
        $response = (new ValidationRuleParser($this->data))->explode($rules);
        $this->rules = array_merge_recursive($this->rules, $response->rules);
        $this->implicitAttributes = array_merge($this->implicitAttributes, $response->implicitAttributes);
    }

    /**
     * Move the replacement of [$this->dotPlaceholder, '__asterisk__'] with ['.', '*'], as is the case in the
     * parent::addFailure(). The asterisk won't be present anyway, due to $this->addRules(). And the dots need to
     * remain as $this->dotPlaceholder so $this->shouldBeExcluded() can locate the corresponding value properly by
     * $this->excludeAttribute(). But the conversion does still need to be done before the error message is produced.
     * @inheritDoc
     */
    public function addFailure($attribute, $rule, $parameters = [])
    {
        !$this->messages && $this->passes();

        if (in_array($rule, $this->excludeRules)) {
            $this->excludeAttribute($attribute);

            return;
        }

        $attribute = str_replace($this->dotPlaceholder, '\.', $attribute);
        $message = $this->getMessage($attribute, $rule);
        $message = $this->makeReplacements($message, $attribute, $rule, $parameters);
        $this->messages->add($attribute, $message);
        $this->failedRules[$attribute][$rule] = $parameters;
    }

    /**
     * If the rule is one that references another field, the first parameter/field must go through the same dot
     * placeholder conversion as the field names in order for a match to be possible
     * @inheritDoc
     */
    protected function validateAttribute($attribute, $rule)
    {
        $this->currentRule = $rule;
        [$rule, $parameters] = ValidationRuleParser::parse($rule);

        if (in_array($rule, $this->excludeRules)) {
            $parameters[0] = str_replace('\.', $this->dotPlaceholder, $parameters[0]);
        }

        if (!$rule) {
            return null;
        }

        if (($keys = $this->getExplicitKeys($attribute)) && $this->dependsOnOtherFields($rule)) {
            $parameters = $this->replaceAsterisksInParameters($parameters, $keys);
        }

        $value = $this->getValue($attribute);
        $rules = array_merge($this->fileRules, $this->implicitRules);

        if ($value instanceof UploadedFile && ! $value->isValid() && $this->hasRule($attribute, $rules)) {
            $this->addFailure($attribute, 'uploaded', []);

            return null;
        }

        $validatable = $this->isValidatable($rule, $attribute, $value);

        if ($rule instanceof RuleContract) {
            $validatable && $this->validateUsingCustomRule($attribute, $value, $rule);

            return null;
        }

        $method = "validate{$rule}";
        $isValid = $this->$method($attribute, $value, $parameters, $this);

        if ($validatable && !$isValid) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    /**
     * Ignore 'required' rules on elements within arrays that are missing in their entirety. The parent arrays can be
     * set to 'required' if their existence is necessary, rather than expect it as a side effect of nested data.
     * @inheritDoc
     */
    public function validateRequired($attribute, $value)
    {
        if (!($valid = parent::validateRequired($attribute, $value))) {
            $parent = explode('.', $attribute);
            $attributeIsNested = count($parent) >= 2;
            array_pop($parent);
            $parent = implode('.', $parent);
            $parentExists = $this->getValue($parent);

            $valid |= ($attributeIsNested && !$parentExists);
        }

        return $valid;
    }

    /**
     * @return string[]
     */
    public static function getIsoFormats(): array
    {
        return [
            DateTime::ISO8601,
            'Y-m-d\TH:i:svO',
            'Y-m-d\TH:i:s\Z',
            'Y-m-d\TH:i:s.v\Z',
            'c'
        ];
    }

    /**
     * ISO8601 can be represented with a colon in the timezone ("c"), without one (DateTime::ISO8601), or Zulu
     * @inheritDoc
     */
    public function validateDateFormat($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_format');

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $format = $parameters[0];
        $isoFormats = static::getIsoFormats();
        $formats = in_array($format, $isoFormats) ? $isoFormats : [$format];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);

            if ($date && $date->format($format) == $value) {
                return true;
            }
        }

        return false;
    }
}
