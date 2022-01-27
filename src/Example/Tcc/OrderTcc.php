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

class OrderTcc extends TccOption
{
    protected $orderId;

    public function try()
    {
        // 获取依赖参数 优惠券, 商品
        $goods = $this->tcc->get(GoodsLockTcc::class);
        $coupon = $this->tcc->get(CouponLockTcc::class);

        // 创建订单
        $service = new OrderService();
        $order = $service->createOrder($goods, $coupon);
        $this->orderId = (int) $order['id'];

        // 返回订单
        return $order;
    }

    public function confirm()
    {
        // 空提交
    }

    public function cancel()
    {
        // 删除订单
        $service = new OrderService();
        $service->deleteOrder($this->orderId);
    }
}
