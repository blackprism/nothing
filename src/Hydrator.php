<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Blackprism\Nothing\Hydrator\Mapper;
use Blackprism\Nothing\Hydrator\Reducer;

class Hydrator
{
    public function map(iterable $rows, iterable $data, Mapper $map, RowConverter $rowConverter = null): iterable
    {
        foreach($rows as $row) {
            if ($rowConverter !== null) {
                $row = $rowConverter->convert($row);
            }

            $data = $map->map($row, $data);
        }

        return $data;
    }

    public function mapReduce(iterable $rows, iterable $data, Mapper $map, Reducer $reduce): iterable
    {
        return $reduce->reduce($this->map($rows, $data, $map), $data);
    }
}
