<?php

namespace Kilik\Backup;

class Command
{
    private $defaultConfigFilename = "app/config/demo.json";
    private $defaultConfig;
    private $config;

    const CMD_DISPLAY_HELP = 'help';

    /**
     * @param $configFilename
     * @throws \Exception
     */
    public function loadConfig($configFilename)
    {
        if (!file_exists($configFilename)) {
            throw new \Exception('file not found \''.$configFilename.'\'');
        }

        $json = file_get_contents($configFilename);

        $this->config = json_decode($json, true);

        if ($this->config == false) {
            throw new \Exception('config file \''.$configFilename.'\' has a bad format');
        }
    }

    public function exec()
    {
        global $argc, $argv;

        $configFilename = $this->defaultConfigFilename;
        $cmd = self::CMD_DISPLAY_HELP;

        for ($i = 1; $i < $argc; $i++) {
            switch ($argv[$i]) {
                case '--config':
                    $configFilename = $argv[++$i];
                    break;
                case '--help':
                    $cmd = self::CMD_DISPLAY_HELP;
                    break;
            }


        }

        try {
            $this->loadConfig($configFilename);
        } catch (\Exception $e) {
            echo 'exception '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().PHP_EOL;
        }

        switch ($cmd) {
            case self::CMD_DISPLAY_HELP:
                echo 'config:'.PHP_EOL;
                print_r($this->config);
                break;
        }

    }
}