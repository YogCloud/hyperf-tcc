<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Example\Service;

use Hyperf\DbConnection\Db;
use YogCloud\TccTransaction\Exception\ServiceException;

/*
 * 优惠券服务
 *
 * - [method] 获取优惠券
 *
 * - [method] 锁定优惠券
 * - [method] 解锁优惠券
 *
 * - [method] 使用优惠券
 * - [method] 恢复优惠券
 */
class CouponService
{
    // 获取优惠券
    public function getCoupon(int $couponId): array
    {
        $result = Db::table('tcc_coupon')
            ->where('id', $couponId)
            ->first();
        if (! $result) {
            throw new ServiceException('优惠券不存在');
        }

        return (array) $result;
    }

    // 锁定优惠券
    public function lockCoupon(int $couponId)
    {
        Db::transaction(function () use ($couponId) {
            $lockResult = Db::table('tcc_coupon')
                ->where('id', $couponId)
                ->where('status', 0)
                ->where('lock', 0)
                ->update([
                    'lock' => 1,
                ]);
            if (! $lockResult) {
                throw new ServiceException('无法使用该优惠券');
            }
        });
    }

    // 解锁优惠券
    public function releaseCoupon(int $couponId)
    {
        Db::transaction(function () use ($couponId) {
            $lockResult = Db::table('tcc_coupon')
                ->where('id', $couponId)
                ->where('status', 0)
                ->where('lock', 1)
                ->update([
                    'lock' => 0,
                ]);
            if (! $lockResult) {
                throw new ServiceException('无法解锁该优惠券');
            }
        });
    }

    // 使用优惠券
    public function useCoupon(int $couponId)
    {
        Db::transaction(function () use ($couponId) {
            $result = Db::table('tcc_coupon')
                ->where('id', $couponId)
                ->where('status', 0)
                ->where('lock', 1)
                ->update([
                    'status' => 1,
                    'lock' => 0,
                ]);
            if (! $result) {
                throw new ServiceException('使用优惠券失败');
            }
        });
    }

    // 解除使用优惠券
    public function unUseCoupon(int $couponId)
    {
        Db::transaction(function () use ($couponId) {
            $result = Db::table('tcc_coupon')
                ->where('id', $couponId)
                ->where('status', 1)
                ->where('lock', 0)
                ->update([
                    'status' => 0,
                    'lock' => 1,
                ]);
            if (! $result) {
                throw new ServiceException('解锁优惠券失败');
            }
        });
    }
}
