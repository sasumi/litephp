<ul id="main-nav" class="main-nav">
	<?php
	
	use Lite\Core\Config;
	use Lite\Core\View;
	use const Lite\Component\Menu\MENU_KEY_ACTIVE;
	use const Lite\Component\Menu\MENU_KEY_SUB;
	use const Lite\Component\Menu\MENU_KEY_TITLE;
	use const Lite\Component\Menu\MENU_KEY_URI;
	use function Lite\func\array_first;
	
	/**
	 * @var View $viewer
	 * @var array $main_nav
	 */
	$viewer = Config::get('app/render');

	foreach($main_nav as $k=>$item){
		$has_children = true;
		$sub = $item[MENU_KEY_SUB] ? array_first($item[MENU_KEY_SUB]) : array();
		if(empty($sub)){
			$has_children = false;
		}
		else if(count($sub) == 1 && count($item[MENU_KEY_SUB]) == 1){
			$first = $sub[0];
			if($first && $first[MENU_KEY_URI] == $item[MENU_KEY_URI]){
				$has_children = false;
			}
		}
		?>
		<li class="<?php echo $item[MENU_KEY_ACTIVE] ? "active":"";?> <?php echo !$has_children ? "main-nav-empty" : "";?>">
			<a href="<?php echo $item[MENU_KEY_URI] ? $viewer::getUrl($item[MENU_KEY_URI]) : "javascript:void(0);";?>" <?php echo !$item[MENU_KEY_URI] ? 'class="empty-link"':"";?>>
				<?php echo $item[MENU_KEY_TITLE];?>
			</a>
			<?php if(!empty($item[MENU_KEY_SUB])):?>
				<dl class="sub-nav">
					<?php foreach($item[MENU_KEY_SUB] as $cap=>$sub_nav_list):?>
						<?php if(count($item[MENU_KEY_SUB])>1):?>
							<dt><span><?php echo $cap;?></span></dt>
						<?php endif;?>

						<?php foreach($sub_nav_list as $sub_item):?>
							<dd <?php if($sub_item[MENU_KEY_ACTIVE]):?>class="active"<?php endif;?>>
								<a href="<?php echo $sub_item[MENU_KEY_URI] ? $viewer::getUrl($sub_item[MENU_KEY_URI]) : "javascript:void(0);";?>">
									<?php echo $sub_item[MENU_KEY_TITLE];?>
								</a>
							</dd>
						<?php endforeach;?>
					<?php endforeach;?>
				</dl>
			<?php endif;?>
		</li>
	<?php }?>
</ul>