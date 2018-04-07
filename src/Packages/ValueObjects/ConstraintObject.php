<?php

namespace Deplink\Packages\ValueObjects;

use Deplink\Constraints\Context;
use Deplink\Constraints\Exceptions\TraversePathNotFoundException;
use Deplink\Constraints\Json;

/**
 * Store object where some elements in the tree can contain additional constraints.
 *
 * For example the: $obj->get('compiler.gcc')
 * will resolve the array key: ["compiler": => ["gcc" => <value>]]
 *
 * Keys in array can contains additional constraints:
 * ["compiler": => ["gcc:linux" => <value>]] (value still can be an array or object)
 *
 * Constraints could be merged to group, example:
 * - $obj->group(['linux', 'windows', 'mac'])
 * - $obj->group(['x86', 'x64'])
 *
 * Now calling $obj->get('path.to.key', null, ['linux, 'windows', 'x86']
 * will try to resolve the path 'path.to.key' where 'key' has applied
 * the 'linux', 'windows' and 'x86' constraints.
 *
 * If none of constraints from group was applied on the key (e.g. 'key:linux,windows')
 * then engine will pass the test assuming that all was applied ('key:linux,windows,x86,x64').
 *
 * @link https://deplink.org/docs/guide/constraints#additional-constraints
 */
class ConstraintObject
{
    /**
     * @var Json;
     */
    private $json;

    /**
     * Access element using dot notation.
     *
     * @param string $key Key in dot notation.
     * @param mixed $default
     * @param string[] $constraints Filter results by constraints.
     * @return mixed
     */
    public function get($key, $default = null, array $constraints = [])
    {
        try {
            return $this->json->get($key, $constraints);
        } catch (TraversePathNotFoundException $e) {
            return $default;
        }
    }

    /**
     * Merge some constraints to group.
     *
     * @param array ...$constraints
     * @return $this
     */
    public function group(...$constraints)
    {
        $this->json->getContext()->group($constraints);
        return $this;
    }

    /**
     * @param string[] ...$groups
     * @return $this
     */
    public function setGroups(...$groups)
    {
        $context = new Context();
        foreach ($groups as $group) {
            $context->group($group);
        }

        $this->json->setContext($context);
        return $this;
    }

    /**
     * Convert raw array to object.
     *
     * @param object|array $object
     * @return ConstraintObject
     * @throws \Deplink\Constraints\Exceptions\IncorrectJsonValueException
     */
    public static function hydrate($object)
    {
        $result = new ConstraintObject();
        $result->json = new Json($object);

        return $result;
    }
}
