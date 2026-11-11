# Phalcon4To5Rector

基于 [Rector](https://getrector.com/) 的 Phalcon Framework 4 → 5 自动升级规则集。

Rector 官方目前没有 Phalcon 规则包，本项目按照 `rectorphp/rector-symfony` 的扩展包结构，实现 Phalcon 5 [官方升级指南](https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md) 中的破坏性变更，让应用代码升级尽可能自动化。

## 功能

- 一键重命名迁移到新命名空间的类（`Phalcon\Loader` → `Phalcon\Autoload\Loader` 等）
- 方法重命名（`Loader::registerDirs()` → `setDirectories()`、`Asset::getLocal()` → `isLocal()` 等）
- 构造函数注入修复（`Cache\AdapterFactory` 自动注入 `Storage\SerializerFactory`）

## 安装

```bash
composer require --dev phalcon4to5/rector
```

## 使用

### 方式一：自动加载（推荐）

本包已通过 `extra.rector.includes` 注册规则集。在项目中安装 [`rector/extension-installer`](https://github.com/rectorphp/extension-installer) 插件后，`composer install` 时会自动发现并加载 `phalcon50` 规则集，无需任何手动配置：

```bash
composer require --dev rector/extension-installer
```

> 原理：插件按 `type: rector-extension` 发现本包，将 `extra.rector.includes` 聚合写入生成的 `GeneratedConfig`；Rector 运行时通过 `Bootstrap\ExtensionConfigResolver` 读取并加载这些配置文件。

### 方式二：手动加载

不想用插件的话，在项目根目录 `rector.php` 中手动引用规则集路径：

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        __DIR__ . '/vendor/phalcon4to5/rector/config/sets/phalcon50.php',
    ]);
};
```

### 运行

先预览变更，确认后再应用：

```bash
vendor/bin/rector process src --dry-run   # 预览 diff
vendor/bin/rector process src             # 应用变更
```

## 支持的规则

### 类与命名空间重命名（RenameClassRector）

| Phalcon 4 | Phalcon 5 |
|---|---|
| `Phalcon\Loader` | `Phalcon\Autoload\Loader` |
| `Phalcon\Cache` | `Phalcon\Cache\Cache` |
| `Phalcon\Collection` | `Phalcon\Support\Collection` |
| `Phalcon\Config` | `Phalcon\Config\Config` |
| `Phalcon\Container` | `Phalcon\Container\Container` |
| `Phalcon\Crypt` | `Phalcon\Encryption\Crypt` |
| `Phalcon\Debug` | `Phalcon\Support\Debug` |
| `Phalcon\Di` | `Phalcon\Di\Di` |
| `Phalcon\Escaper` | `Phalcon\Html\Escaper` |
| `Phalcon\Filter` | `Phalcon\Filter\Filter` |
| `Phalcon\Logger` | `Phalcon\Logger\Logger` |
| `Phalcon\Registry` | `Phalcon\Support\Registry` |
| `Phalcon\Security` | `Phalcon\Encryption\Security` |
| `Phalcon\Url` | `Phalcon\Mvc\Url` |
| `Phalcon\Validation` | `Phalcon\Filter\Validation` |
| `Phalcon\Version` | `Phalcon\Support\Version` |
| `Phalcon\Acl\ComponentAware` | `Phalcon\Acl\ComponentAwareInterface` |
| `Phalcon\Acl\RoleAware` | `Phalcon\Acl\RoleAwareInterface` |

### 方法重命名（RenameMethodRector）

| 类 | 旧方法 | 新方法 |
|---|---|---|
| `Phalcon\Autoload\Loader` | `registerDirs()` | `setDirectories()` |
| `Phalcon\Autoload\Loader` | `registerNamespaces()` | `setNamespaces()` |
| `Phalcon\Autoload\Loader` | `registerFiles()` | `setFiles()` |
| `Phalcon\Autoload\Loader` | `registerClasses()` | `setClasses()` |
| `Phalcon\Autoload\Loader` | `registerExtensions()` | `setExtensions()` |
| `Phalcon\Autoload\Loader` | `getDirs()` | `getDirectories()` |
| `Phalcon\Assets\Asset` | `getLocal()` | `isLocal()` |
| `Phalcon\Assets\Asset` | `setLocal()` | `setIsLocal()` |
| `Phalcon\Assets\Collection` | `getLocal()` | `isLocal()` |
| `Phalcon\Assets\Collection` | `setLocal()` | `setIsLocal()` |
| `Phalcon\Assets\Collection` | `getTargetLocal()` | `getTargetIsLocal()` |
| `Phalcon\Assets\Collection` | `setTargetLocal()` | `setTargetIsLocal()` |

### 自定义规则

- `CacheAdapterFactoryConstructorInjectionRector` — 补齐 `Phalcon\Cache\AdapterFactory` 构造函数中 v5 必传的 `Phalcon\Storage\SerializerFactory` 参数（v4 中可选，默认 `null`）：

```php
// v4（两种写法在 v5 都会报 TypeError）
$factory = new AdapterFactory();
$factory = new AdapterFactory(null);

