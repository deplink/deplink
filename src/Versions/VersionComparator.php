<?php

namespace Deplink\Versions;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;

/**
 * Helper class to operate on the semantic versions.
 *
 * @link http://semver.org/
 */
class VersionComparator
{
    /**
     * Evaluates the expression: $version1 > $version2.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    public function greaterThan($v1, $v2)
    {
        return Comparator::greaterThan($v1, $v2);
    }

    /**
     * Evaluates the expression: $version1 >= $version2.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    public function greaterThanOrEqualTo($v1, $v2)
    {
        return Comparator::greaterThanOrEqualTo($v1, $v2);
    }

    /**
     * Evaluates the expression: $version1 < $version2.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    public function lessThan($v1, $v2)
    {
        return Comparator::lessThan($v1, $v2);
    }

    /**
     * Evaluates the expression: $version1 <= $version2.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    public function lessThanOrEqualTo($v1, $v2)
    {
        return Comparator::lessThanOrEqualTo($v1, $v2);
    }

    /**
     * Evaluates the expression: $version1 != $version2.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    public function notEqualTo($v1, $v2)
    {
        return !$this->equalTo($v1, $v2);
    }

    /**
     * Evaluates the expression: $version1 == $version2.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    public function equalTo($v1, $v2)
    {
        $v1 = $this->strToSem($v1);
        $v2 = $this->strToSem($v2);

        return $v1->major === $v2->major
            && $v1->minor === $v2->minor
            && $v1->patch === $v2->patch;
    }

    /**
     * String to semantic version.
     *
     * @param string $v Version in "<major>[.<minor>[.<patch>]]" format.
     * @return object Contains major, minor and patch parameters.
     */
    private function strToSem($v)
    {
        $v = explode('.', $v, 3);

        return (object)[
            'major' => isset($v[0]) ? (int)$v[0] : 0,
            'minor' => isset($v[1]) ? (int)$v[1] : 0,
            'patch' => isset($v[2]) ? (int)$v[2] : 0,
        ];
    }

    /**
     * Determine if given version satisfies given constraints.
     *
     * @param string $version
     * @param string $constraint
     * @return bool
     */
    public function satisfies($version, $constraint)
    {
        return Semver::satisfies($version, $constraint);
    }

    /**
     * Return all versions that satisfy given constraints.
     *
     * @param string|string[] $versions
     * @param string $constraint
     * @return string[]
     */
    public function satisfiedBy($versions, $constraint)
    {
        $versions = (array)$versions;
        return Semver::satisfiedBy($versions, $constraint);
    }

    /**
     * Sort given array of versions
     * (from oldest to the newest version).
     *
     * @param string[] $versions
     * @return string[]
     */
    public function sort(array $versions)
    {
        return Semver::sort($versions);
    }

    /**
     * Sort given array of versions in reverse order
     * (from newest to the oldest version).
     *
     * @param string[] $versions
     * @return string[]
     */
    public function reverseSort(array $versions)
    {
        return Semver::rsort($versions);
    }
}
