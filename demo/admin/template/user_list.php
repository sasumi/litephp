<?php include 'header.inc.php'?>
<?php echo table($user_list, array('id'=>'索引号', 'qq'=>'QQ号码', 'invite_total'=>'邀请数量', 'bno'=>'业务号码', 'email'=>'邮箱', 'datetime'=>'时间'));?>
<?php echo $pager_html;?>
<?php include 'footer.inc.php'?>