# Phalcon 3 → 4 自动升级规则集实施规划

> 状态：P0、P1、P2 全部实施。P2 已完成 Cache / Logger / Session checklist，以及 Filter 工厂化、`Filter::add()` → `set()`、sanitizer 字符串、`Model::save($data)`、`handle()` URI、`getSource()` → `initialize()` + `setSource()` 自动改写；剩余工作集中在真实项目验证与发布准备。
>
> 当前目录结构与命名空间以 `plan/DIRECTORY_STRUCTURE_PLAN.md` 为准。
>
> 来源：根据 Phalcon 官方 3.4 → 4.0 升级指南、feature 分支已实现内容、当前代码与测试结构整理。
>
> 规则集入口：`src/Phalcon4/Config/Set/phalcon40.php`。
>
> 实现基线：Phalcon 3.4.x → Phalcon 4.0.x；后续用 4.2 / 4.3 做回归验证。
>
> 当前验证基线：PHP 8.4.27、Composer 2.11.2、Rector 2.8.5；完整测试套件 66 tests / 83 assertions 通过。

本文档既是对当前 3 → 4 P0 能力的 Implementation Plan，也记录后续 P1/P2 的规划。

### 规则集命名约定

`src/Phalcon4/Config/Set/phalcon40.php` 中的 `40` 表示“目标 Phalcon 4.0 基线”，即从 Phalcon 3.x 升级到 Phalcon 4.x 的基础规则集，**不是只支持 Phalcon 4.0.0，也不是 40.x**。

后续版本演进约定：

- Phalcon 4.0.x 补丁版本，以及无破坏性变更的 4.x 小版本：继续使用 `phalcon40.php`。
- 仅对较新 4.x 成立的破坏性变更：新增附加规则集，如 `src/Phalcon4/Config/Set/phalcon413.php`，不重命名 `phalcon40.php`。
- Phalcon 4 → 5 的新主版本：使用 `src/Phalcon5/Config/Set/phalcon50.php`。
- 规则集不通过 `extension-installer` 自动加载，用户必须显式选择 set。

---

## 一、项目定位与目标

### 1.1 定位

本项目 `xuwenxiong/rector-phalcon` 是一个 Rector 扩展包（`type: rector-extension`），目标是支持 Phalcon 各主版本自动升级。

Phalcon 3 → 4 规则集用于将 Phalcon 3.x 应用代码迁移到 Phalcon 4.x，按照 `rectorphp/rector-symfony` 风格组织，实现 Phalcon 4 官方升级指南中的破坏性变更。

### 1.2 目标边界

目标：

- 对确定性的破坏性变更做自动改写。
- 对没有确定迁移方案的类/用法输出 TODO 提示，让人工迁移点在 Rector diff 中可见。
- 对参数语义不确定、依赖 DI 或全局状态的调用保持原样，交给人工处理。
- 保持改动范围只限制在 Phalcon 相关符号，避免影响业务类和第三方类。
- 保持与 4 → 5 阶段隔离，避免跨阶段规则混跑。

非目标：

- 不追求 100% 自动升级。
- 不做需要业务判断的架构级改写。
- 不自动执行 DI 化、服务注册、复杂 Cache/Logger/Session 重构。
- 不替代人工 code review 和测试。
- 不在一次 Rector 运行中同时加载 3 → 4 和 4 → 5 规则。

README 只保留简要使用入口；规则清单、限制和 Roadmap 以本文件为准。

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
  - `RectorPhalcon\Phalcon4\Tests\` → `src/Phalcon4/Tests`
  - `RectorPhalcon\Phalcon5\Tests\` → `src/Phalcon5/Tests`
  - `RectorPhalcon\Set\Tests\` → `src/Set/Tests`
  - `RectorPhalcon\Support\Tests\` → `src/Support/Tests`
- 规则集接入：
  - 不配置 `extra.rector.includes`
  - 不启用 `rector/extension-installer` 插件白名单
  - 用户通过 `PhalconSetList` 显式选择规则集

注意：当前 dev stubs 是 `^5.0`，但 P0 主要是配置驱动的类名/`use` 改写，不依赖反射。P1 引入 `RenameMethodRector` 前，需要建立 `phalcon/ide-stubs:^4.0` 的测试矩阵。

用户侧建议：在执行 `UP_PHALCON_3_TO_4` 阶段前，将运行 Rector 的 Composer 环境中的 `phalcon/ide-stubs` 切换为 `^4.0`。当前 P0 不强制；但 P1 方法重命名需要它做类型解析，`COMPOSER_BASED` 也用它识别目标 Phalcon 4。若使用 `COMPOSER_BASED` 且未安装 stubs/扩展，版本探测会返回 no-op。

### 2.2 显式接入链路

```text
用户在 rector.php 中选择 PhalconSetList 常量
  ├─ UP_PHALCON_3_TO_4
  │   └─ src/Phalcon4/Config/Set/phalcon40.php
  └─ COMPOSER_BASED
      └─ src/Support/Set/ComposerBased/config.php
          └─ PhalconSetResolver
              └─ 命中目标 Phalcon 4 时选择 UP_PHALCON_3_TO_4

rector 启动
  └─ 加载用户 rector.php 中 withSets() 指定的配置
      └─ phalcon40.php 中注册的规则生效
```

本包不通过 `extension-installer` 自动加载规则集，决策见 `plan/EXTENSION_INSTALLER_PLAN.md`。

### 2.3 显式选择规则集

用户可以在 `rector.php` 中通过公共 `PhalconSetList` 选择规则集：

```php
use RectorPhalcon\Set\PhalconSetList;

return RectorConfig::configure()
    ->withSets([
        PhalconSetList::UP_PHALCON_3_TO_4,
    ]);
