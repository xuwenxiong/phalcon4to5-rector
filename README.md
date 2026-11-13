# Phalcon4To5Rector

基于 [Rector](https://getrector.com/) 的 Phalcon Framework 4 → 5 自动升级规则集。

**作者**：[xuwenxiong](https://github.com/xuwenxiong)

Rector 官方目前没有 Phalcon 规则包，本项目按照 `rectorphp/rector-symfony` 的扩展包结构，实现 Phalcon 5 [官方升级指南](https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md) 中的破坏性变更，让应用代码升级尽可能自动化。

> ⚠️ **开发测试阶段**：本项目目前处于开发测试阶段，规则覆盖度和行为仍在迭代验证中，**暂不能用于生产**。使用前请务必先 `--dry-run` 预览全部变更并人工审查，再应用到代码。

## 功能

- 一键重命名迁移到新命名空间的类（`Phalcon\Loader` → `Phalcon\Autoload\Loader` 等）
- 方法重命名（`Loader::registerDirs()` → `setDirectories()`、`Asset::getLocal()` → `isLocal()` 等）
- 构造函数注入修复（`Cache\AdapterFactory` 自动注入 `Storage\SerializerFactory`）

## 安装

```bash
composer require --dev xuwenxiong/phalcon4to5-rector
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
        __DIR__ . '/vendor/xuwenxiong/phalcon4to5-rector/config/sets/phalcon50.php',
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
| `Phalcon\Exception` | `\Exception`（v5 已移除根级异常，[cphalcon#15800](https://github.com/phalcon/cphalcon/issues/15800)） |

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

- `TextAndHelperToSupportHelperRector` — 将已移除的 `Phalcon\Text` / `Phalcon\Helper\*` 静态调用迁移为 v5 官方推荐的 `Phalcon\Support\HelperFactory` 方法调用（工厂 `__call` 按方法名代理各 helper，方法名 = helper 类名的 lcfirst），共映射 51 个方法 + 6 个 `RANDOM_*` 常量：

```php
// v4
$camel = Text::camelize('foo_bar');
$endsWith = Helper\Str::endsWith('hello world', 'world');
$first = Helper\Arr::first($items);
$type = Text::RANDOM_ALPHA;

// 迁移后（use 导入自动补齐）
use Phalcon\Support\HelperFactory;

$camel = $helperFactory->camelize('foo_bar');
$endsWith = $helperFactory->endsWith('hello world', 'world');
$first = $helperFactory->first($items);
$type = \Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA;
```

> 注：v4 `Helper\Number::between()` → v5 工厂方法 `isBetween()`（方法名大小写不敏感匹配）；`Phalcon\Helper\File` 在 v4 中不存在，不做迁移。未映射的调用（如动态方法名 `$class::$method()`）原样跳过。`RANDOM_*` 常量无工厂代理，仍指向 `Helper\Str\Random` 类常量。

- `DeduplicateHelperFactoryRector` — 同一方法体内出现 ≥ 2 次内联 `new HelperFactory()` 时，自动提取为局部变量复用（单次调用保持内联）；方法内已存在同名变量或调用位于嵌套普通 `function` 内时整体跳过：

```php
// 提取前
$camel = (new HelperFactory())->camelize($a);
$upper = (new HelperFactory())->upper($b);

// 提取后
$helperFactory = new HelperFactory();
$camel = $helperFactory->camelize($a);
$upper = $helperFactory->upper($b);
```

> 💡 **生产环境建议改用 DI 注册服务**：`HelperFactory` 是无状态的，在 Phalcon 5 的 DI 容器中注册为单例服务并注入到需要的类，可全应用复用一个实例，避免逐方法提取变量：
>
> ```php
> $di->setShared('helperFactory', HelperFactory::class);
> // 类中通过构造器注入 / $di->get('helperFactory') 使用
> ```
>
> 规则输出是机械改写的安全下限，DI 化属于应用架构决策，由你自行决定。

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

- `RemovedClassUsageHintRector` — 对仍引用 v5 中**已移除且无自动迁移方案**类（`Phalcon\Kernel` 等）的语句 / `use` 导入添加 TODO 提示注释，让手工迁移点在 rector diff 中可见：

```php
// v4
use Phalcon\Kernel;
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);

// 迁移后（加提示，不自动改写）
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
use Phalcon\Kernel;
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement - inline the bootstrap/pre-flight checks it performed manually
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);
```

> 注：提示清单刻意保持精简——有迁移方案的类都由对应规则处理（重命名、TagFactory、Support\Helper 等），避免与其他规则产生冲突注释。`Phalcon\Exception` 属于"有迁移方案"（替换为 `\Exception`），由 `RenameClassRector` 处理，不在此列。

- `RenameUseImportsRector` — 改写类重命名后残留的 `use` 导入语句（Rector 2.x 的 `RenameClassRector` 只重命名内联引用，旧导入会留在文件顶部）。与 `RenameClassRector` 共享同一份映射表（`PhalconClassRenames::MAP`），纯配置驱动、不依赖类反射。**只处理映射表中的 Phalcon 类**，业务类、第三方库、PHP 内置类的 `use` 一律不动：

```php
// 迁移前
use Phalcon\Loader;
use Phalcon\Crypt as PhalconCrypt;
use App\Support\Helpers;

