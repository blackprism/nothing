<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class AutoMapping
{
    public const SUB_OBJECT = 'sub_object';

    private $platform;
    private $mappings;

    /**
     * @param AbstractPlatform $platform
     * @param EntityMapping[]  $mappings
     */
    public function __construct(AbstractPlatform $platform, iterable $mappings)
    {
        $this->platform = $platform;

        foreach ($mappings as $alias => $mapping) {
            if (is_string($alias) === false) {
                $alias = '';
            }

            /**
             * @TODO Exception plus propre
             */
            if ($mapping instanceof EntityMapping === false) {
                throw new \RuntimeException();
            }

            $this->mappings[] = [
                'class'             => $mapping->getClass(),
                'alias'             => $alias,
                'parameters'        => $mapping->getParameters(),
                'namedConstructors' => $mapping->getNamedConstructors()
            ];
        }

        $this->resolveDependencies();
    }

    private function resolveDependencies()
    {
        /**
         * @TODO resolve dependencies for named constructors
         */
        do {
            $updated = false;

            foreach ($this->mappings as $index => $mapping) {
                $dependencyIndex = null;
                foreach ($this->mappings as $searchedIndex => $searchedMapping) {
                    if (isset($searchedMapping['parameters'][$mapping['class']]) === true
                        && $searchedMapping['parameters'][$mapping['class']] === static::SUB_OBJECT) {
                        $dependencyIndex = $searchedIndex;
                        break;
                    }
                }

                if ($dependencyIndex !== null && $dependencyIndex < $index) {
                    unset($this->mappings[$index]);
                    array_splice($this->mappings, $dependencyIndex, 0, [$mapping]);
                    $updated = true;
                }
            }
        } while ($updated === true);
    }

    /**
     * @param iterable $rows
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return \ArrayObject
     */
    public function map(iterable $rows): \ArrayObject
    {
        $collection = new \ArrayObject();

        foreach ($rows as $row) {
            $this->mapRow($row, $collection);
        }

        return $collection;
    }

    /**
     * @param array        $row
     * @param \ArrayObject $collection
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function mapRow(array $row, \ArrayObject $collection)
    {
        $tmpData = [];
        $method  = null;

        foreach ($this->mappings as $mapping) {
            $values = $this->mapRowForParameters($row, $mapping['parameters'], $mapping['alias'], $tmpData);

            if ($values === []) {
                foreach ($mapping['namedConstructors'] as $method => $parameters) {
                    $values = $this->mapRowForParameters($row, $parameters, $mapping['alias'], $tmpData);
                    if ($values !== []) {
                        break;
                    }
                }
            }

            if ($values !== []) {
                if ($method === null) {
                    $tmpData[$mapping['class']] = new $mapping['class'](...$values);
                } else {
                    $tmpData[$mapping['class']] = $mapping['class']::$method(...$values);
                }
            }
        }

        $collection->append($tmpData);
    }

    private function mapRowForParameters(iterable $row, array $parameters, $alias, &$data)
    {
        $values = [];
        $found = false;

        foreach ($parameters as $name => $type) {
            $nameAliased = $alias . $name;

            if ($type !== static::SUB_OBJECT && isset($row[$nameAliased]) === false) {
                $found = false;
                break;
            }

            if ($type === static::SUB_OBJECT && isset($tmpData[$name]) === true) {
                $values[] = $data[$name];
                unset($data[$name]);
            } elseif ($type !== static::SUB_OBJECT) {
                $values[] = Type::getType($type)->convertToPHPValue($row[$nameAliased], $this->platform);
            }

            $found = true;
        }

        if ($found === false) {
            $values = [];
        }

        return $values;
    }
}
