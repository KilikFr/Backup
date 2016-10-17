<?php

namespace Kilik\Backup;

use Kilik\Backup\Traits\LoggerTrait;

class Config
{
    use LoggerTrait;

    private $defaultConfigFilename = "app/config/demo.json";

    /**
     * Default configuration
     *
     * @var array
     */
    private $defaultConfig = [
        'repository' => [
            'path' => '/var/backup',
        ],
    ];

    /**
     * User defined configuration
     *
     * @var array
     */
    private $config;

    /**
     * @param $configFilename
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
        $this->config = array_merge($this->defaultConfig, $config);
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
     * Get repository path
     *
     * @return string
     * @throws \Exception if repository path is not set
     */
    public function getRepositoryPath()
    {
        // check repository exists
        if (!isset($this->config['repository']['path'])) {
            throw new \Exception('repository path is not defined');
        }

        // check repository is not empty
        if ($this->config['repository']['path'] == '') {
            throw new \Exception('repository path is empty');
        }

        return $this->config['repository']['path'];
    }

    /**
     * Get current base path
     *
     * @return string
     */
    public function getCurrentRepositoryPath()
    {
        return $this->getRepositoryPath().'/current';
    }

    /**
     * get history base path
     *
     * @return string
     */
    public function getHistoryRepositoryPath()
    {
        return $this->getRepositoryPath().'/history';
    }

    /**
     * Check config
     *
     * @throws \Exception if problem occur
     */
    public function checkConfig()
    {
        // check is dir
        if (!is_dir($this->getRepositoryPath())) {
            throw new \Exception('repository path \''.$this->getRepositoryPath().'\' not exists');
        }

        // check current dir
        if (!is_dir($this->getCurrentRepositoryPath())) {
            $this->logger->addNotice(
                'current repository directory \''.$this->getCurrentRepositoryPath().'\' not exists'
            );
            if (!mkdir($this->getCurrentRepositoryPath(), '0600', true)) {
                $this->logger->addError('can\'t create current repository directory');
            } else {
                $this->logger->addNotice('current repository directory created');
            }
        }
    }

}