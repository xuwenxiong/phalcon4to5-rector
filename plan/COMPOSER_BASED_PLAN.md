# Composer-based 自动选择规则集规划

> 状态：已实施。本文记录设计、边界与实施顺序。
>
> 当前基础：
>
> - 公共 SetList 入口：`src/Set/PhalconSetList.php`
> - Phalcon 5 版本内部 SetList：`src/Phalcon5/Set/Phalcon5SetList.php`
> - 当前已实现规则集：`src/Phalcon5/Config/Set/phalcon50.php`
> - Composer-based 实现：
>   - `src/Support/Set/ComposerBased/PhalconVersionResolver.php`
>   - `src/Support/Set/ComposerBased/PhalconSetResolver.php`
>   - `src/Support/ValueObject/PhalconVersion.php`

---

## 一、这个功能要解决什么问题

目标是让用户不必手动判断应该使用哪个 Phalcon 升级阶段，也不用记住具体 SetList 常量。

当前用户需要显式选择：

```php
PhalconSetList::UP_PHALCON_4_TO_5
```

未来还会增加：

```php
PhalconSetList::UP_PHALCON_3_TO_4
PhalconSetList::UP_PHALCON_5_TO_6
```

当项目支持多版本升级后，让用户自己判断源版本、目标版本和对应 set，容易出错。

Composer-based 配置要做的是：

```text
读取项目环境
  ↓
判断目标 Phalcon 版本
  ↓
自动选择单个升级阶段的规则集
```

用户侧只需要写：

```php
return RectorConfig::configure()
    ->withSets([
        PhalconSetList::COMPOSER_BASED,
    ]);
```

---

## 二、它和现有方式的区别

| 方式 | 行为 |
|---|---|
| `PhalconSetList::UP_PHALCON_4_TO_5` | 用户显式指定一个阶段 |
| `PhalconSetList::COMPOSER_BASED` | 根据项目环境自动选择阶段 |

本包已移除 `extra.rector.includes`，不再通过 extension-installer 无条件加载规则集。

Composer-based 配置是显式配置中的条件式选择：

```text
用户显式使用 PhalconSetList::COMPOSER_BASED
  └─ 探测项目 Phalcon 版本
      └─ 只加载一个匹配的规则集
```

---

## 三、命名来源

Rector 中 Laravel 的用法类似：

```php
return RectorConfig::configure()
    ->withComposerBased(laravel: true);
```

它内部本质上是把 `LaravelSetList::COMPOSER_BASED` 加入 sets。

本项目不能修改 Rector 核心的 `withComposerBased()` 方法签名，也不能直接支持：

```php
->withComposerBased(phalcon: true)
```

原因是：

- `RectorConfigBuilder` 参数是 Rector 核心写死的。
- `RectorConfig` / `RectorConfigBuilder` 是 `final class`。
- 本项目不能给 vendor 类动态增加具名参数。

因此本项目采用 Rector 原生可组合方式：

```text
PhalconSetList::COMPOSER_BASED
```

它仍然通过 `withSets()` 使用。

---

## 四、目标行为

### 4.1 用户侧 API

新增公共常量：

```text
PhalconSetList::COMPOSER_BASED
```

该常量指向一个配置文件，例如：

```text
src/Support/Set/ComposerBased/config.php
```

配置文件内部完成版本探测和 set 选择。

### 4.2 期望映射

| 探测到的目标版本 | 自动选择的规则集 |
|---|---|
| Phalcon 4 | `PhalconSetList::UP_PHALCON_3_TO_4`，用于 3 → 4 |
| Phalcon 5 | `PhalconSetList::UP_PHALCON_4_TO_5`，用于 4 → 5 |
| Phalcon 6 | 未来的 `UP_PHALCON_5_TO_6`，当前 no-op |
| 探测不到 | no-op，不注册 Phalcon 规则 |
| Phalcon 3 目标为 5 | 不自动合并两个 set，提示使用 `upgrade:3-to-5` 分阶段 CLI |

当前已实现目标 Phalcon 4 和 Phalcon 5，因此当前行为为：

