<?php
$PAGE_CLASS .= 'page-user-payment';
$PAGE_HEAD .= css('user.css');
include 'inc/header.inc.php';
include 'inc/usernav.inc.php';
?>
<div class="right-col">
	<h2 class="cap">付费</h2>
	<div class="con">
		<dl>
			<dt>账户余额</dt>
			<dd>9元</dd>
		</dl>
		<dl>
			<dt>会员期限至</dt>
			<dd>
				<span class="expired">2012年 2月 3日</span>
			</dd>
		</dl>
		<dl>
			<dt>付费记录</dt>
			<dd>
				<ul>
					<li>
						<span class="date">20121233</span>
						增加一天权限
					</li>
					<li>
						<span class="date">20121233</span>
						增加一天权限
					</li>
				</ul>
			</dd>
		</dl>
		<dl>
			<dt>充值记录</dt>
			<dd>

				<ul>
					<li>
						<span class="date">20121233</span>
						增加一天权限
					</li>
					<li>
						<span class="date">20121233</span>
						增加一天权限
					</li>
				</ul>
			</dd>
		</dl>
	</div>
</div>
<?php include 'inc/footer.inc.php'?>
