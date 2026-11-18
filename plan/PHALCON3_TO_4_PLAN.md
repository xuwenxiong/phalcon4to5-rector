# Phalcon 3 → 4 自动升级规则集实施规划

> 状态：P0 已实施，P1 的 stubs CI 已实施，其余 P1/P2 持续迭代中。
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

当前 P0 未启用方法重命名。

P1 计划使用 Rector 内置 `RenameMethodRector` + `MethodCallRename` 配置，集中处理确定性的方法名变化，例如 ACL、Paginator、CLI 相关方法。

引入前需要先准备 `phalcon/ide-stubs:^4.0` 测试矩阵。

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
| ACL | `Phalcon\Acl\Resource` → `Phalcon\Acl\Component` |
| Annotations | `Phalcon\Annotations\Adapter\Files` → `Phalcon\Annotations\Adapter\Stream` |
| Application | `Phalcon\Application` → `Phalcon\Application\AbstractApplication` |
| Assets | `Phalcon\Assets\Resource` → `Phalcon\Assets\Asset` |
| Cache | `Phalcon\Cache\Backend\File` → `Phalcon\Cache\Adapter\Stream` |
| Config | `Phalcon\Config\Factory` → `Phalcon\Config\ConfigFactory` |
| DB | `Phalcon\Db\Adapter\Pdo\Factory` → `Phalcon\Db\Adapter\PdoFactory` |
| Logger | `Phalcon\Logger\Adapter\File` → `Phalcon\Logger\Adapter\Stream` |
| MVC | `Phalcon\Mvc\Collection` → `Phalcon\Collection` |
| Paginator | `Phalcon\Paginator\Factory` → `Phalcon\Paginator\PaginatorFactory` |
| Session | `Phalcon\Session\Factory` → `Phalcon\Session\Manager` |
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

当前 P0 未实现。

P1 计划覆盖：

| 类/组件 | 旧方法 | 新方法 |
|---|---|---|
| ACL Adapter/Component/Role | `isResource()` / `addResource()` / `getResources()` | `isComponent()` / `addComponent()` / `getComponents()` |
| Paginator | `getPaginate()` | `paginate()` |
| Paginator | `$before` | `$previous` |
| CLI Console | `addModules()` | `registerModules()` |

依赖说明：

- `RenameMethodRector` 依赖 `phalcon/ide-stubs` 提供类定义做类型解析。
- 无法解析的类型会被跳过。
- `RenameUseImportsRector` 不依赖反射，因为它由映射表驱动。

### 4.3 自定义规则

#### 4.3.1 `RenameUseImportsRector`

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

#### 4.3.2 `RemovedClassUsageHintRector`

对 Phalcon 4 中已移除且无自动迁移方案的类加 TODO 注释。

它不改写代码，只让手工迁移点在 diff 中可见。

已覆盖类别：

- ACL：`Phalcon\Acl`
- Assets filters：`CssMin`、`JsMin`
- 旧 Cache Backend / Frontend：`Apc`、`Memcache`、`Mongo`、`Xcache`、`Base64`、`Data`、`Json`、`IgBinary`、`Factory` 等
- `Phalcon\Cache\Multiple`
- `Phalcon\Image`
- 旧 Logger adapter/formatter：`Firephp`、`Syslog`、`Logger\Multiple`
- 旧 Collection behavior/document/manager
- 旧 Model metadata adapter：`Apc`、`Memcache`、`Session`、`Xcache`
- `Phalcon\Queue\Beanstalk*`
- `Phalcon\Session\Adapter\Memcache`
- `Phalcon\Translate`

设计取舍：

- 提示清单只包含“没有确定自动迁移方案”的类。
- 有确定迁移方案的类交给类名映射或后续规则处理。
- TODO 注释带标记，重复运行不会叠加。

### 4.4 辅助类

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
    └── Stmt/
        ├── RenameUseImportsRector/
        └── RemovedClassUsageHintRector/
```

当前 P0 fixture：

- `Phalcon40Set`：4 个 `.php.inc`
- `RenameUseImportsRector`：2 个 `.php.inc`
- `RemovedClassUsageHintRector`：2 个 `.php.inc`

### 5.2 测试类型

单规则测试：

- 目录与 `src/Phalcon4/Rector/` 一一对应。
- 每个规则有自己的 `config/configured_rule.php`，只启用该规则。
- 继承 `Rector\Testing\PHPUnit\AbstractRectorTestCase`。
- 使用 `#[DataProvider]` 从 `Fixture/` 读取用例。

