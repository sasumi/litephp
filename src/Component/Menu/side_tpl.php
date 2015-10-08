<?php
use Lite\Core\Config;
use Lite\Core\View;

/** @var View $view */
$view = Config::get('app/render');
foreach($side_nav as $col_title=>$nav_list):?>
	<dl class="aside-nav">
		<dt><?php echo $col_title;?></dt>
		<?php foreach($nav_list as $item):?>
			<dd class="<?php echo $item[2] ? "active" : "";?>">
				<a href="<?php echo $view::getUrl($item[1]);?>"><?php echo $item[0];?></a>
			</dd>
		<?php endforeach;?>
	</dl>
<?php endforeach;?>