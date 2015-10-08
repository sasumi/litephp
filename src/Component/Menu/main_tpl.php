<ul id="main-nav" class="main-nav">
	<?php
	use Lite\Core\Config;
	use Lite\Core\View;
	use function Lite\func\array_first;

	/** @var View $viewer */
	$viewer = Config::get('app/render');

	foreach($main_nav as $k=>$item){
		$has_children = true;
		$sub = array_first($item[3]);
		if(empty($sub)){
			$has_children = false;
		}
		else if(count($sub) == 1 && count($item[3]) == 1){
			$first = $sub[0];
			if($first && $first[1] == $item[1]){
				$has_children = false;
			}
		}
		?>
		<li class="<?php echo $item[2] ? "active":"";?> <?php echo !$has_children ? "main-nav-empty" : "";?>">
			<a href="<?php echo $item[1] ? $viewer::getUrl($item[1]) : "javascript:void(0);";?>" <?php echo !$item[1] ? 'class="empty-link"':"";?>>
				<?php echo $item[0];?>
			</a>
			<?php if(!empty($item[3])):?>
				<dl class="sub-nav">
					<?php foreach($item[3] as $cap=>$sub_nav_list):?>
						<?php if(count($item[3])>1):?>
							<dt><span><?php echo $cap;?></span></dt>
						<?php endif;?>

						<?php foreach($sub_nav_list as $sub_item):?>
							<dd <?php if($sub_item[2]):?>class="active"<?php endif;?>>
								<a href="<?php echo $sub_item[1] ? $viewer::getUrl($sub_item[1]) : "javascript:void(0);";?>">
									<?php echo $sub_item[0];?>
								</a>
							</dd>
						<?php endforeach;?>
					<?php endforeach;?>
				</dl>
			<?php endif;?>
		</li>
	<?php }?>
</ul>