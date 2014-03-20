<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title>Note#</title>
		<?php echo css('reset.css', 'global.css');?>
		<?php echo js('http://localhost/ysl/source/ysl.base.js');?>
	</head>
	<body>
		<div class="page">
		<div class="header">
			<h1 class="logo"><a href="">note #</a></h1>
			<ul class="project-nav hide">
				<li><a href="">project one</a></li>
			</ul>
			<ul class="shortcut">
				<li><a href="" class="add-note-lnk">Add Note</a></li>
				<li class="hide"><a href="">添加分类</a></li>
			</ul>
		</div>

		<div class="container">
			<div class="sidebar">
				<form action="" class="search">
					<input type="text" name="" placeholder="search note" id="" class="txt">
					<input type="button" value="search" class="btn search-btn">
				</form>

				<dl class="all-catalog">
					<dd>
						<a href="<?php echo url();?>"><i></i>All Notes</a>
						<span class="cnt"><?php echo count($note_list);?></span>
					</dd>
				</dl>

				<dl class="catalog">
					<dt>
						<span>Catalog</span>
						<a href="<?php echo url('catalog');?>" rel="pop" title="catalog" class="add-btn">edit</a>
					</dt>
					<?php foreach($catalog_list as $name=>$count):?>
					<dd>
						<a href="<?php echo url('index', array('catalog'=>$name));?>"><?php echo $name?></a>
						<span class="cnt"><?php echo $count?></span>
						<a href="<?php echo url('catalog/edit', array('catalog'=>$name));?>" class="add-btn">edit</a>
					</dd>
					<?php endforeach;?>
				</dl>

				<dl class="todo-list">
					<dt><span>Tag</span></dt>
					<?php foreach($tag_list as $tag=>$count):?>
					<dd>
						<a href="<?php echo url(null, array('tag'=>urlencode($tag)));?>"><?php echo $tag;?></a>
						<span class="cnt"><?php echo $count;?></span>
					</dd>
					<?php endforeach;?>
				</dl>

				<dl class="other-fun">
					<dt>Other</dt>
					<dd><a href="">草稿</a></dd>
					<dd><a href="">回收站</a></dd>
				</dl>
			</div>

			<div class="data-collection">
				<div class="cap">
					<h2>专业心得</h2>
					<dl class="ord-lst">
						<dt>排序</dt>
						<dd>按修改时间</dd>
						<dd>按创建时间</dd>
					</dl>
				</div>

				<ul class="data-list">
					<?php while($i++<10):?>
					<li>
						<a href="" class="ti">QQ笔记使用攻略</a>
						<span class="con">内容描述可以放在这里</span>
						<span class="date">7月18日</span>
					</li>
					<?php endwhile;?>
				</ul>
			</div>

			<div class="content">
				<div class="cap">
					<h2>QQ笔记使用攻略</h2>
					<ul class="op-list">
						<li><a href="">删除</a></li>
					</ul>
				</div>
				<div class="main">
					被很多人想当然地看作阻断破坏了两宋国家统一大业的夏、辽与金，不再仅仅是音乐正剧里的几段不和谐的变奏或插曲，而都在本书中担当起积极正面的主角，由它们来贯穿从唐到元的中国史进程。这条另辟蹊径的讲述路线，为我们刻画出一段很不一样的中国史，感觉似乎有点陌生，但细想却又合情合理。
我们常见的唐代总章二年的疆域图往往会误导读者，因为唐对边远地区那些羁縻府州所能实施的主权，在不少场合虚弱到近乎只剩下一个空名的程度。
研究辽、夏、金、元史的一个巨大障碍，乃是有关它们史料的散漫性。《驰骋草原的征服者：辽西夏金元》的作者杉山正明言及契丹史资料的严重不足时哀叹道：能允许被展开来从事“研究的界限已经到了令人伤心的程度。与其说缺失的链条多，不如说了解的情况少”（页79）；至若“尝试研究西夏，本身就已经要成为一种壮举了”（页91）。而关于金、元历史资料，则除了在数量及其报道所覆盖的内容范围方面的依然欠缺外，女真、蒙古统治者的立场、情感和行动更是在占压倒多数的汉语文献有意或无意地遮蔽曲解下变得难以识辨。因此可以想见，要想在一部经汉译后不过十六七万字正文的书稿里，以简明连贯的叙事把这段历史讲述得连非专业的阅读者也能感觉饶有兴趣，这是对写作者具有何等挑战性的事
				</div>
			</div>
		</div>
	</div>
	<?php echo js('global.js');?>
</body>
</html>