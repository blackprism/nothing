<?php

declare(strict_types = 1);

namespace Blackprism\Nothing\Hydrator;

interface Mapper
{
    /**
     * @param array $row
     * @param mixed $data
     *
     * @return mixed
     */
    public function map(array $row, $data);
}
