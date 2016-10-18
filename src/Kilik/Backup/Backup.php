<?php

namespace Kilik\Backup;

use Kilik\Backup\Traits\ConfigTrait;
use Kilik\Backup\Traits\LoggerTrait;

class Backup
{
    use LoggerTrait;
    use ConfigTrait;

    /**
     * @var Backup
     */
    private $backup;

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
    const CMD_BACKUP = 'backup';
    const CMD_CHECK_CONFIG = 'check-config';
    const CMD_DISPLAY_HELP = 'help';
    const CMD_PURGE = 'purge';

    const ALL = 'all';

    /**
     * Set test mode
     *
     * @param bool $test
     *
     * @return static
     */
    public function setTest($test)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Set force mode
     *
     * @param bool $force
     *
     * @return static
     */
    public function setForce($force)
    {
        $this->force = $force;
    }

    /**
     * make backups
     *
     * @param string $strServers : servers to backup (ex: srv1) (ex: srv1,srv2) (ex: all)
     * @param string $strBackups : backups to save (ex: bk1) (ex: bk1,bk2) (ex: all)
     * @throws \Exception
     */
    public function backup($strServers, $strBackups)
    {
        // check config
        $this->config->checkConfig();

        if (is_null($strServers)) {
            throw new \Exception('no servers defined, use \'--backup all\' to backup all servers');
        }

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
        $this->logger->addInfo('backup '.$serverName.'/'.$backupName.' start');
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
                $this->logger->addInfo(
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

        $this->logger->addInfo('backup '.$serverName.'/'.$backupName.' end');
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