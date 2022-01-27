<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Example\Tcc;

use YogCloud\TccTransaction\Example\Service\OrderService;
use YogCloud\TccTransaction\TccOption;

class OrderMessageTcc extends TccOption
{
    /**
     * @var int
     */
    protected $msgId;

    public function try()
    {
        // 获取订单信息
        $orderId = (int) $this->tcc->get(OrderTcc::class)['id'];
        // 创建订单消息
        $service = new OrderService();
        $this->msgId = $service->createMessage($orderId, '订单创建成功');
    }

    public function confirm()
    {
        // 空操作
    }

    public function cancel()
    {
        // 删除订单消息
        $service = new OrderService();
        $service->deleteMessage($this->msgId);
    }
}
