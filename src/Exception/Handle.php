<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Exception;

use Hyperf\DbConnection\Db;
use YogCloud\TccTransaction\TccState;

class Handle
{
    public function handle(string $tccId, TccState $state, \Throwable $e): void
    {
        Db::table('tcc_fail')->insert([
            'iid' => $tccId,
            'options' => serialize($state),
            'created_at' => date('Y-m-d H:i:s', $state->createAt),
            'exception' => json_encode([
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'location' => $e->getLine() . '#' . $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
