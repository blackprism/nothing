<?php

declare(strict_types = 1);

namespace Blackprism\Nothing\Hydrator;

interface Mapper
{
    public function map(iterable $row, iterable $data): iterable;
}
