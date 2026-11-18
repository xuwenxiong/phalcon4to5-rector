# rector-phalcon

基于 [Rector](https://getrector.com/) 的 Phalcon 框架自动升级工具，目标是以独立规则集支持 Phalcon 各主版本升级。

当前已实现 Phalcon 4 → 5；Phalcon 3 → 4、3 → 5 已规划，详见 [`plan/`](plan/)。

## 总体能力

- 类与命名空间重命名
- 方法重命名
- 已移除类 TODO 提示
- 构造函数依赖注入修复
- `use` 导入与引用风格保持
- 按项目环境自动选择规则集

详细规则、限制和 Roadmap 见 [`plan/PHALCON4_TO_5_PLAN.md`](plan/PHALCON4_TO_5_PLAN.md)。

## 运行环境

- PHP ^8.1
- Composer
- 项目需安装 `rector/rector:^2.1`（当前规则集基于 Rector 2.1 API 开发与测试）

## 安装

```bash
composer require --dev rector/rector:^2.1
composer require --dev xuwenxiong/rector-phalcon
```

## 使用

以下三种方式任选其一，不要同时叠加使用，否则可能出现意外结果。

### 方式一：自动加载（推荐）

安装 Rector 扩展自动加载器后，当前规则集会自动注册：

```bash
composer require --dev rector/extension-installer
```

### 方式二：SetList 常量配置

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

### 方式三：Composer-based 自动选择

```php
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        PhalconSetList::COMPOSER_BASED,
    ]);
```

当前会探测 `phalcon/ide-stubs` 或 `phalcon` 扩展版本。探测到目标 Phalcon 5 时加载 4 → 5 规则集；探测不到或暂无对应规则集时不加载。

设计细节见 [`plan/COMPOSER_BASED_PLAN.md`](plan/COMPOSER_BASED_PLAN.md)。

## 运行

先预览，再应用：

```bash
# 预览
vendor/bin/rector process src --dry-run

# 应用
vendor/bin/rector process src
```

其中 `src` 是示例路径，请替换为实际待升级的代码目录；也可以传入多个路径，例如 `vendor/bin/rector process src tests --dry-run`。若已在 `rector.php` 中通过 `withPaths()` 配置路径，命令行路径会覆盖该配置。

更多rector命令用法见 [Rector 官方文档](https://docs.getrector.org/)。

建议在新分支上执行 Rector，并通过 Git diff 审查变更。

## 文档

- [`plan/DIRECTORY_STRUCTURE_PLAN.md`](plan/DIRECTORY_STRUCTURE_PLAN.md)：目录结构与命名空间
- [`plan/PHALCON4_TO_5_PLAN.md`](plan/PHALCON4_TO_5_PLAN.md)：4 → 5 规则与 Roadmap
- [`plan/PHALCON3_TO_4_PLAN.md`](plan/PHALCON3_TO_4_PLAN.md)：3 → 4 规划
- [`plan/PHALCON3_TO_5_PLAN.md`](plan/PHALCON3_TO_5_PLAN.md)：3 → 5 直接升级规划
- [`plan/COMPOSER_BASED_PLAN.md`](plan/COMPOSER_BASED_PLAN.md)：自动选择规则集
- [`plan/EXTENSION_INSTALLER_PLAN.md`](plan/EXTENSION_INSTALLER_PLAN.md)：自动加载原理

## 许可

MIT
