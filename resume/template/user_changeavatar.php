<?php
$org_src = '';
?>
<!DOCTYPE html>
<html class="dialog">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title>append catalog</title>
	<?php echo css('reset.css', 'dialog.css');?>
	<style>
		.img {width:128px; height:128px; overflow: hidden; float:left;}
		.loading {background:url("<?php echo img_url('loading.gif');?>") no-repeat center;}
		.error {background:url("<?php echo img_url('warning32.png');?>") no-repeat center;}
		.op {float:right; width:240px;}
		.file {width:235px;}
		.desc {padding:10px 0; color:gray;}
	</style>
	<script>
		var transPic = "<?php echo img_url('none.gif');?>";
	</script>
</head>
<body style="height:150px; overflow:hidden;" class="dialog">
	<form action="<?php echo url('user/changeAvatar')?>" id="myform" method="POST" class="frm" rel="iframe-form" enctype="multipart/form-data" onresponse="response">
		<fieldset>
			<div class="img"><img src="<?php echo ($org_src ?: img_url('avatar.jpg'));?>" alt="" id="img"></div>
			<div class="op">
				<input type="file" name="file" class="file" id="file">
				<p class="desc">
					支持文件格式：png，jpg，jpeg，gif<br/>
					文件最大尺寸：100KB<br/>
					文件最小分辨率：120像素 X 120像素
				</p>
				<input type="submit" class="btn none" value="保存">
			</div>
		</fieldset>
	</form>
	<?php echo js(YSL_URL, 'global.js');?>
	<script type="text/javascript">
	var Y = YSL;
	var resultUrl, resultState;
	var imgObj = Y.dom.one('#img'),
		imgNode = imgObj.getDomNode();

	var response = function(msg, code, data){
		imgNode.title = '';
		Y.dom.one('#file').getDomNode().disabled = false;
		if(code == 'succ' && data){
			imgNode.src = data;
			imgNode.className = '';
			updateState('done', data);
		} else {
			imgNode.className = 'error';
			R.showTip(msg, 'err');
			updateState('error', '');
			console.log(data);
		}
	}

	var uploadPhoto = function(callback){
		Y.dom.one('#myform').getDomNode().submit();
	};

	var saveSetting = function(url, succCb){
		//save
		console.log('save');

		YSL.use('widget.Popup', function(Y, Pop){
			Pop.getIO('onSucc', function(fn){fn(url)});
			Pop.closeCurrentPopup();
		});
	};

	var updateState = function(state, url){
		resultState = state;
		resultUrl = url;
	};

	YSL.use('widget.Popup', function(Y, Pop){
		Pop.getCurrentPopup().onClose = function(){
			if(resultState == 'uploading'){
				if(!confirm('头像正在上传中，是否确定要取消？')){
					return false;
				}
			}
			return true;
		};

		Pop.addIO('saveSetting', function(fn){
			if(resultState == 'uploading'){
				R.showTip('头像正在上传中，请稍侯···', 'tip');
			} else if(resultState == 'error'){
				R.showTip('头像上传失败，请重新上传', 'err');
			} else if(resultState == 'init' && resultUrl){
				Pop.closeCurrentPopup();
			} else if(!resultUrl){
				R.showTip('请上传您的头像');
			} else {
				saveSetting(resultUrl);
			}
		});

		Y.dom.one('#myform').on('submit', function(){
			R.showTip('正在上传照片，请稍侯···', 'loading');
		});

		Y.dom.one('#file').on('change', function(){
			if(!this.getDomNode().value){
				return;
			}
			imgNode.src = transPic;
			imgObj.addClass('loading');
			imgNode.title = '正在上传';
			Y.dom.one('#myform').getDomNode().submit();
			updateState('uploading', '');
			setTimeout(function(){
				Y.dom.one('#file').getDomNode().disabled = true;
			}, 0);
		});

		imgObj.on('load', function(){
			R.scaleAvaImg(this.getDomNode());
		});
		updateState('init', '<?php echo $org_src;?>');
	});
	</script>
</body>
</html>
