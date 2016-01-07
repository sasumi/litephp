# LitePHP手册

## 简介

## 快速入门

## CRUD 模型
1. 脚手架

## 核心组件
1. 核心

## MySQL数据类型-HTML表单映射
1. 数字
	1. tinyint, smallint, mediumint, int, bigint
		* 如果define里面没有额外声明options会被转换成`input:number`
	      如果定义UNSIGNED,会产生 min=0
	      
	2. decimal, float, double
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
	4. 备注内包含括号部分,如:(note), 将被解析为字段补充描述(description),追加在输入表单后面,enum,set除外
	5. default在不为null情况下,值将被填入默认表单值(新建页面)
	6. primary key缺省为readonly, 不产生表单元素
	

	
## CommonBackend通用后台说明
1. 样式
	1. 缺省所有`input:text`, input:password, input:number 追加类名.txt	
	2. 如果定义里面包含rel=*属性, 该属性会被定义在表单元素里面, 如`input:text`\[rel=upload-image\]
	3. 如定义rel=tags, 元素将被转换为 ul.tags>li>{text}
	   表单元素将被转换为 div.tags-input>(ul.tags>li>{text}+`input:text`)
	
   
[1]: 简单富文本编辑器不包含图片、或其他资源上传功能。 http://www.baidu.com 