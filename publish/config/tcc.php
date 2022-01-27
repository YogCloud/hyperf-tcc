<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
return [
    'nsq_detection_time' => 5, // NSQ检测补偿事务时间
    'nsq_topic' => env('APP_NAME', 'hyperf') . ':tcc', // NSQ Topic
    'redis_prefix' => env('APP_NAME', 'hyperf') . ':tcc', // Redis 缓存前缀
    'exception' => \YogCloud\TccTransaction\Exception\Handle::class, // 无法处理异常通知类
    'logger' => \Hyperf\Contract\StdoutLoggerInterface::class, // 日志提供者
];
