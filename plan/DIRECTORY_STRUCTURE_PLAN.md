# 项目目录结构与命名空间重构规划

> 状态：已实施。本文记录已落地的目标结构与迁移顺序。
>
> 前提：项目尚未正式发布，**不需要兼容既有用户、既有类命名空间或既有规则集路径**。因此本规划采用干净切换，不保留旧包装层。

---

## 一、背景与目标

项目定位是通用的 Phalcon 自动升级工具，当前已实现 Phalcon 4 → 5，未来会加入 Phalcon 3 → 4、Phalcon 3 → 5 以及其他版本升级路径。

现有结构把规则实现、测试和规则集配置分散在多个根目录：

```text
config/sets/
rules/
rules-tests/
src/Set/
```

这种结构在只有 4 → 5 时还能接受，但多版本共存时会带来几个问题：

1. 一个版本的实现分散在多个根目录，定位成本高。
2. `RectorPhalcon\`、`Phalcon3To4\Rector\` 等命名空间会继续强化“按源版本命名”的耦合。
3. 版本之间复用规则基类时，容易让 `Phalcon4` 依赖 `Phalcon5` 的内部实现。
4. 新增版本时要同时改多个根目录，扩展成本高。

目标结构：

- 所有规则集相关代码统一放在 `src/` 下。
- `src/` 下按目标版本划分目录：`Phalcon4/`、`Phalcon5/`、未来的 `Phalcon6/`。
- 根命名空间统一为 `RectorPhalcon\`，与 `src/` 对应。
- 子命名空间层级与子目录严格对应。
- 每个版本目录内部拥有自己的规则、规则集配置、SetList 和测试。
- 跨版本编排（如 3 → 5 两阶段直接升级）放在独立 `Upgrade/` 中，不塞进某个版本目录。

---

## 二、核心原则

### 2.1 单一根命名空间

```text
RectorPhalcon\  →  src/
```

不再保留：

```text
RectorPhalcon\
Phalcon3To4\Rector\
RectorPhalcon\Set\
```

所有公开与内部代码统一迁移到 `RectorPhalcon\` 下。

### 2.2 子命名空间 = 子目录

示例：

```text
RectorPhalcon\Phalcon5\Rector\Stmt\RenameUseImportsRector
  → src/Phalcon5/Rector/Stmt/RenameUseImportsRector.php

RectorPhalcon\Phalcon5\Tests\Phalcon50Set\Phalcon50SetTest
  → src/Phalcon5/Tests/Phalcon50Set/Phalcon50SetTest.php
