<?php
return array(
		'name' => '基础',
		'thumb' => '{$THEME_IMG_URL}base.thumb.png',
		'css' => <<<EOT
.resume-theme-base .txt {background-color:transparent;}
.resume-theme-base .resume-conver {background:url("{$THEME_IMG_URL}base.conver.png") no-repeat 0 0 white; color:#79A0E4}
.resume-theme-base .resume-conver span {text-indent:300px}
.resume-theme-base .resume-conver-name {font-size:42px; margin-top:260px}
.resume-theme-base .resume-conver-email {margin-top:325px}
.resume-theme-base .resume-conver-mobile {margin-top:365px}
.resume-theme-base .resume-main {background:url("{$THEME_IMG_URL}base.main.hd.jpg") no-repeat 0 0 white}
.resume-theme-base .resume-main .resume-main-wrap {background:url("{$THEME_IMG_URL}base.main.ft.jpg") no-repeat 0 100%}
EOT
);