<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function in_array;
use function is_array;

/**
 * @psalm-type Options = array{
 *     strict?: bool,
 *     list?: array,
 *     ...
 * }
 * @extends AbstractFilter<Options>
 * @final
 */
class DenyList extends AbstractFilter
{
    /** @var bool */
    protected $strict = false;

    /** @var array */
    protected $list = [];

    /**
     * @param null|array|Traversable $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Determine whether the in_array() call should be "strict" or not. See in_array docs.
     *
     * @deprecated since 2.38.0 - All option setters and getters will be removed in 3.0
     *
     * @param  bool $strict
     */
    public function setStrict($strict = true): void
    {
        $this->strict = (bool) $strict;
    }

    /**
     * Returns whether the in_array() call should be "strict" or not. See in_array docs.
     *
     * @deprecated since 2.38.0 - All option setters and getters will be removed in 3.0
     *
     * @return bool
     */
    public function getStrict()
    {
        return $this->strict;
    }

    /**
     * Set the list of items to black-list.
     *
     * @deprecated since 2.38.0 - All option setters and getters will be removed in 3.0
     *
     * @param array|Traversable $list
     */
    public function setList($list = []): void
    {
        if (! is_array($list)) {
            $list = ArrayUtils::iteratorToArray($list);
        }

        $this->list = $list;
    }

    /**
     * Get the list of items to black-list
     *
     * @deprecated since 2.38.0 - All option setters and getters will be removed in 3.0
     *
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * {@inheritDoc}
     *
     * Will return null if $value is present in the black-list. If $value is NOT present then it will return $value.
     */
    public function filter($value)
    {
        return in_array($value, $this->getList(), $this->getStrict()) ? null : $value;
    }
}