```

目录名与命名空间大小写保持一致，建议使用：

```text
Phalcon4/
Phalcon5/
Phalcon6/
```

不建议使用小写 `phalcon4/`，避免 Linux 下 PSR-4 大小写敏感问题。

### 2.3 按“目标版本”划分，不按源版本划分

`src/Phalcon5/` 表示“升级到 Phalcon 5.x 的规则集”。

因此：

| 目录 | 含义 |
|---|---|
| `src/Phalcon4/` | 目标版本为 Phalcon 4.x，当前规划中用于 3 → 4 |
| `src/Phalcon5/` | 目标版本为 Phalcon 5.x，当前用于 4 → 5 |
| `src/Phalcon6/` | 未来目标版本为 Phalcon 6.x |

这样目录名的语义长期稳定，不会因为源版本不同而混乱。

### 2.4 版本之间不互相依赖

不允许出现：

```text
Phalcon4 的规则继承 Phalcon5 的具体规则
Phalcon5 的规则引用 Phalcon4 的映射表
```

共享能力按职责上移：

- 可被多个版本规则继承的抽象基类放到 `src/Base/`。
- 版本解析、配置解析、值对象等通用支持代码放到 `src/Support/`。

### 2.5 不考虑向后兼容

由于尚未正式发布，本次重构直接删除旧目录和旧命名空间：

- 删除根 `config/`
- 删除根 `rules/`
- 删除根 `rules-tests/`
- 删除旧 PSR-4 映射
- 不保留旧路径 wrapper
- 不保留旧类名 alias

---

## 三、目标目录结构

```text
project-root/
├── bin/
│   └── rector-phalcon                         # CLI 薄入口，未来 3→5 直接升级使用
│
├── src/
│   ├── Set/
│   │   └── PhalconSetList.php                 # 公共稳定 API，所有升级阶段共用
│   │
│   ├── Base/
│   │   └── Rector/
│   │       └── Stmt/
│   │           ├── AbstractRenameUseImportsRector.php
│   │           └── AbstractRemovedClassUsageHintRector.php
│   │
│   ├── Support/
│   │   ├── Set/
│   │   │   └── ComposerBased/
│   │   │       ├── PhalconVersionResolver.php
│   │   │       └── PhalconSetResolver.php
│   │   │
│   │   └── ValueObject/
│   │       └── PhalconVersion.php
│   │
│   ├── Upgrade/
│   │   ├── Application.php
│   │   ├── UpgradeOrchestrator.php
│   │   ├── RectorProcessRunner.php
│   │   ├── TempConfigFactory.php
│   │   └── TempWorkspace.php
│   │
│   ├── Phalcon5/
│   │   ├── Config/
│   │   │   └── Set/
│   │   │       ├── phalcon50.php              # 4→5 基础规则集
│   │   │       └── phalcon513.php             # 未来 5.13+ 附加规则集
│   │   │
│   │   ├── Set/
│   │   │   └── Phalcon5SetList.php            # v5 内部常量
│   │   │
│   │   ├── ClassRenames.php                   # 原 ClassRenames
│   │   ├── ImportShortener.php                # 原 ImportShortener
│   │   │
│   │   ├── Rector/
│   │   │   ├── New_/
│   │   │   ├── StaticCall/
│   │   │   └── Stmt/
│   │   │
│   │   └── Tests/
│   │       ├── Phalcon50Set/
│   │       └── Rector/
│   │
│   └── Phalcon4/                              # 已实施的 3→4 基础阶段
│       ├── Config/
│       │   └── Set/
│       │       └── phalcon40.php
│       │
│       ├── Set/
│       │   └── Phalcon4SetList.php
│       │
│       ├── ClassRenames.php
│       ├── ImportShortener.php
│       │
│       ├── Rector/
│       │   ├── New_/
│       │   ├── StaticCall/
│       │   └── Stmt/
│       │
│       └── Tests/
│           ├── Phalcon40Set/
│           └── Rector/
│
├── docs/
├── plan/
├── composer.json
├── phpunit.xml
└── README.md
```

工程元文件（`composer.json`、`phpunit.xml`、README、`docs/`、`plan/`）仍在根目录。`bin/rector-phalcon` 是 Composer bin 约定要求的薄入口，不属于规则实现代码。

---

## 四、命名空间映射

`composer.json` 最终目标：

```text
autoload.psr-4
  RectorPhalcon\ → src/

autoload-dev.psr-4
  RectorPhalcon\Phalcon5\Tests\ → src/Phalcon5/Tests/
  RectorPhalcon\Phalcon4\Tests\ → src/Phalcon4/Tests/

