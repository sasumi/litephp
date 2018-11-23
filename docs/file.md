# 常规项目文件说明

``` cmd
├─api                                                   //api入口目录,可以根据实际项目配置其他的入口位置
│      index.php                                        //api入口引导文件
│
├─app
│  ├─api                                                //api逻辑目录
│  ├─controller                                         //控制器controller目录
│  │      AccessController.php                          //控制器
│  │      ...
│  │
│  ├─include                                            //项目额外实现类库（命名空间在 {app}\下面）
│  │      Auth.php
│  │      ViewBase.php                                  //View基类,一般建议项目有自身的View来实现相应的视图定制
│  │
│  ├─model                                              //业务Model页面
│  │      AppBizReport.php
│  │      ...
│  │
│  └─template                                           //模版文件配置
│      ├─crud                                           //缺省CRUD模版目录(可通过ControllerInterface重载)
│      │      index.php                                 //CRUD列表页
│      │      info.php                                  //CRUD信息页
│      │      update.php                                //CRUD编辑\新增页面
│      ├─index                                          //对应IndexController控制器页面
│      │      deny.php
│      │      ...
│      └─ ...
│
├─config                                                //应用配置目录
│      app.inc.php                                      //应用基础信息配置
│      router.inc.php                                   //路由规则配置 -
│      ...
│
├─database                                              //数据库模型目录 -
│  └─www
│      │  db.inc.php                                    //数据库连接配置(可被Model子类覆盖) -
│      │
│      ├─db_definition                                  //公用数据表定义(继承DB/Model)
│      │      TableAppBizReport.php                     //实际数据库表定义类
│      │      ...
│      └─ ...                                           //其他DB库
│
├─public                                                //对外开放目录
│  │  index.php                                         //主引导脚本
│  │
│  ├─static                                             //静态资源脚本(目录可在app.inc.php)中定制
│  │  ├─css                                             //样式目录
│  │  │      default.css
│  │  │
│  │  ├─img                                             //图片目录
│  │  │      default_image.png
│  │  │
│  │  └─js                                              //javascript目录
│  │          global.js
│  │
│  └─upload                                             //上传目录(仅限于当前目录提供文件存储)
├─script                                                //项目运行脚本(包括crontab脚本)
│      scaffold.php                                     //系统脚手架
│
├─tmp                                                   //系统缓存目录
└─vendor                                                //第三方库目录
    └─autoload.php                                    //第三方库加载脚本
```