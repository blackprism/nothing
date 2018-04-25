<?php

declare(strict_types = 1);

namespace Blackprism\Nothing;

class EntityMapping
{
    private $class;
    private $constructors = [];
    private $sorted = false;

    public function __construct(string $class, array $parameters)
    {
        $this->class      = $class;
        $this->constructors[] = ['method' => null, 'parameters' => $parameters];
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function buildWith(string $method, array $parameters): void
    {
        $this->sorted = false;
        $this->constructors[] = ['method' => $method, 'parameters' => $parameters];
    }

    public function getConstructors(): array
    {
        if ($this->sorted === false) {
            $this->sortConstructorsWithMaxParametersFirst();
        }

        return $this->constructors;
    }

    private function sortConstructorsWithMaxParametersFirst(): bool
    {
        $this->sorted = true;

        return uasort($this->constructors, function ($a, $b) {
            return count($b['parameters']) <=> count($a['parameters']);
        });
    }
}