规则集测试：

- `Phalcon40Set` 加载完整 `src/Phalcon4/Config/Set/phalcon40.php`。
- 验证多规则协作场景，例如类重命名、`use` 改写、已移除类提示同时发生。

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
- `Application::handle()` URI 参数来源不确定，暂不自动改。

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

当前 P0 不用 `RenameMethodRector`，因此不依赖 stubs 做类型解析。

P1 引入方法重命名时才需要 `phalcon/ide-stubs:^4.0` 矩阵，用户侧也建议在该阶段前切换到该版本 stubs，以保证方法调用的类型可解析。

---

## 七、已知限制

1. 当前只实现 P0：类名/命名空间、`use` 导入、已移除类 TODO 提示。
2. 方法重命名尚未实现。
   - ACL / Paginator / CLI 等 1:1 方法变化待 P1。
3. 常量迁移尚未实现。
   - Filter、DB 常量待 P1。
4. 结构性迁移未实现。
   - Cache、Logger、Session、Filter 只输出 TODO 或保持原样。
5. `Phalcon\Text` → `Phalcon\Helper\Str` 尚未实现，待 P1。
6. 当前 dev stubs 是 `^5.0`，P1 方法重命名前必须建立 `^4.0` stubs 测试矩阵。
7. 官方 4.x 升级页已 deprecated，需以 3.4 → 4.0 cheat sheet 为基线，并用 4.2 / 4.3 回归。
8. 尚未在真实 Phalcon 3.4 项目完成端到端验证。
9. 3 → 5 直接升级的完整度仍受本阶段 P1/P2 完成度限制。

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

### 8.2 P1：方法与常量迁移（待实施）

- [x] 建立 `phalcon/ide-stubs:^4.0` CI job。
- [ ] ACL 方法重命名：
  - `isResource()` → `isComponent()`
  - `addResource()` → `addComponent()`
  - `getResources()` → `getComponents()`
  - `getActiveResource()` → `getActiveComponent()`
- [ ] Paginator：
  - `getPaginate()` → `paginate()`
  - `$before` → `$previous`
- [ ] CLI：
  - `Console::addModules()` → `registerModules()`
- [ ] Filter 常量：
  - `FILTER_APHANUM` → `FILTER_ALNUM`
  - `FILTER_SPECIAL_CHARS` → `FILTER_SPECIAL`
- [ ] DB 常量迁移到 `Phalcon\Db\Enum`。
- [ ] `Phalcon\Text` → `Phalcon\Helper\Str`。
- [ ] 为上述规则补 fixture、规则集测试和文档更新。

### 8.3 P2：结构性迁移提示（待实施）

- [ ] Cache Frontend/Backend → Adapter + Serializer + Cache 的 TODO/checklist。
- [ ] Logger Multiple → 单 Logger + 多 Adapter 的 TODO/checklist。
- [ ] Session Factory → Manager，以及 Adapter 变化的 TODO/checklist。
- [ ] Filter 对象 → `FilterLocator` / `FilterLocatorFactory` 的 TODO/checklist。
- [ ] `Model::save([...])` → `assign([...]); save()` 的固定形态改写或提示。
- [ ] `Application` / `Micro::handle()` 缺少 URI 的人工检查提示。
- [ ] `getSource()` 变 final 的迁移提示：`initialize()` + `setSource()`。

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
3. 在 `src/Phalcon4/Config/Set/phalcon40.php` 的 `RenameMethodRector` 列表增加 `MethodCallRename`。
4. 写 fixture，覆盖正常对象调用和无法解析类型时的跳过行为。
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
  - RenameClassRector 配置
  - importNames(false, false)

src/Phalcon4/ClassRenames.php
  - 类名映射单一数据源
  - MAP / PREFIX_MAP / resolveNewClass()

src/Phalcon4/Set/Phalcon4SetList.php
  - Phalcon 4 阶段内部 set 常量

src/Phalcon4/ImportShortener.php
  - 后续 P1/P2 新引入 Phalcon 类时补 use

src/Phalcon4/Rector/Stmt/RenameUseImportsRector.php
  - 继承 Base 抽象类，提供 Phalcon 3 → 4 映射

src/Phalcon4/Rector/Stmt/RemovedClassUsageHintRector.php
  - 继承 Base 抽象类，提供 Phalcon 4 removed 清单

src/Phalcon4/Tests/
  - Phalcon40Set/
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