exclude-from-classmap
  /src/*/Tests/
```

说明：

- `RectorPhalcon\` 根映射覆盖所有生产代码。
- `RectorPhalcon\Base\...` 对应 `src/Base/...`，只放抽象基类。
- `RectorPhalcon\Support\...` 对应 `src/Support/...`，放通用支持代码。
- 测试放在版本目录内，但通过 `autoload-dev` 明确声明。
- `exclude-from-classmap` 避免生产优化 autoload 时把测试类打进 classmap。
- 不再需要 `RectorPhalcon\Set\` 这种窄映射，因为根映射已经覆盖 `src/Set/`。

---

## 五、各类文件的归属

### 5.1 公共 SetList

位置：

```text
src/Set/PhalconSetList.php
```

职责：

- 对外提供稳定常量。
- 不承载具体规则。
- 聚合各版本内部 SetList 的常量。

公共常量示例：

```text
PhalconSetList::UP_PHALCON_3_TO_4
PhalconSetList::UP_PHALCON_4_TO_5
PhalconSetList::UP_TO_PHALCON_50
PhalconSetList::COMPOSER_BASED
```

当前已实现 `UP_PHALCON_3_TO_4`、`UP_PHALCON_4_TO_5` 及对应 `UP_TO_*` 别名；其他常量随对应阶段实现再补。

### 5.2 版本内部 SetList

位置：

```text
src/Phalcon4/Set/Phalcon4SetList.php
src/Phalcon5/Set/Phalcon5SetList.php
```

职责：

- 定义该目标版本内部的 set 路径。
- 供公共 `PhalconSetList` 聚合。
- 不直接暴露给用户作为唯一入口，避免用户记住多个类。

### 5.3 Rector set 配置

位置：

```text
src/Phalcon5/Config/Set/phalcon50.php
src/Phalcon4/Config/Set/phalcon40.php
```

职责：

- 注册当前版本的规则。
- 配置 `RenameClassRector`、`RenameMethodRector`、自定义规则。
- 控制规则顺序。
- 控制 `importNames(false, false)` 等全局策略。

`composer.json` 不配置 `extra.rector.includes`，规则集通过 `PhalconSetList` 显式选择：

```text
PhalconSetList::UP_PHALCON_4_TO_5 → src/Phalcon5/Config/Set/phalcon50.php
```

决策见 `plan/EXTENSION_INSTALLER_PLAN.md`。

### 5.4 具体规则实现

位置：

```text
src/Phalcon5/Rector/...
src/Phalcon4/Rector/...
```

命名空间：

```text
RectorPhalcon\Phalcon5\Rector\...
RectorPhalcon\Phalcon4\Rector\...
```

已完成的迁移映射：

| 旧文件 | 新文件 |
|---|---|
| `rules/Phalcon5/Rector/New_/...` | `src/Phalcon5/Rector/New_/...` |
| `rules/Phalcon5/Rector/StaticCall/...` | `src/Phalcon5/Rector/StaticCall/...` |
| `rules/Phalcon5/Rector/Stmt/...` | `src/Phalcon5/Rector/Stmt/...` |
| `rules/Phalcon4/Rector/...` | `src/Phalcon4/Rector/...` |

### 5.5 版本级辅助类

位置：

```text
src/Phalcon5/ClassRenames.php
src/Phalcon5/ImportShortener.php
src/Phalcon4/ClassRenames.php
```

命名建议去掉类名中的 `Phalcon5` / `Phalcon4` 前缀，因为命名空间已经表达了版本：

```text
RectorPhalcon\Phalcon5\ClassRenames
RectorPhalcon\Phalcon5\ImportShortener
RectorPhalcon\Phalcon4\ClassRenames
```

### 5.6 Base：抽象规则基类

位置：

```text
src/Base/Rector/Stmt/AbstractRenameUseImportsRector.php
src/Base/Rector/Stmt/AbstractRemovedClassUsageHintRector.php
```

命名空间：

```text
RectorPhalcon\Base\Rector\Stmt\...
```

职责：

- 只放需要被多个版本规则继承的抽象类。
- 统一抽象可重写点：
  - `resolveNewClass()`
  - `prefixMap()`
  - `HINT_MARKER`
  - `REMOVED_CLASS_HINTS`
- 避免 `Phalcon4` 继承 `Phalcon5` 的具体实现。
- 让每个版本只提供自己的映射、提示清单和 RuleDefinition。

### 5.7 Support：通用支持代码

位置：

```text
src/Support/Set/ComposerBased/PhalconVersionResolver.php
src/Support/Set/ComposerBased/PhalconSetResolver.php
src/Support/ValueObject/PhalconVersion.php
```

命名空间：

```text
RectorPhalcon\Support\...
```

职责：

- 探测项目使用的 Phalcon 版本。
- 根据版本决定使用哪个升级 set。
- 提供版本值对象，避免各版本规则各自定义版本判断逻辑。
- 不承载 Rector 规则实现，也不作为版本规则基类使用。

### 5.8 测试

位置：

```text
src/Phalcon5/Tests/...
src/Phalcon4/Tests/...
```

命名空间：

```text
RectorPhalcon\Phalcon5\Tests\...
RectorPhalcon\Phalcon4\Tests\...
```

`phpunit.xml` 的 testsuite 从 `rules-tests` 改成扫描版本目录，例如：

```text
src/Phalcon5/Tests
src/Phalcon4/Tests
```

fixture 仍沿用 `.php.inc` 和 `-----` 约定。

### 5.9 跨版本直接升级

位置：

```text
src/Upgrade/
bin/rector-phalcon
```

职责：

- 3 → 5 不注册一个“合并 set”。
- 分两阶段执行：
  - `src/Phalcon4/Config/Set/phalcon40.php`
  - `src/Phalcon5/Config/Set/phalcon50.php`
- 每阶段启动独立 Rector 进程。
- 每阶段使用显式配置，只加载对应版本的 set。
- `--dry-run` 基于临时副本，先真实执行第一阶段，再 dry-run 第二阶段。

---

## 六、迁移步骤

### 6.1 冻结当前测试基线

重构前先确认当前测试全绿。

当前作为基线：

```text
PHPUnit 全量测试通过
composer validate 通过
```

### 6.2 建立目标目录

新增：

```text
src/Set/
src/Base/
src/Support/
src/Upgrade/
src/Phalcon5/
src/Phalcon5/Config/Set/
src/Phalcon5/Set/
src/Phalcon5/Rector/
src/Phalcon5/Tests/
src/Phalcon4/
```

### 6.3 迁移 Phalcon5

按以下顺序：

1. 移动规则实现：
   ```text
   src/Phalcon5/Rector/* → src/Phalcon5/Rector 或 src/Phalcon5/*
   ```
2. 移动测试：
   ```text
   src/Phalcon5/Tests/* → src/Phalcon5/Tests/*
   ```
3. 移动 set 配置：
   ```text
   src/Phalcon5/Config/Set/phalcon50.php → src/Phalcon5/Config/Set/phalcon50.php
   ```
4. 命名空间改为：
   ```text
   RectorPhalcon\Phalcon5\...
   RectorPhalcon\Phalcon5\Tests\...
   ```
5. 调整所有 `use`。
6. 调整测试 config 中引用的 set 路径。

### 6.4 抽象 Base 基类与 Support 代码

把当前 `RenameUseImportsRector`、`RemovedClassUsageHintRector` 中“可被继承的通用部分”上移至：

```text
src/Base/Rector/Stmt/
```

把版本探测和 set 选择相关代码放到：

```text
src/Support/Set/ComposerBased/
src/Support/ValueObject/
```

然后：

- `src/Phalcon5/Rector/Stmt/RenameUseImportsRector.php` 只提供 v5 映射。
- `src/Phalcon4/Rector/Stmt/RenameUseImportsRector.php` 只提供 v4 映射。
- 二者都继承 Base 抽象基类，不再互相继承。
- Composer-based 版本解析不依赖任何具体版本规则目录。

### 6.5 迁移 Phalcon4

如果 `feature/phalcon-3-to-4` 的内容要并入：

1. 移动 Phalcon4 规则到 `src/Phalcon4/Rector/`。
2. 移动 Phalcon4 测试到 `src/Phalcon4/Tests/`。
3. 移动 `src/Phalcon4/Config/Set/phalcon40.php` 到 `src/Phalcon4/Config/Set/phalcon40.php`。
4. 命名空间改为 `RectorPhalcon\Phalcon4\...`。
5. 移除对 `RectorPhalcon\Phalcon5` 具体类的继承，改为继承 Base 抽象基类。

### 6.6 更新 Composer

最终目标：

```text
autoload.psr-4
  RectorPhalcon\ → src/

autoload-dev.psr-4
  RectorPhalcon\Phalcon5\Tests\ → src/Phalcon5/Tests/
  RectorPhalcon\Phalcon4\Tests\ → src/Phalcon4/Tests/

no extra.rector.includes
```

规则集通过 `PhalconSetList` 显式选择，不使用自动加载。

删除所有旧 PSR-4 映射：

```text
Phalcon4To5\Rector\
Phalcon3To4\Rector\
RectorPhalcon\Set\
```

然后执行：

```text
composer dump-autoload
composer update --lock
composer validate --no-check-publish
```

### 6.7 更新 phpunit.xml

把测试目录从：

```text
rules-tests
```

改为：

```text
src/Phalcon5/Tests
src/Phalcon4/Tests
```

或统一扫描 `src` 下 `*Test.php`。

### 6.8 更新公共 SetList

`src/Set/PhalconSetList.php` 改为引用新路径：

```text
UP_PHALCON_4_TO_5 → src/Phalcon5/Config/Set/phalcon50.php
UP_PHALCON_3_TO_4 → src/Phalcon4/Config/Set/phalcon40.php
```

当前未实现的阶段不提前暴露常量，避免用户误用。

### 6.9 删除旧目录

全部测试通过后删除：

```text
config/
rules/
rules-tests/
```

并确认代码库中不存在旧命名空间和旧路径引用。

### 6.10 更新文档

同步修改：

```text
README.md
docs/
plan/PHALCON4_TO_5_PLAN.md
plan/PHALCON3_TO_4_PLAN.md
plan/PHALCON3_TO_5_PLAN.md
```

重点更新：

- 目录结构
- 命名空间
- 规则集路径
- SetList 常量
- 开发命令
- 测试路径

---

## 七、验收标准

1. 根目录不再存在 `config/`、`rules/`、`rules-tests/`。
2. 所有规则实现、set 配置、测试都在 `src/` 下。
3. 所有 PHP 类都使用 `RectorPhalcon\` 根命名空间。
4. 子命名空间与目录层级一致。
5. `Phalcon4` 不继承 `Phalcon5` 的具体类。
6. 可被继承的抽象规则基类都在 `src/Base/`；通用支持代码都在 `src/Support/`。
7. `composer.json` 只有一个生产 PSR-4 根映射：`RectorPhalcon\ → src/`。
8. `composer.json` 不配置 `extra.rector.includes`，规则集由 `PhalconSetList` 显式选择。
9. `phpunit.xml` 指向新测试目录。
10. 全量 PHPUnit 通过。
11. `composer validate --no-check-publish` 通过。
12. README 和 plan 文档中的路径全部可用。

---

## 八、风险与注意事项

### 8.1 Rector set 文件不是类

`Config/Set/*.php` 是 Rector 配置文件，不需要 PSR-4 类名对应。但为保持版本内聚，仍放在各版本目录中。

### 8.2 测试放在 src 下的发布体积

如果测试放在 `src/Phalcon5/Tests/`，发布包可能包含测试文件。

建议加入：

```text
.gitattributes
```

排除：

```text
/src/*/Tests export-ignore
/plan export-ignore
/docs export-ignore
```

### 8.3 目录大小写

统一使用：

```text
Phalcon4/
Phalcon5/
Base/
Support/
Upgrade/
```

避免 Linux/macOS 与 Windows 之间的 PSR-4 大小写差异。

### 8.4 不要提前暴露未实现常量

公共 `PhalconSetList` 虽然面向未来所有阶段，但只暴露已实现的常量。

当前阶段仅暴露：

```text
UP_PHALCON_4_TO_5
UP_TO_PHALCON_50
```

避免用户误以为 3 → 4 或 3 → 5 已经可用。

### 8.5 直接升级仍然分阶段

目录统一后也不能把 3 → 4 和 4 → 5 合成一个 set。

3 → 5 仍由：

```text
src/Upgrade/
bin/rector-phalcon
```

分两阶段执行。

---

## 九、推荐执行顺序

建议一次完成，但按提交拆分：

1. 提交 1：建立目标目录与 Base 抽象基类，暂不改行为。
2. 提交 2：迁移 Phalcon5 实现、测试、set 配置。
3. 提交 3：迁移 Phalcon4 实现、测试、set 配置。
4. 提交 4：更新 composer、phpunit、autoload。
5. 提交 5：更新公共 SetList。
6. 提交 6：删除旧目录。
7. 提交 7：更新 README、docs、plan。

每步都跑一次全量测试，避免大爆炸式重构。

---

## 十、一句话总结

本重构的核心不是简单移动文件，而是建立长期稳定的分层：

```text
Set/       公共入口
Base/      可被各版本继承的抽象基类
Support/   通用支持代码
Phalcon4/  3→4 阶段规则
Phalcon5/  4→5 阶段规则
Upgrade/   跨阶段直接升级编排
```

这样后续新增 Phalcon6 时，只需要新增：

```text
src/Phalcon6/
```

并在公共 `PhalconSetList` 中暴露对应常量，而不需要再次调整项目结构。
