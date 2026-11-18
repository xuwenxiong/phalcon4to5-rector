# Phalcon 3 → 4 自动升级支持规划

> 状态：规划稿。本文只描述设计与实施顺序，不包含当前分支代码改动。
>
> 当前目录结构与命名空间以 `plan/DIRECTORY_STRUCTURE_PLAN.md` 为准；文中保留的版本脉络和实现细节作为历史规划记录。
>
> 当前项目：`xuwenxiong/rector-phalcon`，已支持 Phalcon 4 → 5（规则集 `src/Phalcon5/Config/Set/phalcon50.php`）。
>
> 目标：在同一项目中增加 Phalcon 3 → 4 自动升级能力。

### 规则集命名约定

`src/Phalcon5/Config/Set/phalcon50.php` 中的 `50` 表示“目标 Phalcon 5.0 基线”，即从 Phalcon 4.x 升级到 Phalcon 5.x 的基础规则集，不是只支持 5.0.0，也不是 50.x。当前规则集已验证到 Phalcon 5.23.0。后续若 Phalcon 5.x 小版本引入破坏性变更，将采用附加规则集（如 `phalcon513.php`），不重命名基础规则集。

同理，规划中的 `src/Phalcon4/Config/Set/phalcon40.php` 表示“目标 Phalcon 4.0 基线”，负责 Phalcon 3.x → 4.x 的基础升级规则。

`extra.rector.includes` 继续只登记 `phalcon50.php`，避免多套规则被 extension-installer 无条件同时加载。

---

## 一、总体结论

建议仍放在当前包中，做成“多规则集”结构：

- `src/Phalcon4/Config/Set/phalcon40.php`：负责 Phalcon 3 → 4。
- `src/Phalcon5/Config/Set/phalcon50.php`：保持现有 Phalcon 4 → 5，不破坏现有用户。

但有一个关键决策：**3 → 4 规则集不要默认加入 `composer.json` 的 `extra.rector.includes` 自动加载**。

原因是当前自动加载链路是：

```text
composer install / update
  └─ rector/extension-installer 扫描 vendor/ 下所有 composer.json
      └─ 找出 "type": "rector-extension" 的包
          └─ 读取 extra.rector.includes
              └─ 汇总生成 vendor/rector/extension-installer/src/GeneratedConfig.php

rector 启动
  └─ 自动 include GeneratedConfig.php
      └─ 加载其中登记的所有规则配置文件
```

这个机制是**无条件加载**的。如果把 `phalcon40.php` 和 `phalcon50.php` 都登记进去，那么一个 Phalcon 3 项目、Phalcon 4 项目、Phalcon 5 项目都会同时跑两套规则，容易误伤。

典型误伤例子：现有 4 → 5 规则中有 `Phalcon\Config → Phalcon\Config\Config`，但 `Phalcon\Config` 在 Phalcon 3/4 中是正常类。如果在 Phalcon 3 项目上同时加载 4 → 5 规则，会被错误改写。

因此推荐策略：

- 默认自动加载仍只保留 4 → 5，兼容现有用户。
- 3 → 4 由用户手动在 `rector.php` 中选择 `phalcon40.php`。
- 未来如需要“一键 3 → 5”，再提供显式入口（如 `phalcon34-to-50.php`），仍不建议默认自动加载。

---

## 二、先锁定边界：分阶段升级，而不是一次性混跑

建议明确支持矩阵：

| 阶段 | 源版本 | 目标版本 | 规则集 |
|---|---|---|---|
| 第一阶段 | Phalcon 3.4.x | Phalcon 4.0 / 4.2 | `src/Phalcon4/Config/Set/phalcon40.php` |
| 第二阶段 | Phalcon 4.x | Phalcon 5.x | 现有 `src/Phalcon5/Config/Set/phalcon50.php` |
| 可选串联 | Phalcon 3.4.x | Phalcon 5.x | 分两次运行，或未来提供显式全链路 set |

建议文档写成：

> 从 Phalcon 3 升级到 Phalcon 5 时，先运行 `phalcon40.php`，确认测试通过后再运行 `phalcon50.php`。不要同时启用两套规则。

另外，官方 4.x 升级页已标记为 deprecated，因此实施前应先锁定目标小版本：

