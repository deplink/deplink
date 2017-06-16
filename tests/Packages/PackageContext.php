<?php

namespace Deplink\Tests\Packages;

use Behat\Gherkin\Node\TableNode;
use Deplink\Tests\BaseContext;
use PHPUnit\Framework\Assert;

class PackageContext extends BaseContext
{
    /**
     * @Given there is package which requires:
     */
    public function thereIsPackageWhichRequires(TableNode $table)
    {
        $dependencies = [];
        $devDependencies = [];
        foreach ($table as $row) {
            $packageName = $row['package'];
            $versionConstraint = $row['version'];
            if (isset($row['isDev']) && $row['isDev'] == 'true') {
                $devDependencies[$packageName] = $versionConstraint;
            } else {
                $dependencies[$packageName] = $versionConstraint;
            }
        }

        Assert::assertFileNotExists('deplink.json');
        $this->fs->writeFile('deplink.json', json_encode([
            'name' => 'org/package',
            'type' => 'project',
            'dependencies' => new \ArrayObject($dependencies),
            'dev-dependencies' => new \ArrayObject($devDependencies),
            // TODO: local repository entry
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
