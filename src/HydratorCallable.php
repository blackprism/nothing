<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

class HydratorCallable
{
    public function map(iterable $rows, iterable $data, callable $map, RowConverter $rowConverter = null): iterable
    {
        foreach($rows as $row) {
            if ($rowConverter !== null) {
                $row = $rowConverter->convert($row);
            }

            $data = $map($row, $data);
        }

        return $data;
    }

    public function mapReduce(iterable $rows, iterable $data, callable $map, callable $reduce)
    {
        return $reduce($this->map($rows, $data, $map), $data);
    }
}