- 以 3.4.x → 4.0 的 cheat sheet 作为规则基线。
- 用 4.2 / 4.3 做回归验证。
- README 中写清楚“已验证的目标版本范围”。

参考资料：

- Phalcon 4.2 Upgrade Guide：<https://docs.phalcon.io/4.2/upgrade/>
- phalcon-orphanage docs 原文：<https://github.com/phalcon-orphanage/docs/blob/e9077e2f2f98a46e46c88e697a533d4dc2dd6226/en/upgrade.md>

---

## 三、规则分层与优先级

核心原则：**机械安全的高置信改写自动做；语义变化只提示，不猜业务意图。**

### P0：高置信、机械安全，优先做

| 类型 | 例子 | 处理方式 |
|---|---|---|
| 类/接口 1:1 重命名 | `Phalcon\Acl\Resource → Phalcon\Acl\Component`、`Phalcon\Assets\Resource → Phalcon\Assets\Asset`、`Phalcon\Mvc\Collection → Phalcon\Collection`、`Phalcon\Mvc\Model\Message → Phalcon\Messages\Message`、`Phalcon\Mvc\Url → Phalcon\Url` | 复用 `RenameClassRector` + 新建 3 → 4 映射表 |
| 命名空间前缀迁移 | `Phalcon\Mvc\Model\Validator\* → Phalcon\Validation\Validator\*` | 用 `PREFIX_MAP`，由 `RenameUseImportsRector` 处理 `use`、分组 use、别名和 docblock |
| `use` 导入改写 | 旧类导入、相对引用、分组 use、冗余别名 | 复用现有 `RenameUseImportsRector` 模式 |
| 已移除类提示 | `Phalcon\Acl`、`Phalcon\Image`、`Phalcon\Translate`、`Phalcon\Cache\Frontend\*`、`Phalcon\Queue\Beanstalk*` | 复用 `RemovedClassUsageHintRector`，只加 TODO，不自动替换 |

这部分覆盖面最大、风险最低，是 MVP 的主体。

### P1：方法 / 常量迁移，只做确定子集

| 类型 | 例子 | 处理方式 |
|---|---|---|
| ACL 方法重命名 | `isResource → isComponent`、`addResource → addComponent`、`getResources → getComponents`、`getActiveResource → getActiveComponent` | `RenameMethodRector` 配置化 |
| Paginator | `getPaginate() → paginate()`、属性 `$before → $previous` | 方法重命名 + 少量自定义规则 |
| CLI | `Console::addModules() → registerModules()` | 方法重命名 |
| Filter 常量 | `FILTER_APHANUM → FILTER_ALNUM`、`FILTER_SPECIAL_CHARS → FILTER_SPECIAL` | 新增常量类规则，处理 `ClassConstFetch` |
| DB 常量 | 常量迁到 `Phalcon\Db\Enum` | 常量规则 + 明确提示人工确认 |
| `Phalcon\Text` | `Text::camelize()` → `Phalcon\Helper\Str::camelize()` | 参照 4 → 5 的 `TextAndHelperToSupportHelperRector` 做 v4 版本 |

注意：方法重命名依赖 Phalcon 4 stubs 做类型解析，无法解析的类型会被跳过。这一点必须写进 README 的“已知限制”和验收标准。

### P2：结构重构，先提示，不急着自动改写

| 类型 | 为什么不能直接自动 |
|---|---|
| Cache：Frontend / Backend → Adapter + Serializer + Cache | 架构级变化，涉及 DI 注册、序列化策略；机械替换容易“语法对、语义错” |
| Logger：`Logger\Multiple` → `Logger` + 多 Adapter | 需要重新设计日志器注册方式 |
| Session：`Session\Factory → Session\Manager`、Adapter 实现要求变化 | 服务注册方式需人工决定 |
| Filter 对象 → `FilterLocator` / `FilterLocatorFactory` | 常量、服务注册、sanitizer 名称都变了 |
| `Model::save([...]) → assign([...]); save()` | 可以规则化，但要处理返回值、错误分支；建议后置 |
| `Application` / `Micro::handle()` 需要传 URI | URI 来源依赖应用入口，不能盲猜 |
| `getSource()` 变 final | 只能检测并提示改到 `initialize()` + `setSource()` |