// 迁移后
$factory = new AdapterFactory(new SerializerFactory());
```

- `AssetsManagerTagFactoryInjectionRector` — 为 `Phalcon\Assets\Manager` 构造函数注入 v5 必传的 `Phalcon\Html\TagFactory`（含其必需的 `Escaper` 依赖链），v4 的 `options` 数组自动后移：

```php
// v4
$assets = new Manager();
$assets = new Manager(['sourceBasePath' => '/src']);

// 迁移后
$assets = new Manager(new TagFactory(new Escaper()));
$assets = new Manager(new TagFactory(new Escaper()), ['sourceBasePath' => '/src']);
```

- `TextAndHelperToSupportHelperRector` — 将已移除的 `Phalcon\Text` / `Phalcon\Helper\*` 静态调用迁移为 `Phalcon\Support\Helper\*` 实例调用（v5 Helper 不再是静态类，改为 `__invoke`），共映射 51 个方法 + 6 个 `RANDOM_*` 常量：

```php
// v4
$camel = Text::camelize('foo_bar');
$endsWith = Helper\Str::endsWith('hello world', 'world');
$first = Helper\Arr::first($items);
$type = Text::RANDOM_ALPHA;

// 迁移后
$camel = (new \Phalcon\Support\Helper\Str\Camelize())->__invoke('foo_bar');
$endsWith = (new \Phalcon\Support\Helper\Str\EndsWith())->__invoke('hello world', 'world');
$first = (new \Phalcon\Support\Helper\Arr\First())->__invoke($items);
$type = \Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA;
```

> 注：v4 `Helper\Number::between()` → v5 `Number\IsBetween`（方法名大小写不敏感匹配）；`Phalcon\Helper\File` 在 v4 中不存在，不做迁移。未映射的调用（如动态方法名 `$class::$method()`）原样跳过。

- `TagToTagFactoryRector` — 将已移除的 `Phalcon\Tag` 的**确定性子集**迁移为 `$tagFactory->...` 调用（`$tagFactory` 为作用域内的 `Phalcon\Html\TagFactory` 变量，是本包的显式约定）：

```php
// v4
$name = Tag::textField(['name', 'value' => 'x', 'class' => 'c']);
$slug = Tag::friendlyText('Some Title', '-');
$doctype = Tag::HTML5;

