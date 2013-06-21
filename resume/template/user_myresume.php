<?php include 'header.inc.php'?>
<?php echo css('user.css')?>
<?php include 'usernav.inc.php'?>
<div class="page-user-myresume clearfix">
	<div class="left-col">
		<a href="<?php echo url('resume/create')?>" class="btn">创建简历</a>
		<ul class="myresume-list">
			<?php for($i=10; $i>0; $i--):?>
			<li>
				<h2>我的第一份简历</h2>
				<p class="abs">
					这个是简历的摘要信息，可以由简历的文本内容中获取，或者由简历的描述生成<br/>
					当然，也是可以包含换行符号的。
				</p>
				<p class="info">
					<span class="date">2013-12-03</span>
					<span class="type">模板类型：销售</span>
					<span class="size">文件长度：2页以上</span>
				</p>
				<p class="op">
					<a href="<?php echo url('resume/detail', array('id'=>'xxdac'))?>">阅读(232次)</a> |
					<a href="<?php echo url('resume/modify', array('id'=>'xxdac'))?>">修改</a> |
					<a href="<?php echo url('resume/delete', array('id'=>'xxdac'))?>">删除</a>
				</p>
			</li>
			<?php endfor;?>
		</ul>
	</div>
	<div class="right-col">
		<div class="page-tip">
			除了在这里创建您的简历之外, 您还可以通过上传文件,或者发送您的简历信息到我们的邮箱:
			<a href="mailto:xxa@a.com">xxa@a.com</a>
		</div>
	</div>
</div>
<?php include 'footer.inc.php'?>
