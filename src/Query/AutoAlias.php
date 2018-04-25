<?php

declare(strict_types = 1);

namespace Blackprism\Nothing\Query;

class AutoAlias
{
    private $joinCharacter;

    public function __construct(string $joinCharacter = '_')
    {
        $this->joinCharacter = $joinCharacter;
    }

    public function __invoke(...$arguments)
    {
        if (is_array($arguments[0]) === true) {
            $arguments = $arguments[0];
        }

        foreach ($arguments as &$argument) {
            $argumentSplitted = explode('.', $argument);
            if (count($argumentSplitted) === 2) {
                $argument = $argument . ' AS ' . $argumentSplitted[0] . $this->joinCharacter . $argumentSplitted[1];
            }
        }

        return $arguments;
    }
}
