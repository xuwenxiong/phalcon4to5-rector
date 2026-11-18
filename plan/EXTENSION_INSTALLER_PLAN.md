# Rector Extension Installer 自动加载规划

> 状态：已实施。本文记录 `extra.rector.includes` 的自动加载机制与多版本约束。
>
> 相关文档：
>
> - `plan/DIRECTORY_STRUCTURE_PLAN.md`：规则集路径与命名空间结构
> - `plan/COMPOSER_BASED_PLAN.md`：按项目环境自动选择规则集

---

## 一、目标

用户安装本包和 `rector/extension-installer` 后，无需在项目 `rector.php` 中手写 `$rectorConfig->sets([...])`，即可加载当前默认规则集。

当前默认规则集：

```text
src/Phalcon5/Config/Set/phalcon50.php
```

---

## 二、自动加载链路

```text
composer install / composer update
  └─ rector/extension-installer 扫描 vendor/ 下所有包的 composer.json
      └─ 找出 "type": "rector-extension" 的包
          └─ 读取该包 extra.rector.includes 中登记的路径
              └─ 本包登记 src/Phalcon5/Config/Set/phalcon50.php
                  └─ 汇总写入 vendor/rector/extension-installer/src/GeneratedConfig.php

rector 启动
  └─ 自动加载 GeneratedConfig.php
      └─ include 其中登记的所有配置文件
          └─ 本包的 src/Phalcon5/Config/Set/phalcon50.php 被加载
              └─ phalcon50.php 中注册的规则生效
```

对应 Composer 配置：

```text
extra.rector.includes
  └─ src/Phalcon5/Config/Set/phalcon50.php
```

---

## 三、当前边界

`extension-installer` 的加载是**无条件加载**：

- 它只按 `extra.rector.includes` 聚合路径。
- 不判断当前项目使用的是 Phalcon 3、4 还是 5。
- Rector 启动时 include 其中登记的所有配置文件。

因此当前只登记 `phalcon50.php`。

多版本共存时，不能简单把多个规则集都登记进去：

```text
不要默认登记：
  ├─ src/Phalcon4/Config/Set/phalcon40.php
  └─ src/Phalcon5/Config/Set/phalcon50.php
```

原因：

- 多阶段规则同时启用会互相污染。
- 3 → 4 与 4 → 5 的 `RenameClassRector`、`RenameUseImportsRector` 依赖不同阶段 AST 和 token 信息。
- v5 规则假设输入已是 Phalcon 4 形态。

---

## 四、使用方式

安装插件：

```bash
composer require --dev rector/extension-installer
```

安装本包后，正常执行 Rector 即可：

```bash
vendor/bin/rector process src --dry-run
vendor/bin/rector process src
```

---

## 五、与其他配置方式的关系

| 方式 | 行为 |
|---|---|
| `extra.rector.includes` | 无条件加载当前默认 `phalcon50.php` |
| `PhalconSetList::UP_PHALCON_4_TO_5` | 用户显式指定一个阶段 |
| `PhalconSetList::COMPOSER_BASED` | 根据项目环境自动选择一个阶段 |
| `upgrade:3-to-5`（规划中） | 分两个 Rector 进程串行执行 3 → 4、4 → 5 |

不要同时叠加：

- `extra.rector.includes` 默认自动加载
- `withSets([PhalconSetList::UP_PHALCON_4_TO_5])` 或 `COMPOSER_BASED`

否则可能重复加载同一个 set。

详细设计见 `plan/COMPOSER_BASED_PLAN.md`。
