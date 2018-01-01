<?php

namespace Deplink\Packages\ValueObjects;

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
     * @var array
     */
    private $keys = [];

    /**
     * @var array
     */
    private $groups = [];

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
        $parts = explode('.', $key);

        $result = $this->keys['_'];
        foreach ($parts as $part) {
            // Key not existing - use default.
            if (!is_array($result->value) || !isset($result->value[$part])) {
                return $default;
            }

            // Not pass constraints - use default.
            $data = $result->value[$part];
            if (!$this->passConstraints($constraints, $data->constraints)) {
                return $default;
            }

            $result = $result->value[$part];
        }

        return $result->value;
    }

    /**
     * Merge some constraints to group.
     *
     * @param array ...$constraints
     * @return $this
     */
    public function group(...$constraints)
    {
        $this->groups[] = $constraints;

        return $this;
    }

    /**
     * @param string[] ...$groups
     * @return $this
     */
    public function setGroups(...$groups)
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @param string[] $requested
     * @param string[] $existing
     * @return bool
     */
    private function passConstraints(array $requested, array $existing)
    {
        foreach ($this->groups as $group) {
            $existingPerGroup = array_intersect($group, $existing);

            // No constraint defined for this group,
            // so we assume that any constraint will pass.
            if (empty($existingPerGroup)) {
                continue;
            }

            $requestedPerGroup = array_intersect($group, $requested);

            // If user not requested any constraint for this group
            // then we assume that user pick first matching constraint.
            if (empty($requestedPerGroup)) {
                continue;
            }

            // Each requested constraint must exists
            // in the key constraints(in group scope).
            foreach ($requestedPerGroup as $rpg) {
                if (!in_array($rpg, $existingPerGroup)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Convert raw array to object.
     *
     * @param object|array $object
     * @return ConstraintObject
     */
    public static function hydrate($object)
    {
        $stack = [];
        $result = new ConstraintObject();

        // Create root node.
        $result->keys['_'] = (object)[
            'value' => $object,
            'constraints' => [],
        ];

        // Pass node to expand if it is json object.
        if (self::isJsonObject($object)) {
            $stack[] = $result->keys['_'];
        }

        // While any object left to expand.
        while (!empty($stack)) {
            $item = array_shift($stack);

            $jsonObject = (array)$item->value;
            $item->value = [];

            foreach ($jsonObject as $attr => $value) {
                $data = self::extractKeyConstraints($attr);

                $item->value[$data->key] = (object)[
                    'value' => $value,
                    'constraints' => $data->constraints,
                ];

                if (self::isJsonObject($value)) {
                    $stack[] = $item->value[$data->key];
                }
            }
        }

        return $result;
    }

    /**
     * @param mixed $object
     * @return bool
     */
    private static function isJsonObject($object)
    {
        if (is_object($object)) {
            $object = (array)$object;
        }

        if (!is_array($object)) {
            return false;
        }

        // Get only keys from range 0 to last array index (integer).
        $numKeys = array_keys($object, range(0, count($object) - 1), true);

        // If count is not equal then we have object without natural keys.
        return count($numKeys) !== count($object);
    }

    /**
     * @param string $attr
     * @return object Contains 'key' and 'constraints' keys.
     */
    private static function extractKeyConstraints($attr)
    {
        $parts = explode(':', $attr, 2);
        $key = $parts[0];

        $constraints = [];
        if (isset($parts[1])) {
            $constraints = explode(',', $parts[1]);
        }

        return (object)[
            'key' => $key,
            'constraints' => $constraints,
        ];
    }
}