```

也可以按项目环境自动选择单个阶段：

```php
->withSets([
    PhalconSetList::COMPOSER_BASED,
])
```

当前 Composer-based 映射：

| 探测到的目标版本 | 加载的 set |
|---|---|
| Phalcon 4 | `PhalconSetList::UP_PHALCON_3_TO_4` |
| Phalcon 5 | `PhalconSetList::UP_PHALCON_4_TO_5` |
| 其他 | no-op |

详见 `plan/COMPOSER_BASED_PLAN.md`。前提是项目已安装 `rector/rector`。

---

## 三、规则集组织与执行顺序

规则集入口是 `src/Phalcon4/Config/Set/phalcon40.php`，当前分为三部分。

### 3.1 类与命名空间重命名

使用 Rector 内置 `RenameClassRector`，配置来源是 `ClassRenames::MAP`。

重点说明：

- `ClassRenames` 是 Phalcon 3 → 4 类重命名的单一数据源。
- `MAP` 是精确类名映射。
- `PREFIX_MAP` 是命名空间前缀映射。
- `resolveNewClass()` 先查精确映射，再按最长前缀匹配。
- `RenameClassRector` 处理内联类引用、PHPDoc、`extends`、`implements` 等剩余职责。

### 3.2 自定义规则

当前已注册的自定义规则必须先于 `RenameClassRector` 执行：

```text
RemovedClassUsageHintRector
RenameUseImportsRector
```

顺序原因：

- `RenameClassRector` 会把命中节点替换成新的 `FullyQualified` 节点。
- 新节点会丢失原始 token 位置。
- `RenameUseImportsRector` 继承共享基类，依赖原始写法信息判断引用到底是内联 FQCN、`use` 短名、相对引用还是分组 use。
- 如果 `RenameClassRector` 先执行，`RenameUseImportsRector` 就无法保持原来的引用风格。

因此规则执行顺序是本项目的重要不变量。

### 3.3 方法重命名

P1 已启用 ACL 与 Paginator 的确定性重命名，使用 Rector 内置 `RenameMethodRector` + `MethodCallRename` 配置。

配置中的类名使用 Phalcon 4 名称，因为 `RenameClassRector` 先于方法规则执行，已经把 Phalcon 3 引用改写成 v4 等价类名。ACL 覆盖 `AbstractAdapter`、`AdapterInterface` 和 `Memory`；Paginator 覆盖 `AbstractAdapter`、`AdapterInterface`、`Model`、`NativeArray` 和 `QueryBuilder`。

`Phalcon\Paginator\AdapterInterface` 也加入了 `ClassRenames::MAP`，统一改写为 `Phalcon\Paginator\Adapter\AdapterInterface`。

Paginator 结果对象的 `$before` → `$previous` 由自定义规则 `RenamePaginatorBeforePropertyRector` 处理：它记录同一函数作用域内由 Paginator `getPaginate()` / `paginate()` 赋值得到的变量名，只改写这些变量上的 `->before`，避免误伤业务对象的同名字段。

CLI `Console::addModules()` → `registerModules()` 由自定义规则 `RenameCliConsoleAddModulesRector` 处理，并自动补上第二个参数 `true`，保留 Phalcon 3 `addModules()` 的合并语义。

其他方法重命名仍在后续 P1 中实施。

### 3.4 导入策略

规则集显式关闭全局自动导入：

```php
$rectorConfig->importNames(false, false);
```

原因：

- Rector 全局 `importNames()` 会把所有 FQCN 都改成短名并补 `use`。
- 这会把 `new \PDO()` 变成 `use PDO;`，波及业务类和第三方类。
- 本包只想改 Phalcon 类。
- 新引入的 Phalcon 类应由版本特定的 `ImportShortener` 精确补 `use`。

当前 P0 没有新引入 Phalcon 类的自定义规则，因此 `src/Phalcon4/ImportShortener.php` 只作为后续扩展点预留。

---

## 四、已实现规则清单

### 4.1 类与命名空间重命名

来自 `src/Phalcon4/ClassRenames.php`。

精确映射 `MAP` 覆盖以下组件：

| 组件 | 示例映射 |
|---|---|
| ACL | `Phalcon\Acl\Resource` → `Phalcon\Acl\Component`；`Phalcon\Acl\AdapterInterface` → `Phalcon\Acl\Adapter\AdapterInterface` |
| Annotations | `Phalcon\Annotations\Adapter\Files` → `Phalcon\Annotations\Adapter\Stream` |
| Application | `Phalcon\Application` → `Phalcon\Application\AbstractApplication` |
| Assets | `Phalcon\Assets\Resource` → `Phalcon\Assets\Asset` |
| Cache | `Phalcon\Cache\Backend\File` → `Phalcon\Cache\Adapter\Stream` |
| Config | `Phalcon\Config\Factory` → `Phalcon\Config\ConfigFactory` |
| DB | `Phalcon\Db\Adapter\Pdo\Factory` → `Phalcon\Db\Adapter\PdoFactory` |
| Filter | `Phalcon\Filter` → `Phalcon\Filter\Filter`；`Phalcon\FilterInterface` → `Phalcon\Filter\FilterInterface` |
| Logger | `Phalcon\Logger\Adapter\File` → `Phalcon\Logger\Adapter\Stream` |
| MVC | `Phalcon\Mvc\Collection` → `Phalcon\Collection` |
| Paginator | `Phalcon\Paginator\Factory` → `Phalcon\Paginator\PaginatorFactory` |
| Session | `Phalcon\Session\Factory` → `Phalcon\Session\Manager` |
| Text | `Phalcon\Text` → `Phalcon\Helper\Str` |
| Translate | `Phalcon\Translate\Factory` → `Phalcon\Translate\TranslateFactory` |
| Validation | `Phalcon\Validation\Message` → `Phalcon\Messages\Message` |

完整映射以 `src/Phalcon4/ClassRenames.php` 为准。

命名空间前缀映射 `PREFIX_MAP`：

| Phalcon 3 前缀 | Phalcon 4 前缀 |
|---|---|
| `Phalcon\Mvc\Model\Validator` | `Phalcon\Validation\Validator` |

例如：

```text
Phalcon\Mvc\Model\Validator\Email
  → Phalcon\Validation\Validator\Email

