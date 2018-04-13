<?php

declare(strict_types = 1);

namespace Blackprism\Nothing\RowConvertor;

interface FieldConvertor
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function convertFromDatabase($value);

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function convertToDatabase($value);
}
