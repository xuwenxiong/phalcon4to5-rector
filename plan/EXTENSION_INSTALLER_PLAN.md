# 不采用 Extension Installer 自动加载

> 状态：决策已实施。本包不再通过 `rector/extension-installer` 自动加载规则集。
>
> 相关文档：
>
> - `plan/DIRECTORY_STRUCTURE_PLAN.md`：规则集路径与命名空间结构
> - `plan/COMPOSER_BASED_PLAN.md`：按项目环境自动选择规则集
> - `plan/PHALCON3_TO_5_PLAN.md`：3 → 5 分阶段直接升级规划

---

## 一、决策

本包的 `composer.json` 不再包含：

```text
extra.rector.includes
config.allow-plugins.rector/extension-installer
```

保留 `type: rector-extension` 仅作为包类型元数据，不用于自动注册规则。

用户必须在项目 `rector.php` 中显式选择规则集：

```php
use RectorPhalcon\Set\PhalconSetList;

return RectorConfig::configure()
    ->withSets([
        PhalconSetList::UP_PHALCON_4_TO_5,
    ]);
```

或：

```php
return RectorConfig::configure()
    ->withSets([
        PhalconSetList::COMPOSER_BASED,
    ]);
```

---

## 二、为什么不采用自动加载

`extension-installer` 的机制是：

```text
composer install / composer update
  └─ 扫描 vendor/ 下 composer.json
      └─ 找出 "type": "rector-extension" 的包
          └─ 读取 extra.rector.includes
              └─ 写入 GeneratedConfig.php

rector 启动
  └─ 自动 include GeneratedConfig.php 中登记的所有配置
```

它是**无条件加载**：

- 不判断项目处于 Phalcon 3、4 还是 5。
- 不判断用户是否想执行升级。
- 不判断用户是否已经显式配置了另一个阶段。
- 多套规则同时登记时容易互相污染。

对单一目标版本工具，这种零配置体验有价值；但本项目定位是通用 Phalcon 多版本升级工具，未来会同时存在：

```text
3 → 4
4 → 5
3 → 5
5 → 6
```

如果默认自动加载，用户安装本包后可能不清楚自己启用了哪个升级阶段。

---

## 三、去掉后的收益

1. 用户必须显式选择阶段，行为更可预测。
2. 不会和 `PhalconSetList::UP_PHALCON_4_TO_5`、`COMPOSER_BASED` 重复加载。
3. 3 → 5 直接升级不需要 autoload gate 环境变量。
4. 未来新增 3 → 4、5 → 6 规则集时，不会自动污染现有用户项目。
5. 更符合 Rector 作为代码改写工具的高风险操作习惯：显式配置优先。

---

## 四、代价

用户安装后必须写 `rector.php`，不能再只安装就运行。

这是有意取舍：

> 升级工具的执行条件应该显式；隐式自动启用多阶段规则的风险高于少写几行配置的成本。

---

## 五、当前代码状态

- `composer.json`：
  - 无 `extra.rector.includes`
  - 无 `rector/extension-installer` 插件白名单
  - 保留 `type: rector-extension`
- README：
  - 不安装 `rector/extension-installer`
  - 使用章节只保留 SetList 常量与 Composer-based 两种方式
- `src/Phalcon5/Config/Set/phalcon50.php`：
  - 无 autoload gate
  - 正常作为显式规则集使用
- 3 → 5 直接升级：
  - 规划中不再需要 `RECTOR_PHALCON_SKIP_AUTOLOAD`
  - CLI wrapper 只负责按阶段选择对应 set

---

## 六、结论

本项目不采用 `rector/extension-installer` 的自动加载机制。

规则集只通过以下方式生效：

```text
PhalconSetList::UP_PHALCON_X_TO_Y
PhalconSetList::COMPOSER_BASED
未来的 upgrade:3-to-5 CLI wrapper
```
