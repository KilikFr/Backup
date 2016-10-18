<?php

namespace Kilik\Backup;

use Kilik\Backup\Traits\LoggerTrait;

class Command
{
    use LoggerTrait;

    /**
     * @var Config
     */
    private $config;

    /**
     * test mode
     *
     * @var bool
     */
    private $test = false;

    /**
     * force mode
     *
     * @var bool
     */
    private $force = false;

    /**
     * Commands asked from command line
     */
    const CMD_DISPLAY_HELP = 'help';
    const CMD_BACKUP = 'backup';
    const CMD_PURGE = 'purge';

    const ALL = 'all';

    /**
     * Entry point
     */
    public function exec()
    {
        global $argc, $argv;

        $this->logger = new Logger();
        $this->config = new Config();
        $this->config->setLogger($this->getLogger());

        $configFilename = $this->config->getDefaultConfigFilename();
        $cmd = null;
        $servers = null;
        $backups = 'all';

        for ($i = 1; $i < $argc; $i++) {
            switch ($argv[$i]) {
                case '--config':
                    $configFilename = $argv[++$i];
                    break;
                case '--help':
                    if (!is_null($cmd)) {
                        throw new \Exception('too many commands');
                    }
                    $cmd = self::CMD_DISPLAY_HELP;
                    break;
                case '--backup':
                    if (!is_null($cmd)) {
                        throw new \Exception('too many commands');
                    }
                    $cmd = self::CMD_BACKUP;
                    break;
                case '--force':
                    $this->force = true;
                    break;
                case '--test':
                case '--dry-run':
                    $this->test = true;
                    break;
                case '--server':
                case '--servers':
                    $servers = $argv[++$i];
                    break;
                case '--backup':
                case '--backups':
                    $backups = $argv[++$i];
                    break;
            }
        }

        // default command: display help
        if (is_null($cmd)) {
            $cmd = self::CMD_DISPLAY_HELP;
        }

        try {
            $this->config->loadFromFile($configFilename);
        } catch (\Exception $e) {
            echo 'exception '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().PHP_EOL;
        }

        switch ($cmd) {
            case self::CMD_DISPLAY_HELP:
                echo 'config:'.PHP_EOL;
                print_r($this->config);
                break;
            case self::CMD_BACKUP:
                $this->backup($servers, $backups);
                break;
        }

    }

    /**
     * make backups
     *
     * @param string $strServers : servers to backup (ex: srv1) (ex: srv1,srv2) (ex: all)
     * @param string $strBackups : backups to save (ex: bk1) (ex: bk1,bk2) (ex: all)
     */
    public function backup($strServers, $strBackups)
    {
        // check config
        $this->config->checkConfig();

        $serversNames = explode(',', $strServers);

        // for each server
        foreach ($this->config->getServers() as $serverName => $serverConfig) {
            if ($strServers == self::ALL || in_array($serverName, $serversNames)) {
                $this->backupServer($serverName, $serverConfig, $strBackups);
            }
        }
    }

    /**
     * make server backup
     *
     * @param string $serverName
     * @param array $serverConfig
     * @param string $strBackups : backups to save (ex: bk1) (ex: bk1,bk2) (ex: all)
     */
    public function backupServer($serverName, $serverConfig, $strBackups)
    {
        $this->logger->addInfo('backupServer '.$serverName.' start');
        $backupsNames = explode(',', $strBackups);

        if (isset($serverConfig['backups']) && is_array($serverConfig['backups'])) {
            foreach ($serverConfig['backups'] as $backupName => $backupConfig) {
                if ($strBackups == self::ALL || in_array($backupName, $backupsNames)) {
                    // @todo: create consistent snapshots

                    $this->backupServerBackup($serverName, $serverConfig, $backupName, $backupConfig);
                }
            }
        }
        $this->logger->addInfo('backupServer '.$serverName.' end');
    }

    /**
     * make backup server backup
     *
     * @param string $serverName
     * @param array $serverConfig
     * @param string $backupName
     * @param array $backupConfig
     */
    public function backupServerBackup($serverName, $serverConfig, $backupName, $backupConfig)
    {
        // @todo: check if history directory not already exists, else, need --force to overwrite

        $currentRepository = $this->config->getCurrentRepositoryPath().'/'.$serverName.'/'.$backupName;
        if (!is_dir($currentRepository)) {
            if (!mkdir($currentRepository, 0700, true)) {
                $this->logger->addError('can\'t create \''.$currentRepository.'\'');

                return;
            }
        }

        $date = new \DateTime('now');

        $historyBaseRepository = $this->config->getHistoryRepositoryPath().'/'.$date->format(
                'Ymd'
            ).'/'.$serverName;
        $historyRepository = $historyBaseRepository.'/'.$backupName;

        // if backup already exists
        if (is_dir($historyRepository)) {
            if ($this->force) {
                $this->logger->addError(
                    'history directory \''.$historyRepository.'\' already exists (force: removed)'
                );
                if (!$this->test) {
                    $this->rmdir($historyRepository);
                }
            } else {
                $this->logger->addError(
                    'history directory \''.$historyRepository.'\' already exists (use --force or delete it before)'
                );

                return;
            }
        }

        // if base directory not exists
        if (!is_dir($historyBaseRepository)) {
            // create the directory
            if (!mkdir($historyBaseRepository, 0700, true)) {
                $this->logger->addError('can\'t create \''.$historyBaseRepository.'\'');

                return;
            }
        }

        // if snapshot is used
        if (isset($backupConfig['snapshot']) && $backupConfig['snapshot'] != '') {
            $remotePath = $serverConfig['snapshots'][$backupConfig['snapshot']]['mount'].$backupConfig['path'];
        } // else, without snapshot: absolute path
        else {
            $remotePath = $backupConfig['path'];
        }

        $rsyncOptions = $this->config->getBackupRsyncOptions($serverConfig, $backupConfig);

        // @todo work in progress rsync command line
        $cmd = 'rsync '.$rsyncOptions.' root@'.$serverConfig['hostname'].':'.$remotePath.'/* '.$currentRepository;
        $this->logger->addDebug($cmd);
        if (!$this->test) {
            system($cmd, $result);
        } else {
            $result = 0;
        }
        $this->logger->addDebug('returned '.$result);

        // @todo after rsync success, create a hard copy
        $cmd = 'cp -al '.$currentRepository.' '.$historyRepository;
        $this->logger->addDebug($cmd);
        if (!$this->test) {
            system($cmd, $result);
        } else {
            $result = 0;
        }

        $this->logger->addDebug('returned '.$result);
    }

    /**
     * Remove dir, recursively
     *
     * @param $dir
     * @return bool
     */
    public function rmdir($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir($dir.'/'.$file)) {
                $this->rmdir($dir.'/'.$file);
            } else {
                //$this->logger->addDebug('unlink('.$dir.'/'.$file.')');
                unlink($dir.'/'.$file);
            }
        }

        //$this->logger->addDebug('rmdir('.$dir.')');
        return rmdir($dir);
    }

}