<?php
$PAGE_CLASS .= 'page-user-myresume';
$PAGE_HEAD .= css('user.css');
include 'inc/header.inc.php';
include 'inc/usernav.inc.php';
?>
<div class="right-col">
	<h2 class="cap">简历管理</h2>
	<p class="tb">
		<a href="<?php echo url('resume/create')?>" class="btn btn-strong">创建简历</a>
		<span class="btn btn-strong" id="batch-manager-btn">批量管理</span>
	</p>
	<p class="batch-tb">
		<label for="sel-all"><input type="checkbox" name="" id="sel-all"> 全选</label>
		<a href="">批量删除</a>
		<a href="">批量下载</a>
		<a href="">批量发布</a>
	</p>

	<ul class="myresume-list">
		<?php for($i=5; $i>0; $i--):?>
		<li>
			<h2>
				<input type="checkbox" name="" id="chk_<?php echo $i?>" />
				<label for="chk_<?php echo $i?>"><a href="<?php echo url('resume')?>" title="我的第一份简历">我的第一份简历</a></label>
			</h2>
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

<script>
(function(Y){
	var bb = Y.dom.one('#batch-manager-btn');
	var tb = (function(){
		var CUR_STATE = 1;
		return function(){
			//Y.dom.one('#sel-all').getDomNode().checked = false;
			Y.dom.all('.page-user-myresume input[type=checkbox]').each(function(n){
				n.getDomNode().checked = false;
			});
			Y.dom.one('.page-user-myresume')[CUR_STATE ? 'addClass' : 'removeClass']('page-user-myresume-batch-mode');
			CUR_STATE = !CUR_STATE;
		}
	})();
	bb.on('click', tb);
	Y.dom.one('#sel-all').on('click', function(){
		var chked = this.getDomNode().checked;
		Y.dom.all('.myresume-list input[type=checkbox]').each(function(n){
			n.getDomNode().checked = chked;
		});
	});
})(YSL);
</script>
<?php include 'inc/footer.inc.php'?>
