<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Stringable;

use function is_scalar;

final class ToString implements FilterInterface
{
    /**
     * Returns (string) $value
     *
     * If the value provided is non-scalar, the value will remain unfiltered
     *
     * @return ($value is scalar ? string : mixed)
     */
    public function filter(mixed $value): mixed
    {
        if (
            ! is_scalar($value)
            && ! $value instanceof Stringable
        ) {
            return $value;
        }

        return (string) $value;
    }

    public function __invoke(mixed $value): mixed
    {
        return $this->filter($value);
    }
}
