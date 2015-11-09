	</div>
	<div id="footer">
		Copyright &copy; 2014-2015 Guojj.com All Rights Reserved
		<a href="<?php echo $this->getUrl();?>?SYS_STAT=1" target="_blank">系统性能</a>
	</div>
</div>
<script>
	var BATCH_PUB_SCHEDULE_CGI = '<?php echo $this->getUrl('schedule/batchUpdate');?>';
	var set_pub_schedule = function(_select_item){
		seajs.use(['jquery', 'ywj/net', 'ywj/popup'], function($, net, pop){
			var frm = $(_select_item).closest('form');
			var type = $(_select_item).data('type');
			var ids_name = $(_select_item).data('input-name') || 'ids\\[\\]';

			var target_ids = [];
			$("input[name="+ids_name+"]", frm).each(function(){
				if(this.checked){
					target_ids.push(this.value);
				}
			});
			if(!type || !target_ids.length){
				throw("BATCH UPDATE SCHEDULE CONFIG ERROR");
			}
			var cgi = net.mergeCgiUri(BATCH_PUB_SCHEDULE_CGI, {
				'target_ids': target_ids.join(','),
				'target_type': type,
				'ref': 'iframe'
			});
			var p = new pop({
				title: '批量设置定时发布',
				width: 600,
				height: 400,
				content: {src:cgi}
			});
			p.listen('onSuccess', function(){

			});
			p.show();
		});
		return false;
	};
</script>
</body>
</html>