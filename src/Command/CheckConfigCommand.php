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
    name: 'check-config',
    description: 'Check backup config',
)]
class CheckConfigCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Use config file', Config::DEFAULT_CONFIG_FILENAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $service = new BackupService();

        $configFile = $input->getOption('config');
        $service->getConfig()->loadFromFile($configFile);

        try {
            $service->getConfig()->checkConfig();

            $io->success('Config is OK');
        } catch (\Exception $e) {
            $io->error(sprintf('Error in config: %s', $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
