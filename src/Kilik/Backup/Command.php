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
     * Commands asked from command line
     */
    const CMD_DISPLAY_HELP = 'help';
    const CMD_BACKUP = 'backup';
    const CMD_PURGE = 'purge';


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
                $this->backup();
                break;
        }

    }

    /**
     * make backup
     */
    public function backup()
    {
        // check config
        $this->config->checkConfig();
    }

}