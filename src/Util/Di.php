<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Util;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nsq\Nsq;
use Hyperf\Redis\Redis;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Utils\ApplicationContext;
use YogCloud\TccTransaction\Exception\Handle;

class Di
{
    /**
     * @return Nsq
     */
    public static function nsq()
    {
        return self::get(Nsq::class);
    }

    /**
     * @return Redis
     */
    public static function redis()
    {
        return self::get(Redis::class);
    }

    /**
     * @return StdoutLoggerInterface
     */
    public static function logger()
    {
        return self::get(self::config('tcc.logger', StdoutLoggerInterface::class));
    }

    /**
     * @return Handle
     */
    public static function exception()
    {
        return self::get(self::config('tcc.exception', Handle::class));
    }

    /**
     * @return IdGeneratorInterface
     */
    public static function idGenerator()
    {
        return self::get(IdGeneratorInterface::class);
    }

    /**
     * @param null $default
     *
     * @return mixed
     */
    public static function config(string $key, $default = null)
    {
        return self::get(ConfigInterface::class)->get($key, $default);
    }

    /**
     * @return mixed|object
     */
    public static function get(string $id)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }

        return $container;
    }
}
