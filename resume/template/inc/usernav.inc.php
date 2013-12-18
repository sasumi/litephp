<?php $CUR_CLASS = array(); $CUR_CLASS[$ACTION] = 'current';?>
<div class="left-col">
	<dl class="usernav">
		<dt>简历管理</dt>
		<dd class="<?php echo $CUR_CLASS['myresume'];?>"><a href="<?php echo url('user/myresume')?>">简历管理</a></dd><!-- 
		<dd class="<?php echo $CUR_CLASS['payment'];?>"><a href="<?php echo url('user/payment')?>">付费历史</a></dd>
		<dd class="<?php echo $CUR_CLASS['invite'];?>"><a href="<?php echo url('user/invite')?>">新会员邀请</a></dd> -->
	</dl>
	<dl class="usernav">
		<dt>个人信息</dt>
		<dd class="<?php echo $CUR_CLASS['info'];?>"><a href="<?php echo url('user/info')?>">个人资料</a></dd>
		<dd class="<?php echo $CUR_CLASS['changepsw'];?>"><a href="<?php echo url('user/changepsw')?>">修改密码</a></dd>
	</dl>
</div>