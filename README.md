# LitePHP手册

## 简介

## 快速入门

项目中主要配置文件如下：

* `/index.php` 入口文件，引入litephp bootstrap启动脚本，配置命名空间
  主要调用：  
```
Application::init(__NAMESPACE__);
```

* `/protected/config/app.inc.php` 项目路径信息、核心组件配置
* `/protected/config/db.inc.php` 数据库配置信息（如无数据库项目，该文件可不需要）
* `/protected/config/nav.inc.php` 菜单项配置（CB项目中需要）

## 目录结构说明
```
├─app                   //程序业务逻辑目录
│  ├─api                //对外API目录?
│  ├─controller         //Controller
│  ├─include            //程序内部公用类库（如果是外部库）
│  ├─model              //Model
│  └─template           //模版
│      ├─access
│      ├─admin
│      ├─usergroup
│      └─userlog
├─config                //程序配置目录，可以通过Config::get('aa/bb')方式读取
├─database              //程序数据库定义目录(可外配)
│  ├─log                //log数据库定义
│  │  └─db_definition   //定义table
		db.inc.php      //数据库连接信息
│  └─www                //默认网站www配置
│      └─db_definition
├─document              //程序文档
├─public                //httpd开放目录
│  ├─static             //静态资源目录
│  │  ├─css
│  │  ├─img
│  │  └─js
│  └─upload             //上传目录
├─script                //站点脚本
│  └─build
├─tests                 //测试用例
└─tmp                   //缓存
```


## CRUD 模型
1. 脚手架

	项目中需要自行构建脚手架脚本，在脚本中主动引入boostrap，调用CodeGenerator。
	实际代码如：
```
use Lite\Cli\CodeGenerator;
include '../../../litephp/bootstrap.php';
CodeGenerator::Load();
```
2. 数据映射
	MySQL数据类型将由一下映射规则进行 table <=> properties_define 属性定义进行映射
	|-- MySQL数据类型 --|-- Property 定义类型 --|
	|-- varchar --|-- string --|

3. **日期**


## 核心组件
1. 核心

## MySQL数据类型-HTML表单映射
1. 数字
	1. tinyint，smallint，mediumint，int，bigint
		* 如果define里面没有额外声明options会被转换成`input:number`
	      如果定义UNSIGNED,会产生 min=0
	      
	2. decimal，float，double
		* 转换成 `input:number`
		* 根据小数点精度产生相应的 step精度控制,如step=0.01
		* 如果定义UNSIGNED,会产生 min=0

2. 日期
	1. year
		* 产生 `input:text`
				
	2. datetime
		* 产生 `input:text`
		
	3. date
		* 产生 `input:text`
				
	4. time
		* 产生 `input:text`
		
	5. timestamp
		* 不产生表单元素
		
3. 字符
	1. char
		* 产生 `input:text`
	
	2. varchar
	3. tinytext
		* 产生 `textarea`
		
	4. text
		* 产生 简单富文本编辑器[1]
		
	5. mediumtext
		* 产生复杂富文本编辑器[2]
		
	6. longtext
	7. enum
		* 产生 `select` 或 `input:radio`
		* 备注内包含格式(a,b,c) 被作为对应的(按照下标排序)选项名称
	   
	8. set
		* 产生 `input:checkbox`
		* 备注内包含格式(a,b,c) 被作为对应的(按照下标排序)选项名称
		
4. **通用规则**
	1. 数据表comment缺省被解析为模型(model)名称
	2. 字段comment缺省被解析为alias(字段名称)
	3. 未设置default=NULL的字段将被定义为required,	表单元素追加required="required"属性
	4. 备注内包含括号部分,如:(note)，将被解析为字段补充描述(description),追加在输入表单后面,enum,set除外
	5. default在不为null情况下,值将被填入默认表单值(新建页面)
	6. primary key缺省为readonly，不产生表单元素
	

	
## CB通用后台说明
1. 样式
	1. 缺省所有`input:text`，input:password，input:number 追加类名.txt	
	2. 如果定义里面包含rel=*属性，该属性会被定义在表单元素里面，如`input:text`\[rel=upload-image\]
	3. 如定义rel=tags，元素将被转换为 ul.tags>li>{text}
	   表单元素将被转换为	   
```
<div class="tags-input">
	<ul class="tags">
		<li>tag1</li>
	</ul>
	<input type="text" />
</div>
```

#MySQL构建规范建议
1. 数值	
2. 日期
3. 时间戳
4. 主键
5. 表名
6. 备注
7. 分库、分表、分区

   
[1]: 简单富文本编辑器不包含图片、或其他资源上传功能。 http://www.baidu.com 