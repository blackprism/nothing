<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Blackprism\Nothing\RowConverter\FieldConverter;

final class RowConverter
{
    /**
     * @var FieldConverter[]
     */
    private $fieldConverters;

    public function registerField(string $field, FieldConverter $fieldConverter)
    {
        $this->fieldConverters[$field] = $fieldConverter;
    }

    private function convertFor(string $name, $value)
    {
        if (isset($this->registered[$name]) === false) {
            return $value;
        }

        return $this->fieldConverters[$name]->convertFromDatabase($value);
    }

    public function convert(array $row)
    {
        foreach ($row as $key => &$value) {
            $value = $this->convertFor($key, $value);
        }

        return $row;
    }
}
