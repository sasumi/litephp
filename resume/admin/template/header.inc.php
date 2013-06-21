<!DOCTYPE>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<title>blognote</title>
		<?php echo css('reset.css', 'global.css');?>
		<?php echo js('http://localhost/ysl/source/ysl.base.js');?>
	</head>
	<body>
		<div class="page">
			<div class="header">
				<h1 class="logo hide">
					<a href="<?php echo url();?>">resume</a>
					<span>beta 0.01</span>
				</h1>
				<ul class="main-nav">
					<li>
						<a href="<?php echo url()?>">导航</a>
						<ul>
							<li><a href="<?php echo SITE_URL?>">网站首页</a></li>
							<li><a href="<?php echo url('access/logout')?>">退出登录</a></li>
						</ul>
					</li>
					<li>
						<a href="<?php echo url('resume')?>">简历管理</a>
					</li>
					<li>
						<a href="<?php echo url('template/add')?>">履历录入</a>
					</li>
					<li>
						<a href="<?php echo url('setting/feeds')?>">资费设定</a>
					</li>
					<li>
						<a href="<?php echo url('payment/log')?>">充值记录</a>
					</li>
					<li>
						<a href="<?php echo url('user')?>">用户管理</a>
					</li>
					<li class=" last">
						<a href="<?php echo url('access')?>">权限管理</a>
					</li>
				</ul>

				<script type="text/javascript">
					YSL.use('', function(Y){
						Y.dom.all('.main-nav>li').on('mouseover', function(){
							this.all('ul').show();
						});
						Y.dom.all('.main-nav>li').on('mouseout', function(){
							this.all('ul').hide();
						});
					});
				</script>
			</div>
			<div class="container">