// 迁移后
use Phalcon\Autoload\Loader;
use Phalcon\Encryption\Crypt;
use App\Support\Helpers;   // 非 Phalcon 类保持原样

$crypt = new Crypt();      // 别名 PhalconCrypt 一并改写为短名
```

  别名只是新短名的「前缀变体」时会连同引用一起简化（`PException` → `Exception`、`PhalconCrypt` → `Crypt`），代码与 docblock 中的引用同步改写：

```php
// 迁移前
use Phalcon\Exception as PException;

/** @throws PException */
throw new PException('boom');

// 迁移后
use Exception;

/** @throws Exception */
throw new Exception('boom');
```

  以下情况保留别名（否则会改变语义）：别名与新短名无关（`as PEx`）；新短名已被文件内其它 `use` 导入占用（如同时有 `use App\Support\Exception;`）；文件中存在同名的 class/interface/trait/enum 声明。引用改写作用于 Rector 解析后的 FQCN（`\Phalcon\Exception`），未解析成 FQCN 的短名引用不会被误改。

**明确不迁移**（参数语义非确定或依赖 DI/全局状态，机械替换会产出错代码，需手动改写）：
- `linkTo()`、`image()`、`stylesheetLink()`、`javascriptInclude()`、`select()`/`selectStatic()`、`form()`/`endForm()`、`tagHtml()`/`tagHtmlClose()`、`renderAttributes()`
- 状态方法：`setTitle()`/`getTitle()` 系列、`setDefault()`/`getValue()` 系列、`setDocType()`/`getDocType()`
- DI 访问器：`setDI()`/`getDI()`/`getEscaper()`/`getUrlService()`

## 已知限制

- **`Phalcon\Tag` 仅迁移确定性子集**：输入字段方法、`friendlyText` 和 doctype 常量已自动迁移；`linkTo`、`image`、`select`、`form` 等参数语义非确定的方法需手动改写（见上方 `TagToTagFactoryRector` 的说明）。`Phalcon\Text` / `Phalcon\Helper` 静态调用已全量迁移，`Phalcon\Exception` 已自动重命名，`Phalcon\Kernel` 已自动添加 TODO 提示。
- **反射依赖**：方法重命名规则依赖 `phalcon/ide-stubs` 提供的类定义来解析类型，无法解析的类型会被跳过。`use` 导入重命名（`RenameUseImportsRector`）为纯配置驱动，不受此限制。
- **`use` 导入只处理 Phalcon 类**：迁移引入的 FQCN 会自动补 `use` 导入并缩写为短名（如 `new \Phalcon\Autoload\Loader()` → `use Phalcon\Autoload\Loader;` + `new Loader()`），但仅限 `Phalcon\` 命名空间与映射表目标类。其它类的 FQCN 保持全限定写法（`new \PDO()` 不会变成 `use PDO;`）——规则集关闭了 Rector 全局的 `importNames()`，避免波及业务与第三方类。

## 开发

### 环境要求

- PHP ^8.1
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
```

### 测试约定（Rector 社区惯例）

- 每条规则先写失败的 fixture 测试，再实现规则
- fixture 是 `*.php.inc` 文件，`-----` 分隔符上方为输入代码（升级前），下方为期望输出（升级后）
- 只有上半段、没有 `-----` 的 fixture 表示"规则必须原样跳过"（命名惯例 `skip_xxx.php.inc`）

运行测试：

```bash
vendor/bin/phpunit --colors=never
```

### 本地开发环境（Windows）

本项目用 **PHP 8.4** 开发与测试（PHP 8.4 已验证 20 个测试全绿；包要求 PHP 8.1+，见 `composer.json`）。

```powershell
Set-Location "d:\opt\htdocs\Phalcon4To5Rector"

# 运行测试
& "D:\software\php\php8.4.27\php.exe" "vendor\phpunit\phpunit\phpunit" --colors=never

# 运行 rector CLI 处理你自己的项目
& "D:\software\php\php8.4.27\php.exe" "vendor\rector\rector\bin\rector" process src --config "config\sets\phalcon50.php" --no-progress-bar
```

## Roadmap

- [x] `Phalcon\Tag` 确定性子集 → `Phalcon\Html\TagFactory` 迁移规则 ✅（`linkTo`/`image`/`select` 等复杂方法仍需手动改写）
- [x] `Phalcon\Assets\Manager` 构造器注入 `Phalcon\Html\TagFactory` ✅
- [x] `Phalcon\Text` / `Phalcon\Helper` → `Phalcon\Support\Helper\*` 静态调用迁移 ✅
- [x] 对 v5 中已移除且无自动迁移方案的类（`Phalcon\Kernel` 等）输出提示 ✅
- [x] 接入 `rector/extension-installer` 实现规则集自动注册 ✅
- [ ] 发布到 Packagist，并尝试收录进 `rectorphp` 官方组织

## 许可

MIT
