<?php
session_start();
unset($_SESSION[code]);
$code=substr(md5(rand()),10,4);
$_SESSION[code]=$code;
$font = new SWFBrowserFont('verdana');
//$font = new SWFFont( 'verdana' );    //设置文字显示的字体，这个在支持自定义字体方面还比较麻烦，现在使用系统字体吧。
$tf = new SWFTextField();             //设置文本域
$tf->setFont( $font );                   //指定文本使用的字体
$tf->setColor( rand(90,255),rand(90,255),rand(90,255) );   //文本的颜色
$tf->setHeight( 80 );                //文本高度
$tf->addString( $code);            //添加文本内容
$m = new SWFMovie();            //创建一个flash文件
$m->setbackground( 60,60,60 );   //设置背景颜色
$m->setDimension( 250, 80 );       //设置flash的大小
$f_tf = $m->add( $tf );               //将文本域添加到影片中
$f_tf->moveTo( -1000, 0 );         //将文本对象移到影片左边，-1000应该让大家看不到的了。
for( $i = 0; $i < 31; $i++ ) {
$m->nextframe();                     //影片播放下一帧
if ($i>25)
$f_tf->moveTo( ($i-10)*($i-10), 0 );   //将文本对象加速向右移动，飞出右边框
else if($i>5)
$f_tf->moveTo( ($i-5)*4, 0 );            //慢点移动，好让大家看清文本内容
else
$f_tf->moveTo( -($i-15)*($i-15), 0 );   //迅速从左边飞到中间
}
header('Content-type: application/x-shockwave-flash');  //设置输出格式
$m->output();                                          //将影片输出