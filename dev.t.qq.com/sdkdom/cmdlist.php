<?php
@header('Content-Type:text/html;charset=utf-8'); 
session_start();
@require_once('config.php');
@require_once('oauth.php');
@require_once('opent.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="minimum-scale=1.0, maximum-scale=1.0, initial-scale=1.0, width=device-width, user-scalable=no">
<link href="css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<div class="main">
		<h1>生成授权连接</h1>
		<div>
			授权完成!
		</div>
		<div class="tips">
			<h3>时间线相关</h3>
			<ol>
				<li><a href="exp/timeline.php">主页或其他用户时间线</a></li>
				<li><a href="exp/public_timeline.php">广播大厅时间线</a></li>
				<li><a href="exp/my_timeline.php">和我相关的时间线</a>
					<ol type="a">
						<li><a href="exp/my_timeline.php">用户提及时间线</a></li>
						<li><a href="exp/my_timeline.php">我发表的时间线</a></li>
					</ol>
				</li>
				<li><a href="exp/ht_timeline.php">话题时间线</a></li>
				<li><a href="exp/special_timeline.php">特别收听的人发表时间线</a></li>
			</ol>
			<h3>微博相关</h3>
			<ol>
				<li><a href="exp/show.php">获取一条微博数据</a></li>
				<li><a href="exp/add.php">发表微博</a>
					<ol type="a">
						<li><a href="exp/add.php">发表一条带图片的微博</a></li>
						<li><a href="exp/add.php">发表音乐微博</a></li>
						<li><a href="exp/add.php">发表视频微博</a></li>
						<li><a href="exp/add.php">转播一条微博</a></li>
						<li><a href="exp/add.php">回复一条微博</a></li>
						<li><a href="exp/add.php">点评一条微博</a></li>
					</ol>
				</li>
				<li><a href="exp/re_count.php">转播数</a></li>
				<li><a href="exp/re_list.php">获取单条微博的转发或点评列表</a></li>
				<li><a href="exp/getvideoinfo.php">获取视频信息</a></li>
			</ol>
			<h3>帐户相关</h3>
			<ol>
				<li><a href="exp/info.php">获取用户详细资料</a>
					<ol type="a">
						<li><a href="exp/info.php">获取自己详细资料</a></li>
						<li><a href="exp/info.php">获取其他用户详细资料</a></li>
					</ol>
				</li>
				<li><a href="exp/update.php">更新用户信息</a></li>
				<li><a href="exp/update_head.php">更新用户头像信息</a></li>
				<li><a href="exp/update_edu.php">更新用户教育信息</a></li>
			</ol>
			<h3>关系链相关</h3>
			<ol>
				<li><a href="exp/getfans.php">获取听众列表/偶像列表</a>
					<ol type="a">
						<li><a href="exp/getfans.php">获取听众列表</a></li>
						<li><a href="exp/getfans.php">获取我收听的列表</a></li>
						<li><a href="exp/getfans.php">获取username的听众列表</a></li>
						<li><a href="exp/getfans.php">获取username收听的列表</a></li>
					</ol>
				</li>
				<li><a href="exp/getblack.php">获取黑名单列表</a></li>
				<!--li><a href="exp/speciallist.php">获取我的/用户的特别收听列表</a></li-->
				<li><a href="exp/setmyidol.php">收听/取消收听某人</a>
					<ol type="a">
						<li><a href="exp/setmyidol.php">收听某人</a></li>
						<li><a href="exp/setmyidol.php">取消收听某人</a></li>
						<!--li><a href="exp/setmyidol.php">特别收听</a></li-->
						<!--li><a href="exp/setmyidol.php">取消特别收听</a></li-->
						<li><a href="exp/setmyidol.php">加入黑名单</a></li>
						<li><a href="exp/setmyidol.php">从黑名单中删除</a></li>
					</ol>
				</li>
				<li><a href="exp/checkfriend.php">查看用户/粉丝</a></li>
			</ol>
			<h3>私信相关</h3>
			<ol>
				<li><a href="exp/postmail.php">发私信</a></li>
				<li><a href="exp/delmail.php">删除私信</a></li>
				<li><a href="exp/maillist.php">收件箱/发件箱</a></li>
			</ol>
			<h3>TAG相关</h3>
			<ol>
				<li><a href="exp/addtag.php">增加tag</a></li>
				<li><a href="exp/deltag.php">删除tag</a></li>
			</ol>
			<h3>搜索相关</h3>
			<ol>
				<li><a href="exp/search.php">搜索</a>
					<ol type="a">
						<li><a href="exp/search.php">搜索用户</a></li>
						<li><a href="exp/search.php">搜索消息</a></li>
						<li><a href="exp/search.php">搜索标签</a></li>
					</ol>
				</li>
			</ol>
		</div>
		</div>
	</div>
</body>
</html>

