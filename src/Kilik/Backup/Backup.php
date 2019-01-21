<?php

namespace Kilik\Backup;

use Kilik\Backup\Config\Server;
use Kilik\Backup\Config\Snapshot;
use Kilik\Backup\Traits\ConfigTrait;
use Kilik\Backup\Traits\LoggerTrait;
use Kilik\Backup\Config\TimeRule;

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
        $this->logger->addInfo('backup() start');
        $startTime = microtime(true);

        // check config
        $this->config->checkConfig();

        if (is_null($strServers)) {
            throw new \Exception('no servers defined, use \'--backup all\' to backup all servers');
        }

        $serversNames = explode(',', $strServers);

        // for each server
        foreach ($this->config->getServers() as $server) {
            if ($strServers == self::ALL || in_array($server->getName(), $serversNames)) {
                $this->backupServer($server, $strBackups);
            }
        }

        $endTime = microtime(true);
        $this->logger->addInfo('backup() end ('.sprintf('%.1fs', $endTime - $startTime).')');
    }

    /**
     * make server backup
     *
     * @param Server $server
     * @param string $strBackups : backups to save (ex: bk1) (ex: bk1,bk2) (ex: all)
     */
    public function backupServer(Server $server, $strBackups)
    {
        $this->logger->addInfo('backupServer('.$server.') start');
        $startTime = microtime(true);

        $backupsNames = explode(',', $strBackups);

        $backupsTodo = [];
        /* @var $snapshotsTodo Snapshot[] */
        $snapshotsTodo = [];
        /* @var $snapshotsCreated Snapshot[] */
        $snapshotsCreated = [];
        $snapshotsMissing = false;

        // evaluate backup to do and snapshots to make
        foreach ($server->getBackups() as $backup) {
            if ($strBackups == self::ALL || in_array($backup->getName(), $backupsNames)) {
                $backupsTodo[] = $backup;
                // if this backup need a snapshot
                if (!is_null($backup->getSnapshot())) {
                    $snapshot = $backup->getSnapshot()->getName();
                    if (!isset($snapshotsTodo[$snapshot])) {
                        $snapshotsTodo[$snapshot] = $backup->getSnapshot();
                    }
                }
            }
        }

        // prepare snapshots before sync
        if (count($snapshotsTodo) > 0) {
            $this->logger->addNotice(count($snapshotsTodo).' snapshots to create');
            foreach ($snapshotsTodo as $snapshotTodo) {
                try {
                    $this->createSnapshot($snapshotTodo);
                    $snapshotsCreated[] = $snapshotTodo;
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    $snapshotsMissing = true;
                    // alt on first failure
                    break;
                }
            }
        }

        // if no snapshots are missing
        if (!$snapshotsMissing) {
            // foreach backup to do,
            foreach ($backupsTodo as $backupTodo) {
                $this->backupBackup($backupTodo);
            }
        } else {
            $this->logger->addError('one snapshot is missing, backups of '.$server.' ignored');
        }

        // remove snapshots after sync
        if (count($snapshotsCreated) > 0) {
            $this->logger->addNotice(count($snapshotsCreated).' snapshots to remove');
            foreach ($snapshotsCreated as $snapshotTodo) {
                $this->removeSnapshot($snapshotTodo);
            }
        }

        $endTime = microtime(true);
        $this->logger->addInfo('backupServer('.$server.') end ('.sprintf('%.1fs', $endTime - $startTime).')');
    }

    /**
     * Execute remote command on a server
     *
     * @param Server $server
     * @param string $cmd
     *
     * @return int command line returned value
     */
    public function execRemoteCmd(Server $server, $cmd)
    {
        $remoteCmd = $server->getRemoteCmd($cmd);
        $this->logger->addDebug($remoteCmd);
        if (!$this->test) {
            system($remoteCmd, $result);
        } else {
            $result = 0;
        }
        $this->logger->addDebug('returned '.$result);

        return $result;
    }

    /**
     * Create a snapshot
     *
     * @param Snapshot $snapshot
     * @throws \Exception
     */
    public function createSnapshot(Snapshot $snapshot)
    {
        $server = $snapshot->getServer();
        $this->logger->addInfo('createSnapshot('.$server.','.$snapshot.') start');
        $startTime = microtime(true);

        try {
            // exec before create ?
            if ($snapshot->getExecBeforeCreate()) {
                $this->execRemoteCmd($server, $snapshot->getExecBeforeCreate());
            }

            // create the snapshot
            $lvCmd = $this->config->getBin('lvcreate').' '.$snapshot->getCreateCmdLine();
            $result = $this->execRemoteCmd($server, $lvCmd);

            if ($result != 0) {
                throw new \Exception('error creating snapshot '.$server.','.$snapshot);
            }

            // mount the snapshot
            // ex: mount /dev/vg/snaphome /snapshots/home
            $mountCmd = $this->config->getBin('mount').' '.$snapshot->getMountCmdLine();
            $result = $this->execRemoteCmd($server, $mountCmd);

            if ($result != 0) {
                throw new \Exception('error mounting snapshot '.$server.','.$snapshot);
            }

            // exec after create ?
            if ($snapshot->getExecAfterCreate()) {
                $this->execRemoteCmd($server, $snapshot->getExecAfterCreate());
            }
        } catch (\Exception $e) {
            if ($snapshot->getExecAfterCreateFailed()) {
                $this->execRemoteCmd($server, $snapshot->getExecAfterCreateFailed());
            }
            throw $e;
        }

        $endTime = microtime(true);
        $this->logger->addInfo(
            'createSnapshot('.$server.','.$snapshot.') end ('.sprintf('%.1fs', $endTime - $startTime).')'
        );
    }

    /**
     * Remove a snapshot
     *
     * @param Snapshot $snapshot
     */
    public function removeSnapshot(Snapshot $snapshot)
    {
        $server = $snapshot->getServer();

        $this->logger->addInfo('removeSnapshot('.$server.','.$snapshot.') start');
        $startTime = microtime(true);

        // exec before remove ?
        if ($snapshot->getExecBeforeRemove()) {
            $this->execRemoteCmd($server, $snapshot->getExecBeforeRemove());
        }

        // unmount the snapshot
        // ex: umount /dev/vg/snaphome
        $mountCmd = $this->config->getBin('umount').' '.$snapshot->getUmountCmdLine();
        $result = $this->execRemoteCmd($server, $mountCmd);

        // remove the snapshot
        $lvCmd = $this->config->getBin('lvremove').' '.$snapshot->getRemoveCmdLine();
        $result = $this->execRemoteCmd($server, $lvCmd);

        // exec after remove ?
        if ($snapshot->getExecAfterRemove()) {
            $this->execRemoteCmd($server, $snapshot->getExecAfterRemove());
        }

        $endTime = microtime(true);
        $this->logger->addInfo(
            'removeSnapshot('.$server.','.$snapshot.') end ('.sprintf('%.1fs', $endTime - $startTime).')'
        );
    }

    /**
     * make backup server backup
     *
     * @param \Kilik\Backup\Config\Backup $backup
     */
    public function backupBackup(\Kilik\Backup\Config\Backup $backup)
    {
        $server = $backup->getServer();
        $this->logger->addInfo('backupBackup('.$server.','.$backup.') start');
        $startTime = microtime(true);

        $currentRepository = $this->config->getRepository()->getCurrentPath().'/'.$server.'/'.$backup;
        if (!is_dir($currentRepository)) {
            if (!mkdir($currentRepository, 0700, true)) {
                $this->logger->addError('can\'t create \''.$currentRepository.'\'');

                return;
            }
        }

        $date = new \DateTime('now');

        $historyBaseRepository = $this->config->getRepository()->getHistoryPath().'/'.$date->format(
                'Ymd'
            ).'/'.$server;
        $historyRepository = $historyBaseRepository.'/'.$backup;

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
        if (!is_null($backup->getSnapshot())) {
            $remotePath = $backup->getSnapshot()->getMount().$backup->getPath();
        } // else, without snapshot: absolute path
        else {
            $remotePath = $backup->getPath();
        }

        $cmd = $this->config->getBin('rsync');
        $cmd .= ' '.$backup->getRsyncOptions();
        $cmd .= ' root@'.$server->getHostname().':'.$remotePath.'/ '.$currentRepository;

        $cmdStartTime = microtime(true);
        $this->logger->addDebug($cmd);
        if (!$this->test) {
            system($cmd, $result);
        } else {
            $result = 0;
        }
        $cmdEndTime = microtime(true);
        $this->logger->addDebug('returned '.$result.' ('.sprintf('%.1fs', $cmdEndTime - $cmdStartTime).')');

        // only if rsync is a success
        if ($result == 0) {
            $cmd = $this->config->getBin('cp');
            $cmd .= ' -al '.$currentRepository.' '.$historyRepository;

            $cmdStartTime = microtime(true);
            $this->logger->addDebug($cmd);
            if (!$this->test) {
                system($cmd, $result);
            } else {
                $result = 0;
            }
            $cmdEndTime = microtime(true);
            $this->logger->addDebug('returned '.$result.' ('.sprintf('%.1fs', $cmdEndTime - $cmdStartTime).')');
        }

        $endTime = microtime(true);
        $this->logger->addInfo(
            'backupBackup('.$server.','.$backup.') end ('.sprintf('%.1fs', $endTime - $startTime).')'
        );
    }

    /**
     * Remove dir, recursively
     *
     * @param $dir
     * @return bool
     */
    public function rmdir($dir)
    {
        $cmd = $this->config->getBin('rm').' -rf '.$dir;
        $this->logger->addDebug($cmd);
        system($cmd);
    }

    /**
     * Get the best rule
     *
     * @param TimeRule[] $rules
     * @param \DateTime $date
     * @return TimeRule the match rule (or null)
     */
    public function getBestRule($rules, \DateTime $date)
    {
        $minDate = clone $date;
        $matchRule = null;

        // test all time rules
        foreach ($rules as $rule) {
            // if rule match
            if ($rule->dateMatch($date)) {
                $delayDate = $rule->getDelayDate($date);
                // if time expression is valid
                if ($delayDate->getTimestamp() < ($date->getTimestamp() - (24 * 60 * 60))) {
                    // if rule is older, get it
                    if ($delayDate->getTimestamp() < $minDate->getTimestamp()) {
                        $minDate = $delayDate;
                        $matchRule = $rule;
                    }
                }
            }
        }

        return $matchRule;
    }

    /**
     * Remove old backups
     *
     * @throws \Exception
     */
    public function purge()
    {
        $this->logger->addInfo('purge start');
        $startTime = microtime(true);

        // check config
        $this->config->checkConfig();
        $timeRules = $this->config->getTimeRules();

        $historyPath = $this->config->getRepository()->getHistoryPath();
        $historyDirs = scandir($historyPath, true);

        foreach ($historyDirs as $historyDir) {
            // skip current and parent directories
            if (in_array($historyDir, ['.', '..'])) {
                continue;
            }
            // if is a directory
            if (is_dir($historyPath.'/'.$historyDir)) {
                // if directory is date formated
                if (preg_match('|^[0-9]{8}$|', $historyDir)) {
                    $historyDate = \DateTime::createFromFormat('!Ymd', $historyDir);
                    // if is a good date
                    if ($historyDate) {
                        // check if backup should be kept
                        $this->logger->addNotice('checking date '.$historyDate->format('Ymd'));

                        $rule = $this->getBestRule($timeRules, $historyDate);

                        if (is_null($rule)) {
                            $this->logger->addError('no rule match');
                        } else {
                            $ruleDate = $rule->getDelayDate(new \DateTime('now'));
                            $keep = ($ruleDate->getTimestamp() < $historyDate->getTimestamp());

                            $this->logger->addNotice(
                                'match rule: '.$rule->getName().' after '.$ruleDate->format(
                                    'Y-m-d'
                                ).': '.($keep ? 'keep' : 'remove')
                            );

                            if (!$keep) {
                                if (!$this->test) {
                                    $this->rmdir($historyPath.'/'.$historyDir);
                                }
                            }
                        }
                    }
                }
            }
        }

        $endTime = microtime(true);
        $this->logger->addInfo('purge end ('.sprintf('%.1fs', $endTime - $startTime).')');
    }

}
