<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction;

use YogCloud\TccTransaction\Util\Di;

abstract class TccOption
{
    /** @var string 返回主键 */
    protected string $key;

    /** @var string 当前步骤 try, confirm, cancel */
    protected string $step;

    protected ?Tcc $tcc;

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setStep(string $step): void
    {
        $this->step = $step;
        Di::logger()->info('[TCC事务] 任务项 ' . get_class($this) . '#' . $step);
    }

    public function getStep(): string
    {
        return $this->step;
    }

    /**
     * @param $tcc
     */
    public function setTcc($tcc = null): void
    {
        $this->tcc = $tcc;
    }

    public function getTcc(): Tcc
    {
        return $this->tcc;
    }

    abstract public function try();

    abstract public function confirm();

    abstract public function cancel();
}
