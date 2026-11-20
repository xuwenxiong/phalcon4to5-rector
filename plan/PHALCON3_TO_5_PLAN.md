# Phalcon 3 → 5 直接升级实施规划

> 状态：规划稿。本文只描述设计与实施顺序，不包含代码改动。
>
> 当前基础：
>
> - 当前分支已实现 Phalcon 4 → 5：`src/Phalcon5/Config/Set/phalcon50.php`
> - Phalcon 3 → 4 基础规则已实现，详见 `plan/PHALCON3_TO_4_PLAN.md`
> - 规则集不通过 extension-installer 自动加载，由用户显式选择
>
> 目标：等 3 → 4 能力落地后，提供一个“直接升级”体验，让用户可以用一个入口/一条命令完成 Phalcon 3.x → 5.x 升级。

---

## 一、结论先行

推荐把“直接升级”实现为：

> **用户侧一次调用，内部严格分两阶段串行执行：先 `phalcon40.php`，再 `phalcon50.php`。**

不建议直接做成下面这种单一 Rector set：

```text
src/Upgrade/Config/Set/phalcon30-to-50.php
  └─ 同时 include phalcon40.php + phalcon50.php
```

原因不是“不能拼”，而是**不安全**：Rector 会把两套规则合并到同一次遍历中，而本项目已有规则依赖中间 AST 状态和原始 token 信息，跨版本混跑会产生难以预测的结果。

因此直接升级的准确语义应该是：

```text
一次用户指令
  └─ 第一阶段：Phalcon 3 → 4，完整跑完并写回 AST
  └─ 第二阶段：Phalcon 4 → 5，基于第一阶段结果重新解析并跑完
```

也就是：**两阶段、两进程、两棵 AST**。

---

## 二、为什么不能直接合并两个 set

### 2.1 两套 `RenameUseImportsRector` 都依赖原始 token

`RenameUseImportsRector` 会读取 `$file->getOldStmts()` 和 `getOldTokens()`，判断一个引用原本是：

- 内联 FQCN：`new \Phalcon\Loader()`
- `use` 短名：`use Phalcon\Loader; new Loader()`
- 分组 use：`use Phalcon\{Url as UrlResolver};`
- 相对子命名空间：`StringLength\Max`

如果把 3 → 4 和 4 → 5 规则塞进同一次 Rector 运行：

- 3 → 4 规则先把 AST 里的 `Phalcon\Mvc\Url` 改成 `Phalcon\Url`
- 4 → 5 规则随后看到的是“中间状态”
- 但它的 alias plan 仍可能基于“原始 3.x 文件”构建
- 结果可能是短名退化成 FQCN、别名处理错位，或引用风格不一致

这不是简单调整注册顺序就能解决的。

### 2.2 两套 `RenameClassRector` 配置会同时参与

`phalcon40.php` 注册一个 `RenameClassRector`，`phalcon50.php` 也注册一个。

合并后同一节点可能被两个映射表连续处理：

```text
Phalcon\Mvc\Model\Validator\Email
  → Phalcon\Validation\Validator\Email       （3→4）
  → Phalcon\Filter\Validation\Validator\Email（4→5）
```

这个链式结果碰巧是正确的，但不能推广到所有类。直接合并会引入“映射链依赖遍历顺序”的隐性行为，后续维护很难保证稳定。

### 2.3 v5 规则假设输入已是 v4 形态

4 → 5 的部分规则假设代码已经处于 Phalcon 4 语义，例如：

- `CacheAdapterFactoryConstructorInjectionRector`
- `AssetsManagerTagFactoryInjectionRector`
- `TextAndHelperToSupportHelperRector`
- `TagToTagFactoryRector`

如果直接从 Phalcon 3 源码开始跑 v5 规则，这些规则看到的类形态、构造签名、helper 集合都可能不对。

### 2.4 TODO 提示标记不同

- 3 → 4 规则使用 `TODO Phalcon v4`
- 4 → 5 规则使用 `TODO Phalcon v5`

同一次遍历里，对同一个已移除类可能同时满足两套提示规则，产生重复或顺序混乱的注释。

