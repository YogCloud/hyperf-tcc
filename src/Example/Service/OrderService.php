<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Example\Service;

use Hyperf\Database\Query\Expression;
use Hyperf\DbConnection\Db;
use YogCloud\TccTransaction\Util\Di;

/*
 * 订单服务
 *
 * - [method] 创建订单
 * - [method] 删除订单
 *
 * - [method] 创建订单消息
 * - [method] 删除订单消息
 *
 * - [method] 增加订单统计
 * - [method] 减少订单统计
 */
class OrderService
{
    # 创建订单
    public function createOrder(array $goods, array $coupon = null): array
    {
        $order = null;
        Db::transaction(function () use (&$order, $goods, $coupon) {
            $order = [
                'order_sn' => Di::idGenerator()->generate(),
                'body' => '购买' . $goods['name'],
                'total_fee' => $goods['price'],
                'goods_id' => $goods['id'],
            ];
            if ($coupon) {
                $order['coupon_id'] = $coupon['id'];
                $order['sub_fee'] = $coupon['fee'];
                $order['pay_fee'] = $goods['price'] - $coupon['fee'];
            }
            $order['id'] = $orderId = (int) Db::table('tcc_order')->insertGetId($order);
        });
        return (array) $order;
    }

    # 删除订单
    public function deleteOrder(int $orderId)
    {
        Db::transaction(function () use ($orderId) {
            Db::table('tcc_order')
                ->where('id', $orderId)
                ->delete();
        });
    }

    # 创建订单消息
    public function createMessage(int $orderId, string $message): int
    {
        $id = null;
        Db::transaction(function () use (&$id, $orderId) {
            $id = (int) Db::table('tcc_order_message')
                ->insertGetId([
                    'order_id' => $orderId,
                    'message' => '订单创建成功, 通知管理员',
                ]);
        });
        return $id;
    }

    # 删除订单消息
    public function deleteMessage(int $msgId)
    {
        Db::transaction(function () use ($msgId) {
            Db::table('tcc_order_message')
                ->where('id', $msgId)
                ->delete();
        });
    }

    # 增加订单统计
    public function incOrderStatistics()
    {
        Db::transaction(function () {
            Db::table('tcc_order_statistics')
                ->where('id', 1)
                ->update(['order_num' => new Expression('order_num + 1')]);
        });
    }

    # 减少订单统计
    public function decOrderStatistics()
    {
        Db::transaction(function () {
            Db::table('tcc_order_statistics')
                ->where('id', 1)
                ->update(['order_num' => new Expression('order_num - 1')]);
        });
    }
}
