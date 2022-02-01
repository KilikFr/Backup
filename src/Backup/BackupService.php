<?php

namespace App\Backup;

use App\Backup\Config\Backup;
use App\Backup\Config\Server;
use App\Backup\Config\Snapshot;
use App\Backup\Traits\LoggerTrait;
use App\Backup\Config\TimeRule;

class BackupService
{
    use LoggerTrait;

    private Config $config;
    private bool $test = false;
    private bool $force = false;

    const ALL = 'all';

    public function __construct()
    {
        $this->config = new Config();
        $this->logger = new Logger();
    }

    public function setConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setTest(bool $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function setForce(bool $force): self
    {
        $this->force = $force;
    }

    /**
     * make backups
     * @throws \Exception
     */
    public function backup(string $strServers, string $strBackups)
    {
        $this->logger->addInfo('backup() start');
        $startTime = microtime(true);

        // check config
        $this->config->checkConfig();

        if (is_null($strServers)) {
            throw new \Exception('no servers defined, use \'all\' to backup all servers');
        }

        $serversNames = explode(',', $strServers);

        $nbServers=0;

        // for each server
        foreach ($this->config->getServers() as $server) {
            if ($strServers == self::ALL || in_array($server->getName(), $serversNames)) {
                $this->backupServer($server, $strBackups);
                $nbServers++;
            }
        }

        if(0 === $nbServers) {
            $this->logger->addError('no server matched');
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
    public function backupServer(Server $server, string $strBackups)
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
    public function execRemoteCmd(Server $server, string $cmd)
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
     * run server backup
     */
    public function backupBackup(Backup $backup)
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
        $cmd .= ' '.$server->getUser().'@'.$server->getHostname().':'.$remotePath.'/ '.$currentRepository;

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
