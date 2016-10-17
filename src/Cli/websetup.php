<?php
if($_POST){
	$script = $_SERVER['SCRIPT_FILENAME'];
	$command = $_POST['php'];
	$command .= " $script";
	$command .= ' '.$_POST['cmd'];
	$command .= " -ns {$_POST['ns']}";
	$command .= $_POST['o'] ? ' -o' : '';
	$command .= ' '.$_POST['p'] ?: '';
	$ret = shell_exec($command);
} else {
	//set default value
	$_POST['ns'] = 'www';
	$_POST['php'] = 'php';
}

$cmd_list = array(
	'table' => array('Table', '-t %T%'),
	'alltable' => array('All Table'),
	'model' => array('Model', '-m %M%'),
	'allmodel' => array('All Model'),
	'controller' => array('Controller', '-c %C%'),
	'crud' => array('CRUD', '-t %T% -m %M% -c %C%'),
	'allcrud' => array('All CRUD'),
);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<style>
		* {margin:0; padding:0; font-size:16px; font-family:helvetica, verdana, sans-serif;}
		html {background-color:#eee;}
		body {padding:30px}
		table {border-collapse:collapse; border-spacing:0;}
		th { font-weight:normal; padding-left:0;text-align: left;}
		th, td {padding:5px 10px}
		h1, h2, h3, h4, h5, h6 {font-size:32px; margin-bottom:30px; border-bottom:1px solid white; font-weight:normal; text-shadow:1px 1px 1px white;}
		h1:after {content:""; display:block; margin-top:10px; border-bottom:1px solid #ccc;}
		input[type=text], select {border:1px solid #ddd; border-left-color:#ccc; border-top-color:#ccc; padding:5px; width:270px; border-radius:4px}
		select {width:282px;}
		input[type=button],input[type=submit] {padding:5px 10px}
		form {padding:20px 0; float:left;}
		.cmd {color:green; font-style: italic; background-color:#ccc; padding:10px; margin-bottom:10px; display:inline-block;}
		code {width:100%; line-height:1.8; clear:both; display: block; background-color:#ccc; padding:10px;}
		.no-support-to-change {background-color:#ddd}
		iframe {width:400px; height:500px;}
		table {width:100%;}
		caption {background-color:#eee; text-align:left; padding:5px 10px;}
	</style>
</head>
<body>

<?php
$db_config_key = $_GET['show_db_config'];
if(isset($db_config_key)):
?>
<?php if($db_config_key):?>
<table>
	<caption>Database Configuration</caption>
	<tbody>
	<tr>
		<th>type:</th>
		<td><?php $db_config['type'];?></td>
	</tr>
	<tr>
		<th>host:</th>
		<td><?php $db_config['host'];?></td>
	</tr>
	<tr>
		<th>user:</th>
		<td><?php $db_config['user'];?></td>
	</tr>
	<tr>
		<th>password:</th>
		<td><?php $db_config['password'];?></td>
	</tr>
	<tr>
		<th>database:</th>
		<td><?php $db_config['database'];?></td>
	</tr>
	<tr>
		<th>charset:</th>
		<td><?php $db_config['charset'];?></td>
	</tr>
	</tbody>
</table>
<?php else:?>
<div style="color:gray;">please input namespace</div>
<?php endif;?>

<?php else:?>
<h1>Site Scaffold</h1>
<form action="" method="post">
	<table class="ft">
		<tbody>
		<tr>
			<th>PHP SCRIPT:</th>
			<td>
				<input type="text" name="php" class="no-support-to-change" value="<?php echo $_POST['php'];?>" required="required" placeholder="php shell script">
			</td>
		</tr>
		<tr>
			<th>Namespace:</th>
			<td>
				<select name="ns" id="ns" required="required">
					<?php foreach($ns_list as $ns):?>
					<option value="<?php echo $ns;?>"><?php echo $ns;?></option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<th>To Generate:</th>
			<td>
				<select name="cmd" id="cmd" required="required">
					<option value="">select command</option>
					<?php foreach($cmd_list as $cmd=>list($cap, $def_p)):?>
					<option value="<?php echo $cmd;?>"
						data-default-parameter="<?php echo $def_p;?>"
						<?php echo $cmd == $_POST['cmd'] ? 'selected':'';?>>
						<?php echo $cap;?>
					</option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Parameters:</th>
			<td>
				<input type="text" name="p" id="p" value="<?php echo $_POST['p'];?>">
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<label>
					<input type="checkbox" name="o" <?php echo $_POST['o'] ? 'checked="checked"' : '';?>>
					Overwrite
				</label>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Execute">
				<input type="button" value="Reset" id="reset-btn">
			</td>
		</tr>
		</tbody>
	</table>
</form>

<iframe src="?show_db_config=<?php echo $_POST['ns'];?>" frameborder="0" id="db_config_frame">

</iframe>

<?php if($_SERVER['REQUEST_METHOD'] == 'POST'):?>
<div class="cmd"><?php echo $command;?></div>
<code>
<?php echo nl2br(trim(print_r($ret, true)));?>
</code>
<?php endif;?>

<script>
	var g = function(id){
		return document.getElementById(id);
	};
	g('cmd').addEventListener('change', function(){
		var v = g('cmd').options[this.selectedIndex].getAttribute('data-default-parameter');
		console.log(v);
		g('p').value = v;
		if(v){
			g('p').focus();
			g('p').removeAttribute('disabled');
			g('p').setAttribute('placeholder', '');
		} else {
			g('p').setAttribute('disabled', 'disabled');
			g('p').setAttribute('placeholder', 'no parameter required');
		}
	}, false);

	var opt = new Option('add', 'add');
	g('ns').appendChild(opt);

	g('ns').addEventListener('change', function(){
		if(this.value == 'add'){
			var ns = prompt('add new namespace');
			var opt = new Option(ns, ns);
			this.addChild(opt);
		}
		g('db_config_frame').src = '?show_db_config='+this.value;
	}, false);
	g('reset-btn').addEventListener('click', function(){
		location.href = '<?php echo $_SERVER['REQUEST_URI'];?>';
	}, false);
</script>
<?php endif;?>
</body>
</html>