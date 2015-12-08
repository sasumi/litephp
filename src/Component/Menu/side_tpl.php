<?php
use Lite\Core\Config;
use Lite\Core\View;
use const Lite\Component\Menu\MENU_KEY_ACTIVE;
use const Lite\Component\Menu\MENU_KEY_TITLE;
use const Lite\Component\Menu\MENU_KEY_URI;
use function Lite\func\dump;

/** @var View $view */
$view = Config::get('app/render');
foreach($side_nav as $col_title=>$nav_list):?>
	<dl class="aside-nav">
		<dt><?php echo $col_title;?></dt>
		<?php foreach($nav_list as $item):?>
			<dd class="<?php echo $item[MENU_KEY_ACTIVE] ? "active" : "";?>">
				<a href="<?php echo $view::getUrl($item[MENU_KEY_URI]);?>"><?php echo $item[MENU_KEY_TITLE];?></a>
			</dd>
		<?php endforeach;?>
	</dl>
<?php endforeach;?>