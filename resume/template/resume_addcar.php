<!DOCTYPE html>
<html class="dialog">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title>career search</title>
	<?php echo css('reset.css', 'global.css', 'resume_addcar.css');?>
</head>
<body>
	<form action="" class="filte-frm">
		<fieldset>
			<p>
				关键字：<input type="text" name="" class="txt"/>
				<input type="button" value="搜索" class="btn btn-strong">
				<a href="javascript:;" onclick="document.getElementById('mc').className = ''; return false;" class="more-con-link">更多条件</a>
			</p>
			<p class="none" id="mc">
				<label for="">按职位：</label><select name="" id="" class="sel">
					<option value="">人事</option>
				</select>
				<label for="">按资历：</label><select name="" id="">
					<option value="">人事</option></select>
				<label for="">按行业：</label>
				<select name="" id="">
					<option value="">人事</option></select>
				</select>
			</p>
			<p>

			</p>
		</fieldset>
	</form>

	<ul class="result-list">
		<?php for($i=0; $i<5; $i++):?>
		<li>
			<h3 class="ti">2012年7月 — 至今  苏建房地产公司 职业顾问</h3>
			<p class="op">
				<input type="button" value="插入" class="btn btn-strong"/>
			</p>
			<p class="con">
				负责所属区域楼盘的销售，热情接待客户，为客户分析市场的信息，拓展客户群并保持良好沟通，跟进房屋的买卖交易过户，银行按揭等售后服务情况，完成公司所下达销售任务。具有良好的销售技巧，较强的协调能力、处事能力，工作责任心和团队合作精神，成绩显著。月度综合排名前三甲，销售能力得到领导和同事的肯定。
			</p>
		</li>
		<?php endfor;?>
	</ul>
</body>
</html>
