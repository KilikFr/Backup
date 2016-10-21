<?php

namespace Kilik\Backup;

use Kilik\Backup\Config\Repository;
use Kilik\Backup\Config\Rsync;
use Kilik\Backup\Config\Server;
use Kilik\Backup\Config\TimeRule;
use Kilik\Backup\Config\Traits\RsyncTrait;
use Kilik\Backup\Traits\LoggerTrait;

class Config
{
    use LoggerTrait;
    use RsyncTrait;

    private $defaultConfigFilename = "/etc/kilik.backup.json";

    /**
     * Default configuration
     *
     * @var array
     */
    private $defaultConfig = [
        'bin' => [
            'cp' => '/bin/cp',
            'lvcreate' => '/sbin/lvcreate',
            'lvremove' => '/sbin/lvremove',
            'mount' => '/bin/mount',
            'rm' => '/bin/rm',
            'rsync' => '/usr/bin/rsync',
            'umount' => '/bin/umount',
        ],
        'rsync' => [
            'options' => '-rlogtpxW --delete-after --delete-excluded',
        ],
        'lvm' => [
            'size' => '10G',
        ],
        'repository' => [
            'path' => '/var/backup',
        ],
        'servers' => [],
    ];

    /**
     * Binaries
     *
     * @var array
     */
    private $bin;

    /**
     * Time rules
     *
     * @var TimeRule[]
     */
    private $timeRules;

    /**
     * Repository
     *
     * @var Repository
     */
    private $repository;

    /**
     * Servers
     *
     * @var Server[]
     */
    private $servers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->repository = new Repository();
        $this->timeRules = [];
        $this->servers = [];
        $this->rsync = new Rsync();
    }

    /**
     * Get the default config filemane
     *
     * @return string
     */
    public function getDefaultConfigFilename()
    {
        return $this->defaultConfigFilename;
    }

    /**
     * Get repository
     *
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Get servers list
     *
     * @return Server[]
     */
    public function getServers()
    {
        return $this->servers;
    }


    /**
     * Load config from a file
     *
     * @param string $configFilename
     * @throws \Exception
     */
    public function loadFromFile($configFilename)
    {
        if (!file_exists($configFilename)) {
            throw new \Exception('file not found \''.$configFilename.'\'');
        }

        $json = file_get_contents($configFilename);

        $config = json_decode($json, true);

        if ($config == false) {
            throw new \Exception('config file \''.$configFilename.'\' has a bad format');
        }

        // merge config and default config
        $fullConfig = array_merge($this->defaultConfig, $config);

        // load from array
        $this->loadFromArray($fullConfig);
    }

    /**
     * Load config from array
     *
     * @param array $config
     * @throws \Exception
     */
    public function loadFromArray($config)
    {
        // load binaries
        if (isset($config['bin']) && is_array($config['bin'])) {
            $this->bin = $config['bin'];
        }

        // load time rules
        if (!isset($config['time']) || !is_array($config['time'])) {
            throw new \Exception('no time rules');
        }

        $this->timeRules = [];

        foreach ($config['time'] as $ruleName => $time) {
            $this->timeRules[] = (new TimeRule())->setName($ruleName)->setFromArray($time);
        }

        // load repository
        if (isset($config['repository']['path'])) {
            $this->repository->setPath($config['repository']['path']);
        }

        // load rsync config
        if (isset($config['rsync'])) {
            $this->rsync->setFromArray($config['rsync']);
        }

        // load servers configs
        if (!isset($config['servers']) || !is_array($config['servers'])) {
            throw new \Exception('no servers configurations');
        }

        foreach ($config['servers'] as $serverName => $serverConfig) {
            $this->servers[] = (new Server())->setName($serverName)->setFromArray($serverConfig)->setConfig($this);
        }

    }

    /**
     * Check config
     *
     * @throws \Exception if problem occur
     */
    public function checkConfig()
    {
        // check is dir
        if (!is_dir($this->repository->getPath())) {
            throw new \Exception('repository path \''.$this->repository->getPath().'\' not exists');
        }

        // check current dir
        if (!is_dir($this->repository->getCurrentPath())) {
            $this->logger->addNotice(
                'current repository directory \''.$this->repository->getCurrentPath().'\' not exists'
            );
            if (!mkdir($this->repository->getCurrentPath(), 0700, true)) {
                $this->logger->addError('can\'t create current repository directory');
            } else {
                $this->logger->addNotice('current repository directory created');
            }
        }

        // check history dir
        if (!is_dir($this->repository->getHistoryPath())) {
            $this->logger->addNotice(
                'history repository directory \''.$this->repository->getHistoryPath().'\' not exists'
            );
            if (!mkdir($this->repository->getHistoryPath(), 0700, true)) {
                $this->logger->addError('can\'t create history repository directory');
            } else {
                $this->logger->addNotice('history repository directory created');
            }
        }

        // check servers config
        foreach ($this->servers as $server) {
            $server->checkConfig();
        }
    }

    /**
     * Get server rsync options
     *
     * @param array $serverConfig
     * @param array $backupConfig
     * @return array
     */
    public function getBackupRsyncOptions($serverConfig, $backupConfig)
    {
        if (isset($backupConfig['rsync']['options'])) {
            return $backupConfig['rsync']['options'];
        }
        if (isset($serverConfig['rsync']['options'])) {
            $serverOptions = $serverConfig['rsync']['options'];
        } else {
            $serverOptions = $this->getGlobalRsyncOptions();
        }


        if (isset($serverConfig['rsync']['more_options'])) {
            $serverOptions .= ' '.$serverConfig['rsync']['more_options'];
        }
        if (isset($backupConfig['rsync']['more_options'])) {
            $serverOptions .= ' '.$backupConfig['rsync']['more_options'];
        }

        return $serverOptions;

    }

    /**
     * Get bin path
     *
     * @param string $binary
     *
     * @return string
     * @throws \Exception
     */
    public function getBin($binary)
    {
        if (!isset($this->bin[$binary])) {
            throw new \Exception('binary '.$binary.' undefined');
        }

        return $this->bin[$binary];
    }

}