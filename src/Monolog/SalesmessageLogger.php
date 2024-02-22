<?php
declare(strict_types=1);

namespace Salesmessage\Monolog;

use Salesmessage\Monolog\Formatter\SalesmessageFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Logger;
use Throwable;
use Monolog\LogRecord;
use Monolog\Formatter\LineFormatter;

/**
 * Logging Extender to provide more info and avoid giant logs
 */
class SalesmessageLogger
{
    private const LINE_FORMATTER_BACKTRACE_DEPTH = 10;
    private const SYMBOLS_LIMIT = 10000;

    /**
     * @param Logger $logger
     */
    public function __invoke(Logger $logger): void
    {
        try {
            if (function_exists('app')) {
                $traceId = app()->make('request_trace_id');
                $isConsole = app()->runningInConsole();
            }
            if (function_exists('request')) {
                $clientIp = request()?->getClientIp() ?? null;
                $userId = request()?->user()?->id ?? null;
            }
        } catch (Throwable) {
            $traceId = null;
            $clientIp = null;
            $isConsole = false;
            $userId = null;
        }

        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function (LogRecord $record) use ($traceId, $clientIp, $isConsole, $userId) {

                $extraData = [
                    'trace_id' => $traceId,
                    'client_ip' => $clientIp,
                    'user_id' => $userId,
                    'is_console' => $isConsole
                ];

                $this->addToExtra($record, $extraData);
                $contextBytes = mb_strlen(json_encode($record, JSON_NUMERIC_CHECK), '8bit');

                if ($contextBytes > env('GIANT_LOGS_THRESHOLD', self::SYMBOLS_LIMIT)) {
                    $this->addToExtra($record, ['giant_log_detected' => true]);
                }

                return $record;
            });

            $handler->setFormatter(
                (new SalesmessageFormatter(includeStacktraces: true))
                    ->setBacktraceDepth(self::LINE_FORMATTER_BACKTRACE_DEPTH)
            );
        }
    }

    /**
     * @param LogRecord $record
     * @param array $extraData
     * @return void
     */
    private function addToExtra(LogRecord $record, array $extraData): void
    {
        $record->offsetSet('extra', array_merge($record['extra'], $extraData));
    }
}