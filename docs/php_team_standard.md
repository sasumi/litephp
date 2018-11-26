# PHP 编码检查

[TOC]

## 前言
编写本手册目的在于：
1. 规范程序开发人员代码编写行为 
2. 减少发布环境编码缺陷 
3. 纳入技术开发人员绩效考核

## 编码基础
当前编码标准主要针对
1. PHP：5.6+版本语法
2. HTML：4.0+版本语法
3. CSS：2.0版本语法，不包含针对特定浏览器、场景进行HACK代码
4. Javascript:1.8+

## 严格检查

严格检查标准表示，以下所列举（包含并不仅限于）代码检查项目，将作为代码质量考核标准首要项目。 

技术开发人员在编码过程中，如提交涉及以下项目代码内容，将被作为 代码 **严重缺陷** 评估。```

**1.禁止在foreach循环中使用引用方式对元素进行修改**
```php
foreach ($list as $k => &$item) {
    //
}
```

**2. Controller、Class、Model等业务数值处理逻辑中使用 *number_format* 方法进行数字取位**

```除非真实需要处理数字表现加上千分位分隔符，否则千分位数字精度取位必须使用 round()或其他精度处理函数```

**3. 禁止使用空置try···catch语句，消耗代码性能，浪费代码行数**
``` php
try {
    //....
} catch(Exception $e){
    throw $e;
}
```

**4. 禁止 数据处理脚本、一次性数据清洗脚本、测试脚本、测试代码放入正常业务Controller::action里面。**

接口可用性校验脚本除外，所有类似代码统一放入script代码（或其他独立目录），或不放入svn主线。

**5. 未定义变量、常量、函数、类名、方法**（魔术方法特别对待） 

未定义变量除非出现在合适场景：如模板语法等人为制定特殊场景 
```php
//场景1：常量当做字符串使用
$a = $row[name]; //fail
$a = $row['name']; //correct
$_ids=$select_code->asArray()->getPairData("item_id", item_id); //fail

//场景2：类名大小写与原类名不一致
$app = new APPlication(); //fail

//场景3：函数名大小写错误
$str = str_Replace('aa','b', $source); //fail
$len = strLen('aaaa'); //fail

//场景4：方法名大小写错误
$a = $obj->GeTmyName(); //fail
```

**6. 个人调试、废弃逻辑代码、功能测试、接口测试、定位问题、临时处理脚本或其他 无实际意义注释代码**
```php
//场景1：
//dump('xxx'); die;

//场景2：
//暂时不启用该功能
//$a = new A();
//$a->execute();

//场景3：
if("done"==$done){ //查看已经完成的
//$select->where("clearance_name<>'' and clearance_code<>'' and clearance_price>0");
}

//场景4：
//审核通过的状态下
//$select->where("status=?",Table_Prd_Product::STATUS_ACCESS);
//品类控制
//$select = ACL::addCatalogControl($select);
//个人级部门级控制
//$select = ACL::addDataControl($select,"purchase_userid","clearance@product");

//场景4：空白注释
//删除当前品类下的所有负责人
//
//
foreach ($user_cat as $user){
```

**7. URL拼接方法不考虑字符：?与&**
拼接URL时，不考虑原串是否包含?或者&字符，可能会因为路由方式的改变，导致拼接逻辑错误。
如： 
```php
var url = "<?=url("shipping/getShippingMethodByWh@common")?>"+"?id="+$(this).val();
$("#btn-batch_code").attr('href',"<?=url("batch/getcode")?>"+"/account/"+account);
```

**8. 代码语法解析错误**
这里主要针对IDE能够及时检测出代码格式错误（ERROR），包含以下几种场景

* PHP语法错误
* HTML标签嵌套不合理，如：

``` html
<table>
    <div>
        <tbody></tbody>
    </div>
</table>
```

* CSS属性拼写错误，如：

```css
a {magin-top:1em} /** fail **/
```

* CSS包含错误分号，如：
```css
a {margin-top:1em}; /** fail **/
```

**9. 使用 *strtotime("$m month")* 来计算月份**
**由于 -xx month不能计算出准确天数，如在使用过程严格需要计算天数，需要使用 mktime来推导**

**10. 单词拼写错误**

*单词拼写检查不包含：特有名词（如winnt、temtop）、单词缩写（单词缩写规则请自行参考网上规则）、精简变量*

**11. 多个属性、常用值未定成为常量、公共变量**
多个属性一般指包含3个以上属性，某些简单实用场景：如is_del, is_update等可酌情考虑
```php
//场景1：订单类型可定义成为订单类常量
if($order_type == 'F'){
    //...
} else if($order_type == 'O'){
    //...
}

//场景2：status、state、type等具备多个固定值时，需要针对每个固定值定义常量，尤其禁止使用常量值进行运算、比较！！！
$sql = "SELECT * FROM order WHERE status > 1";

//场景3：需要针对固定文本定义全局文本数组，方便其他程序公用
if($order_type == ORDER_TYPE_NORMAL){
    echo "正常";
}
if($order_type == ORDER_TYPE_DISCARD){
    echo "废弃";
}
if($order_type == ORDER_TYPE_PUBLISH){
    echo "发布";
}
```

**12. 代码公开访问目录遗留有 泄露风险文件，如包含：**

- 源代码压缩包，如：source.tar.gz
- SQL数据文件，如：bak.sql
- 临时处理数据文件，如：userlist.txt
- 包含phpinfo()检测代码，如：phpinfo.php
- 未测试隐藏入口脚本，如：back.php
- log日志文件，如：www.a.com-error.log
- 敏感信息配置文件，如：database.php，database.yaml
- 产品、技术相关敏感文档：如：加密协议.doc

## 告警检查
> - 告警检查主要基于在PHP IDE语法识别为出发点，提醒编码人员在编码过程中，对未识别变量、方法、类型等进行重定义。
> - 当前推荐使用 *PHPStorm* 系列产品（以下以*PHPStorm*为基准）进行PHP语法、语义检查，以下告警检查标准为编码过程推荐标准。
> - **代码质量评级标准中，最高质量标准必须通过所有IDE语义检查！**

### 设置PHP版本环境、包含外部类库
IDE环境应当正确设置PHP版本信息，PHP命令检查器（PHP CLI Interpreter）以及项目涉及框架等类库类库。
![IDE外部库设置][1]

** PHPDoc[^phpdoc]告警** 
PHP Doc告警包含以下几点：

- 缺失参数`@param`、缺失返回声明`@return`、缺失异常声明`@throws`
- 缺失类型声明、错误类型声明，如：
```php
class A{
    /**
    *  处理时间显示精确度为分钟
    * @param $time 类型 [fail]
    * @param string $str [fail]
    * @return false|string
    */
    public static function showTime(array $time, array $str){
        return '';
    }
}
```
** 废弃数据库表、字段未及时删除**
因数据处理、临时备份等原因，遗留数据库表、字段，在完成操作之后，长时间未清理删除。
如：*prd_product_copy*、 *prd_product_bak*、*content_bak*等

** 废弃文件未及时处理**