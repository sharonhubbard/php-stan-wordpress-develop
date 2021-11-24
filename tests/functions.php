<?php

declare(strict_types=1);

/**
 * Returns the passed value.
 *
 * @template T
 * @param T $value Value.
 * @return T Value.
 */
function returnValue($value)
{
    return $value;
}

interface TestInterface
{
}

class ParentTestClass implements \TestInterface
{
}

class ChildTestClass extends \ParentTestClass
{
}