// 迁移后
$name = $tagFactory->inputText('name', 'x', ['class' => 'c']);
$slug = $tagFactory->friendlyTitle('Some Title', '-');
$doctype = \Phalcon\Html\Helper\Doctype::HTML5;
```

覆盖：22 个输入字段方法（`textField`/`emailField`/`checkField`/`submitButton` 等，字符串形态与字面量数组形态自动拆解为 `(name, value, attributes)`）、`friendlyText()` → `friendlyTitle()`（签名一致）、11 个 `HTML*/XHTML*` doctype 常量 → `Doctype::` 常量。

**明确不迁移**（参数语义非确定或依赖 DI/全局状态，机械替换会产出错代码，需手动改写）：
- `linkTo()`、`image()`、`stylesheetLink()`、`javascriptInclude()`、`select()`/`selectStatic()`、`form()`/`endForm()`、`tagHtml()`/`tagHtmlClose()`、`renderAttributes()`
- 状态方法：`setTitle()`/`getTitle()` 系列、`setDefault()`/`getValue()` 系列、`setDocType()`/`getDocType()`
- DI 访问器：`setDI()`/`getDI()`/`getEscaper()`/`getUrlService()`

## 已知限制

- **Rector 2.x 不会自动更新 `use` 导入语句**：类重命名采用内联全限定名替换（`new Loader()` → `new \Phalcon\Autoload\Loader()`），旧的 `use Phalcon\Loader;` 会保留在文件顶部。功能上没问题（编译正确），但建议重跑后手动清理无用 import。
- **已移除的类不处理**：`Phalcon\Tag`、`Phalcon\Text`、`Phalcon\Helper`、`Phalcon\Exception`、`Phalcon\Kernel` 在 v5 已删除，需要自定义规则迁移到 `Phalcon\Html\Helper\*` / `Phalcon\Support\Helper\*`（尚未实现，欢迎 PR）。
- **反射依赖**：方法重命名规则依赖 `phalcon/ide-stubs` 提供的类定义来解析类型，无法解析的类型会被跳过。

## 开发

### 环境要求

- PHP 8.1+
- Composer

### 目录结构

```
├── config/sets/
│   └── phalcon50.php                          # 规则集入口（RenameClass / RenameMethod 配置 + 自定义规则）
├── rules/
│   └── Phalcon5/Rector/New_/
│       └── CacheAdapterFactoryConstructorInjectionRector.php
├── rules-tests/
│   ├── Phalcon5/Phalcon50Set/                 # 规则集整体测试
│   └── Phalcon5/Rector/New_/.../              # 单条规则测试
└── php-config/php.ini                         # 本机 PHP 8.2 CLI 开发配置
```

### 测试约定（Rector 社区惯例）

- 每条规则先写失败的 fixture 测试，再实现规则
- fixture 是 `*.php.inc` 文件，`-----` 分隔符上方为输入代码（升级前），下方为期望输出（升级后）
- 只有上半段、没有 `-----` 的 fixture 表示"规则必须原样跳过"（命名惯例 `skip_xxx.php.inc`）

运行测试：

```bash
vendor/bin/phpunit --colors=never
```

### 本地开发环境（phpstudy 用户）

phpstudy 生成的 `php.ini` 使用 PHP7 语法（`track_errors`），在 PHP 8 下会致命错误。本仓库提供干净的 CLI 配置 `php-config/php.ini`，所有命令都应带 `-c` 参数：

```powershell
Set-Location "d:\opt\htdocs\Phalcon4To5Rector"

# 运行测试
& "D:\phpstudy_pro\Extensions\php\php8.2.9nts\php.exe" -c "d:\opt\htdocs\Phalcon4To5Rector\php-config" "vendor\phpunit\phpunit\phpunit" --colors=never

# 运行 rector CLI（必须设置 PHPRC，否则并行子进程会读取 PHP7 的全局 ini 报错）
$env:PHPRC = "d:\opt\htdocs\Phalcon4To5Rector\php-config"
& "D:\phpstudy_pro\Extensions\php\php8.2.9nts\php.exe" -c "d:\opt\htdocs\Phalcon4To5Rector\php-config" "vendor\rector\rector\bin\rector" process "tmp-debug\sample.php" --config "config\sets\phalcon50.php" --no-progress-bar
```

## Roadmap

- [x] `Phalcon\Tag` 确定性子集 → `Phalcon\Html\TagFactory` 迁移规则 ✅（`linkTo`/`image`/`select` 等复杂方法仍需手动改写）
- [x] `Phalcon\Assets\Manager` 构造器注入 `Phalcon\Html\TagFactory` ✅
- [x] `Phalcon\Text` / `Phalcon\Helper` → `Phalcon\Support\Helper\*` 静态调用迁移 ✅
- [ ] 对 v5 中已移除且无自动迁移方案的类（`Phalcon\Kernel` 等）输出提示
- [x] 接入 `rector/extension-installer` 实现规则集自动注册 ✅
- [ ] 发布到 Packagist，并尝试收录进 `rectorphp` 官方组织

## 许可

MIT
