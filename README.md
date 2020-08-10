# crontab
定时任务组件

### 安装

```
composer require 7csn/crontab
```

### 使用示例
```php
<?php

use chaser\container\Container;
use chaser\crontab\Crontab;
use chaser\crontab\FormatException;
use PDO;

// composer 自加载
require '../vendor/autoload.php';

try {
    // 创建定时任务
    $crontab = new Crontab(
        // 行程表：分 时 日 月 周 => 可调用结构（或者对象方法：类全限定名@函数名）
        [
            '*/7 * * * *' => 'app\http\controller\Index@index',     # 对象方法
            '* */17 * * *' => 'app\http\controller\Index::test',    # 静态方法
            '2 * * 1,20-31/3 *' => 'rand',                          # rand() 函数
            '* 1-4,9,15-22/5 * * 6' => function (PDO $pdo) {
                var_dump($pdo);
            },
        ],
        // 轮询周期（秒）
        60
    );

    // 定时任务轮询（传入 IoC 容器）
    $crontab->loop(Container::getInstance());
} catch (FormatException $e) {
    exit($e->getMessage());
}
```
