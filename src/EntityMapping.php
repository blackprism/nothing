<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

class EntityMapping
{
    private $class;
    private $parameters;
    private $alternativeBuilds = [];

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

    public function buildWith(string $method, array $parameters)
    {
        $this->alternativeBuilds[$method] = $parameters;
    }

    public function getAlternativeBuilds(): array
    {
        return $this->alternativeBuilds;
    }
}
