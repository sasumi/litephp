<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<title>catalog</title>
</head>
<body>
	<ul>
		<?php foreach($catalog_list as $name=>$count):?>
		<li>
			<?php echo $name;?>
			<a href="">edit</a>
			<a href="">remove</a>
		</li>
		<?php endforeach;?>
	</ul>
</body>
</html>