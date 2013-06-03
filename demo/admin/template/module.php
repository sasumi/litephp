<?php include 'header.inc.php'?>
<table class="tbl">
	<caption>模块列表</caption>
	<thead>
		<tr>
			<th>模块名称</th>
			<th>安装路径</th>
			<th>版本</th>
			<th>状态</th>
			<th>操作</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($module_list as $id=>$module):?>
		<tr>
			<td><?php echo $module['name'];?></td>
			<td><?php echo $module['dir'];?></td>
			<td><?php echo $module['version'] ?: '未定义';?></td>
			<td><?php echo $module['state'];?></td>
			<td>
				<?php if(!$module['installed']):?>
					<span style="color:#ddd" ><?php if(!$module['enable']):?>启用<?php else:?>禁用<?php endif;?></span>
					<a href="<?php echo url('module/install', array('id'=>$id));?>" class="link-btn">安装</a>
				<?php else:?>
					<?php if(!$module['enable']):?>
					<a href="<?php echo url('module/enable', array('id'=>$id))?>" class="link-btn">启用</a>
					<?php else:?>
					<a href="<?php echo url('module/disable', array('id'=>$id))?>" class="link-btn">禁用</a>
					<?php endif;?>
					<a href="<?php echo url('module/uninstall', array('id'=>$id))?>" class="link-btn">卸载</a>
				<?php endif;?>
			</td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<?php include 'footer.inc.php'?>