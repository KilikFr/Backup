<?php

namespace App\Backup;

class Logger
{
    /**
     * @param string $level
     * @param string $message
     */
    public function addMessage($level, $message)
    {
        echo date('Y-m-d H:i:s').' - '.strtoupper($level).': '.$message.PHP_EOL;
    }

    /**
     * @param string $message
     */
    public function addDebug($message)
    {
        $this->addMessage('DEBUG', $message);
    }

    /**
     * @param string $message
     */
    public function addNotice($message)
    {
        $this->addMessage('NOTICE', $message);
    }

    /**
     * @param string $message
     */
    public function addInfo($message)
    {
        $this->addMessage('INFO', $message);
    }

    /**
     * @param string $message
     */
    public function addError($message)
    {
        $this->addMessage('ERROR', $message);
    }
}