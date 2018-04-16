<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Blackprism\Nothing\Hydrator\Mapper;
use Blackprism\Nothing\Hydrator\Reducer;

class Hydrator
{
    /**
     * @param iterable          $rows
     * @param mixed             $data
     * @param Mapper            $map
     * @param RowConverter|null $rowConverter
     *
     * @return mixed
     */
    public function map(iterable $rows, $data, Mapper $map, RowConverter $rowConverter = null)
    {
        foreach($rows as $row) {
            if ($rowConverter !== null) {
                $row = $rowConverter->convert($row);
            }

            $data = $map->map($row, $data);
        }

        return $data;
    }

    /**
     * @param iterable $rows
     * @param mixed    $data
     * @param Mapper   $map
     * @param Reducer  $reduce
     *
     * @return mixed
     */
    public function mapReduce(iterable $rows, $data, Mapper $map, Reducer $reduce)
    {
        return $reduce->reduce($this->map($rows, $data, $map), $data);
    }
}
