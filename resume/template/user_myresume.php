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
		<span class="btn btn-strong none" id="batch-manager-btn">批量管理</span>
	</p>
	<p class="batch-tb">
		<label for="sel-all"><input type="checkbox" name="" id="sel-all"> 全选</label>
		<a href="">批量删除</a>
		<a href="">批量下载</a>
		<a href="">批量发布</a>
	</p>

	<ul class="myresume-list">
		<?php if(!empty($resume_list)):?>
		<?php foreach($resume_list as $resume):?>
		<li>
			<h2>
				<input type="checkbox" name="" id="chk_<?php echo $i?>" />
				<label for="chk_<?php echo $i?>"><a href="<?php echo url('resume', array('id'=>$resume->id))?>" title="<?php echo $resume->title?>"><?php echo $resume->title?></a></label>
			</h2>
			<p class="abs"><?php echo $resme->description?>
			</p>
			<p class="info">
				<span class="date"><?php echo $resume->create?></span>
				<span class="type">模板类型：销售</span>
				<span class="size">文件长度：2页以上</span>
			</p>
			<p class="op">
				<a href="<?php echo url('resume/detail', array('id'=>$resume->id))?>">阅读(<?php echo $resume->visit_count?>)</a> |
				<a href="<?php echo url('resume/modify', array('id'=>$resume->id))?>">修改</a> |
				<a href="<?php echo url('resume/delete', array('id'=>$resume->id))?>">删除</a>
			</p>
		</li>
		<?php endforeach;?>
		<?php else:?>
		<li class="empty">
			您还没有简历，<a href="<?php echo url('resume/create')?>">马上去新建</a>。
		</li>
		<?php endif;?>
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