---

## 三、目标使用体验

### 3.1 最终期望

用户只需要执行一个入口，例如：

```text
vendor/bin/rector-phalcon upgrade:3-to-5 --path src
```

或至少在文档中提供一条清晰的组合命令说明。

入口内部执行：

```text
阶段 1：Phalcon 3 → 4
  rector process <path> --config phalcon40.php

阶段 2：Phalcon 4 → 5
  rector process <path> --config phalcon50.php
```

### 3.2 必须保留的手动路径

以下现有用法继续保留：

- 4 → 5：用户通过 `PhalconSetList::UP_PHALCON_4_TO_5` 显式选择
- 3 → 4：落地后用户通过 `PhalconSetList::UP_PHALCON_3_TO_4` 显式选择
- 分阶段升级：先 3 → 4，测试通过后手动再跑 4 → 5

“直接升级”是新增便利入口，不替代现有分阶段流程。

---

## 四、关键设计：显式选择阶段

本包不通过 `extension-installer` 自动加载规则集，因此 3 → 5 的两阶段执行不需要 autoload gate 环境变量。wrapper 只负责为每个阶段生成对应的显式配置：

```text
阶段 1 wrapper
  └─ 只加载 src/Phalcon4/Config/Set/phalcon40.php
      → 只有 3 → 4 规则生效

阶段 2 wrapper
  └─ 只加载 src/Phalcon5/Config/Set/phalcon50.php
      → 只有 4 → 5 规则生效
```

约束：

- 每个阶段必须使用独立的 Rector 进程。
- 每阶段只加载一个目标版本的 set。
- 不通过 `extra.rector.includes` 注入任何默认 set。
- 不设置 `RECTOR_PHALCON_SKIP_AUTOLOAD` 之类的环境变量。
- `Phalcon40Set`、`Phalcon50Set` 各自独立运行，确保阶段隔离。

决策详见 `plan/EXTENSION_INSTALLER_PLAN.md`。

### 4.1 阶段互斥保护（已实现）

同一次运行**不能**同时加载 `UP_PHALCON_3_TO_4` 与 `UP_PHALCON_4_TO_5`（包括各自的 `UP_TO_*` 别名）。这是“两进程两 AST”约束的强制保障，实现在 `src/Set/PhalconStageGuard.php`：

- 两个阶段 set 文件在闭包开头各声明一次阶段；同一 `RectorConfig` 实例声明到不同阶段时，立即抛 `InvalidConfigurationException`，在任何文件被处理前中止。
- 异常信息会同时列出冲突的两个配置常量名（如 `PhalconSetList::UP_PHALCON_3_TO_4` 与 `PhalconSetList::UP_PHALCON_4_TO_5`），并提示分两个独立进程按顺序执行，而不是静默产生回退改动。
- 状态用 `WeakMap` 按 config 实例隔离；别名重复声明同一阶段会被忽略，不误报。
- 不依赖任何 Rector 内部接口（如 `ResettableInterface`），以保证与较低的 Rector 版本兼容；改为在两个 set 文件的 guard 调用处用 `defined('PHPUNIT_COMPOSER_INSTALL')` 检测测试环境并跳过，规避 Rector 测试基类跨测试共享同一 config 造成的误报。
- 源码版本保护同样改用该纯 PHP 检测，不使用 Rector 内部的 `StaticPHPUnitEnvironment`。
- `COMPOSER_BASED` 只会选中单个阶段；但若同时显式配置了另一阶段，同样会被拦下。

### 4.2 源码版本保护（已实现）

执行 `UP_PHALCON_3_TO_4` 前会扫描待处理源码，防止在已是 Phalcon 5 的代码上误跑 3 → 4（例如把 `Phalcon\Mvc\Url` 静默改回 `Phalcon\Url`）。实现在 `src/Set/Phalcon5SourceGuard.php` 与 `src/Set/Phalcon5Signatures.php`：

