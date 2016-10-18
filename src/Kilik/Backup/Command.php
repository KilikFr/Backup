<?php

namespace Kilik\Backup;

use Kilik\Backup\Traits\ConfigTrait;
use Kilik\Backup\Traits\LoggerTrait;

class Command
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
     * Entry point
     */
    public function exec()
    {
        global $argc, $argv;

        try {
            $this->logger = new Logger();

            $this->config = new Config();
            $this->config->setLogger($this->getLogger());

            $this->backup = new Backup();
            $this->backup->setLogger($this->logger);
            $this->backup->setConfig($this->config);

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
                        if (isset($argv[$i + 1]) && $argv[$i + 1][0] != '-') {
                            $servers = $argv[++$i];
                        }
                        if (isset($argv[$i + 1]) && $argv[$i + 1][0] != '-') {
                            $backups = $argv[++$i];
                        }
                        break;
                    case '--check-config':
                        if (!is_null($cmd)) {
                            throw new \Exception('too many commands');
                        }
                        $cmd = self::CMD_CHECK_CONFIG;
                        break;
                    case '--purge':
                        if (!is_null($cmd)) {
                            throw new \Exception('too many commands');
                        }
                        $cmd = self::CMD_PURGE;
                        break;
                    case '--force':
                        $this->backup->setForce(true);
                        break;
                    case '--test':
                    case '--dry-run':
                        $this->backup->setTest(true);
                        break;
                    default:
                        throw new \Exception('unknown argument \''.$argv[$i].'\'');
                        break;
                }
            }

            // default command: display help
            if (is_null($cmd)) {
                $cmd = self::CMD_DISPLAY_HELP;
            }


            switch ($cmd) {
                case self::CMD_CHECK_CONFIG:
                    $this->config->loadFromFile($configFilename);
                    $this->config->checkConfig();
                    echo 'config is valid'.PHP_EOL;
                    break;
                case self::CMD_DISPLAY_HELP:
                    echo 'syntax: backup [command] [options]'.PHP_EOL.PHP_EOL;
                    echo 'commands: '.PHP_EOL;
                    echo ' --backup [servers] [backups] launch backup of servers (ex: all) or (ex: srv1,srv2)'.PHP_EOL;
                    echo '                              for backups (ex: all - by default) or (ex: bk1,bk2)'.PHP_EOL;
                    echo ' --check-config               check configuration'.PHP_EOL;
                    echo ' --purge                      remove old backups'.PHP_EOL;
                    echo 'options: '.PHP_EOL;
                    echo ' --test                       enable test (no copy, no delete)'.PHP_EOL;
                    echo ' --config [config-filename]   use custom config file (default: '.
                        $this->config->getDefaultConfigFilename().')'.PHP_EOL;
                    break;
                case self::CMD_PURGE:
                    $this->config->loadFromFile($configFilename);
                    $this->backup->purge();
                    break;
                case self::CMD_BACKUP:
                    $this->config->loadFromFile($configFilename);
                    $this->backup->backup($servers, $backups);
                    break;
            }
        } catch (\Exception $e) {
            echo $e->getMessage().PHP_EOL;
            //echo 'exception '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().PHP_EOL;
        }

    }

}