# 配置说明

[TOC]

应用配置项一般存放于 /config/目录，文件命名采用 `$name.inc.php`（没有采用以上规则的文件将不会被Lite\Config::get方法获取到）。
配置文件通过返回数组形式封装配置项，如： 

``` php
 <?php return array('key'=>'val'); 
```

## app.inc.php（基础应用配置）

| 配置项 | 解释 | 默认值 | 是否必填 | 举例 |
| ----- | -----| ----- | ----- | ----- |
| url| 应用访问URL路径，以斜杠结尾 | - | 必填 | "http://www.site.com/app/" |
| static | 静态资源访问URL路径，以斜杠结尾 | - | 必填 | "/static/" |
| debug | 是否开启debug调试 | false | 非必填 | true |
| render | 应用访问URL路径，以斜杠结尾 | ViewBase::class | 非必填 | MyView::class |
| page404 | 404页面地址，或处理回调 | - | 非必填 | 未配置该项值，项目404直接以message方式显示。如可配置为：<br /> function($err){ echo $err; } |
| pageError | 程序错误统一地址（500），或处理回调 | - | 非必填 | 同上 |

示例代码：
``` php
<?php
return [
	'url' => 'http://www.site.com/app/',
	'debug' => true
];
```

## router.inc.php（路由配置，可选）

| 配置项 | 解释 | （默认值 | 是否必填 | 举例 |
| ----- | -----| ----- | ----- | ----- |
| mode | 路由模式 | Router::MODE_NORMAL 普通路由模式（以?r=uri作为路由识别） | 非必填 | Router::MODE_REWRITE |
| router_key | 路由索引键 | "r" | 非必填 | "rt" |
| default_controller | 默认控制器 | "index" | 非必填 | "main" |
| default_action | 默认方法 | "index" | 非必填 | "mainMethod" |

示例代码：
``` php
<?php
return [
	'mode' => Router::MODE_REWRITE
];
```

## 自定义配置项

用户可自行新增代码使用配置数据。使用 `Lite\Core\Config` 类可通过多层路径访问获取配置数值。例：

access.inc.php

``` php
<?php
return [
    'MaxLoginCount' => 3，
    'DefaultUser' => [
        'name' => 'Jackson'
    ]
];
```

调用方式：

``` php
<?php
    use \Lite\Core\Config;
    $max_login_count = Config::get('access/MaxLoginCount');  //3
	$def_user = Config::get('access/DefaultUser'); // ['name'=>'Jackson']
	$def_user_name = Config::get('access/DefaultUser/name'); // Jackson
```

