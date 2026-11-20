# rector-phalcon

基于 [Rector](https://getrector.com/) 的 Phalcon 框架自动升级工具，目标是以独立规则集支持 Phalcon 各主版本升级。

当前已实现 Phalcon 3 → 4（基础规则）和 Phalcon 4 → 5；Phalcon 3 → 5 直接升级已规划，详见 [`plan/`](plan/)。

## 总体能力

- 类与命名空间重命名
- 方法重命名
- 已移除类 TODO 提示
- 构造函数依赖注入修复
- `use` 导入与引用风格保持
- 按项目环境自动选择规则集

详细规则、限制和 Roadmap 见 [`plan/PHALCON3_TO_4_PLAN.md`](plan/PHALCON3_TO_4_PLAN.md) 与 [`plan/PHALCON4_TO_5_PLAN.md`](plan/PHALCON4_TO_5_PLAN.md)。

## 运行环境建议

- PHP ^8.1
- Composer 2.x
- rector/rector:^2.8

本项目开发时的环境：

- PHP 8.4.27
- Composer 2.11.2
- Rector 2.8.5

## 安装

```bash
composer require --dev rector/rector:^2.8
composer require --dev xuwenxiong/rector-phalcon
```

建议在执行每个升级阶段前，将运行 Rector 的 Composer 环境中的 `phalcon/ide-stubs` 切换为该阶段的目标版本：

| 阶段 | 建议的 stubs 版本 |
|---|---|
| Phalcon 3 → 4 | `phalcon/ide-stubs:^4.0` |
| Phalcon 4 → 5 | `phalcon/ide-stubs:^5.0` |

例如：

```bash
composer require --dev phalcon/ide-stubs:^4.0
```

## 使用

> [!WARNING]
> **完成 Phalcon 4 → 5 后，禁止再执行 Phalcon 3 → 4。** 阶段 set 不可交换：3 → 4 会把 v5 代码中的 `Phalcon\Mvc\Url` 改回 `Phalcon\Url`，使代码退回 Phalcon 4 中间态。若已误执行，请撤销该次 3 → 4 改动，或重新执行 4 → 5。

### 可用规则集常量

> 这些是规则集入口常量，不是单条规则的开关。

| 常量 | 作用 |
|---|---|
| `PhalconSetList::UP_PHALCON_3_TO_4` | 显式加载 Phalcon 3 → 4 基础规则集，指向 `src/Phalcon4/Config/Set/phalcon40.php` |
| `PhalconSetList::UP_PHALCON_4_TO_5` | 显式加载 Phalcon 4 → 5 规则集，指向 `src/Phalcon5/Config/Set/phalcon50.php` |
| `PhalconSetList::UP_TO_PHALCON_40` | `UP_PHALCON_3_TO_4` 的别名，采用 `UP_TO_*` 命名风格 |
| `PhalconSetList::UP_TO_PHALCON_50` | `UP_PHALCON_4_TO_5` 的别名，采用 `UP_TO_*` 命名风格 |
| `PhalconSetList::COMPOSER_BASED` | 根据项目环境自动选择单个升级阶段；目标 Phalcon 4 加载 3 → 4，目标 Phalcon 5 加载 4 → 5 |

### 方式一：SetList 常量配置

在 `rector.php` 中显式选择规则集：

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorPhalcon\Set\PhalconSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        PhalconSetList::UP_PHALCON_4_TO_5,
    ]);
```

### 方式二：Composer-based 自动选择

```php
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        PhalconSetList::COMPOSER_BASED,
    ]);
```

当前会探测 `phalcon/ide-stubs` 或 `phalcon` 扩展版本。探测到目标 Phalcon 4 时加载 3 → 4 规则集；目标 Phalcon 5 时加载 4 → 5 规则集；其他情况不加载。

两种方式不要同时叠加使用，否则可能出现意外结果。设计细节见 [`plan/COMPOSER_BASED_PLAN.md`](plan/COMPOSER_BASED_PLAN.md)。

## 运行

先预览，再应用：

预览
```bash
vendor/bin/rector process src --dry-run
```
应用
```bash
vendor/bin/rector process src
```

其中 `src` 是示例路径，请替换为实际待升级的代码目录；也可以传入多个路径，例如 `vendor/bin/rector process src tests --dry-run`。若已在 `rector.php` 中通过 `withPaths()` 配置路径，命令行路径会覆盖该配置。

更多rector命令用法见 [Rector 官方文档](https://docs.getrector.org/)。

建议在新分支上执行 Rector，并通过 Git diff 审查变更。

## 文档

- [`plan/DIRECTORY_STRUCTURE_PLAN.md`](plan/DIRECTORY_STRUCTURE_PLAN.md)：目录结构与命名空间
- [`plan/PHALCON4_TO_5_PLAN.md`](plan/PHALCON4_TO_5_PLAN.md)：4 → 5 规则与 Roadmap
- [`plan/PHALCON3_TO_4_PLAN.md`](plan/PHALCON3_TO_4_PLAN.md)：3 → 4 规则与 Roadmap
- [`plan/PHALCON3_TO_5_PLAN.md`](plan/PHALCON3_TO_5_PLAN.md)：3 → 5 直接升级规划
- [`plan/COMPOSER_BASED_PLAN.md`](plan/COMPOSER_BASED_PLAN.md)：自动选择规则集
- [`plan/EXTENSION_INSTALLER_PLAN.md`](plan/EXTENSION_INSTALLER_PLAN.md)：为什么不使用自动加载

## 许可

MIT
