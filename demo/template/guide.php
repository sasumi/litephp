<?php include 'header.inc.php'?>

<h2>查阅参考简历库</h2>
<form action="">
	<input type="text" name="" value="" placeholder="请输入关键字"/>
	<input type="submit" value="过滤"/>
</form>

<h3>快速索引</h3>
<dl>
	<dt>按职位</dt>
	<dd>
		<?php for($i=0; $i<10; $i++):?>
		<a href="<?php echo url('guid', array('key'=>$i));?>">关键字<?php echo $i;?></a>
		<?php endfor;?>
	</dd>

	<dt>岗位级别</dt>
	<dd>
		<a href="">不限</a>
		<a href="">实习</a>
		<a href="">暑假工</a>
		<a href="">临时工</a>
		<a href="">正式</a>
	</dd>

	<dt>简历长度</dt>
	<dd>
		<a href="">不限</a>
		<a href="">1页</a>
		<a href="">2页</a>
		<a href="">2页以上</a>
	</dd>
</dl>

<ul>
	<li>
		<h4>诸葛亮 <u>234人参考</u></h4>
		<p>职位：综合，级别：中级职员，长度：3页</p>
		<p>点评：年龄ok，适合拿出来配种</p>
	</li>
	<li>
		<h4>诸葛亮 <u>234人参考</u></h4>
		<p>职位：综合，级别：中级职员，长度：3页</p>
		<p>点评：年龄ok，适合拿出来配种</p>
	</li>
	<li>
		<h4>诸葛亮 <u>234人参考</u></h4>
		<p>职位：综合，级别：中级职员，长度：3页</p>
		<p>点评：年龄ok，适合拿出来配种</p>
	</li>
	<li>
		<h4>诸葛亮 <u>234人参考</u></h4>
		<p>职位：综合，级别：中级职员，长度：3页</p>
		<p>点评：年龄ok，适合拿出来配种</p>
	</li>
</ul>

<p>
	<a href="">上一页</a>
	<a href="">下一页</a>
</p>

<?php include 'footer.inc.php'?>