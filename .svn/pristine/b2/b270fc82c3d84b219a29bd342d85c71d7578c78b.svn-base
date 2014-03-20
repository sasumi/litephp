<?php 
define('UPLOAD_URL', '?');

session_start();
if(!empty($_FILES)){
	$content = file_get_contents($_FILES['file']['tmp_name']);
	$_SESSION['content'] = $content;
	$_SESSION['filename'] = $_FILES['file']['name'];
}

$kw = trim($_GET['kw']);
$current_lev = (int)$_GET['lev'];
$filename = $_SESSION['filename'];
$content = $_SESSION['content'];
$lines = explode("\n", $content);
$result = array();
$LOG_LEVEL = array(
	0 => 'VERBOSE',
	1 => 'DEBUG',
	2 => 'INFO',
	3 => 'WARN',
	4 => 'ERROR'
);
$LOG_LEVEL_SHORT = array(
	'v' => 0,
	'd' => 1,
	'i' => 2,
	'w' => 3,
	'e' => 4
);

$idx = 0;
foreach($lines as $line){
	$line = trim($line);
	if(empty($line)){
		continue;
	}
	$idx++;
	
	preg_match("/([\s|\.|\:|\d|-]*)(^|\s+)(\w+)\/([^\(]*)\(\s*(\d+)\s*\)\s*:(.*)/", $line, $matches);
	list($src, $time, $s, $log_lev, $tag, $pid, $message) = $matches;
	
	if(empty($log_lev) && empty($tag)){
		$message = $line;
	}
	
	if((int) $LOG_LEVEL_SHORT[strtolower($log_lev)] < $current_lev){
		continue;
	}
	
	if($kw && stripos($message, $kw) === false && stripos($tag, $kw) === false){
		continue;
	}
	
	array_push($result, array(
		'idx' => $idx,
		'type' => 1,
		'time' => $time,
		'pid' => $pid,
		'tid' => $tid,
		'log_lev' => $log_lev,
		'tag' => $tag,
		'line' => $line_no,
		'content' => $message
	));
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>android log</title>
	<style>
		html, body {height:100%; color:#345}
		body {padding:10px;}
		* {font-size:14px; line-height:175%; padding:0; margin:0;}
		#progressbar {position:fixed; top:0; left:50%; width:300px; margin-left:-150px; background-color:green; color:white; text-align:center;}
		table {border-collapse: collapse;}
		th, td {border: 1px solid #ddd; padding:2px 4px; font-size:12px;}
		th {text-align: left; padding:0 4px;}
		.row-click td {background-color:#eee;}
		.lev-E {color:rgb(247, 90, 90);}
		.lev-I {color:green;}
		.lev-D {color:blue;}
		.lev-V {color:gray;}
		.lev-W {color:orange;}
		col.col-level {font-weight:bolder}
		form {padding:10px 0;}
		.filter {height:18px; line-height:18px; margin-top:-3px; vertical-align: middle; width:400px;}
	</style>
</head>
<body>
	<div id="progressbar"><span></span></div>
	<div id="op-bar">
		<a href="">previous warnning</a>
		<a href="">previous warnning</a>
	</div>
	
	File: <strong><u><?php echo $filename;?></u></strong>
	<form action="?">
		<label for="">show:</label>
		<select name="lev" onchange="this.parentNode.submit();" class="lev-<?php echo substr($LOG_LEVEL[$current_lev], 0, 1)?>">
			<?php foreach($LOG_LEVEL as $val=>$text):?>
			<option class="lev-<?php echo substr($text,0,1);?>" value="<?php echo $val;?>" <?php echo $current_lev == $val ? 'selected' : ''?>><?php echo $text?></option>
			<?php endforeach;?>
		</select>
		<input type="text" name="kw" value="<?php echo $kw;?>" class="filter" onblur="this.parentNode.submit()">
		<a href="?">reset</a>
	</form>
	<table>
		<colgroup>
			<col class="col-idx"/>
			<col class="col-level"/>
			<col class="col-time"/>
			<col class="col-pid"/>
			<col class="col-tid"/>
			<col class="col-tag"/>
			<col class="col-content"/>
			<col class="col-file"/>
			<col class="col-line"/>
		</colgroup>
		<thead>
			<tr>
				<th>No.</th>
				<th>Level</th>
				<th>Time</th>
				<th>PID</th>
				<th>TID</th>
				<th>Tag</th>
				<th>Content</th>
				<th>File</th>
				<th>Line</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($result as $item):?>
			<tr class="lev-<?php echo $item['log_lev'];?>">
				<td><?php echo $item['idx']?></td>
				<td><b><?php echo $LOG_LEVEL[$LOG_LEVEL_SHORT[strtolower($item['log_lev'])]]?></b></td>
				<td><?php echo $item['time']?></td>
				<td><?php echo $item['pid']?></td>
				<td><?php echo $item['tid']?></td>
				<td title="<?php echo htmlspecialchars($item['tag'])?>"><span style="width:150px; display:block; overflow:hidden;"><?php echo htmlspecialchars($item['tag'])?></span></td>
				<td title="<?php echo htmlspecialchars($item['content'])?>"><?php echo htmlspecialchars($item['content'])?></td>
				<td><?php echo htmlspecialchars($item['file'])?></td>
				<td><?php echo $item['line']?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
<script>
var progressBarZone = document.getElementById('progressbar');

var _lasttr;
document.addEventListener('click', function(e){
	if(_lasttr){
		_lasttr.classList.remove('row-click');
	}
	var tag = findParent(e.target, function(n){
		return n.nodeName == 'TR';
	});
	if(tag){
		tag.classList.add('row-click');
		_lasttr = tag;
	}
}, false);


var findParent = function(node, fn){
	if(fn(node)){
		return node;
	}
	if(node.parentNode && node.parentNode.nodeType == 1){
		return findParent(node.parentNode, fn);
	}
	return null;
};

function sendFile(files) {
	if (!files || files.length < 1) {
		 return;
	}

	var file = files[0];
	var fileName = file.name;
	var percent = document.createElement('div' );
	progressBarZone.appendChild(percent);

	var formData = new FormData();		 // 创建一个表单对象FormData
	formData.append( 'submit', '中文' );  // 往表单对象添加文本字段
	formData.append('file', file);


	var xhr = new XMLHttpRequest();
	xhr.upload.addEventListener( 'progress',
		 function uploadProgress(evt) {
			if (evt.lengthComputable) {
				percent.innerHTML = fileName + ' upload percent :' + Math.round((evt.loaded / evt.total)  * 100) + '%' ;
			}
		}, false); // false表示在事件冒泡阶段处理

	xhr.upload.onload = function() {
		percent.innerHTML = fileName + '上传完成。' ;
	setTimeout(function(){
		location.reload();
	}, 500);
	};

	xhr.upload.onerror = function(e) {
		percent.innerHTML = fileName + ' 上传失败。' ;
	};

	xhr.open( 'post', '<?php echo UPLOAD_URL;?>' , true);
	xhr.send(formData);		// 发送表单对象。
}

document.addEventListener("dragover", function(e) {
	e.stopPropagation();
	e.preventDefault();		// 必须调用。否则浏览器会进行默认处理，比如文本类型的文件直接打开，非文本的可能弹出一个下载文件框。
}, false);

document.addEventListener("drop", function(e) {
	e.stopPropagation();
	e.preventDefault();		// 必须调用。否则浏览器会进行默认处理，比如文本类型的文件直接打开，非文本的可能弹出一个下载文件框。
	sendFile(e.dataTransfer.files);
}, false);
</script>
</body>
</html>