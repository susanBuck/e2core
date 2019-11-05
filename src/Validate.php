<?php
namespace E2;

class Validate
{
    public function __construct($fieldsToValidate, $data)
    {
        $this->fieldsToValidate = $fieldsToValidate;
        $this->data = $data;
    }

    /**
     * Given an array of fields => validation rules
     * Will loop through each field's rules
     * Returns an array of error messages
     * Stops after the first error for a given field
     * Available rules:
     * required, alpha, alphaNumeric, digit, numeric,
     * email, url, min:x, max:x, minLength:x, maxLength:x
     */
    public function validate()
    {
        $errors = [];

        foreach ($this->fieldsToValidate as $fieldName => $rules) {
            # Each rule is separated by a |
            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                # Get the value for this field from the request
                $value = $this->data[$fieldName];
                
                # Handle any parameters with the rule, e.g. max:99
                $parameter = null;
                if (strstr($rule, ':')) {
                    list($rule, $parameter) = explode(':', $rule);
                }

                # Run the validation test with the given rule
                $test = $this->$rule($value, $parameter);

                # Test failed
                if (!$test) {
                    $method = $rule . 'Message';
                    $errors[] = 'The value for ' . $fieldName . ' ' . $this->$method($parameter);

                    # Only indicate one error per field
                    break;
                }
            }
        }

        return $errors;
    }

    ### VALIDATION METHODS FOUND BELOW HERE ###

    /**
     * The value can not be blank
     */
    protected function required($value)
    {
        $value = trim($value);

        return $value != '' && isset($value) && !is_null($value);
    }

    protected function requiredMessage()
    {
        return 'can not be blank';
    }

    /**
     *  The value can only contain letters or spaces
     */
    protected function alpha($value)
    {
        return ctype_alpha(str_replace(' ', '', $value));
    }

    protected function alphaMessage()
    {
        return 'can only contain letters';
    }

    /**
     * The value can only contain alpha-numeric characters
     */
    protected function alphaNumeric($value)
    {
        return ctype_alnum(str_replace(' ', '', $value));
    }

    protected function alphaNumericMessage()
    {
        return 'can only contain letters or numbers';
    }

    /**
     * The value can only contain digits (0, 1, 2, 3, 4, 5, 6, 7, 8, 9)
     */
    protected function digit($value)
    {
        return ctype_digit(str_replace(' ', '', $value));
    }

    protected function digitMessage()
    {
        return 'can only contain digits';
    }

    /**
     * The value can only contain numbers
     */
    protected function numeric($value)
    {
        return is_numeric(str_replace(' ', '', $value));
    }

    protected function numericMessage()
    {
        return 'can only contain numerical values';
    }

    /**
     * The value must be a properly formatted email address
     */
    protected function email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    protected function emailMessage()
    {
        return 'must contain a correctly formatted email address';
    }

    /**
     * The value must be a properly formatted URL
     */
    protected function url($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    protected function urlMessage()
    {
        return 'must contain a correctly formatted URL';
    }

    /**
     * The character count of the value must be GREATER THAN (non-inclusive) the given parameter
     * Fails if value is non-numeric
     */
    protected function minLength($value, $parameter)
    {
        return strlen($value) >= $parameter;
    }

    protected function minLengthMessage($parameter)
    {
        return 'must be at least ' . $parameter . ' character(s) long';
    }

    /**
     * The character count of the value must be LESS THAN (inclusive) the given parameter
     * Fails if value is non-numeric
     */
    protected function maxLength($value, $parameter)
    {
        return strlen($value) <= $parameter;
    }

    protected function maxLengthMessage($parameter)
    {
        return 'must be less than ' . $parameter . ' character(s) long';
    }

    /**
     * The value must be GREATER THAN (inclusive) the given parameter
     * Fails if value is non-numeric
     */
    protected function min($value, $parameter)
    {
        if (!$this->numeric($value)) {
            return false;
        }

        return floatval($value) >= floatval($parameter);
    }

    protected function minMessage($parameter)
    {
        return 'must be greater than or equal to ' . $parameter;
    }

    /**
     * The value must be LESS THAN (inclusive) the given parameter
     * Fails if value is non-numeric
     */
    protected function max($value, $parameter)
    {
        if (!$this->numeric($value)) {
            return false;
        }

        return floatval($value) <= floatval($parameter);
    }

    protected function maxMessage($parameter)
    {
        return 'must be less than or equal to ' . $parameter;
    }
}
