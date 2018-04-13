<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

class HydratorCallable
{
    /**
     * @param iterable $rows
     * @param mixed    $data
     * @param callable $map
     *
     * @return mixed
     */
    public function map(iterable $rows, $data, callable $map)
    {
        foreach($rows as $row) {
            $data = $map($row, $data);
        }

        return $data;
    }

    /**
     * @param iterable $rows
     * @param mixed    $data
     * @param callable $map
     * @param callable $reduce
     *
     * @return mixed
     */
    public function mapReduce(iterable $rows, $data, callable $map, callable $reduce)
    {
        return $reduce($map($rows, $data, $map), $data);
    }
}
