<?php
//用户基本信息
return array(
	'base' => array(
		'name' => '默认',
		'css' => <<<EOT
.resume-mod-info-base dl {float:left; width:50%; overflow:hidden; height:28px; margin:5px 0;}
.resume-mod-info-base dt {float:left; width:90px; padding-left:0;}
.resume-mod-info-base .resume-mod-con input.txt {width:225px; border:1px solid #ccc; border-color:white white #ccc white; border-radius:0}
.resume-mod-info-base .resume-mod-info-target-item {width:100%; height:64px; margin-top:20px;}
.resume-mod-info-base .resume-mod-info-target-item .txt {width:560px; height:50px; resize:none}
EOT
	),

	'simple' => array(
		'name' => '简约',
		'css' => <<<EOT
.resume-mod-info-simple dl {float:left; width:50%; overflow:hidden; height:28px; margin:5px 0;}
.resume-mod-info-simple dt {float:left; width:90px; padding-left:0; color:blue;}
.resume-mod-info-simple .resume-mod-con input.txt {width:225px; border:1px solid #ccc; border-color:white white #ccc white; border-radius:0}
.resume-mod-info-simple .resume-mod-info-target-item {width:100%; height:64px; margin-top:20px;}
.resume-mod-info-simple .resume-mod-info-target-item .txt {width:560px; height:50px}
EOT
	),
	'hot' => array(
		'name' => 'Hot',
		'css' => <<<EOT
.resume-mod-info-hot dl {float:left; width:50%; overflow:hidden; height:28px; margin:5px 0;}
.resume-mod-info-hot dt {float:left; width:90px; padding-left:0; color:red;}
.resume-mod-info-hot .resume-mod-con input.txt {width:225px; border:1px solid #ccc; border-color:white white #ccc white; border-radius:0}
.resume-mod-info-hot .resume-mod-info-target-item {width:100%; height:64px; margin-top:20px;}
.resume-mod-info-hot .resume-mod-info-target-item .txt {width:560px; height:50px}
EOT
	)
);