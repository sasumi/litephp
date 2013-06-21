<?php $CUR_CLASS = array(); $CUR_CLASS[ACTION] = 'current';?>
<ul class="usernav">
	<li class="<?php echo $CUR_CLASS['myresume'];?>"><a href="<?php echo url('user/myresume')?>">简历管理</a></li>
	<li class="<?php echo $CUR_CLASS['info'];?>"><a href="<?php echo url('user/info')?>">个人资料</a></li>
	<li class="<?php echo $CUR_CLASS['payment'];?>"><a href="<?php echo url('user/payment')?>">付费历史</a></li>
	<li class="<?php echo $CUR_CLASS['invite'];?>"><a href="<?php echo url('user/invite')?>">新会员邀请</a></li>
</ul>