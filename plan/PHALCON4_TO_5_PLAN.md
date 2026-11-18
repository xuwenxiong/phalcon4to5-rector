# Phalcon 4 → 5 自动升级规则集实施规划

> 状态：已实施，持续迭代中。
>
> 当前目录结构与命名空间以 `plan/DIRECTORY_STRUCTURE_PLAN.md` 为准；本文保留的版本脉络和实现细节作为历史规划记录。
>
> 来源：根据当前项目实现代码、`README.md`、`composer.json`、规则集配置和测试结构反向提取整理。
>
> 规则集入口：`src/Phalcon5/Config/Set/phalcon50.php`。
>
> 实测基线：Phalcon 4.0.2 → Phalcon 5.23.0 的完整升级流程已在真实项目验证。

本文档既是对现有 4 → 5 能力的 Implementation Plan 归档，也可作为后续新增 Phalcon 3 → 4 规则集时参考的模板。

### 规则集命名约定

`src/Phalcon5/Config/Set/phalcon50.php` 中的 `50` 表示“目标 Phalcon 5.0 基线”，即从 Phalcon 4.x 升级到 Phalcon 5.x 的基础规则集，**不是只支持 Phalcon 5.0.0，也不是 50.x**。当前规则集已覆盖并验证到 Phalcon 5.23.0。

后续版本演进约定：

- Phalcon 5.0.x 补丁版本，以及无破坏性变更的 5.x 小版本：继续使用 `phalcon50.php`。
- 仅对较新 5.x 成立的破坏性变更：新增附加规则集，如 `src/Phalcon5/Config/Set/phalcon513.php`，不重命名 `phalcon50.php`。
- Phalcon 5 → 6 的新主版本：新增 `src/Phalcon6/Config/Set/phalcon60.php`。
- 规则集不通过 `extension-installer` 自动加载，用户必须显式选择 set。

---

## 一、项目定位与目标

### 1.1 定位

本项目 `xuwenxiong/rector-phalcon` 是一个 Rector 扩展包（`type: rector-extension`），用于把 Phalcon 4 应用代码自动迁移到 Phalcon 5。

它按照 `rectorphp/rector-symfony` 风格组织，实现 Phalcon 5 官方升级指南中的破坏性变更，让应用代码升级尽可能自动化。

### 1.2 目标边界

目标：

- 对确定性的破坏性变更做自动改写。
- 对没有确定迁移方案的类/用法输出 TODO 提示，让人工迁移点在 Rector diff 中可见。
- 对参数语义不确定、依赖 DI 或全局状态的调用保持原样，交给人工处理。
- 保持改动范围只限制在 Phalcon 相关符号，避免影响业务类和第三方类。

非目标：

- 不追求 100% 自动升级。
- 不做需要业务判断的架构级改写。
- 不自动执行 DI 化、服务注册、复杂模板逻辑重写等决策性变更。
- 不替代人工 code review 和测试。

README 明确提示：项目处于开发测试阶段，生产使用前必须先 `--dry-run` 预览并人工审查。

---

## 二、包结构与规则集接入机制

### 2.1 Composer 约定

关键约定在 `composer.json`：

- 包名：`xuwenxiong/rector-phalcon`
- 类型：`type: rector-extension`
- PHP 要求：`^8.1`
- 运行依赖：`ext-xml`
- 开发依赖：
  - `phalcon/ide-stubs: ^5.0`
  - `phpunit/phpunit: ^11.5`
  - `rector/rector: ^2.1`
- 自动加载：
  - `RectorPhalcon\` → `src`
  - `RectorPhalcon\Phalcon5\Tests\` → `src/Phalcon5/Tests`
  - `RectorPhalcon\Set\Tests\` → `src/Set/Tests`
  - `RectorPhalcon\Support\Tests\` → `src/Support/Tests`
- 规则集接入：
  - 不配置 `extra.rector.includes`
  - 不启用 `rector/extension-installer` 插件白名单
  - 用户通过 `PhalconSetList` 显式选择规则集

### 2.2 显式接入链路

```text
用户在 rector.php 中选择 PhalconSetList 常量
  ├─ UP_PHALCON_4_TO_5
  │   └─ src/Phalcon5/Config/Set/phalcon50.php
  └─ COMPOSER_BASED
      └─ src/Support/Set/ComposerBased/config.php
          └─ PhalconSetResolver
              └─ 命中目标 Phalcon 5 时选择 UP_PHALCON_4_TO_5

