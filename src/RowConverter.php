<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class RowConverter
{
    /**
     * @var string[]
     */
    private $typeMappings;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function registerType(string $field, string $type)
    {
        $this->typeMappings[$field] = $type;
    }

    /**
     * @param string $name
     * @param $value
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return mixed
     */
    private function convertFor(string $name, $value)
    {
        if (isset($this->typeMappings[$name]) === false) {
            return $value;
        }

        return Type::getType($this->typeMappings[$name])->convertToPHPValue($value, $this->platform);
    }

    public function convert(array $row)
    {
        foreach ($row as $key => &$value) {
            $value = $this->convertFor($key, $value);
        }

        return $row;
    }
}
