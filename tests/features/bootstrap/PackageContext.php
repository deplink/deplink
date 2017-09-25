<?php

use Behat\Gherkin\Node\TableNode;
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
            'repositories' => [[
                'type' => 'local',
                'src' => $this->fs->path(self::ROOT_DIR, 'resources/repository'),
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given /^change global package requirements:$/
     */
    public function changeGlobalPackageRequirements(TableNode $table)
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

        Assert::assertFileExists('deplink.json');
        $json = json_decode($this->fs->readFile('deplink.json'), true);

        $json['dependencies'] = new \ArrayObject($dependencies);
        $json['dev-dependencies'] = new \ArrayObject($devDependencies);
        $this->fs->writeFile('deplink.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given /^there is empty package$/
     */
    public function thereIsEmptyPackage()
    {
        return $this->thereIsPackageWhichRequires(new TableNode([]));
    }
}