rector 启动
  └─ 加载用户 rector.php 中 withSets() 指定的配置
      └─ phalcon50.php 中注册的规则生效
```

本包不通过 `extension-installer` 自动加载规则集，决策见 `plan/EXTENSION_INSTALLER_PLAN.md`。

### 2.3 显式选择规则集

用户可以在 `rector.php` 中通过公共 `PhalconSetList` 选择规则集：

```php
use RectorPhalcon\Set\PhalconSetList;

return RectorConfig::configure()
    ->withSets([
        PhalconSetList::UP_PHALCON_4_TO_5,
    ]);
```

也可以按项目环境自动选择单个阶段：

```php
->withSets([
    PhalconSetList::COMPOSER_BASED,
])
```

详见 `plan/COMPOSER_BASED_PLAN.md`。前提是项目已安装 `rector/rector`。

---

## 三、规则集组织与执行顺序

规则集入口是 `src/Phalcon5/Config/Set/phalcon50.php`，整体分成四部分。

### 3.1 类与命名空间重命名

使用 Rector 内置 `RenameClassRector`，配置来源是 `ClassRenames::MAP`。

重点说明：

- `ClassRenames` 是类重命名的单一数据源。
- `MAP` 是精确类名映射。
- `PREFIX_MAP` 是命名空间前缀映射。
- `resolveNewClass()` 先查精确映射，再按最长前缀匹配。
- `RenameClassRector` 处理内联类引用、PHPDoc、`extends`、`implements` 等剩余职责。

### 3.2 自定义规则

自定义规则必须先注册：

```text
CacheAdapterFactoryConstructorInjectionRector
AssetsManagerTagFactoryInjectionRector
TextAndHelperToSupportHelperRector
TagToTagFactoryRector
RemovedClassUsageHintRector
RenameUseImportsRector
DeduplicateHelperFactoryRector
DiBeforeServiceResolveListenerReturnRector
```

顺序原因：

- `RenameClassRector` 会把命中节点替换成新的 `FullyQualified` 节点。
- 新节点会丢失原始 token 位置。
- `RenameUseImportsRector` 依赖原始写法信息判断引用到底是内联 FQCN、`use` 短名、相对引用还是分组 use。
- 如果 `RenameClassRector` 先执行，`RenameUseImportsRector` 就无法保持原来的引用风格。

因此规则执行顺序是本项目的重要不变量。

### 3.3 方法重命名

使用 Rector 内置 `RenameMethodRector` + `MethodCallRename` 配置，集中处理确定性的方法名变化。

### 3.4 导入策略

规则集显式关闭全局自动导入：

```php
$rectorConfig->importNames(false, false);
```

原因：

- Rector 全局 `importNames()` 会把所有 FQCN 都改成短名并补 `use`。
- 这会把 `new \PDO()` 变成 `use PDO;`，波及业务类和第三方类。
- 本包只想改 Phalcon 类。
- 新引入的 Phalcon 类由 `ImportShortener` 精确补 `use`。

---

## 四、已实现规则清单

### 4.1 类与命名空间重命名

来自 `src/Phalcon5/ClassRenames.php`。

精确映射 `MAP`：

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
| `Phalcon\Exception` | `Exception`（实际代码中保持 `\Exception` 全限定写法） |

命名空间前缀映射 `PREFIX_MAP`：

| Phalcon 4 前缀 | Phalcon 5 前缀 |
|---|---|
| `Phalcon\Validation` | `Phalcon\Filter\Validation` |

例如：

```text
Phalcon\Validation\Validator\Email
  → Phalcon\Filter\Validation\Validator\Email

Phalcon\Validation\Validator\StringLength
  → Phalcon\Filter\Validation\Validator\StringLength
