<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

class EntityMapping
{
    private $class;
    private $parameters;

    public function __construct(string $class, array $parameters)
    {
        $this->class      = $class;
        $this->parameters = $parameters;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
