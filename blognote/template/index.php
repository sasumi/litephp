<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
			<title>Document</title>
			<?php echo css('reset.css', 'global.css', 'index.css');?>
		</head>
	<body>
		<div class="page">
		<div class="header">
			<h1 class="logo"><a href="">note</a></h1>
			<ul class="project-nav hide">
				<li><a href="">project one</a></li>
			</ul>
			<ul class="shortcut hide">
				<li><a href="">添加笔记</a></li>
			<li><a href="">添加分类</a></li>
			</ul>
		</div>

		<div class="container">
			<div class="sidebar">
				<form action="" class="search">
					<input type="text" name="" id="" class="txt">
					<input type="button" value="search" class="btn search-btn">
				</form>

				<dl class="all-catalog">
					<dd>
						<a href=""><i></i>全部笔记</a>
						<span class="cnt">12</span>
					</dd>
				</dl>

				<dl class="catalog">
					<dt>分类</dt>
					<dd>
						<a href="">工作</a>
						<span class="cnt">0</span>
						<a href="" class="add-btn">add</a>
					</dd>
					<dd class="current">
						<a href="">专业心得</a>
					</dd>
					<dd><a href="">Javascript</a></dd>
				</dl>

				<dl class="todo-list">
					<dt>标签</dt>
					<dd><a href="">dd</a></dd>
					<dd><a href="">dd</a></dd>
					<dd><a href="">dd</a></dd>
					<dd><a href="">dd</a></dd>
				</dl>

				<dl class="other-fun">
					<dt>其他</dt>
					<dd><a href="">草稿</a></dd>
					<dd><a href="">回收站</a></dd>
				</dl>
			</div>

			<div class="data-list">
				<h2>专业心得</h2>
				<table>
				</table>
			</div>

			<div class="content">
				<h2>content</h2>
				<ul class="op-list">
					<li><a href="">删除</a></li>
				</ul>
				<div class="main">
					main
				</div>
			</div>
		</div>
	</div>
</body>
</html>