```

特殊处理：

- `Phalcon\Exception → \Exception` 属于根命名空间目标。
- 原 `use Phalcon\Exception ...` 会被删除。
- 代码和 docblock 中保持全限定 `\Exception`，不补 `use Exception;`。

### 4.2 方法重命名

来自 `src/Phalcon5/Config/Set/phalcon50.php`。

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

依赖说明：

- `RenameMethodRector` 依赖 `phalcon/ide-stubs` 提供类定义做类型解析。
- 无法解析的类型会被跳过。
- `RenameUseImportsRector` 不依赖反射，因为它由映射表驱动。

### 4.3 自定义规则

#### 4.3.1 `CacheAdapterFactoryConstructorInjectionRector`

补齐 `Phalcon\Cache\AdapterFactory` 在 v5 中必传的 `Phalcon\Storage\SerializerFactory` 参数。

背景：

- v4 中 `new AdapterFactory()` 或 `new AdapterFactory(null)` 可用。
- v5 中构造函数必须有 `SerializerFactory`，否则运行时报 `TypeError`。

迁移形态：

```text
new AdapterFactory()
new AdapterFactory(null)
  → new AdapterFactory(new SerializerFactory())
```

#### 4.3.2 `AssetsManagerTagFactoryInjectionRector`

为 `Phalcon\Assets\Manager` 构造函数注入 v5 必传的 `Phalcon\Html\TagFactory`，并补齐 `Escaper` 依赖链。

迁移形态：

```text
new Manager()
  → new Manager(new TagFactory(new Escaper()))

new Manager(['sourceBasePath' => '/src'])
  → new Manager(new TagFactory(new Escaper()), ['sourceBasePath' => '/src'])
```

v4 的 `options` 数组自动后移。

#### 4.3.3 `TextAndHelperToSupportHelperRector`

把 v5 中已移除的 `Phalcon\Text` / `Phalcon\Helper\*` 静态调用迁移为 `Phalcon\Support\HelperFactory` 方法调用。

覆盖范围：

- 51 个 helper 方法映射。
- 6 个 `RANDOM_*` 常量。
- `Helper\Number::between()` → `isBetween()`。
- 新引入 `HelperFactory` 时自动补 `use` 导入。

迁移示例：

```php
// v4
$camel = Text::camelize('foo_bar');
$endsWith = Helper\Str::endsWith('hello world', 'world');
$first = Helper\Arr::first($items);
$type = Text::RANDOM_ALPHA;

// v5
$camel = $helperFactory->camelize('foo_bar');
$endsWith = $helperFactory->endsWith('hello world', 'world');
$first = $helperFactory->first($items);
$type = \Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA;
```

边界：

- `Phalcon\Helper\File` 在 v4 中不存在，不处理。
- 动态方法名（如 `$class::$method()`）跳过。
- `RANDOM_*` 常量没有工厂代理，仍指向 `Helper\Str\Random` 类常量。

#### 4.3.4 `DeduplicateHelperFactoryRector`

同一方法体内出现两次及以上内联 `new HelperFactory()` 时，提取为局部变量复用。

```text
(new HelperFactory())->camelize($a);
(new HelperFactory())->upper($b);
  ↓
$helperFactory = new HelperFactory();
$helperFactory->camelize($a);
$helperFactory->upper($b);
```

跳过条件：

- 方法体内已存在同名变量。
- 调用位于嵌套普通 `function` 内。
- 单次调用保持内联，不主动提取。

README 建议生产环境进一步用 DI 注册 `HelperFactory` 单例；本规则只做机械改写的安全下限。

#### 4.3.5 `TagToTagFactoryRector`

把 v5 中已移除的 `Phalcon\Tag` 的确定性子集迁移为 `$tagFactory->...` 调用。

`$tagFactory` 是作用域内的 `Phalcon\Html\TagFactory` 变量，这是本包的显式约定。

覆盖范围：

- 22 个输入字段方法，如 `textField`、`emailField`、`checkField`、`submitButton` 等。
- 字符串形态与字面量数组形态自动拆解为 `(name, value, attributes)`。
- `friendlyText()` → `friendlyTitle()`。
- 11 个 `HTML*/XHTML*` doctype 常量 → `Doctype::` 常量。

示例：

```text
Tag::textField(['name', 'value' => 'x', 'class' => 'c'])
  → $tagFactory->inputText('name', 'x', ['class' => 'c'])

Tag::friendlyText('Some Title', '-')
  → $tagFactory->friendlyTitle('Some Title', '-')

