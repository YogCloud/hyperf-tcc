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
use YogCloud\TccTransaction\Exception\ServiceException;

/*
 * 商品服务
 *
 * - [method] 获取商品详情
 *
 * - [method] 锁定库存
 * - [method] 解锁库存
 *
 * - [method] 扣减库存
 * - [method] 增加库存
 */
class GoodsService
{
    // 获取商品详情
    public function getGoods(int $goodsId): array
    {
        $result = Db::table('tcc_goods')
            ->where('id', $goodsId)
            ->first();
        if (! $result) {
            throw new ServiceException('购买商品不存在');
        }

        return (array) $result;
    }

    // 锁定库存
    public function lockStock(int $goodsId)
    {
        Db::transaction(function () use ($goodsId) {
            $lockResult = Db::table('tcc_goods')
                ->where('id', $goodsId)
                ->whereRaw('(`num` - `lock`) > 0')
                ->update([
                    'lock' => new Expression('`lock` + 1'),
                ]);
            if (! $lockResult) {
                throw new ServiceException('暂无商品库存');
            }
        });
    }

    // 解锁库存
    public function releaseStock(int $goodsId)
    {
        Db::transaction(function () use ($goodsId) {
            $lockResult = Db::table('tcc_goods')
                ->where('id', $goodsId)
                ->whereRaw('`lock` > 0')
                ->update([
                    'lock' => new Expression('`lock` - 1'),
                ]);
            if (! $lockResult) {
                throw new ServiceException('商品恢复库存失败');
            }
        });
    }

    // 扣减库存
    public function subStock(int $goodsId)
    {
        Db::transaction(function () use ($goodsId) {
            $result = Db::table('tcc_goods')
                ->where('id', $goodsId)
                ->whereRaw('`num` > 0')
                ->whereRaw('`lock` > 0')
                ->update([
                    'num' => new Expression('`num` - 1'),
                    'lock' => new Expression('`lock` - 1'),
                    'sale' => new Expression('`sale` + 1'),
                ]);
            if (! $result) {
                throw new ServiceException('商品库存扣除失败');
            }
        });
    }

    // 增加库存
    public function addStock(int $goodsId)
    {
        Db::transaction(function () use ($goodsId) {
            $result = Db::table('tcc_goods')
                ->where('id', $goodsId)
                ->update([
                    'num' => new Expression('`num` + 1'),
                    'lock' => new Expression('`lock` + 1'),
                    'sale' => new Expression('`sale` - 1'),
                ]);
            if (! $result) {
                throw new ServiceException('商品库存恢复失败');
            }
        });
    }
}
