<?php

declare(strict_types = 1);

namespace Blackprism\Nothing\Hydrator;

interface Reducer
{
    /**
     * @param iterable $rows
     * @param mixed    $data
     *
     * @return mixed
     */
    public function reduce(iterable $rows, $data);
}