```text
探测到目标 Phalcon 4
  └─ 加载 PhalconSetList::UP_PHALCON_3_TO_4

探测到目标 Phalcon 5
  └─ 加载 PhalconSetList::UP_PHALCON_4_TO_5

其他情况
  └─ 不加载 Phalcon 规则
```

---

## 五、版本探测策略

### 5.1 探测来源优先级

建议优先级：

1. 显式 Composer extra 配置
2. `composer.json` / `composer.lock` 中的依赖
3. Composer 已安装包信息
4. PHP 扩展版本
5. 探测失败则 no-op

### 5.2 显式配置

未来可支持：

```text
extra.rector-phalcon.target-version
```

用途：

- 自动探测失败时提供明确目标。
- 多版本冲突时提供人工覆盖。
- CI 环境中固定测试目标。

当前阶段可不实现，但解析器设计要预留入口。

### 5.3 Composer 依赖探测

重点看：

```text
phalcon/ide-stubs
```

例如：

```text
^5.0 → Phalcon 5
^4.0 → Phalcon 4
^3.4 → Phalcon 3
```

注意：这只是开发 stub 包，不一定等同于生产扩展版本，因此不能作为唯一来源。

### 5.4 Composer InstalledVersions

`PhalconVersionResolver` 可使用：

```text
Composer\InstalledVersions::isInstalled()
Composer\InstalledVersions::getPrettyVersion()
```

优点：

- 不依赖额外的 `composer.json` 路径解析。
- 在 Composer 管理的项目中稳定可用。

### 5.5 PHP 扩展探测

Phalcon 通常是 PHP 扩展，而非普通 Composer 运行时包。

可探测：

```text
extension_loaded('phalcon')
phpversion('phalcon')
Phalcon\Version::get()
```

注意：

- Rector 运行环境不一定加载了phalcon扩展。
- 扩展版本存在时可以作为强信号。
- 扩展版本不存在时不应报错，应继续走其他来源或 no-op。

---

## 六、当前 Support 实现的职责

### 6.1 `PhalconVersion`

位置：

```text
src/Support/ValueObject/PhalconVersion.php
```

职责：

- 表示 Phalcon 主版本枚举。
- 避免各解析器用裸字符串或裸整数判断版本。

### 6.2 `PhalconVersionResolver`

位置：

```text
src/Support/Set/ComposerBased/PhalconVersionResolver.php
```

职责：

- 从 Composer / PHP 扩展探测 Phalcon 主版本。
- 返回 `PhalconVersion`，探测失败返回 `null`。
- 不包含“该选哪个 set”的业务映射。

### 6.3 `PhalconSetResolver`

位置：

```text
src/Support/Set/ComposerBased/PhalconSetResolver.php
```

职责：

- 接收 `PhalconVersionResolver` 的结果。
- 根据主版本返回应加载的 set 路径列表。
- 当前阶段只映射已实现的 4 → 5。

当前实现：

> 探测到目标 Phalcon 4 时选择 `UP_PHALCON_3_TO_4`；探测到目标 Phalcon 5 时选择 `UP_PHALCON_4_TO_5`；其他阶段返回空数组，不注册 Phalcon 规则。

### 6.4 Composer-based 配置文件

已新增：

```text
src/Support/Set/ComposerBased/config.php
```

职责：

- 由 `PhalconSetList::COMPOSER_BASED` 指向。
- 创建 `PhalconSetResolver`。
- 将解析出的 set 路径加入当前 Rector 配置。

---

## 七、重要边界

### 7.1 自动选择的是单个阶段

Composer-based 配置只做：

```text
选一个 set
```

不做：

```text
同时加载 phalcon40.php + phalcon50.php
```

原因和 3 → 5 规划一致：

- 两套 `RenameUseImportsRector` 依赖不同阶段的 token / alias 信息。
- 两套 `RenameClassRector` 会形成顺序敏感的映射链。
- v5 规则假设输入已经是 Phalcon 4 形态。
- TODO 标记和触发条件不同，混跑会产生重复或错乱。

### 7.2 3 → 5 仍走 CLI

如果项目是 Phalcon 3 且目标是 Phalcon 5：

```text
不要用 COMPOSER_BASED 一次加载多个 set
  → 使用 vendor/bin/rector-phalcon upgrade:3-to-5
```

