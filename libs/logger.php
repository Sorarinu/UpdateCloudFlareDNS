<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NativeMailerHandler;

class MyLog {
    static $log = null;

    static function setup($logname = "app") {
        self::$log = new Logger($logname);

	$dateFormat = "Y-m-d H:i:s";
        $output     = "[%datetime%][%level_name%]> %message% : %context% : %extra%\n";
        $formatter  = new LineFormatter($output, $dateFormat);
        $formatter->includeStacktraces(true);

        $streamHandler = new StreamHandler('php://stdout', Logger::DEBUG);
        $streamHandler->setFormatter($formatter);
        self::$log->pushHandler($streamHandler);

	$rotatingFileHandler = new RotatingFileHandler("/var/log/UpdateCloudFlareDNS/app.log", 0, Logger::DEBUG);
        $rotatingFileHandler->setFilenameFormat('{filename}_{date}', 'Y-m');
        $rotatingFileHandler->setFormatter($formatter);
        self::$log->pushHandler($rotatingFileHandler);
    }
}

MyLog::setup();
