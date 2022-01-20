# hyperf-tcc

> 基于 Hyperf 框架的分布式事务 TCC 组件
    
 - 因为有些 Composer 组件依赖的版本可能过高无法低版本兼容, 欢迎PR帮忙解决
 - 已经经过 AB -N 1000 -N 100 压测测试, 无失败事务, 全可回滚, 数据无异常
 - 实现思路参考 [loyaltylu/tcc-transaction](https://github.com/luzzhong/tcc-transaction) 该作者写的
 - 为啥要重写, 因为他使用了太多 `AOP` 并且代码 `可读性较差` 用的不太顺就自己搞了一个
 - 欢迎 `测试` 和 `PR`
    
## Install
> 在使用 tcc 前先确保已经安装 Redis, Nsq
```
composer require yogcloud/hyperf-tcc
```
发布资源
```
php bin/hyperf.php vendor:publish yogcloud/hyperf-tcc
```
执行迁移
```
php bin/hyperf.php migrate
```

# Run

```php
use YogCloud\TccTransaction\Example\Test;

   /**
    * @GetMapping(path="nsq")
    */
    public function nsq(RequestInterface $req, ResponseInterface $res)
    {
        $goodsId = $req->input('goods_id', 1);
        $couponId = $req->input('coupon_id', 0);
        $result = (new Test())->handle($goodsId,$couponId);
        return $res->json([
           'code' => 0,
           'data' => $result,
        ]);
    }
```

# Test
> [Benchmark](./Benchmark.md)
```bash
curl http://localhost:9501/index/nsq?goods_id=1&coupon_id=0

{"code":0,"data":{"order":{"order_sn":255041892524236800,"body":"购买桃子","total_fee":"200.00","goods_id":1,"id":2483},"goods":{"id":1,"price":"200.00","name":"桃子","num":9994,"lock":0,"sale":6},"coupon":null}}
```

# 注意事项

- 分布式事务, 本身是为了确保数据一致性, 在高并发测试下, 应该对接口服务做好限流(使用`RateLimit`组件)


## Nsq配置教程

> [参考文档](https://nsq.io/overview/quick_start.html)

```bash
1. nsqlookupd
2. nsqd --lookupd-tcp-address=127.0.0.1:4160

config/autoload/nsq.php 使用默认配置端口即可
```

## 功能列表

 - [X] 实现TCC操作实现接口 `TccOption`
 - [X] 实现事务处理 `Tcc`
 - [X] 实现事务编排 `Tcc->rely(...)`
 - [X] 实现事务重试 `NSQ消息订阅`
 - [X] 实现无法回滚事务记录并通知 `Database`

## Composer依赖

 - `"hyperf/nsq": "^2.1"`
 - `"hyperf/redis": "~2.1.0"`
 - `"hyperf/database": "~2.1.0"`
 - `"hyperf/snowflake": "^2.1"`
 - `"hyperf/logger": "~2.1.0"`
 - `"hyperf/db-connection": "~2.1.0"`

## 使用方式

 - `Tcc` 事务创建
    - 对于事务进行流程编排
    - 对于事务的启动操作
 - `TccOption` 事务操作
    - 需要针对性实现 `try, confirm, cancel` 方法
    - 例如 `商品库存锁定`,  `商品库存扣除` 等
    - 在 `YogCloud\TccTransaction\Example\Tcc` 下能看到很多演示写法
    - 不应当把 `复杂的参数` 放到该类中去例如对象等, 因为它会作为一个 `序列化的类` 存放到 `redis` 中
    - 如果操作类 `参数过多`, 或者 `属性` 中 `对象过多` 会造成 `存储负担`
    - 不推荐在 `TccOption` 操作类中写业务逻辑, 它应当作为一个调用服务的封装
 - `TccState` 事务状态
    - 存放操作和状态并且序列化到缓存中
 - `Coordinator/TccCoordinator` 事务协调者 `NSQ消费者` 需要加入到消费进程
    - 请先继承该类实现 `NSQ消费者进程绑定 @Consumer 注解`
    - 具体查看 [Hyperf NSQ文档](https://hyperf.wiki/2.0/#/zh-cn/nsq)

### 代码演示

 - 下面的代码演示都在 `Example` 中有实现
 - `Example\Tcc\*` 事务操作项
 - `Example\Service\*` 微服务实现类
 - `Example\Test` 模拟下单接口
 - `Example\database.sql` 演示案例数据库脚本, 测试前先导入

```php
use YogCloud\TccTransaction\Tcc;
use YogCloud\TccTransaction\Example\Tcc\GoodsLockTcc;
use YogCloud\TccTransaction\Example\Tcc\CouponLockTcc;
use YogCloud\TccTransaction\Example\Tcc\OrderTcc;
use YogCloud\TccTransaction\Example\Tcc\GoodsSubTcc;
use YogCloud\TccTransaction\Example\Tcc\CouponSubTcc;
use YogCloud\TccTransaction\Example\Tcc\OrderMessageTcc;
use YogCloud\TccTransaction\Example\Tcc\OrderStatisticsTcc;

$goodsId = 1;
$couponId = 0;
$tcc = new Tcc;
$tcc
    ->tcc(1, new GoodsLockTcc($goodsId)) // 商品库存锁定
    ->tcc(2, new CouponLockTcc($couponId)) // 优惠券锁定
    ->tcc(3, new OrderTcc) // 创建订单
    ->tcc(4, new GoodsSubTcc) // 扣减库存
    ->tcc(5, new CouponSubTcc) // 占用优惠券
    ->tcc(6, new OrderMessageTcc) // 创建订单消息
    ->tcc(7, new OrderStatisticsTcc) // 订单统计
    ->rely([          // 配置执行流程
        // 外层步骤是逐步执行的
        // [...] 内层步骤是同步执行的
        [1, 2],       // 1,2 锁定库存, 锁定优惠券
        [3],          // 3 创建订单
        [4, 5, 6, 7], // 4,5,6,7 扣减库存, 占用优惠券, 订单消息, 订单统计
    ])->begin(); // 开启事务
```

## 实现原理

 - `TccOption` 都必须实现 `try, confirm, cancel` 方法
 - 其中 `confirm` 允许空操作
 
 ![](https://h6play.oss-cn-shenzhen.aliyuncs.com/process.png)

## 联系方式
 
 - 请通过微信联系作者，并备注 `PHP` 方便辨认
 - 请通过扫码，或者添加微信 `h6play`
 
 
 ![](https://h6play.oss-cn-shenzhen.aliyuncs.com/wx.png)
