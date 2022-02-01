<?php

namespace App\Command;

use App\Backup\BackupService;
use App\Backup\Config;
use App\Backup\Config\Backup;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'purge',
    description: 'Purge old backups',
)]
class PurgeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do nothing (dry run)')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Use config file', Config::DEFAULT_CONFIG_FILENAME)
            ->addOption('test', null, InputOption::VALUE_NONE, 'Test (dry run alias)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $service=new BackupService();

        $configFile = $input->getOption('config');
        $dryRun = $input->getOption('dry-run') | $input->getOption('test');

        $service->getConfig()->loadFromFile($configFile);
        $service->getConfig()->checkConfig();
        $service->purge();

        $io->success('purge finished');

        return Command::SUCCESS;
    }
}
