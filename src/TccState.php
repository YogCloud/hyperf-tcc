<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction;

class TccState
{
    /**
     * @var bool 事务状态
     */
    public $tccStatus;

    /**
     * @var bool 操作状态
     */
    public $optionStatus;

    /**
     * @var string 当前步数
     */
    public $optionStep;

    /**
     * @var TccOption[] 操作步骤
     */
    public array $options = [];

    /**
     * @var array 操作响应
     */
    public array $results = [];

    /**
     * @var array 操作编排 [ sync[1, 2], sync[3, 4], sync[5, 6]]
     */
    public array $rely;

    /**
     * @var int 创建时间
     */
    public int $createAt;

    public function __construct(bool $tccStatus = false, bool $optionStatus = false, string $optionStep = 'try')
    {
        $this->tccStatus = $tccStatus;
        $this->optionStatus = $optionStatus;
        $this->optionStep = $optionStep;
        $this->createAt = time();
    }
}
