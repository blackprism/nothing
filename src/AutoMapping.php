<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class AutoMapping implements Hydrator\Mapper
{
    public const SUB_OBJECT = 'sub_object';

    private $platform;
    private $mappings;

    /**
     * @param AbstractPlatform $platform
     * @param EntityMapper[]   $mappings
     */
    public function __construct(AbstractPlatform $platform, array $mappings)
    {
        $this->platform = $platform;

        foreach ($mappings as $alias => $mapping) {
            if (is_string($alias) === false) {
                $alias = '';
            }

            /**
             * @TODO Exception plus propre
             */
            if ($mapping instanceof EntityMapper === false) {
                throw new \RuntimeException();
            }

            $this->mappings[] = [
                'class'      => $mapping->getClass(),
                'alias'      => $alias,
                'parameters' => $mapping->getParameters()
            ];
        }

        $this->resolveDependencies();
    }

    private function resolveDependencies()
    {
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
     * @param iterable $row
     * @param iterable $data
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return iterable
     */
    public function map(iterable $row, iterable $data): iterable
    {
        $found   = false;
        $tmpData = [];

        foreach ($this->mappings as $mapping) {
            $values = [];

            foreach ($mapping['parameters'] as $name => $type) {
                $nameAliased = $mapping['alias'] . $name;

                if ($type !== static::SUB_OBJECT && isset($row[$nameAliased]) === false) {
                    $found = false;
                    break;
                }

                if ($type === static::SUB_OBJECT && isset($tmpData[$name]) === true) {
                    $values[] = $tmpData[$name];
                    unset($tmpData[$name]);
                } elseif ($type !== static::SUB_OBJECT) {
                    $values[] = Type::getType($type)->convertToPHPValue($row[$nameAliased], $this->platform);
                }

                $found = true;
            }

            if ($found === true) {
                $tmpData[$mapping['class']] = new $mapping['class'](...$values);
            }
        }

        $data[] = $tmpData;

        return $data;
    }
}
