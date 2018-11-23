<?php
namespace Lite\doc\index;

use Parsedown;

include_once dirname(__DIR__).'/bootstrap.php';
$dir = __DIR__;
$site = 'LitePHP使用帮助';
$nav = [
	'../readme.md' => '开始使用',
	'install.md'   => '安装说明',
	'config.md'    => '配置说明',
	'model.md'     => 'MySQL数据模型',
	'file.md'      => '常规项目文件说明',
];

$std_nav = [
	'DBDesign.md'                                => 'MySQL数据库规范',
	'inspection.md'                                       => 'PHP 编码检查',
	'http://docs.phpdoc.org/references/phpdoc/index.html' => 'PHPDoc 规范',
	'https://www.php-fig.org/psr/psr-2/'                  => 'PSR-2 Coding Style Guide',
];

function array_index($arr, $key){
	$idx = 0;
	foreach($arr as $k => $v){
		if($k == $key){
			return $idx;
		}
		$idx++;
	}
	return null;
}

function find_by_index($arr, $index){
	$idx = 0;
	foreach($arr as $k => $v){
		if($idx == $index){
			return [$k, $v];
		}
		$idx++;
	}
	return [];
}

$file = $_GET['f'] ?: '../readme.md';
$content = file_get_contents($dir.'/'.$file);
$content = Parsedown::instance()->text($content);
?>
<!doctype html>
<html lang="en" class="md-reader">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title><?= $nav[$file] ?: $std_nav[$file]; ?> - <?= $site; ?></title>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/default.min.css">
	<link rel="stylesheet" href="assert/style.css">
	<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
	<script>hljs.initHighlightingOnLoad();</script>
	<script src="http://s.temtop.com/jquery/jquery-1.8.3.min.js?v2015"></script>
</head>
<body>
<section class="main">
	<aside>
		<div class="ti"><?= $site; ?></div>
		<dl>
			<?php foreach($nav as $f => $ti): ?>
				<dd class="<?= $f == $file ? 'active' : ''; ?>">
					<a href="<?= $_SERVER['SCRIPT_FILE']; ?>?f=<?= urlencode($f); ?>"><?= $ti; ?></a>
				</dd>
			<?php endforeach; ?>
		</dl>

		<dl>
			<dt>规范</dt>
			<?php foreach($std_nav as $f => $ti): ?>
				<dd class="<?= $f == $file ? 'active' : ''; ?>">
					<?php if(stripos($f, '://') == false): ?>
						<a href="<?= $_SERVER['SCRIPT_FILE']; ?>?f=<?= urlencode($f); ?>"><?= $ti; ?></a>
					<?php else: ?>
						<a href="<?= $f; ?>" target="_blank"><?= $ti; ?></a>
					<?php endif; ?>
				</dd>
			<?php endforeach; ?>
		</dl>
	</aside>
	<div class="article-wrap">
		<article>
			<a name="top"></a>
			<?php
			echo $content;
			$next_link = $next_title = '';
			$next_idx = array_index($nav, $file);
			if($next_idx<(count($nav)-1)){
				list($next_link, $next_title) = find_by_index($nav, $next_idx+1);
			}
			?>
			<nav class="x-arts">
				下一篇：
				<?php if($next_link): ?>
					<a href="<?= $_SERVER['SCRIPT_FILE']; ?>?f=<?= urlencode($next_link); ?>"><?= $next_title; ?></a>
				<?php else: ?>
					<span>已是最后一篇</span>
				<?php endif; ?>
			</nav>
		</article>
	</div>
</section>
<script>
	// setTimeout("location.reload()", 2000);
</script>
</body>
</html>