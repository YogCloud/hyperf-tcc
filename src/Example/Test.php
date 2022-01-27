<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Example;

use YogCloud\TccTransaction\Example\Tcc\CouponLockTcc;
use YogCloud\TccTransaction\Example\Tcc\CouponSubTcc;
use YogCloud\TccTransaction\Example\Tcc\GoodsLockTcc;
use YogCloud\TccTransaction\Example\Tcc\GoodsSubTcc;
use YogCloud\TccTransaction\Example\Tcc\OrderMessageTcc;
use YogCloud\TccTransaction\Example\Tcc\OrderStatisticsTcc;
use YogCloud\TccTransaction\Example\Tcc\OrderTcc;
use YogCloud\TccTransaction\Tcc;

/**
 * @internal
 * @coversNothing
 */
class Test
{
    /**
     * 模拟创建订单接口.
     *
     * @param $goodsId
     * @param $couponId
     *
     * @return array
     */
    public function handle($goodsId, $couponId)
    {
        $tcc = new Tcc();
        $tcc
            ->tcc(1, new GoodsLockTcc($goodsId)) // 商品库存锁定
            ->tcc(2, new CouponLockTcc($couponId)) // 优惠券锁定
            ->tcc(3, new OrderTcc()) // 创建订单
            ->tcc(4, new GoodsSubTcc()) // 扣减库存
            ->tcc(5, new CouponSubTcc()) // 占用优惠券
            ->tcc(6, new OrderMessageTcc()) // 创建订单消息
            ->tcc(7, new OrderStatisticsTcc()) // 订单统计
            ->rely([          // 配置执行流程
                [1, 2],       // 1,2 锁定库存, 锁定优惠券
                [3],          // 3 创建订单
                [4, 5, 6, 7], // 4,5,6,7 扣减库存, 占用优惠券, 订单消息, 订单统计
            ])->begin(); // 开启事务

        // 返回参数
        return [
            'order' => $tcc->get(OrderTcc::class),
            'goods' => $tcc->get(GoodsLockTcc::class),
            'coupon' => $tcc->get(CouponLockTcc::class),
        ];
    }
}
