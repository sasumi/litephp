<?php include 'header.inc.php'?>
<?php echo css('index.css')?>
<div class="page-index">
	<div class="left-col">
		<ul>
			<li>最易用——几分钟量身订做一份专业简历</li>
			<li>最专业——已成功为超过1500名会员获得面试和入职机会</li>
			<li>最实用——精选3000份，覆盖50种岗位的优秀简历范本</li>
		</ul>
		<p>
			优秀的你，需要一份更优秀的简历。创造好机会，从创建好简历开始。 <a href="<?php echo url('resume/guide')?>">马上行动</a>
		</p>

		<form action="<?php echo url('resume/guide')?>">
			<input type="submit" class="btn" value="马上去做（几分钟完成）"/>
		</form>

		<h2>怎样才是好简历？</h2>
		<ol>
			<li><b>等于或优于个人能力</b>——卫生法学基础、医疗机构管理制度、执业医师、执业药师、执业护士管理法律制度、传染病防治法律制度、职业病防治法律制度、食品卫生法律制度</li>
			<li><b>理解用人单位需求</b>——卫生法学基础、医疗机构管理制度、执业医师、执业药师、执业护士管理法律制度、传染病防治法律制度、职业病防治法律制度、食品卫生法律制度</li>
			<li><b>理解用人单位需求</b>——卫生法学基础、医疗机构管理制度、执业医师、执业药师、执业护士管理法律制度、传染病防治法律制度、职业病防治法律制度、食品卫生法律制度</li>
			<li><b>理解用人单位需求</b>——卫生法学基础、医疗机构管理制度、执业医师、执业药师、执业护士管理法律制度、传染病防治法律制度、职业病防治法律制度、食品卫生法律制度</li>
			<li><b>理解用人单位需求</b>——卫生法学基础、医疗机构管理制度、执业医师、执业药师、执业护士管理法律制度、传染病防治法律制度、职业病防治法律制度、食品卫生法律制度</li>
		</ol>
	</div>

	<div class="right-col">
		<form action="<?php echo url('user/register')?>"><input class="btn" type="submit" value="马上注册，创建简历"/></form>
		<form action="<?php echo url('user/login')?>" method="post" class="frm quick-login-frm">
			<dl>
				<dt>用户名</dt>
				<dd><input type="text" name="name" id="name" placeholder="邮箱或手机号" class="txt" /></dd>
			</dl>
			<dl>
				<dt>密码</dt>
				<dd><input type="text" name="password" placeholder="输入密码" class="txt"/></dd>
			</dl>
			<dl>
				<dt></dt>
				<dd>
					<input type="checkbox" name="rem_psw" id="remeber"/><label for="remeber">记住登录状态</label>
					<a href="<?php echo url('user/findpassword')?>">忘记密码</a><br/>
				</dd>
			</dl>
			<dl>
				<dt></dt>
				<dd><input type="submit" value="登录" class="btn"/></dd>
			</dl>
		</form>

		<p>
			已精选<b>234</b>份优秀简历，<a href="<?php echo url('resume/demolist')?>">去看看</a>
		</p>
		<p>
			已有 <b>4399</b> 份名用户满意及推荐
		</p>

		<h3>简历大师最新用户评价</h3>
		<ul>
			<li><abbr title="xx">最爱吹牛</abbr>：这个系统真TM牛B，3分钟就搞定一份简历，居然顺利面试入职了。</li>
			<li><abbr title="xx2">我是李钢他爸</abbr>：没有这个系统，我可能终身失业了。真TM牛B，3分钟就搞定一份简历，居然顺利面试入职了。</li>
		</ul>
		<p>（来自简历大师淘宝用户的真实评价）</p>
	</div>
</div>
<?php include 'footer.inc.php'?>