这些场景建议输出醒目的 TODO 注释 + README 人工检查清单，而不是静默改写。

---

## 四、目录与文件规划

建议沿用当前版本化目录习惯，新增一套 `Phalcon4`：

```text
config/
├── sets/
│   ├── phalcon40.php        # 新增：Phalcon 3 -> 4
│   └── phalcon50.php        # 现有：Phalcon 4 -> 5

rules/
├── Phalcon4/                # 新增
│   ├── Phalcon4ClassRenames.php
│   └── Rector/
│       ├── New_/
│       ├── StaticCall/
│       ├── ClassConstFetch/
│       └── Stmt/
└── Phalcon5/                # 现有

rules-tests/
├── Phalcon4/                # 新增
│   ├── Phalcon40Set/
│   └── Rector/...           # 与 rules/Phalcon4 镜像
└── Phalcon5/                # 现有
```

现有 `phalcon50.php` 的关键组织方式必须保留：

1. 自定义规则先注册。
2. Rector 内置 `RenameClassRector` 后注册。
3. `RenameUseImportsRector` 必须在 `RenameClassRector` 之前执行，否则内置规则会重建 `FullyQualified` 节点并丢失原始写法信息（内联 / `use` 短名 / 相对引用）。
4. 保持 `importNames(false, false)`，避免把 `\PDO` 等非 Phalcon FQCN 自动加 `use`。

新增 `phalcon40.php` 时沿用同样顺序。

共享能力建议逐步抽象：

- `PhalconClassRenames`：版本映射表分别是 `Phalcon4ClassRenames` / `Phalcon5ClassRenames`。
- `PhalconImportShortener`：改成通用能力，按版本映射决定哪些新引入类可以短名化。
- `RenameUseImportsRector`：抽成共享基础规则，由版本映射表驱动。
- `RemovedClassUsageHintRector`：抽成共享规则，由版本对应的 removed 清单驱动。

目标是避免复制三份高度相似的实现。

---

## 五、自动加载策略

当前 `composer.json` 中：

```text
extra.rector.includes
  └─ src/Phalcon5/Config/Set/phalcon50.php
```

推荐保持不动。

不建议改成：

```text
extra.rector.includes
  ├─ src/Phalcon4/Config/Set/phalcon40.php   # 不要默认加
  └─ src/Phalcon5/Config/Set/phalcon50.php
```

原因：`extension-installer` 生成 `GeneratedConfig.php` 时只做聚合，不判断项目当前用的是 Phalcon 3、4 还是 5；Rector 启动时也是无条件 include。多版本规则同时启用会互相污染。

推荐的用户使用方式：

- 4 → 5：继续自动加载，无需改 `rector.php`。
- 3 → 4：在 `rector.php` 中手动引用 `vendor/xuwenxiong/rector-phalcon/src/Phalcon4/Config/Set/phalcon40.php`。
- 3 → 5：先手动跑 `phalcon40.php`，再跑默认的 `phalcon50.php`；或未来提供显式 `phalcon34-to-50.php`。

如果未来一定要让两个版本都“自动”，更干净的做法是拆包：

- `xuwenxiong/rector-phalcon-34`
- `xuwenxiong/rector-phalcon-45`

这样每个包只有一个 `extra.rector.includes`，插件自动加载不会冲突。但这会增加用户安装成本，建议作为后续选项，不是当前首选。

---

## 六、命名空间与依赖调整

### 命名空间

