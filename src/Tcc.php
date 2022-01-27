<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\Redis;
use Hyperf\Utils\Parallel;
use YogCloud\TccTransaction\Exception\ServiceException;
use YogCloud\TccTransaction\Exception\TccTraceException;
use YogCloud\TccTransaction\Util\Di;

class Tcc
{
    protected string $tccId; # 事务ID

    /**
     * @var TccState 状态
     */
    protected TccState $state;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * Tcc constructor.
     */
    public function __construct(?string $tccId = null, ?TccState $state = null)
    {
        if ($tccId) {
            $this->tccId = $tccId;
            $this->state = $state;
            // 重新关联自身
            foreach ($this->state->options as $option) {
                $option->setTcc($this);
            }
        } else {
            $this->tccId = (string) Di::idGenerator()->generate();
            $this->state = new TccState(false, false, 'try');
        }
        $this->redis = Di::redis();
        $this->logger = Di::logger();
    }

    /**
     * 增加操作.
     * @param int|string $key
     * @return $this
     */
    public function tcc($key, TccOption $tcc): self
    {
        $tcc->setKey((string) $key);
        $this->state->options[$key] = $tcc;
        return $this;
    }

    /**
     * 依赖关系.
     * @param array $rely 被依赖主键
     * @return $this
     */
    public function rely(array $rely): self
    {
        $this->state->rely = $rely;
        return $this;
    }

    /**
     * 开启事务
     * @throws \Throwable
     * @throws ServiceException
     * @throws TccTraceException
     */
    public function begin()
    {
        // 读取编排
        if (! $this->state->rely) {
            $this->state->rely = [array_keys($this->state->options)];
        }

        // 推送任务
        $this->pushState(false, false, 'try');
        $this->pushMessage();
        $this->bindOptions($this);

        try {
            $this->runOptionTry();   # 开启事务
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), $e->getCode());
        }
        try {
            $this->runOptionConfirm();  # 确认提交
        } catch (\Throwable $e) {
            $this->runOptionCancel($e);   # 回滚事务
        }
    }

    /*
     * 执行事务启动操作
     */
    public function runOptionTry(): void
    {
        // 根据流程编排去执行
        foreach ($this->state->rely as $syncs) {
            $parallel = new Parallel();
            foreach ($syncs as $key) {
                $parallel->add(function () use ($key) {
                    $option = $this->state->options[$key];
                    $this->state->results[$key] = $option->try();
                    $option->setStep('try');
                });
            }
            $parallel->wait();
        }
    }

    /*
     * 执行事务提交操作
     */
    public function runOptionConfirm(): void
    {
        // 根据流程编排去执行
        foreach ($this->state->rely as $syncs) {
            $parallel = new Parallel();
            foreach ($syncs as $key) {
                $parallel->add(function () use ($key) {
                    $option = $this->state->options[$key];
                    if ($option->getStep() === 'try') {
                        $option->confirm();
                        $option->setStep('confirm');
                    }
                });
            }
            $parallel->wait();
        }

        // 推送 处理成功消息
        $this->pushState(true, true, 'confirm');
    }

    /*
     * 执行事务回滚操作
     */
    /**
     * @throws \Throwable
     * @throws TccTraceException
     */
    public function runOptionCancel(\Throwable $tryException = null): void
    {
        try {
            // 根据流程编排倒序去执行
            foreach (array_reverse($this->state->rely) as $syncs) {
                $parallel = new Parallel();
                foreach (array_reverse($syncs) as $key) {
                    $parallel->add(function () use ($key) {
                        $option = $this->state->options[$key];
                        switch ($option->getStep()) {
                            case 'try':
                            case 'confirm':
                                $option->cancel();
                                $option->setStep('cancel');
                                break;
                        }
                    });
                }
                $parallel->wait();
            }

            // 推送 处理业务回滚成功消息
            $this->pushState(true, true, 'cancel');
        } catch (\Throwable $cancelException) {
            // 推送 处理业务回滚成功消息
            $this->pushState(true, false, 'cancel');
            // 抛出错误
            if ($tryException) {
                throw new TccTraceException($tryException, $cancelException);
            }
            throw $cancelException;
        }

        // 抛出错误
        if ($tryException) {
            throw $tryException;
        }
    }

    /**
     * 获取响应参数.
     * @param null $default
     * @return null|mixed
     */
    public function get(string $key, $default = null)
    {
        if (isset($this->state->results[$key])) {
            return $this->state->results[$key];
        }
        foreach ($this->state->options as $option) {
            if (get_class($option) === $key) {
                return $this->state->results[$option->getKey()];
            }
        }
        return $default;
    }

    /**
     * 绑定操作.
     */
    protected function bindOptions(?Tcc $tcc): void
    {
        foreach ($this->state->options as $option) {
            $option->setTcc($tcc);
        }
    }

    /*
     * 推送消息
     */
    protected function pushMessage(): void
    {
        try {
            Di::nsq()->publish(Di::config('tcc.nsq_topic', 'tcc'), $this->tccId, Di::config('tcc.nsq_detection_time', 5));
        } catch (\Throwable $e) {
        }
        $this->logger->info('[TCC事务] 推送通知 ' . $this->tccId);
    }

    /*
     * 推送状态
     */
    protected function pushState(bool $tccStatus = false, bool $optionStatus = false, string $optionStep = 'try'): void
    {
        $this->state->tccStatus = $tccStatus;
        $this->state->optionStatus = $optionStatus;
        $this->state->optionStep = $optionStep;

        // 当推送完成后解绑操作-减轻存储负担
        if ($tccStatus) {
            $this->bindOptions(null);
        }

        // 当全部成功后删除操作和返回-减轻存储负担
        if ($optionStatus) {
            $state = clone $this->state;
            $state->options = [];
            $state->results = [];
            $state->rely = [];
            $state = serialize($state);
        } else {
            $state = serialize($this->state);
        }

        $this->redis->hSet('tcc', $this->tccId, $state);
        $this->logger->info('[TCC事务] 推送状态 ' . $optionStep);
    }
}