Tag::HTML5
  → \Phalcon\Html\Helper\Doctype::HTML5
```

明确不迁移：

- `linkTo()`
- `image()`
- `stylesheetLink()`
- `javascriptInclude()`
- `select()` / `selectStatic()`
- `form()` / `endForm()`
- `tagHtml()` / `tagHtmlClose()`
- `renderAttributes()`
- 状态方法：`setTitle()` / `getTitle()`、`setDefault()` / `getValue()`、`setDocType()` / `getDocType()`
- DI 访问器：`setDI()` / `getDI()` / `getEscaper()` / `getUrlService()`

#### 4.3.6 `RemovedClassUsageHintRector`

对 v5 中已移除且无自动迁移方案的类（如 `Phalcon\Kernel`）加 TODO 注释。

它不改写代码，只让手工迁移点在 diff 中可见。

示例：

```php
// v4
use Phalcon\Kernel;
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);

// v5 迁移后（仅加提示）
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement ...
use Phalcon\Kernel;
// TODO Phalcon v5: "Phalcon\Kernel" was removed in Phalcon v5 without a replacement ...
\Phalcon\Kernel::preFlightChecks($container, $eventsManager);
```

设计取舍：

- 提示清单刻意精简。
- 有确定迁移方案的类交给对应规则处理。
- `Phalcon\Exception` 由 `RenameClassRector` 处理，不进入 TODO 清单。
- TODO 注释带标记，重复运行不会叠加。

#### 4.3.7 `DiBeforeServiceResolveListenerReturnRector`

处理 v5.13+ 中 `di:beforeServiceResolve` 监听器返回值语义收紧问题。

背景：

- v4 中 `Di::get()` 只认对象返回值，非对象返回值被忽略。
- v5.13+ 收紧为非 `null` 即短路。
- 监听器返回 `true` / `false` 会让服务解析返回布尔值并污染共享缓存。

规则行为：

- 剥离监听器中的标量/数组返回值：`true`、`false`、数字、字符串、`[]`。
- 移除因此失效的返回类型声明，如 `: bool`。
- 保留对象返回值，因为这是 v4 就存在的“自建实例”短路机制。
- 每处被剥离的 `return;` 上方加入原因注释。
- 注释带标记，重复运行不叠加。

覆盖：

- `$eventsManager->attach('di:beforeServiceResolve', function (...) {...})` 内联闭包。
- 箭头函数 `fn (...) => true` → 空闭包。
- 监听器类中名为 `beforeServiceResolve()` 的 public 方法。

需人工复核：

- 间接引用监听器，如 `[$this, 'onBeforeResolve']`。
- 可调用字符串。
- 闭包存变量。
- 挂在 `di` 命名空间下用 `$event->getType()` 分流的监听器。
- 返回值类型未知的表达式，如 `return $this->handle();`。

#### 4.3.8 `RenameUseImportsRector`

改写类重命名后残留的 `use` 导入。

背景：

- Rector 2.x 的 `RenameClassRector` 只重命名内联引用。
- 文件顶部的旧 `use` 导入会残留。
- 本规则负责同步改写 `use`、分组 use、别名、docblock 引用。

数据来源：

- 与 `RenameClassRector` 共享 `ClassRenames`。
- 精确映射：`MAP`
- 前缀映射：`PREFIX_MAP`
- 解析入口：`resolveNewClass()`

行为：

- 只处理映射范围内的 Phalcon 类。
- 业务类、第三方库、PHP 内置类的 `use` 不动。
- 保持原引用风格：
  - 内联仍内联。
  - `use` 短名仍短名。
  - 相对子命名空间引用仍保持相对形态。
  - 分组 use 同步支持。
- 冗余别名自动简化：
  - `use Phalcon\Crypt as PhalconCrypt;` → `use Phalcon\Encryption\Crypt;`
  - 代码和 docblock 中 `PhalconCrypt` 同步改为 `Crypt`。
- 根命名空间目标特殊处理：
  - `use Phalcon\Exception as PException;` 被删除。
  - `throw new PException()` → `throw new \Exception()`。
  - `/** @throws PException */` → `/** @throws \Exception */`。

保留别名的情况：

- 别名与新短名无关，如 `as PEx`。
- 新短名已被其他 `use` 占用，如同时存在 `use App\Support\Di;`。
- 文件中存在同名 class / interface / trait / enum 声明。

### 4.4 辅助类

#### `ClassRenames`

职责：

- 类名重命名的单一数据源。
- 被 `RenameClassRector` 和 `RenameUseImportsRector` 共享。
- 避免规则之间映射不一致。

#### `ImportShortener`

职责：

- 给规则新创建的 Phalcon 类节点补 `use` 并返回短名。
- 典型场景：`SerializerFactory`、`TagFactory`、`Escaper`、`HelperFactory`、`Doctype`。
- 只处理 Phalcon 相关类。
- 根命名空间类不导入。
- 非 Phalcon 类保持 FQCN。

设计意义：

- 配合 `importNames(false, false)` 使用。
- 让“只自动导入规则新引入的 Phalcon 类”成为可能。

---

## 五、测试体系

### 5.1 目录结构

```text
src/Phalcon5/Tests/
├── Phalcon50Set/
│   ├── Phalcon50SetTest.php
│   ├── config/configured_rule.php
│   └── Fixture/*.php.inc
└── Rector/
    ├── New_/<RuleName>/
    ├── StaticCall/<RuleName>/
    └── Stmt/<RuleName>/
```

当前验证基线：

- PHP 8.4 已验证 56 个测试、68 个断言全绿。
- 数量包含 4 → 5 规则测试、Composer-based 测试和 SetList 测试。

### 5.2 测试类型

单规则测试：

- 目录与 `src/Phalcon5/Rector/` 一一对应。
- 每个规则有自己的 `config/configured_rule.php`，只启用该规则。
- 继承 `Rector\Testing\PHPUnit\AbstractRectorTestCase`。
- 使用 `#[DataProvider]` 从 `Fixture/` 读取用例。

规则集测试：

- `Phalcon50Set` 加载完整 `src/Phalcon5/Config/Set/phalcon50.php`。
- 验证多规则协作场景，例如类重命名、`use` 改写、方法重命名、新类导入同时发生。

### 5.3 Fixture 约定

- `*.php.inc` 文件。
- `-----` 上方是升级前代码。
- `-----` 下方是期望输出。
- 只有上半段、没有 `-----` 的 fixture 表示规则必须原样跳过。
- 跳过用例命名惯例：`skip_xxx.php.inc`。

### 5.4 运行方式

```bash
vendor/bin/phpunit --colors=never
```

Windows 本地开发命令：

```powershell
Set-Location "d:\opt\htdocs\rector-phalcon"

& "D:\software\php\php8.4.27\php.exe" "vendor\phpunit\phpunit\phpunit" --colors=never

& "D:\software\php\php8.4.27\php.exe" "vendor\rector\rector\bin\rector" process src --config "src\Phalcon5\Config\Set\phalcon50.php" --no-progress-bar
```

---

## 六、关键设计决策

### 6.1 单一映射源

类名映射集中在 `ClassRenames`，避免 `RenameClassRector` 与 `RenameUseImportsRector` 各维护一份表。

收益：

- 新增/修改映射只改一处。
- `use` 导入与内联引用保持一致。
- 前缀映射和精确映射统一解析。

### 6.2 引用风格保持

重命名只改变“指向”，不改变“写法”。

例如：

```text
new \Phalcon\Loader()
  → new \Phalcon\Autoload\Loader()

use Phalcon\Loader; new Loader()
  → use Phalcon\Autoload\Loader; new Loader()
```

这是通过自定义规则先于 `RenameClassRector` 执行实现的。

### 6.3 只处理 Phalcon 符号

规则集关闭全局 `importNames()`。

`ImportShortener` 只对 Phalcon 类补 `use`。

因此：

- `new \PDO()` 不会被改成 `use PDO;`。
- 业务类、第三方库不受影响。
- 根命名空间 `Exception` 保持 `\Exception`。

### 6.4 确定性子集优先

只有语义确定的变更才自动改写。

例如：

- `Tag::textField()` 可自动改。
- `Tag::linkTo()` 不自动改，因为它依赖 DI、URL 服务和参数语义。
- `Phalcon\Kernel` 无确定替代，只加 TODO。
- 复杂 helper 方法、动态调用跳过。

### 6.5 TODO 幂等

`RemovedClassUsageHintRector` 和 `DiBeforeServiceResolveListenerReturnRector` 添加的注释带标记。

重复运行 Rector 不会叠加注释。

### 6.6 反射依赖最小化

方法重命名依赖 stubs；`use` 导入重命名不依赖反射。

这条边界让核心类重命名更稳定，同时把反射依赖限制在确实需要类型解析的方法规则上。

---

## 七、已知限制

来自 README 和当前实现：

1. `Phalcon\Tag` 只迁移确定性子集。
   - 已覆盖输入字段、`friendlyText()`、doctype 常量。
   - `linkTo`、`image`、`select`、`form` 等需要手动改写。

2. 方法重命名依赖 `phalcon/ide-stubs`。
   - 无法解析的类型会被跳过。

3. 引用写法保持原样。
   - 原内联仍内联。
   - 原 `use` 短名仍短名。
   - 只有规则新引入的类才补 `use`。

4. `use` 导入只处理 Phalcon 类。
   - `new \PDO()` 不会被改成 `use PDO;`。
   - 根命名空间类保持全限定。

5. 动态静态调用不处理。
   - 如 `$class::$method()` 这类无法静态确定目标的情况会跳过。

6. 复杂业务语义不处理。
   - DI、状态方法、模板渲染等依赖上下文的调用保持原样。

---

## 八、Roadmap 与后续计划

### 8.0 通用架构（README 精简后归入本文）

- [x] 统一根命名空间 `RectorPhalcon\` 对应 `src/`。
- [x] 按目标版本划分目录：`src/Phalcon5/`、未来的 `src/Phalcon4/`。
- [x] 抽象规则基类放在 `src/Base/`，通用支持代码放在 `src/Support/`。
- [x] 公共 SetList 常量入口 `RectorPhalcon\Set\PhalconSetList`，后续升级阶段共用同一入口。
- [x] 公共 SetList 显式接入当前已实现规则集，不启用 extension-installer 自动加载。
- [x] 按目标版本自动选择规则集的 Composer-based 配置接入（`PhalconSetList::COMPOSER_BASED`）。
- [ ] Phalcon 3 → 4、Phalcon 3 → 5 各阶段规则集接入公共入口。

### 8.1 已完成

- [x] `Phalcon\Tag` 确定性子集 → `Phalcon\Html\TagFactory`。
- [x] `Phalcon\Assets\Manager` 构造器注入 `Phalcon\Html\TagFactory`。
- [x] `Phalcon\Text` / `Phalcon\Helper` → `Phalcon\Support\HelperFactory`。
- [x] 已移除且无替代类输出 TODO 提示。
- [x] 决定不采用 `rector/extension-installer` 自动注册规则集。
- [x] `PREFIX_MAP` 支持 `Phalcon\Validation` 整个子命名空间迁移。
- [x] 引用写法保持原样。
- [x] 相对子命名空间引用保持相对形态。
- [x] 根命名空间目标类特殊处理。
- [x] 冗余别名自动简化。
- [x] 规则执行顺序修复：自定义规则先于 `RenameClassRector`。
- [x] 规则集级测试覆盖多规则协作。
- [x] 真实项目实测验证 Phalcon 4.0.2 → 5.23.0。
- [x] `di:beforeServiceResolve` 监听器返回值迁移。

### 8.2 未完成

- [ ] 发布到 Packagist，并尝试收录进 `rectorphp` 官方组织。
- [ ] `Phalcon\Validation\Validator\*` 方法签名变更的自动适配，如 `ValidationInterface::add()` 的 `$field` 类型放宽。
- [ ] 方法签名变更检查：`add()` / `rule()` 参数类型放宽、异常类改为继承 `\Exception` 等接口级变更的迁移提示。

---

## 九、维护与扩展指南

### 9.1 新增类名映射

步骤：

1. 在 `src/Phalcon5/ClassRenames.php` 的 `MAP` 增加精确映射，或在 `PREFIX_MAP` 增加前缀映射。
2. 写单规则 fixture，覆盖：
   - 内联 FQCN。
   - `use` 短名。
   - 分组 use。
   - 别名。
   - docblock。
3. 跑单规则测试和 `Phalcon50Set` 测试。
4. 更新 `plan/PHALCON4_TO_5_PLAN.md` 的类名映射表。

### 9.2 新增方法重命名

步骤：

1. 确认目标方法在 Phalcon 5 stubs 中存在。
2. 在 `src/Phalcon5/Config/Set/phalcon50.php` 的 `RenameMethodRector` 列表增加 `MethodCallRename`。
3. 写 fixture，覆盖正常对象调用和无法解析类型时的跳过行为。
4. 更新 `plan/PHALCON4_TO_5_PLAN.md` 的方法重命名表。

### 9.3 新增自定义规则

步骤：

1. 按节点类型选择目录：
   - `Rector/New_/`
   - `Rector/StaticCall/`
   - `Rector/Stmt/`
   - 必要时新增 `Rector/ClassConstFetch/` 等目录。
2. 继承 `Rector\Rector\AbstractRector`。
3. 实现：
   - `getNodeTypes()`
   - `refactor(Node $node)`
   - `getRuleDefinition()`
4. 如规则新引入 Phalcon 类，通过 `ImportShortener` 补 `use`。
5. 写 fixture：
   - 成功改写。
   - 原样跳过。
   - 重复运行幂等。
6. 在 `src/Phalcon5/Config/Set/phalcon50.php` 的自定义规则数组中注册。
7. 必须放在 `RenameClassRector` 之前。
8. 更新 `plan/PHALCON4_TO_5_PLAN.md` 的规则清单和 Roadmap。

### 9.4 新增规则时的稳定性检查

- 是否只处理 Phalcon 类？
- 是否保持了原始引用风格？
- 是否依赖 stubs？如果依赖，文档是否说明？
- 是否会产生会叠加的注释？
- 是否在 `Phalcon50Set` 中验证与其他规则协作？
- 是否更新 `plan/PHALCON4_TO_5_PLAN.md` 的“支持的规则”和“已知限制”？

---

## 十、关键文件索引

```text
composer.json
  - 包类型、autoload、显式规则集接入约定（无 extra.rector.includes）

src/Phalcon5/Config/Set/phalcon50.php
  - 4 → 5 规则集入口
  - 自定义规则注册
  - RenameClassRector 配置
  - RenameMethodRector 配置
  - importNames(false, false)

src/Phalcon5/ClassRenames.php
  - 类名映射单一数据源
  - MAP / PREFIX_MAP / resolveNewClass()

src/Phalcon5/ImportShortener.php
  - Phalcon 新类精确补 use
  - 非 Phalcon 类保持 FQCN

src/Phalcon5/Rector/New_/
  - CacheAdapterFactoryConstructorInjectionRector.php
  - AssetsManagerTagFactoryInjectionRector.php

src/Phalcon5/Rector/StaticCall/
  - TagToTagFactoryRector.php
  - TextAndHelperToSupportHelperRector.php

src/Phalcon5/Rector/Stmt/
  - DeduplicateHelperFactoryRector.php
  - DiBeforeServiceResolveListenerReturnRector.php
  - RemovedClassUsageHintRector.php
  - RenameUseImportsRector.php

src/Phalcon5/Tests/
  - Phalcon50Set/
  - Rector/

README.md
  - 简要使用入口；规则明细见 plan/PHALCON4_TO_5_PLAN.md

phpunit.xml
  - PHPUnit bootstrap 与 testsuite 配置
```

---

## 十一、与后续版本规划的关系

本文档描述的是当前已落地的 4 → 5 规则集。

后续若新增 Phalcon 3 → 4 支持，应参考本项目的以下做法：

- 一个升级阶段一个规则集入口。
- 映射表作为单一数据源。
- 自定义规则先于内置 `RenameClassRector`。
- `use` 改写与内联引用改写共享映射。
- 只自动迁移确定性子集。
- 无确定方案时输出 TODO，不猜测业务意图。
- fixture 驱动开发，先写失败用例再实现规则。
- 规则集测试验证多规则协作。
- `plan/PHALCON4_TO_5_PLAN.md` 同步记录规则表、边界和已知限制。

3 → 4 的详细规划见：

```text
plan/PHALCON3_TO_4_PLAN.md
```
