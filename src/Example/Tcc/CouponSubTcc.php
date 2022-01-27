<?php

declare(strict_types=1);
/**
 * This is a TCC distributed transaction component.
 * @link     https://github.com/YogCloud/hyperf-tcc
 * @document https://github.com/YogCloud/hyperf-tcc/blob/main/README.md
 * @license  https://github.com/YogCloud/hyperf-tcc/blob/main/LICENSE
 */
namespace YogCloud\TccTransaction\Example\Tcc;

use YogCloud\TccTransaction\Example\Service\CouponService;
use YogCloud\TccTransaction\TccOption;

class CouponSubTcc extends TccOption
{
    protected $couponId;

    public function try()
    {
        // 获取优惠券ID, 依赖 CouponLockTcc::class 操作返回
        $this->couponId = (int) ($this->tcc->get(CouponLockTcc::class, [])['id'] ?? 0);
        if ($this->couponId) {
            // 占用优惠券
            $service = new CouponService();
            $service->useCoupon($this->couponId);
        }
    }

    public function confirm()
    {
        // 空操作
    }

    public function cancel()
    {
        if ($this->couponId) {
            // 解除优惠券
            $service = new CouponService();
            $service->unUseCoupon($this->couponId);
        }
    }
}
