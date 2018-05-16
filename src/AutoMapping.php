<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

class AutoMapping
{
    public const SUB_OBJECT = 'sub_object';

    private $typeConverter;

    /**
     * @var array
     */
    private $mappings;

    /**
     * @param TypeConverter   $typeConverter
     * @param EntityMapping[] $mappings
     */
    public function __construct(TypeConverter $typeConverter, iterable $mappings)
    {
        $this->typeConverter = $typeConverter;

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
                'class'        => $mapping->getClass(),
                'alias'        => $alias,
                'constructors' => $mapping->getConstructors()
            ];
        }

        $this->resolveDependencies();
    }

    /**
     * @TODO better algo
     */
    private function resolveDependencies()
    {
        do {
            $updated = false;

            foreach ($this->mappings as $index => $mapping) {
                $dependencyIndex = null;
                foreach ($this->mappings as $searchedIndex => $searchedMapping) {
                    foreach ($searchedMapping['constructors'] as $constructor) {
                        if (isset($constructor['parameters'][$mapping['class']]) === true
                            && $constructor['parameters'][$mapping['class']] === static::SUB_OBJECT) {
                            $dependencyIndex = $searchedIndex;
                            break 2;
                        }
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
            $values = [];

            foreach ($mapping['constructors'] as $constructor) {
                $values = $this->mapRowForParameters($row, $constructor['parameters'], $mapping['alias'], $tmpData);
                if ($values !== []) {
                    break;
                }
            }

            if ($values !== []) {
                if ($constructor['method'] === null) {
                    $tmpData[$mapping['class']] = new $mapping['class'](...$values);
                } else {
                    $tmpData[$mapping['class']] = $mapping['class']::{$constructor['method']}(...$values);
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

            if ($type === static::SUB_OBJECT && isset($data[$name]) === true) {
                $values[] = $data[$name];
                unset($data[$name]);
            } elseif ($type !== static::SUB_OBJECT) {
                $values[] = $this->typeConverter->convertToPHP($row[$nameAliased], $type);
            }

            $found = true;
        }

        if ($found === false) {
            $values = [];
        }

        return $values;
    }
}
