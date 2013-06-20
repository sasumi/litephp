<?php include 'header.inc.php'?>
<?php echo css('user.css')?>
<?php include 'usernav.inc.php'?>
<div class="page-user-invite">
	<h3>邀请列表</h3>
	<table class="tbl">
		<thead>
			<tr>
				<th>序号</th>
				<th>时间</th>
				<th>被邀请用户</th>
				<th>获取奖励</th>
			</tr>
		</thead>
		<tbody>
			<?php for($i=0; $i<5; $i++):?>
			<tr>
				<td>1</td>
				<td>2013-23-33</td>
				<td>sasumi</td>
				<td>$10.3</td>
			</tr>
			<?php endfor;?>
		</tbody>
	</table>

	<h3>邀请链接</h3>
	<input type="text" value="http://resume.com/invite?id=2dadc3" id="" class="txt invite-link" readonly>
	<input type="button" value="复制" class="btn">
</div>
<?php include 'footer.inc.php'?>
