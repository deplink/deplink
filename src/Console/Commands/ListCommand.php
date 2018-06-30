<?php

namespace Deplink\Console\Commands;

use Deplink\Console\BaseCommand;
use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Environment\Filesystem;
use Deplink\Resolvers\DependenciesTreeResolver;
use DI\Container;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends BaseCommand
{
    /**
     * @var InstalledPackagesManager
     */
    private $packagesManager;

    /**
     * @var DependenciesTreeResolver
     */
    private $resolver;

    /**
     * @param Filesystem $fs
     * @param Container $di
     * @param InstalledPackagesManager $packagesManager
     * @param DependenciesTreeResolver $resolver
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        Filesystem $fs,
        Container $di,
        InstalledPackagesManager $packagesManager,
        DependenciesTreeResolver $resolver
    ) {
        $this->packagesManager = $packagesManager;
        $this->resolver = $resolver;

        parent::__construct($fs, $di);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('list')
            ->setDescription('List installed dependencies')
            ->addOption('no-dev', null, InputOption::VALUE_NONE,
                'Don\'t print development dependencies')
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
     * @throws \Exception
     */
    protected function exec()
    {
        $this->checkProject();
        $this->packagesManager->snapshot();
        $this->resolver->snapshot(!$this->input->getOption('no-dev'));

        $state = $this->resolver->getResolvedStates()[0];
        $installed = $this->packagesManager->getInstalled();
        foreach ($state->getPackages() as $package) {
            $this->output->write("<info>{$package->getName()}</info> (<info>{$package->getVersion()}</info>)");

            if(!$installed->has($package->getName(), $package->getVersion())) {
                $this->output->writeln(" - <comment>out-of-date</comment>");
            } else {
                $this->output->writeln("");
            }
        }
    }
}
