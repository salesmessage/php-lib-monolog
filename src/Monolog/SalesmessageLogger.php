<?php
declare(strict_types=1);

namespace Salesmessage\Monolog;

use Salesmessage\Monolog\Formatter\SalesmessageFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Logger;
use Throwable;

/**
 * Logging Extender to provide more info and avoid giant logs
 */
class SalesmessageLogger
{
    private const LINE_FORMATTER_BACKTRACE_DEPTH = 10;

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

        $logger->withContext([
            'trace_id' => $traceId,
            'client_ip' => $clientIp,
            'user_id' => $isConsole,
            'is_console' => $userId
        ]);

        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function (array $record) use ($traceId, $clientIp, $isConsole, $userId) {
                $this->clearAdditionalData($record);
                $contextBytes = mb_strlen(json_encode($record, JSON_NUMERIC_CHECK), '8bit');

                if ($contextBytes > env('GIANT_LOGS_THRESHOLD', 10000)) {
                    $record['context']['giant_log_detected'] = true;
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
     * @param array $data
     * @return void
     */
    private function clearAdditionalData(array &$data): void
    {
        foreach ($data as &$value) {

            if (is_array($value)) {
                $this->clearAdditionalData($value);
            }

            if ($value instanceof Model) {
                $value = $value->withoutRelations()->setAppends([])->toArray();
            }
        }
    }
}