- guard 在 3 → 4 set 文件加载阶段执行，早于任何文件处理；命中时抛异常中止整个 Rector 运行，不写入任何改动。
- v5 特征集从两张 `ClassRenames` 映射表自动推导（v5 目标名 − v3 名 − v4 名），随规则表自动同步；`Phalcon\Mvc\Url` 这类 v3/v5 共存名被排除，不会误判。
- 命中时标题显示文件总数，最多列出 10 个文件，其余汇总为 `... and N more file(s)`，避免刷屏。
- 待处理路径优先从 CLI 参数解析（set 加载早于 Rector 设置 `PATHS`），回退到工作目录；排除 `vendor` 等常见目录；PHPUnit 环境自动跳过。
- 误报时可设置环境变量 `RECTOR_PHALCON_SKIP_V5_GUARD=1` 跳过该检查。

### 4.3 stubs 版本与依赖传递

本包 `composer.json` 的 `require-dev` 中固定的 `phalcon/ide-stubs:^5.0` **仅用于本包自身的开发与测试**，不会传递给使用本包的项目。Composer 只传递依赖的 `require`，不传递 `require-dev`，因此用户可以按阶段在自己项目里安装 `^4.0`（3 → 4）或 `^5.0`（4 → 5），与本包无冲突。

---

## 五、入口形态选择

### 方案 A：先只写文档组合命令（最小成本）

在 `docs/UPGRADE_3_TO_5.md` 中写清楚两条命令，并要求用户在两条命令之间跑测试。

优点：

- 不需要新 CLI。
- 不受 wrapper 与自动加载机制交互影响。
- 可以马上落地。

缺点：

- 不是真正的“一条命令”。
- 第一阶段仍需要用户自己按正确的阶段顺序执行两条命令。

### 方案 B：新增项目内 CLI wrapper（推荐）

在包中新增：

```text
bin/rector-phalcon
```

提供子命令：

```text
upgrade:3-to-4
upgrade:4-to-5
upgrade:3-to-5
```

`composer.json` 增加 `bin` 声明，让用户安装后可通过 `vendor/bin/rector-phalcon` 调用。

推荐优先实现 `upgrade:3-to-5`，其余子命令可选。

wrapper 职责：

1. 解析 `--path`、`--dry-run`、`--config` 等参数。
2. 为每一阶段生成临时 Rector 配置。
3. 第一阶段只加载 `src/Phalcon4/Config/Set/phalcon40.php`。
4. 第二阶段只加载 `src/Phalcon5/Config/Set/phalcon50.php`。
5. 透传 Rector 输出和退出码。
6. 失败时明确提示当前停在哪个阶段。

命名空间建议使用独立根命名空间：

```text
RectorPhalcon\Upgrade\...
```

对应 PSR-4：

```text
RectorPhalcon\Upgrade\ → src
```

这样不会污染 `RectorPhalcon\` 或 `RectorPhalcon\Phalcon4\`。

### 方案 C：只提供单一 set 文件（不推荐）

即新增：

```text
src/Upgrade/Config/Set/phalcon30-to-50.php
```

并让它同时 include 两个 set。

本规划不建议采用。若未来必须提供，也应标注为 experimental，并先解决第二节列出的规则冲突。

---

## 六、`--dry-run` 的特殊处理

两阶段直接升级的 dry-run 比单阶段复杂。

如果简单执行：

```text
阶段 1：rector process src --dry-run
阶段 2：rector process src --dry-run
```

第二阶段看到的是原始 Phalcon 3 代码，不是第一阶段迁移后的 Phalcon 4 代码，因此结果不准确。

推荐实现：

```text
用户调用 upgrade:3-to-5 --dry-run
  └─ 创建临时工作副本
      └─ 在临时副本上真实执行阶段 1（3→4）
          └─ 在临时副本上 dry-run 阶段 2（4→5）
              └─ 汇总显示：
                  - 阶段 1 会做的改动
                  - 阶段 2 在阶段 1 结果上会做的改动
              └─ 删除临时副本
