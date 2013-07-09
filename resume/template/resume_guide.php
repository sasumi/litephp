<?php
$PAGE_CLASS .= 'page-resume-guide';
$PAGE_HEAD .= css('resume_guide.css').js('resume_guide.js');
include 'header.inc.php';
?>
<div class="clearfix">
	<div class="left-col">
		<h3>查阅参考简历库</h3>
		<form action="?" class="frm srch-frm">
			<input type="text" name="" value="" class="txt" placeholder="请输入关键字"/>
			<input type="submit" class="btn" value="过滤"/>
		</form>
		<form action="?" class="frm quick-index-frm">
			<legend>快速索引</legend>
			<fieldset>
				<dl>
					<dt>按应聘职位</dt>
					<dd>
						<a href="?" rel="reset-col">不限</a>
						<a href="?">综合</a>
						<a href="?">行政</a>
						<a href="?">人事</a>
						<a href="?">销售</a>
						<a href="?">传媒</a>
						<a href="?">法律</a>
						<a href="?">策划</a>
						<a href="?">公务员</a>
						<a href="?">IT工程师</a>
						<a href="?">医生</a>
						<a href="?">老师</a>
						<a href="?">机械工程师</a>
						<a href="?">报关员</a>
						<a href="?">外贸</a>
						<a href="?">金融</a>
						<a href="?">会计</a>
						<a href="?">仓管</a>
						<a href="?">车间工人</a>
						<a href="?">编辑记者</a>
					</dd>
				</dl>
				<dl>
					<dt>按资历级别</dt>
					<dd>
						<a href="?" rel="reset-col">不限</a>
						<a href="?">实习或兼职</a>
						<a href="?">初级职员</a>
						<a href="?">中级职员</a>
						<a href="?">高级职业</a>
					</dd>
				</dl>
				<dl>
					<dt>按简历长度</dt>
					<dd>
						<a href="?" rel="reset-col">不限</a>
						<a href="?">1页</a>
						<a href="?">2页及以上</a>
					</dd>
				</dl>
				<dl>
					<dt>按所在行业</dt>
					<dd>
						<a href="?" rel="reset-col">不限</a>
						<a href="?">互联网及通讯</a>
						<a href="?">消费零售</a>
						<a href="?">服务业</a>
						<a href="?">广告传媒</a>
						<a href="?">制造行业</a>
						<a href="?">房产建筑</a>
						<a href="?">金融投资</a>
						<a href="?">外贸</a>
						<a href="?">会计审计</a>
						<a href="?">医疗</a>
						<a href="?">法律服务</a>
						<a href="?">运输物流</a>
						<a href="?">教育培训</a>
						<a href="?">政府及非盈利组织</a>
					</dd>
				</dl>
			</fieldset>
		</form>

		<h3>查询结果</h3>
		<ul class="result-list">
			<?php for($i=0; $i<10; $i++):?>
			<li>
				<h4> 诸葛亮 应聘综合岗位（有2341人参照）</h4>
				<p>职位（综合）    级别（中级职员）  行业（服务业）  长度（1页） </p>
				<p><a href="<?php echo url('resume/create');?>">创建简历</a></p>
			</li>
			<?php endfor;?>
		</ul>
		<p class="pager"><a href="?">上一页</a> <a href="?">下一页</a></p>
	</div>
	<div class="right-col">
		<div class="page-tip">
			除了在这里创建您的简历之外, 您还可以通过上传文件,或者发送您的简历信息到我们的邮箱:
			<a href="mailto:xxa@a.com">xxa@a.com</a>
		</div>

		<div class="hot-resume-list">
			<h3>热门简历模板</h3>
			<ul>
				<?php for($i=0; $i<5; $i++):?>
				<li>
					<h4>诸葛亮 <u>234人参考</u></h4>
					<p>职位：综合，级别：中级职员，长度：3页</p>
					<p>点评：年龄ok，适合拿出来配种</p>
					<p><a href="<?php echo url('resume/create');?>">创建简历</a></p>
				</li>
				<?php endfor;?>
			</ul>
		</div>
		<form action="http://dashiwang.taobao.com.taobao.com/" class="tb-link"><input type="submit" class="btn" value="找专业作家代写（需要付费）"/></form>
	</div>
</div>
<?php include 'footer.inc.php'?>