Phalcon\Mvc\Model\Validator\Inclusionin
  → Phalcon\Validation\Validator\InclusionIn
```

### 4.2 方法重命名

P1 已实现 ACL、Paginator 与 CLI 子集：

| 类/组件 | 旧方法/属性 | 新方法/属性 | 状态 |
|---|---|---|---|
| ACL Adapter | `isResource()` | `isComponent()` | 已实现 |
| ACL Adapter | `addResource()` | `addComponent()` | 已实现 |
| ACL Adapter | `getResources()` | `getComponents()` | 已实现 |
| ACL Adapter | `getActiveResource()` | `getActiveComponent()` | 已实现 |
| Paginator Adapter | `getPaginate()` | `paginate()` | 已实现 |
| Paginator Repository | `$before` | `$previous` | 已实现 |
| CLI Console | `addModules()` | `registerModules($modules, true)` | 已实现 |

方法配置目标类包括 `Phalcon\Acl\Adapter\AbstractAdapter`、`Phalcon\Acl\Adapter\AdapterInterface`、`Phalcon\Acl\Adapter\Memory`、`Phalcon\Paginator\Adapter\AbstractAdapter`、`Phalcon\Paginator\Adapter\AdapterInterface` 及具体 Paginator 适配器，用于覆盖抽象类、接口和具体实现上的调用。

`$before` → `$previous` 使用自定义 `RenamePaginatorBeforePropertyRector`，并保留 `RenamePropertyRector` 对已能推断为 Phalcon 4 Repository 类型的调用做补充处理。

`addModules()` → `registerModules()` 使用自定义 `RenameCliConsoleAddModulesRector`，不会用内置 `RenameMethodRector`，因为需要同时补上合并参数。已有第二个参数时保留原值；已有命名参数时使用 `merge: true`；参数解包场景跳过。

依赖说明：

- `RenameMethodRector` 依赖 `phalcon/ide-stubs` 提供类定义做类型解析。
- 无法解析的类型会被跳过。
- `RenameUseImportsRector` 不依赖反射，因为它由映射表驱动。

### 4.3 常量重命名

P1 已实现 Filter 与 DB 常量的确定性重命名，使用 Rector 内置 `RenameClassConstFetchRector`。

| 类/组件 | 旧常量 | 新常量 | 状态 |
|---|---|---|---|
| `Phalcon\Filter` | `FILTER_ALPHANUM` | `FILTER_ALNUM` | 已实现 |
| `Phalcon\Filter` | `FILTER_APHANUM` | `FILTER_ALNUM` | 已实现，兼容升级指南中的拼写 |
| `Phalcon\Filter` | `FILTER_SPECIAL_CHARS` | `FILTER_SPECIAL` | 已实现 |
| `Phalcon\Db` | `FETCH_*` | `Phalcon\Db\Enum::FETCH_*` | 已实现，常量名不变 |

Phalcon 3.4 API 中的 Filter 常量名是 `FILTER_ALPHANUM`；官方升级指南写作 `FILTER_APHANUM`。为避免漏改，两者都映射到 `FILTER_ALNUM`。

DB 侧已复用 `ClassRenames::MAP` 中已有的 `Phalcon\Db` → `Phalcon\Db\AbstractDb`。所有 Phalcon 3.4 `Phalcon\Db::FETCH_*` 常量统一改写到 `Phalcon\Db\Enum`，常量名保持不变；清单集中在 `src/Phalcon4/DbConstants.php`。

常量规则对 v3 类名引用使用 `RenameClassAndConstFetch`，对 v4 类名引用使用 `RenameClassConstFetch`，因此不依赖 `RenameClassRector` 的执行顺序。对于 DB，额外覆盖 `Phalcon\Db\AbstractDb`，防止类重命名先发生时常量遗漏。

`Phalcon\Text` 迁移通过类映射实现：`Phalcon\Text::camelize()` → `Phalcon\Helper\Str::camelize()`，`Phalcon\Text::RANDOM_ALPHA` → `Phalcon\Helper\Str::RANDOM_ALPHA`。因为 Phalcon 4 的 `Phalcon\Helper\Str` 保留了这些静态方法和 `RANDOM_*` 常量，不需要额外的方法/常量改写规则；`use` 导入和内联全限定引用仍由 `RenameUseImportsRector` + `RenameClassRector` 处理。这一步也让 4 → 5 的 `TextAndHelperToSupportHelperRector` 能在直接升级的第二阶段看到正确的 Phalcon 4 形态。

### 4.4 自定义规则

#### 4.4.1 `RenameUseImportsRector`

改写类重命名后残留的 `use` 导入。

背景：

- Rector 2.x 的 `RenameClassRector` 主要重命名内联引用。
- 文件顶部的旧 `use` 导入会残留。
- 本规则负责同步改写 `use`、分组 use、别名、docblock 引用。

继承关系：

- 继承 `RectorPhalcon\Base\Rector\Stmt\AbstractRenameUseImportsRector`。
- 只提供 Phalcon 3 → 4 的 `ClassRenames` 映射。
- 不复制通用改写逻辑。

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
- 别名如果与新短名一致，会自动简化。
- 别名如果与新短名无关，或存在命名冲突，则保留。

#### 4.4.2 `RemovedClassUsageHintRector`

对 Phalcon 4 中已移除且无自动迁移方案的类加 TODO 注释。

它不改写代码，只让手工迁移点在 diff 中可见。

已覆盖类别：

- ACL：`Phalcon\Acl`
- Assets filters：`CssMin`、`JsMin`
- `Phalcon\Image`
- 旧 Logger adapter/formatter：`Firephp`、`Syslog`（`Logger\Multiple` 改由专项 checklist 处理）
- 旧 Collection behavior/document/manager
- 旧 Model metadata adapter：`Apc`、`Memcache`、`Session`、`Xcache`
- `Phalcon\Queue\Beanstalk*`
- `Phalcon\Translate`

设计取舍：

- 提示清单只包含“没有确定自动迁移方案”的类。
- 有确定迁移方案的类交给类名映射或后续规则处理。
- TODO 注释带标记，重复运行不会叠加。

#### 4.4.3 `CacheMigrationHintRector`

针对 `Phalcon\Cache\Frontend\*`、`Phalcon\Cache\Backend\*` 和 `Phalcon\Cache\Multiple` 输出结构化的 4 步 checklist，避免与通用 removed-class 提示重复。

checklist 内容：

1. 用 `Phalcon\Storage\Serializer\*` 或 serializer 配置替换旧 Frontend。
2. 用 `Phalcon\Cache\Adapter\*` 替换旧 Backend。
3. 用 `Phalcon\Cache\Cache` 或 `Phalcon\Cache\CacheFactory` 组合 adapter + serializer。
4. 复核 TTL、prefix 以及 `get` / `set` / `has` / `delete` 语义。

规则在 `RenameClassRector` 之前运行，因此 checklist 指向原始 Phalcon 3 类名；同一表达式上的类重命名仍会正常执行，例如 `Phalcon\Cache\Backend\File` → `Phalcon\Cache\Adapter\Stream`。

#### 4.4.4 `LoggerMigrationHintRector`

针对 `Phalcon\Logger\Multiple` 输出结构化的 4 步 checklist，并从通用 removed-class 提示中移除该类，避免重复 TODO。

checklist 内容：

1. 用单个 `Phalcon\Logger\Logger` 替换 `Phalcon\Logger\Multiple`。
2. 用 `Phalcon\Logger\AdapterFactory` 或 `Phalcon\Logger\Adapter\*` 创建每个目标，旧的 File adapter 对应 v4 Stream。
3. 用 `Logger::addAdapter()` / `setAdapters()` 或 `LoggerFactory::newInstance()` 注册 adapter，替代 `Multiple::push()`。
4. 复核 formatter、log level 以及 transaction/commit 行为。

规则只匹配 `Phalcon\Logger\Multiple` 的导入和使用，不改写代码。`Firephp`、`Syslog` formatter 等仍由通用 removed-class 提示处理。

#### 4.4.5 `SessionMigrationHintRector`

针对 `Phalcon\Session\Factory`、`Phalcon\Session\Adapter`、`Phalcon\Session\Adapter\Files` 和 `Phalcon\Session\Adapter\Memcache` 输出结构化的 4 步 checklist，并从通用 removed-class 提示中移除 `Memcache`，避免重复 TODO。

checklist 内容：

1. 用 `Phalcon\Session\Manager` 替换 `Phalcon\Session\Factory`。
2. 用 `Phalcon\Session\Adapter\*` 替换旧 adapter：`Files` → `Stream`，`Memcache` → `Libmemcached` 或 `Redis`。
3. 用 `Manager::setAdapter()` 注册 adapter，并显式调用 `Manager::start()`，替代旧 factory 装配。
4. 复核 session name、id、options 以及 DI 服务注册。

规则只输出提示，不改写 wiring。`Factory` → `Manager` 和 `Files` → `Stream` 的确定部分仍由类映射处理；`Memcache` 因目标选择依赖业务环境，只保留 checklist。

#### 4.4.6 Filter 自动改写族

原先只对 Filter 输出 checklist，但 Phalcon 4 的 `Phalcon\Filter\Filter` 无参构造不再提供旧默认 sanitizer 集合；只改类名会留下运行时有问题的中间态。因此改为三条自动规则 + 一个缩窄的 TODO 规则。

##### `FilterFactoryNewInstanceRector`

无参 `new Phalcon\Filter()`、`new \Phalcon\Filter()` 和 `new Phalcon\Filter\Factory()` 自动改写为：

```php
(new \Phalcon\Filter\FilterFactory())->newInstance();
```

这样 DI closure 中的旧构造也会得到正确的默认 sanitizer mapper。带自定义 mapper 参数的构造不自动替换。

##### `FilterAddToSetRector`

Phalcon 3 的 `$filter->add($name, $service)` 在 Phalcon 4 中变为 `$filter->set($name, $service)`。规则只处理能推断为 `Phalcon\Filter`、`Phalcon\Filter\Filter` 或 `Phalcon\Filter\FilterInterface` 的调用。

##### `FilterSanitizerNameRector`

常量已经由 `RenameClassConstFetchRector` 处理；字符串字面量另行处理，但只在确定上下文中改写：

- `Filter::sanitize($value, 'alphanum')` → `'alnum'`
- `Filter::sanitize($value, 'double')` → `'float'`
- `Request::getPost($name, 'alphanum')` → `'alnum'`

调用者需要能推断为 Phalcon Filter / Request 类型，避免误改业务对象的同名字符串参数。

##### `FilterMigrationHintRector`

TODO 只保留给带自定义 mapper 参数的旧 Filter 构造，以及无法安全自动处理的场景。

checklist 内容：

1. 显式给 `Phalcon\Filter\Filter` 传 sanitizer mapper，或通过 `Phalcon\Filter\FilterFactory::newInstance()` 注册。
2. Phalcon 4 Filter 不再提供旧默认 sanitizer 集合，逐个复核配置。
3. DI / 服务类型使用 `Phalcon\Filter\FilterInterface`。
4. 复核 `sanitize()` 调用与 sanitizer 名称。

#### 4.4.7 `ModelSaveWithDataRector`

把独立的 `$model->save($data, $whiteList = null)` 语句改写为：

```php
$model->assign($data, $whiteList = null);
$model->save();
```

只处理 `Expression` 中的独立方法调用，且要求调用者能被解析为 `Phalcon\Mvc\Model` 或其子类。赋值表达式中的 `save($data)` 不改写，避免改变返回值语义。无参数 `save()` 保持原样。

#### 4.4.8 `AddHandleUriArgumentRector`

对 `Phalcon\Mvc\Application`、`Phalcon\Mvc\Micro` 和 `Phalcon\Application\AbstractApplication` 上无参数的 `handle()` 调用自动补上 URI 参数。

URI 表达式按以下顺序选择：

1. 当前作用域有 `Phalcon\Http\Request` 或 `Phalcon\Http\RequestInterface` 类型的 `$request` 时，补 `$request->getURI()`。
2. 当前作用域有 `Phalcon\Di\DiInterface` 类型的 `$di` 时，补 `$di->get('request')->getURI()`。
3. 都没有时，补 `\Phalcon\Di\Di::getDefault()->get('request')->getURI()`。

已有 URI 参数的调用以及无关对象的 `handle()` 会跳过。规则只补参数，不修改 DI 注册、rewrite rules 或路由配置；这些仍需人工复核。

#### 4.4.9 `GetSourceToSetSourceRector`

对继承 `Phalcon\Mvc\Model` 的类做结构改写。

对于固定形态的 `getSource()`：

```php
public function getSource()
{
    return 'robots';
}
```

自动删除该方法，并改为：

```php
public function initialize()
{
    $this->setSource('robots');
}
```

如果类中已有 `initialize()`，则在方法开头插入 `$this->setSource(...)`，保留原有逻辑。

说明：

- 不会把子类方法真的改名为 `setSource()`：Phalcon 4 父类 `Model::setSource()` 是 `final protected`，子类定义同名方法会 fatal。
- 只自动改写单一 `return <expr>;` 的固定形态；表达式会原样移到 `setSource()` 参数中。
- 复杂 `getSource()` 方法保留 3 步 TODO，提示人工提取表名并在 `initialize()` 中调用 `setSource()`。
- 已有 TODO 标记的方法不会重复添加注释。

### 4.5 辅助类

#### `ClassRenames`

职责：

- Phalcon 3 → 4 类名重命名的单一数据源。
- 被 `RenameClassRector` 和 `RenameUseImportsRector` 共享。
- 避免规则之间映射不一致。

#### `Phalcon4SetList`

职责：

- 定义 Phalcon 4 阶段内部 set 路径。
- 当前只暴露 `BASELINE`，指向 `src/Phalcon4/Config/Set/phalcon40.php`。
- 公共 `PhalconSetList::UP_PHALCON_3_TO_4` 从此处聚合。

#### `ImportShortener`（预留）

职责：

- 为后续 P1/P2 新引入的 Phalcon 类补 `use` 并返回短名。
- 当前 P0 没有实际新增 Phalcon 类的规则，因此暂未启用。

---

## 五、测试体系

### 5.1 目录结构

```text
src/Phalcon4/Tests/
├── Phalcon40Set/
│   ├── Phalcon40SetTest.php
│   ├── config/configured_rule.php
│   └── Fixture/*.php.inc
└── Rector/
    ├── ClassConstFetch/
    │   ├── RenameDbConstFetchRector/
    │   └── RenameFilterConstFetchRector/
    ├── Expression/
    │   └── ModelSaveWithDataRector/
    ├── MethodCall/
    │   ├── AddHandleUriArgumentRector/
    │   ├── FilterAddToSetRector/
    │   ├── FilterSanitizerNameRector/
    │   └── RenameCliConsoleAddModulesRector/
    ├── New_\
    │   └── FilterFactoryNewInstanceRector/
    ├── PropertyFetch/
    │   └── RenamePaginatorBeforePropertyRector/
    └── Stmt/
        ├── CacheMigrationHintRector/
        ├── FilterMigrationHintRector/
        ├── GetSourceToSetSourceRector/
        ├── LoggerMigrationHintRector/
        ├── RenameUseImportsRector/
        ├── RemovedClassUsageHintRector/
        └── SessionMigrationHintRector/
```

当前 fixture：

- `Phalcon40Set`：18 个 `.php.inc`，覆盖 Filter 工厂化、`add()` → `set()`、sanitizer 字符串、`Model::save($data)`、`handle()` URI、`getSource()` 自动改写，以及类重命名/常量规则协作。
- `RenameUseImportsRector`：2 个 `.php.inc`
- `RenameFilterConstFetchRector`：1 个 `.php.inc`
- `RenameDbConstFetchRector`：1 个 `.php.inc`
- `RemovedClassUsageHintRector`：2 个 `.php.inc`
- `CacheMigrationHintRector`：1 个 `.php.inc`
- `LoggerMigrationHintRector`：1 个 `.php.inc`
- `SessionMigrationHintRector`：1 个 `.php.inc`
- `FilterFactoryNewInstanceRector`：1 个 `.php.inc`
- `FilterAddToSetRector`：1 个 `.php.inc`
- `FilterSanitizerNameRector`：1 个 `.php.inc`
- `FilterMigrationHintRector`：1 个 `.php.inc`
- `GetSourceToSetSourceRector`：1 个 `.php.inc`
- `AddHandleUriArgumentRector`：3 个 `.php.inc`
- `ModelSaveWithDataRector`：1 个 `.php.inc`
- `RenamePaginatorBeforePropertyRector`：1 个 `.php.inc`
- `RenameCliConsoleAddModulesRector`：1 个 `.php.inc`

### 5.2 测试类型

单规则测试：

- 目录与 `src/Phalcon4/Rector/` 一一对应。
- 每个规则有自己的 `config/configured_rule.php`，只启用该规则。
- 继承 `Rector\Testing\PHPUnit\AbstractRectorTestCase`。
- 使用 `#[DataProvider]` 从 `Fixture/` 读取用例。

规则集测试：

- `Phalcon40Set` 加载完整 `src/Phalcon4/Config/Set/phalcon40.php`。
- 验证多规则协作场景，例如类重命名、`use` 改写、Filter/DB 常量重命名、Filter 工厂化与 sanitizer 字符串改写、ACL/Paginator/CLI 方法重命名、`Text` → `Helper\Str`、Paginator 属性重命名、`Model::save($data)`、`handle()` URI、`getSource()` → `initialize()` + `setSource()` 自动改写、Cache / Logger / Session checklist、已移除类提示同时发生。

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

CI 使用 `.github/workflows/tests.yml` 分阶段切换 stubs：

| Job | stubs | 执行范围 |
|---|---|---|
| `phalcon40` | `phalcon/ide-stubs:^4.0` | `--filter Phalcon4`，覆盖 3 → 4 单规则与 `Phalcon40Set` |
| `phalcon50` | `phalcon/ide-stubs:^5.0` | 全量 PHPUnit |

`phalcon40` 阶段不跑全量测试，是为了避免用 `^4.0` stubs 验证依赖 `^5.0` 类型解析的 4 → 5 方法重命名规则。

Windows 本地开发命令：

```powershell
Set-Location "d:\opt\htdocs\rector-phalcon"

& "D:\software\php\php8.4.27\php.exe" "vendor\phpunit\phpunit\phpunit" --colors=never

& "D:\software\php\php8.4.27\php.exe" "vendor\rector\rector\bin\rector" process src --config "src\Phalcon4\Config\Set\phalcon40.php" --no-progress-bar
```

---

## 六、关键设计决策

### 6.1 单一映射源

类名映射集中在 `src/Phalcon4/ClassRenames.php`，避免 `RenameClassRector` 与 `RenameUseImportsRector` 各维护一份表。

收益：

- 新增/修改映射只改一处。
- `use` 导入与内联引用保持一致。
- 前缀映射和精确映射统一解析。

### 6.2 引用风格保持

重命名只改变“指向”，不改变“写法”。

例如：

```text
new \Phalcon\Mvc\Url()
  → new \Phalcon\Url()

use Phalcon\Mvc\Url; new Url()
  → use Phalcon\Url; new Url()
```

这是通过自定义规则先于 `RenameClassRector` 执行实现的。

### 6.3 只处理 Phalcon 符号

规则集关闭全局 `importNames()`。

`RenameUseImportsRector` 只处理映射范围内的 Phalcon 类。

因此：

- `new \PDO()` 不会被改成 `use PDO;`。
- 业务类、第三方库不受影响。
- 未映射的 Phalcon 类保持原样。

### 6.4 确定性子集优先

只有语义确定的变更才自动改写。

例如：

- `Phalcon\Acl\Resource` → `Phalcon\Acl\Component` 可自动改。
- `Phalcon\Cache\Frontend\*` 无确定机械迁移方案，只加 TODO。
- `Model::save([...])` 依赖业务语义，暂不自动改。
- `Application::handle()` 按 `$request` / `$di` / `Di::getDefault()` 的确定顺序自动补 URI。

### 6.5 TODO 幂等

`RemovedClassUsageHintRector` 添加的注释带标记。

重复运行 Rector 不会叠加注释。

### 6.6 复用 Base 抽象基类

Phalcon 4 规则不继承 Phalcon 5 的具体规则。

共享能力放在：

```text
src/Base/Rector/Stmt/AbstractRenameUseImportsRector.php
src/Base/Rector/Stmt/AbstractRemovedClassUsageHintRector.php
```

版本规则只提供映射、提示清单和 RuleDefinition。

### 6.7 反射依赖最小化

类重命名、`use` 导入改写、TODO 提示以及 Cache / Logger checklist 均由名称或映射表驱动，不依赖反射。

ACL / Paginator 方法重命名使用 `RenameMethodRector`，这部分需要可解析的 Phalcon 4 类型。`RenamePaginatorBeforePropertyRector` 只需要识别分页结果赋值来源。`RenameCliConsoleAddModulesRector` 需要识别 `Phalcon\Cli\Console` 类型，但不需要读取目标方法定义。Filter / DB 常量重命名按类名和常量名匹配，不读取目标常量定义。`FilterFactoryNewInstanceRector` 只按旧类名改写无参构造，`FilterSanitizerNameRector` 只按方法名、对象类型与字符串字面量改写。`phalcon/ide-stubs:^4.0` CI job 已建立，用户侧也建议在该阶段前切换到该版本 stubs。

---

## 七、已知限制

1. 当前已实现 P0、P1 与全部 P2 规划规则：机械改写、方法/常量重命名、Filter 工厂化与 sanitizer 字符串改写、Cache / Logger / Session checklist，以及 `Model::save($data)`、`handle()` URI、`getSource()` → `initialize()` + `setSource()` 自动改写。
2. 结构性迁移仍以人工确认为主。
   - Cache、Logger\Multiple、Session 输出 checklist 或 TODO，但不自动重构架构。
   - 带自定义 mapper 的 Filter 构造、复杂 `getSource()` 方法只保留 TODO；`Model::save($data)` 的多行/复杂表达式仍需人工确认；`handle()` URI 只补参数，不修改 DI 注册与路由配置。
3. ACL / Paginator 方法重命名、`Model::save($data)` 和 `handle()` URI 自动补参依赖可解析的 Phalcon 4 类型；无法解析的调用会被跳过。`$before` 属性只在同一函数作用域内能确认来自 Paginator 结果时改写。CI 已覆盖 `^4.0` stubs，本机默认 dev stubs 仍是 `^5.0`。
4. 官方 4.x 升级页已 deprecated，需以 3.4 → 4.0 cheat sheet 为基线，并用 4.2 / 4.3 回归。
5. 尚未在真实 Phalcon 3.4 项目完成端到端验证。
6. 3 → 5 直接升级的完整度仍受真实项目验证结果限制。

---

## 八、Roadmap 与后续计划

### 8.0 通用架构与接入（已完成）

- [x] 从 `feature/phalcon-3-to-4` 迁移 P0 实现到当前 `develop` 目录结构。
- [x] 命名空间统一为 `RectorPhalcon\Phalcon4\...`。
- [x] 规则集入口迁移到 `src/Phalcon4/Config/Set/phalcon40.php`。
- [x] 新增 `src/Phalcon4/Set/Phalcon4SetList.php`。
- [x] 公共 `PhalconSetList` 暴露 `UP_PHALCON_3_TO_4` 与 `UP_TO_PHALCON_40`。
- [x] `COMPOSER_BASED` 支持目标 Phalcon 4 时选择 3 → 4 规则集。
- [x] 不启用 `rector/extension-installer` 自动加载。
- [x] 测试与公共 SetList 测试覆盖 3 → 4 常量路径。

### 8.1 P0：高置信机械改写（已完成）

- [x] `src/Phalcon4/Config/Set/phalcon40.php` 规则集骨架。
- [x] `ClassRenames::MAP` 精确类映射。
- [x] `ClassRenames::PREFIX_MAP` 命名空间前缀映射。
- [x] `RenameClassRector` 接入 3 → 4 映射。
- [x] `RenameUseImportsRector` 改写旧 `use` 导入并保持引用风格。
- [x] `RemovedClassUsageHintRector` 对已移除类输出 TODO。
- [x] `Phalcon40Set` 多规则协作测试。
- [x] 两个单规则测试目录及 fixture。
- [x] 非 Phalcon 类与无关 `use` 保持原样验证。

### 8.2 P1：方法与常量迁移（进行中）

- [x] 建立 `phalcon/ide-stubs:^4.0` CI job。
- [x] ACL 方法重命名：
  - `isResource()` → `isComponent()`
  - `addResource()` → `addComponent()`
  - `getResources()` → `getComponents()`
  - `getActiveResource()` → `getActiveComponent()`
- [x] Paginator：
  - `getPaginate()` → `paginate()`
  - `$before` → `$previous`
- [x] CLI：
  - `Console::addModules()` → `registerModules($modules, true)`
- [x] Filter 常量：
  - `FILTER_ALPHANUM` → `FILTER_ALNUM`
  - `FILTER_APHANUM` → `FILTER_ALNUM`（兼容升级指南拼写）
  - `FILTER_SPECIAL_CHARS` → `FILTER_SPECIAL`
- [x] DB 常量迁移到 `Phalcon\Db\Enum`：
  - 17 个 Phalcon 3.4 `FETCH_*` 常量统一改写为 `Phalcon\Db\Enum::FETCH_*`
  - 清单集中在 `src/Phalcon4/DbConstants.php`
- [x] `Phalcon\Text` → `Phalcon\Helper\Str`：静态方法与 `RANDOM_*` 常量由类映射统一改写。
- [x] 为上述规则补 fixture、规则集测试和文档更新。

### 8.3 P2：结构性迁移提示（已完成）

- [x] Cache Frontend/Backend → Adapter + Serializer + Cache 的 TODO/checklist。
- [x] Logger Multiple → 单 Logger + 多 Adapter 的 TODO/checklist。
- [x] Session Factory → Manager，以及 Adapter 变化的 TODO/checklist。
- [x] Filter 无参构造 → `FilterFactory::newInstance()`；`add()` → `set()`；sanitizer 字符串改写；复杂构造保留 TODO。
- [x] `Model::save([...])` → `assign([...]); save()` 的固定形态改写。
- [x] `Application` / `Micro::handle()` 缺少 URI 时自动补参。
- [x] `getSource()` → `initialize()` + `setSource()` 自动改写；复杂方法保留 TODO。

### 8.4 验证与发布（待实施）

- [ ] 找真实 Phalcon 3.4 项目执行 `--dry-run`。
- [ ] 人工审查 diff，并在测试环境运行应用与测试。
- [ ] 验证 3 → 4 规则跑在已迁移的 4.x 代码上基本 no-op。
- [ ] 建立 PHP 8.1 / 8.2 / 8.3 / 8.4 兼容性检查。
- [ ] 完成 Packagist 发布准备。
- [ ] 在 `plan/PHALCON3_TO_5_PLAN.md` 中更新 direct-upgrade 完整度状态。

---

## 九、维护与扩展指南

### 9.1 新增类名映射

步骤：

1. 在 `src/Phalcon4/ClassRenames.php` 的 `MAP` 增加精确映射，或在 `PREFIX_MAP` 增加前缀映射。
2. 写单规则 fixture，覆盖：
   - 内联 FQCN。
   - `use` 短名。
   - 分组 use。
   - 别名。
   - docblock。
3. 跑 `RenameUseImportsRector` 测试和 `Phalcon40Set` 测试。
4. 更新本文件的类名映射摘要。

### 9.2 新增方法重命名

步骤：

1. 确认目标方法在 Phalcon 4 stubs 中存在。
2. 建立或切换到 `phalcon/ide-stubs:^4.0` 测试环境。
3. 在 `src/Phalcon4/Config/Set/phalcon40.php` 的 `RenameMethodRector` 列表增加 `MethodCallRename`，类名使用 Phalcon 4 目标名称。
4. 写 fixture，覆盖正常对象调用、接口类型调用和无关类保持原样。
5. 更新本文件的方法重命名表。

### 9.3 新增自定义规则

步骤：

1. 按节点类型选择目录：
   - `src/Phalcon4/Rector/New_/`
   - `src/Phalcon4/Rector/StaticCall/`
   - `src/Phalcon4/Rector/Stmt/`
   - 必要时新增 `src/Phalcon4/Rector/ClassConstFetch/`
2. 能复用 Base 抽象基类时优先复用，不复制 v5 规则实现。
3. 如规则新引入 Phalcon 类，通过版本特定 `ImportShortener` 补 `use`。
4. 写 fixture：
   - 成功改写。
   - 原样跳过。
   - 重复运行幂等。
5. 在 `src/Phalcon4/Config/Set/phalcon40.php` 的自定义规则数组中注册。
6. 必须放在 `RenameClassRector` 之前。
7. 更新本文件的规则清单和 Roadmap。

### 9.4 新增规则时的稳定性检查

- 是否只处理 Phalcon 类？
- 是否保持了原始引用风格？
- 是否依赖 stubs？如果依赖，文档是否说明？
- 是否会产生会叠加的注释？
- 是否在 `Phalcon40Set` 中验证与其他规则协作？
- 是否更新本文件的“支持的规则”和“已知限制”？

---

## 十、关键文件索引

```text
composer.json
  - 包类型、autoload、显式规则集接入约定（无 extra.rector.includes）

src/Phalcon4/Config/Set/phalcon40.php
  - 3 → 4 规则集入口
  - 自定义规则注册
  - Filter / DB RenameClassConstFetchRector 配置
  - RenameClassRector 配置
  - ACL / Paginator RenameMethodRector 配置
  - Repository RenamePropertyRector 补充配置
  - importNames(false, false)

src/Phalcon4/ClassRenames.php
  - 类名映射单一数据源
  - MAP / PREFIX_MAP / resolveNewClass()

src/Phalcon4/DbConstants.php
  - Phalcon 3.4 Db FETCH_* 常量清单

src/Phalcon4/Set/Phalcon4SetList.php
  - Phalcon 4 阶段内部 set 常量

src/Phalcon4/ImportShortener.php
  - 后续 P1/P2 新引入 Phalcon 类时补 use

src/Phalcon4/Rector/Stmt/CacheMigrationHintRector.php
  - 输出 Cache Frontend/Backend → Adapter + Serializer + Cache 的 4 步 checklist

src/Phalcon4/Rector/Stmt/LoggerMigrationHintRector.php
  - 输出 Logger\Multiple → 单 Logger + 多 Adapter 的 4 步 checklist

src/Phalcon4/Rector/Stmt/SessionMigrationHintRector.php
  - 输出 Session Factory / Adapter 变化的 4 步 checklist

src/Phalcon4/Rector/New_/FilterFactoryNewInstanceRector.php
  - 无参旧 Filter 构造 → FilterFactory::newInstance()

src/Phalcon4/Rector/MethodCall/FilterAddToSetRector.php
  - Filter::add() → Filter::set()

src/Phalcon4/Rector/MethodCall/FilterSanitizerNameRector.php
  - Filter/Request 上下文中的旧 sanitizer 字符串 → v4 名称

src/Phalcon4/Rector/Stmt/FilterMigrationHintRector.php
  - 带自定义 mapper 的旧 Filter 构造保留 4 步 TODO

src/Phalcon4/Rector/Stmt/GetSourceToSetSourceRector.php
  - 把固定形态 getSource() 改写为 initialize() + setSource()
  - 复杂方法保留人工迁移 TODO

src/Phalcon4/Rector/MethodCall/AddHandleUriArgumentRector.php
  - 自动为 Application/Micro::handle() 补 URI 参数
  - 优先使用 $request / $di，回退到 Di::getDefault()

src/Phalcon4/Rector/Expression/ModelSaveWithDataRector.php
  - 把 Model::save($data) 拆成 assign($data) + save()

src/Phalcon4/Rector/MethodCall/RenameCliConsoleAddModulesRector.php
  - 改写 CLI Console addModules() → registerModules() 并保留合并语义

src/Phalcon4/Rector/PropertyFetch/RenamePaginatorBeforePropertyRector.php
  - 根据分页结果赋值来源改写 $before → $previous

src/Phalcon4/Rector/Stmt/RenameUseImportsRector.php
  - 继承 Base 抽象类，提供 Phalcon 3 → 4 映射

src/Phalcon4/Rector/Stmt/RemovedClassUsageHintRector.php
  - 继承 Base 抽象类，提供 Phalcon 4 removed 清单

src/Phalcon4/Tests/
  - Phalcon40Set/
  - Rector/ClassConstFetch/
  - Rector/MethodCall/
  - Rector/PropertyFetch/
  - Rector/Stmt/

src/Base/Rector/Stmt/
  - 版本无关的规则基类

README.md
  - 简要使用入口；规则明细见 plan/PHALCON3_TO_4_PLAN.md

phpunit.xml
  - PHPUnit bootstrap 与 testsuite 配置
```

---

## 十一、与 3 → 5 直接升级的关系

本文档描述的是当前已落地的 3 → 4 P0 规则集。

3 → 5 直接升级规划见 `plan/PHALCON3_TO_5_PLAN.md`。它应复用本阶段作为第一阶段：

```text
阶段 1：src/Phalcon4/Config/Set/phalcon40.php
阶段 2：src/Phalcon5/Config/Set/phalcon50.php
```

当前约束：

- 两个阶段必须使用独立 Rector 进程。
- 不在一次运行中同时加载两个 set。
- 3 → 5 的完整度受本规划 P1/P2 完成度限制。
- 3 → 4 的 TODO 提示使用 `TODO Phalcon v4`，4 → 5 使用 `TODO Phalcon v5`，不会互相覆盖。
