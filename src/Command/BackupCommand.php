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
    name: 'backup',
    description: 'Launch backups',
)]
class BackupCommand extends Command
{
    const ACTION_BACKUP='backup';
    const ACTION_CHECK_CONFIG='check-config';
    const ACTION_PURGE='purge';

    protected function configure(): void
    {
        $this
            ->addArgument('server', InputArgument::REQUIRED, 'Server to backup (ex: "server1,server2" - you can use "all")')
            ->addArgument('backup', InputArgument::OPTIONAL, 'Backup name (ex: "path1,path2" - you can use "all")', BackupService::ALL)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do nothing (dry run)')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Use config file', Config::DEFAULT_CONFIG_FILENAME)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force action (when needed)')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Test (dry run alias)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $service=new BackupService();

        $configFile = $input->getOption('config');

        $server = $input->getArgument('server');
        $backup = $input->getArgument('backup');

        $service->getConfig()->loadFromFile($configFile);
        $service->getConfig()->checkConfig();

        if($input->getOption('dry-run') | $input->getOption('test')) {
            $service->getLogger()->addInfo('dry-run enabled');
            $service->setTest(true);
        }
        if($input->getOption('force')) {
            $service->getLogger()->addInfo('force enabled');
            $service->setForce(true);
        }
        $service->backup($server, $backup);

        $io->success('Backup complete');

        return Command::SUCCESS;
    }
}