当前 autoload 使用 `RectorPhalcon\`。继续往这个命名空间下塞 3 → 4 规则会造成语义混乱。

建议单独做一次通用化重构，例如：

```text
RectorPhalcon\Rector\Phalcon4\...
RectorPhalcon\Rector\Phalcon5\...
```

或：

```text
Xuwenxiong\RectorPhalcon\Phalcon4\...
Xuwenxiong\RectorPhalcon\Phalcon5\...
```

为了兼容现有用户，可以保留旧类名的 alias。这个重构建议独立提交，不要和 3 → 4 规则开发混在一起。

### stubs 依赖

当前 dev 依赖是 `phalcon/ide-stubs: ^5.0`。做 3 → 4 时需要 `^4.0` 的 stubs。

建议：

- 本包开发与 CI 使用矩阵：
  - 一组安装 `phalcon/ide-stubs:^4.0`，跑 `Phalcon40Set` 和 Phalcon4 单规则测试。
  - 一组安装 `phalcon/ide-stubs:^5.0`，跑 `Phalcon50Set` 和 Phalcon5 单规则测试。
- 发布包不要硬依赖某个版本的 stubs。
- 在 `suggest` 和 README 中说明：
  - 使用 3 → 4 规则：安装 `phalcon/ide-stubs:^4.0`。
  - 使用 4 → 5 规则：安装 `phalcon/ide-stubs:^5.0`。
- 尽量把不依赖反射的改写做成配置驱动，减少 stubs 缺失导致的静默跳过。

一个 `composer.lock` 无法同时固定 `^4.0` 和 `^5.0` 的 stubs，因此 CI 矩阵是必须的。

---

## 七、测试与验收规划

沿用现有 fixture 约定：

- 每条规则一个目录：`config/configured_rule.php` + `Fixture/*.php.inc`。
- 有 `-----` 分隔符的文件：上方是输入，下方是期望输出。
- 没有分隔符的 `skip_*.php.inc`：表示规则必须原样跳过。
- 增加 `src/Phalcon4/Tests/Phalcon40Set/`，验证多规则协作。

验收标准建议定为：

1. `Phalcon40Set` 在 `phalcon/ide-stubs:^4.0` 下全绿。
2. `Phalcon50Set` 在 `phalcon/ide-stubs:^5.0` 下仍全绿，无回归。
3. 每条规则重复运行两次无额外 diff（幂等）。
4. 3 → 4 规则跑在已经迁移到 4.x 的代码上应基本 no-op，不能误伤。
5. 找真实 Phalcon 3.4 项目执行 `--dry-run`，人工审查 diff，再在测试环境运行应用和测试。
6. README 明确记录“自动处理”和“必须人工处理”的边界。

---

## 八、README 需要同步调整

建议把 README 从“Phalcon 4 → 5 工具”升级为“Phalcon 多版本升级工具”。

需要改的位置：

- 标题和项目定位：支持 3 → 4、4 → 5 两套规则集。
- 使用章节：
  - 前提仍是已安装 `rector/rector`。
  - 自动加载说明注明：默认自动加载的是 4 → 5。
  - 3 → 4 通过手动 set 选择。
- 新增“升级路径”小节：3.4 → 4.x → 5.x，分阶段执行。
- 新增“Phalcon 3 → 4 支持的规则”表格，沿用现有“支持规则”的表现方式。
- 新增“3 → 4 已知限制 / 人工检查清单”，重点列：
  - Cache
  - Logger
  - Session
  - Filter
  - `Application::handle()`
  - `Model::save()`
  - 已移除组件
- 开发目录结构树加入：
  - `src/Phalcon4/Rector/`
  - `src/Phalcon4/Tests/`
  - `src/Phalcon4/Config/Set/phalcon40.php`
- Roadmap 增加 3 → 4 的完成项。

---

## 九、推荐实施顺序

1. **P0-1**：建立 `src/Phalcon4/Config/Set/phalcon40.php` 空规则集 + `src/Phalcon4/Tests/Phalcon40Set/` 测试骨架，先不注册任何规则。
2. **P0-2**：整理 3 → 4 类映射表，逐条写 fixture，再接入 `RenameClassRector` + `RenameUseImportsRector`。
3. **P0-3**：接入 removed-class TODO 提示规则。
4. **P1-1**：做 ACL、Paginator、CLI 等确定方法重命名。
5. **P1-2**：做 Filter / DB 常量迁移和 `Text → Helper\Str`。
6. **P1-3**：做 `Model::save([...]) → assign([...]); save()` 这类有固定形态的自定义规则。
7. **P2**：逐步补 Cache / Logger / Session / Filter 的结构性迁移提示。
8. **最后**：真实项目验证 + README 完整改版 + 可选的全链路 set。

如果只做最小可用版本，建议范围是：

> P0 + P1 的确定性子集：类名 / 命名空间、`use`、部分方法和常量、`Text → Helper\Str`、removed TODO 提示。

这样能安全解决大部分机械迁移，把真正需要业务判断的部分留给人工。
