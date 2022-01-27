<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Exception;

class TccTraceException extends \Exception
{
    public array $traces;

    public function __construct(\Throwable $try, \Throwable $cancel)
    {
        parent::__construct('TCC事务回滚异常', 0, null);
        foreach (['try' => $try, 'cancel' => $cancel] as $key => $exception) {
            if ($exception) {
                $this->traces[$key] = [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'location' => $exception->getLine() . '#' . $exception->getFile(),
                ];
            } else {
                $this->traces[$key] = null;
            }
        }
    }
}
