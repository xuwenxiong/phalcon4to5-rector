### 方式一：自动加载（推荐）

本包已通过 `extra.rector.includes` 注册规则集。在项目中安装 [`rector/extension-installer`](https://github.com/rectorphp/extension-installer) 插件后，`composer install` 时会自动发现并加载 `phalcon50` 规则集，无需任何手动配置：

```bash
composer require --dev rector/extension-installer
```

#### 原理

`composer install/update`时扫描`config/sets/phalcon50.php`中的规则配置生成`vendor/rector/extension-installer/src/GeneratedConfig.php`，
Rector 启动时`Rector\BootstrapExtensionConfigResolver`自动加载 `GeneratedConfig.php`中规则配置。所以在项目的 `rector.php` 里不用再手写 `$rectorConfig->sets([...])`。
```text
composer install/update
  └─ rector/extension-installer 扫描 vendor/ 下所有包的 composer.json
      └─ 找出 "type": "rector-extension" 的包（本包就是）
          └─ 读取该包 extra.rector.includes 中登记的路径
              └─ 本包登记的是 config/sets/phalcon50.php
                  └─ 汇总写入 vendor/rector/extension-installer/src/GeneratedConfig.php
rector 启动
  └─ 自动加载 GeneratedConfig.php
      └─ include 其中登记的所有配置文件
          └─ 本包的 config/sets/phalcon50.php 被加载
              └─ phalcon50.php 里的规则生效                  
```