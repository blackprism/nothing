<?php

declare(strict_types = 1);

namespace Blackprism\Nothing\Hydrator;

interface Reducer
{
    public function reduce(iterable $rows, iterable $data): iterable;
}