```

补充策略：

- 如果项目在 Git 仓库中，优先用 Git worktree 或临时分支复制，保证不污染工作区。
- 如果没有 Git，退化为复制到系统临时目录。
- 如果无法创建临时副本，应明确警告“两阶段 dry-run 结果可能不完整”，不要静默给出误导结果。

apply 模式则直接对用户代码分两阶段执行，并要求：

- Git 工作区干净。
- 建议在新分支执行。
- 每阶段结束打印摘要。
- 阶段 2 失败时，明确告诉用户当前代码处于 Phalcon 4 中间态。

---

## 七、测试规划

### 7.1 现有测试保持

必须继续通过：

- `Phalcon40Set`
- `Phalcon4` 单规则测试
- `Phalcon50Set`
- `Phalcon5` 单规则测试

### 7.2 新增 3 → 5 集成测试目录

建议新增：

```text
src/Upgrade/Tests/
└── DirectUpgrade/
    ├── Fixture/
    ├── config/
    └── DirectUpgradeTest.php
```

命名可用 `Phalcon35Set` 或 `DirectUpgrade`。 Test 类本身应验证 wrapper 的两个阶段，而不是注册一个合并 set。

### 7.3 必测场景

| 场景 | 输入 | 期望 |
|---|---|---|
| 跨两阶段类映射 | `Phalcon\Mvc\Model\Validator\Email` | `Phalcon\Filter\Validation\Validator\Email` |
| 只在阶段 2 改名 | `Phalcon\Loader` | `Phalcon\Autoload\Loader` |
| 只在阶段 1 改名，v5 不再改 | `Phalcon\Acl\Resource` | `Phalcon\Acl\Component` |
| 文本回到原点但语义变化 | `Phalcon\Mvc\Url` | `Phalcon\Mvc\Url`（确认中间经历 `Phalcon\Url`，最终不误伤） |
| `use` 短名保持 | `use Phalcon\Loader; new Loader();` | `use Phalcon\Autoload\Loader; new Loader();` |
| 内联 FQCN 保持 | `new \Phalcon\Loader()` | `new \Phalcon\Autoload\Loader()` |
| 分组 use | `use Phalcon\{Url as UrlResolver};` | 最终指向 Phalcon 5 正确类 |
| 别名简化 | `use Phalcon\Crypt as PhalconCrypt;` | `use Phalcon\Encryption\Crypt;` |
| v4 TODO | `Phalcon\Image` | 出现 `TODO Phalcon v4` |
| v5 TODO | `Phalcon\Kernel` | 出现 `TODO Phalcon v5` |
| 非 Phalcon 类 | `\PDO`、`App\Support\Helpers` | 保持原样 |
| 幂等 | 跑两次直接升级 | 第二次无新增 diff |

### 7.4 stubs 版本矩阵

当前 `composer.lock` 固定的是 `phalcon/ide-stubs: ^5.0`。

3 → 4 类重命名是配置驱动、不依赖反射；但当前 P1 已启用 ACL / Paginator 方法重命名和 CLI Console 类型识别，因此 `phalcon40` 阶段需要 `^4.0` stubs。

CI 至少分两个 job：

| Job | stubs | 验证内容 |
|---|---|---|
| `phalcon40` | `phalcon/ide-stubs:^4.0` | 3 → 4 单规则 + `Phalcon40Set` |
| `phalcon50` | `phalcon/ide-stubs:^5.0` | 4 → 5 单规则 + `Phalcon50Set` |
| `direct-35` | 分阶段切换或两套 Rector 安装 | 3 → 5 集成 fixture |

`direct-35` 可以有两档：

1. 基础 P0 版：只用类映射，不需要切换 stubs。
2. 当前版：阶段 1 用 `^4.0`，阶段 2 用 `^5.0`。

用户侧执行直接升级时也建议按阶段切换：先安装 `phalcon/ide-stubs:^4.0` 完成 3 → 4，再切换到 `^5.0` 执行 4 → 5。

---

## 八、规则兼容性清单

实施 direct-upgrade 时，需要按以下方式看待现有规则。

### 8.1 可安全分阶段复用

| 规则 | 处理方式 |
|---|---|
| `src/Phalcon4/ClassRenames.php` + `RenameClassRector` | 阶段 1 使用 |
| `Phalcon4` 的 `RenameUseImportsRector` | 阶段 1 使用 |
| `Phalcon4` 的 `RemovedClassUsageHintRector` | 阶段 1 使用 |
| `Phalcon4` 的 ACL / Paginator `RenameMethodRector` | 阶段 1 使用，需 `^4.0` stubs |
| `Phalcon4` 的 `RenamePaginatorBeforePropertyRector` | 阶段 1 使用 |
| `Phalcon4` 的 `RenameCliConsoleAddModulesRector` | 阶段 1 使用 |
| `Phalcon5` 全套规则 | 阶段 2 使用 |

### 8.2 需要额外验证的规则

| 规则 | 风险 |
|---|---|
| `TextAndHelperToSupportHelperRector` | 3 → 4 已实现 `Phalcon\Text` → `Phalcon\Helper\Str`，第二阶段可以看到正确的 Phalcon 4 形态；仍需补充两阶段集成 fixture |
| `TagToTagFactoryRector` | Phalcon 3/4 都有 `Phalcon\Tag`，但参数语义和 DI 依赖需要中间态验证 |
| `CacheAdapterFactoryConstructorInjectionRector` | 3 → 4 已自动改写固定 Frontend/Backend 与 Factory 配置；复杂 Cache wiring 仍需人工确认，此 v5 规则仍需两阶段集成 fixture |
| 方法重命名 | 3 → 4 已实现 ACL、Paginator、CLI、Filter `add()` → `set()` 与 Cache PSR-16 `exists/save` → `has/set`；其他方法重命名仍待实施，直接升级会漏掉这些 v3 → v4 方法变更 |
| 常量重命名 | 3 → 4 已实现 Filter 常量和 DB `FETCH_*` → `Phalcon\Db\Enum`；其他常量迁移仍待实施 |

### 8.3 不应在同一次运行中混用

| 组合 | 原因 |
|---|---|
| 两个 `RenameUseImportsRector` 同时注册 | alias plan 基于旧 AST，可能错位 |
| 两个 `RenameClassRector` 配置同时注册 | 映射链依赖遍历顺序 |
| v4 TODO 与 v5 TODO 同时注册 | 注释标记和触发条件不同，容易重复 |
| 阶段 2 规则直接处理原始 3.x 代码 | 输入形态不符合规则假设 |

---

## 九、文档规划

新增：

```text
docs/UPGRADE_3_TO_5.md
```

内容建议包括：

1. 适用范围：Phalcon 3.4.x → Phalcon 5.x。
2. 推荐路径：仍建议分阶段升级并在中间跑测试。
3. 直接升级命令：一条 wrapper 命令。
4. 内部两阶段说明。
5. `--dry-run` 行为说明。
6. Git 工作区要求。
7. stubs 版本要求。
8. 已知限制：P1/P2 尚未完成的 v3 → v4 变更。

README 增加“方式四：Phalcon 3 → 5 直接升级（实验/推荐入口）”，链接到 `docs/UPGRADE_3_TO_5.md`。

同时更新：

- `plan/PHALCON3_TO_4_PLAN.md`：标注 direct-upgrade 依赖其 P1/P2 完成度。
- `plan/PHALCON4_TO_5_PLAN.md`：标注 direct-upgrade 复用其全套规则。
- `plan/PHALCON3_TO_5_PLAN.md`：记录本规划的实施状态。

---

## 十、实施顺序

### 阶段 0：冻结基线

- 确认当前测试全绿。
- 确认 `composer.json` 不含 `extra.rector.includes`。
- 确认 3 → 4 与 4 → 5 测试互相独立。

### 阶段 1：显式阶段隔离

- 不修改 `src/Phalcon5/Config/Set/phalcon50.php` 的自动加载行为，因为本包根本没有自动加载。
- 确认每个阶段由 wrapper 生成独立配置，只加载对应 set。
- 确认阶段之间没有规则交叉注册。

### 阶段 2：文档式直接升级

- 新增 `docs/UPGRADE_3_TO_5.md`。
- 写清两条命令和中间测试要求。
- README 增加入口链接。
- 暂不提供 wrapper。

### 阶段 3：CLI wrapper

- 新增 `bin/rector-phalcon`。
- 新增 `RectorPhalcon\Upgrade\` 命名空间和 PSR-4 映射。
- 实现 `upgrade:3-to-5`：
  - 阶段 1 只加载 `phalcon40.php`。
  - 阶段 2 只加载 `phalcon50.php`。
- 实现 apply 模式。
- 实现基于临时副本的 dry-run 模式。

### 阶段 4：集成测试

- 新增 `src/Upgrade/Tests/DirectUpgrade/`。
- 覆盖跨阶段映射、引用风格、TODO、幂等。
- 加 CI job。

### 阶段 5：完整能力提升

按 `plan/PHALCON3_TO_4_PLAN.md` 继续推进：

- P1：完成
- P2：完成；下一步是为这些方法/常量/checklist 规则补 3 → 5 两阶段集成 fixture

每完成一项，都要补 3 → 5 集成 fixture，而不是只补 3 → 4 单规则 fixture。

### 阶段 6（可选）：单次遍历组合 set

只有在以下情况都满足时才考虑：

- 用户明确需要单次 Rector 遍历。
- 两阶段 CLI 性能不可接受。
- 已解决原始 token、alias plan、TODO 标记、stubs 差异等冲突。

可实验性新增：

```text
src/Upgrade/Config/Set/phalcon30-to-50-composed.php
```

其基础应是新的组合映射表：

```text
Phalcon35ClassRenames = resolve3to4(v3Class) → resolve4to5(result)
```

并配套重写 `RenameUseImportsRector` 的 alias plan 逻辑。该方案成本高，不应作为第一版。

---

## 十一、验收标准

1. 安装本包后不会自动加载任何规则集，用户必须显式配置。
2. `Phalcon40Set`、`Phalcon50Set`、单规则测试全部继续通过。
3. `upgrade:3-to-5 --path <dir>` 能完成两阶段 apply。
4. `upgrade:3-to-5 --dry-run` 的阶段 2 结果基于阶段 1 之后的代码。
5. 第一阶段不会加载 v5 规则；通过 wrapper 生成的阶段专用配置验证。
6. 跨阶段类名映射 fixture 全部通过。
7. 重复运行直接升级不产生额外 diff。
8. 失败时明确报告阶段和当前代码状态。
9. README 和 `docs/UPGRADE_3_TO_5.md` 与实现一致。

---

## 十二、关键文件清单（预计）

```text
src/Phalcon4/Config/Set/phalcon40.php                         # 阶段 1 显式加载的目标 set
bin/rector-phalcon                                # 新增 CLI wrapper
composer.json                                     # bin + RectorPhalcon\Upgrade\ PSR-4
src/Upgrade/...                                   # wrapper 实现
src/Upgrade/Tests/DirectUpgrade/...            # 3→5 集成测试
docs/UPGRADE_3_TO_5.md                             # 使用说明
README.md                                         # 增加方式四入口
plan/PHALCON3_TO_5_PLAN.md                         # 本规划
plan/PHALCON3_TO_4_PLAN.md                         # 标注 direct-upgrade 依赖
plan/PHALCON4_TO_5_PLAN.md                         # 标注 direct-upgrade 复用
```

---

## 十三、最小可行版本（MVP）

建议 MVP 只做：

1. 确认显式阶段隔离
2. `docs/UPGRADE_3_TO_5.md`
3. 3 → 5 集成 fixture
4. 后续再决定是否做 CLI wrapper

理由：

- 现在 3 → 4 的 P0 / P1 / P2 规则已全部实施，直接升级的完整度仍受真实项目验证与两阶段集成 fixture 覆盖限制。
- 显式阶段隔离是所有后续方案的基础。
- 集成 fixture 能提前暴露两阶段真实交互问题。
- CLI wrapper 是可玩性提升，不是正确性前提。

一句话：**先把“两阶段直接升级”做正确，再把“一条命令”做顺手。**
