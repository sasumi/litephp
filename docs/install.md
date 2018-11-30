# 安装使用

[TOC]

## 环境要求
### 操作系统
框架对环境所在系统如特殊要求，可使用各种常见Linux发行版、Windows、Mac等环境，能正常运行PHP 5.6+环境即可。经验证过系统环境为 CentOS、Windows xp+。

### PHP环境
PHP 5.6 或以上，需安装以下扩展：
- php_mbstring **[必要]**
- php_pdo_mysql *[使用MySQL数据库时，推荐使用PDO驱动]*
- php_mysqli *[可选]*
- php_gd2  *[图片操作、验证码之类业务需要]*
- php_curl  *[网络请求场景需要]*

PHP 需设置配置项：
``` ini
date.timezone = Asia/Shanghai
short_open_tag = On
error_reporting = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
expose_php = Off
```

如LitePHP被多个项目同时调用，建议将LitePHP所在目录设置为 PHP Include路径。即在php.ini中开启如下配置：

``` ini
; 如在windows环境，启用下面代码
include_path = ".:/LitePHPDir"

; 如在Linux环境，启用以下代码
include_path = ".:/LitePHPDir"
```

项目中可统一使用简单方法引入：

``` php
include 'litephp/bootstrap.php';
```

### WebServer环境

如搭建项目为CGI服务（如网站），需假设WebServer服务。LitePHP对web服务无特别要求，仅在使用路由重写方式时，需Web服务器提供一下正确的环境配置：

a. 支持正确URL PathInfo到PHP环境PathInfo的解析映射

b. 支持访问目录路由重写。

以下为Apache路由重写范例（.htaccess 文件内容）：

``` apacheconfig
<IfModule mod_rewrite.c>
	RewriteEngine on
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
</IfModule>
```

## 代码获取

最新代码请访问 [GitHub](https://github.com/sasumi/litephp) 获取最新代码，或从 [这里](https://github.com/sasumi/litephp/archive/master.zip) 直接下载代码压缩包。

## 安装初始化

1. 下载框架代码，将框架代码解压到与代码同一级目录下（建议不要放在Web访问目录）。如
  ```cmd
  |-- dir/框架代码目录/
  |-- dir/框架代码目录/src
  |-- dir/框架代码目录/doc
  |-- dir/项目文件/
  ```

2. 初始化项目目录结构如下：
  ```cmd
  |-- dir/项目文件/
  |-- dir/项目文件/app
  |-- dir/项目文件/app/controller
  |-- dir/项目文件/app/model
  |-- dir/项目文件/app/template
  |-- dir/项目文件/config
  |-- dir/项目文件/public
  ```

### 使用脚手架
项目中需要自行构建脚手架脚本，在脚本中主动引入boostrap，调用CodeGenerator。
实际代码如：

script.php

``` PHP
use Lite\Cli\CodeGenerator;
include '../../../litephp/bootstrap.php';
CodeGenerator::Load();
```

实际执行：

``` shell
php script.php project Helloworld ./
php script.php allmodel -o
```

