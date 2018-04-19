<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TypeConverter
{
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return mixed
     */
    public function convertToPHP($value, string $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->platform);
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return mixed
     */
    public function convertToDatabase($value, string $type)
    {
        return Type::getType($type)->convertToDatabaseValue($value, $this->platform);
    }
}
