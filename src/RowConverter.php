<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Blackprism\Nothing\RowConvertor\FieldConvertor;

final class RowConverter
{
    /**
     * @var FieldConvertor[]
     */
    private $fieldConvertors;

    public function registerField(string $field, FieldConvertor $fieldConvertor)
    {
        $this->fieldConvertors[$field] = $fieldConvertor;
    }

    private function convertFor(string $name, $value)
    {
        if (isset($this->registered[$name]) === false) {
            return $value;
        }

        return $this->fieldConvertors[$name]->convertFromDatabase($value);
    }

    public function convert(array $row)
    {
        foreach ($row as $key => &$value) {
            $value = $this->convertFor($key, $value);
        }

        return $row;
    }
}