CLI wrapper 内部再分两阶段执行：

```text
阶段 1：src/Phalcon4/Config/Set/phalcon40.php
阶段 2：src/Phalcon5/Config/Set/phalcon50.php
```

### 7.3 不与自动加载叠加

本包已移除 `extra.rector.includes` 和 `rector/extension-installer` 插件白名单，不再无条件自动加载规则集。

因此 `COMPOSER_BASED` 是显式配置方式之一，不会和默认自动加载重复。用户也不应同时叠加：

```php
withSets([
    PhalconSetList::UP_PHALCON_4_TO_5,
    PhalconSetList::COMPOSER_BASED,
])
```

原因：

- `UP_PHALCON_4_TO_5` 是固定阶段。
- `COMPOSER_BASED` 会根据环境再次选择阶段。
- 同时配置可能导致同一个 set 被重复加入。

决策详见 `plan/EXTENSION_INSTALLER_PLAN.md`。

---

## 八、测试规划

### 8.1 单元测试

已新增：

```text
src/Support/Tests/Set/ComposerBased/PhalconVersionResolverTest.php
src/Support/Tests/Set/ComposerBased/PhalconSetResolverTest.php
```

覆盖：

- `^3.4`、`^4.0`、`^5.0` 解析。
- 非法版本字符串返回 `null`。
- 无 Composer 包、无扩展时返回 `null`。
- 探测到 Phalcon 5 → 返回 `UP_PHALCON_4_TO_5`。
- 探测到 Phalcon 4 → 当前阶段 no-op 或未来返回 3 → 4。
- 探测不到 → 返回空数组。

### 8.2 SetList 测试

已扩展：

```text
src/Set/Tests/PhalconSetListTest.php
```

覆盖：

- `COMPOSER_BASED` 常量存在。
- 常量指向的文件存在。
- 指向路径位于 `src/Support/Set/ComposerBased/`。

### 8.3 集成测试

已新增一个简单配置加载测试：

```text
src/Support/Tests/Set/ComposerBased/ComposerBasedConfigTest.php
```

验证：

- 使用 `PhalconSetList::COMPOSER_BASED` 能正常加载 Rector 配置。
- 探测失败时不会注册 Phalcon 规则。
- 探测到 Phalcon 5 时会注册 `phalcon50.php` 中规则。

---

## 九、实施记录

1. [x] 明确版本到 set 的最终映射表。
2. [x] 修正 `PhalconSetResolver` 的映射逻辑。
3. [x] 为 `PhalconVersionResolver` 增加可测试注入点，避免测试强依赖真实 Composer 环境。
4. [x] 新增 `src/Support/Set/ComposerBased/config.php`。
5. [x] 在 `PhalconSetList` 中增加 `COMPOSER_BASED` 常量。
6. [x] 扩展 SetList 测试。
7. [x] 新增 Resolver 单测。
8. [x] 新增配置集成测试。
9. [x] 更新 README：增加 Composer-based 用法和限制。
10. [x] 更新 `plan/PHALCON3_TO_5_PLAN.md`：说明 3 → 5 不依赖该方式一次混跑。

---

## 十、验收标准

1. 用户可以使用 `PhalconSetList::COMPOSER_BASED` 配置 Rector。
2. 探测到 Phalcon 5 时自动选择 `UP_PHALCON_4_TO_5`。
3. 探测不到版本时 no-op，不报错。
4. 不会同时加载多个阶段的 set。
5. 3 → 5 仍由 CLI wrapper 分阶段执行。
6. 单测覆盖主要版本来源和边界情况。
7. README 明确说明 `COMPOSER_BASED` 与固定阶段常量的区别，并说明本包不启用自动加载。
8. 全量 PHPUnit 通过。
9. `composer validate --no-check-publish` 通过。

---

## 十一、一句话总结

Composer-based 配置不是让 Rector 核心支持 `withComposerBased(phalcon: true)`，而是本项目提供自己的 `PhalconSetList::COMPOSER_BASED`：

> 根据项目声明或安装的 Phalcon 版本，自动选择一个正确的升级阶段规则集，避免用户手动判断该用哪个 set。
