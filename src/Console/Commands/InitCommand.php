<?php

namespace Deplink\Console\Commands;

use Deplink\Console\BaseCommand;
use Deplink\Packages\ValueObjects\PackageNameObject;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InitCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Create basic deplink.json file')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the package')
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED,
                'Use the given directory as working directory', '.');
    }

    /**
     * Executes the current command.
     *
     * If method throw an exception then command exits with code 1
     * and show error message, otherwise exits with code 0.
     *
     * @return void|int Exit code, if not provided then status code 0 is returned.
     */
    protected function exec()
    {
        $this->output->write('Creating deplink.json file... ');

        $packageName = $this->resolveName();
        $this->createPackageFile($packageName);

        $this->output->writeln('<info>OK</info>');
    }

    /**
     * Get package name passed in the argument
     * or retrieve it from the current working directory.
     *
     * @return PackageNameObject
     */
    private function resolveName()
    {
        $name = $this->input->getArgument('name');
        if (empty($name)) {
            $workingDir = $this->fs->getWorkingDir();
            $parts = explode('/', $workingDir);

            $package = array_pop($parts);
            $org = array_pop($parts);

            $name = "$org/$package";
        }

        return new PackageNameObject($name);
    }

    /**
     * @param PackageNameObject $packageName
     * @throws \Exception
     */
    private function createPackageFile(PackageNameObject $packageName)
    {
        if ($this->fs->existsFile('deplink.json')) {
            throw new \Exception("Package already exists in given directory");
        }

        if (!empty($this->fs->listFiles('.')) || !empty($this->fs->listDirs('.'))) {
            throw new \Exception("Cannot initialize package in non-empty directory");
        }

        $contents = json_encode([
            'name' => $packageName->get(),
            'type' => 'project',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->fs->writeFile('deplink.json', $contents);
    }
}
