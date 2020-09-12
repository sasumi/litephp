<?php
namespace Lite\Performance\display;

use function LFPhp\Func\format_size;
use function LFPhp\Func\ha;
use function LFPhp\Func\h;
use function LFPhp\Func\microtime_diff;
use function LFPhp\Func\microtime_to_date;
use Lite\Performance\Performance;

function display_time_point_text($start, $end){
	$t1 = explode(' ', $start);
	$st = floatval($t1[0])+floatval($t1[1]);
	$t2 = explode(' ', $end);
	$et = floatval($t2[0])+floatval($t2[1]);
	$offset = $et - $st;
	return '<span class="time-point-offset iconfont icon-expand_all level-'.Performance::getQueryTimeLevel($offset*1000).'">'.number_format(round($offset*1000, 2), 2, '.','').'ms</span>';
}

/** string $type 操作类型：open、close、result*/
/** string $ignore_rules*/
/** @var array $static_data */
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>PERFORMANCE STATICS</title>
	<style>
		* {font-size:14px; margin:0; padding:0; text-align:left; font-family:"Tahoma", "Helvetica", "Microsoft YaHei New", "Microsoft YaHei", "宋体", "SimSun", "STXihei", "华文细黑", sans-serif;;}
		ul {list-style:none;}
		html {background-color:#aaa;}
		body {margin:2em; padding:1em 2em 2em; background-color:#fff; color:#000; text-shadow:1px 1px 1px white; box-shadow:0 1px 20px 0 #848484; line-height:1.6}
		table {border-collapse:collapse; border-spacing:0}
		h1, h2, h3, h4, h5, h6 {font-weight:normal; padding:1em 0 0.5em; color:#1276c6;}
		h1 {font-size:24px; padding-bottom:0;}
		h2 {font-size:18px;}
		.switch-btn {float:right; margin-top:-3em; padding:0.3em 1em; display:inline-block; border:1px solid #ccc; background-color:#eee; color:#333; text-decoration:none;}
		.info-tbl {min-width:70%;}
		.info-tbl th, .info-tbl td {padding:3px 0.5em; vertical-align:top;}
		.info-tbl th {width:8em;white-space:nowrap}
		.info-tbl tr {border-bottom:1px solid #eee;}
		.info-tbl tr:last-child {border-bottom:none;}
		.info-tbl textarea[readonly] {display:block; width:100%; padding:0.5em; margin:0; resize:none; line-height:1.6; font-size:12px;  height:2.5em; border:none; background-color:#fff; transition:all 0.1s linear}
		.info-tbl textarea[readonly]:focus {height:10em; outline:none; background-color:#eee;}

		.data-tbl {width:100%;}
		.data-tbl caption {padding:1em 0 0.5em; font-size:16px; color:#1276c6}
		.data-tbl .time-cell {white-space:nowrap}
		.data-tbl tr {border-bottom:1px solid #ddd;}
		.data-tbl thead {background-color:#ddd;}
		.data-tbl thead th {white-space:nowrap; padding:8px 0.5em;}
		.data-tbl tbody tr:nth-child(even) {background-color:#eee;}
		.data-tbl th, .data-tbl td {padding:10px 0.5em; border:1px solid #ccc; vertical-align:top; position:relative;}
		.data-tbl .msg {display:block; word-break:break-all;}
		.data-tbl .msg-hide-more {max-height:100px;  overflow:hidden;}
		.data-tbl .cell-num {text-align:right;}
		.data-tbl .cell-idx {text-align:center; width:10px; white-space:nowrap; color:gray;}
		.data-tbl .col-tag {white-space:nowrap}

		.data-tbl .loc {color:#3e79a9;}

		.flex {display:flex;}
		.flex>div {flex:1}
		.time-point-offset {display:block; z-index:1; top:-15px; right:-9px; position:absolute; background-color:#fff; padding:1px 3px; border-radius:3px; transform:scale(0.8); transition:all 0.1s linear;}
		#ignore-filters {border:1px solid #ddd;  width:500px; margin-bottom:10px; border-left-color:#bbb; border-top-color:#bbb; display:block; resize:vertical; min-height:40px; padding:0.5em; box-sizing:border-box}
		#ignore-filters-save {padding:0.3em 1em}

		#lv-sel {margin-left:1em; padding:0.15em 0 0.25em 0; border-radius:3px;}
		<?php foreach(Performance::$COLOR_MAP as $lv=>$color):?>
		.level-<?=$lv;?> {color:<?=$color;?>}
		.time-point-offset.level-<?=$lv;?> {border:1px solid <?=$color;?>}
		<?php endforeach;?>
	</style>
	<link rel="stylesheet" href="//at.alicdn.com/t/font_1146533_2musbh4g7lk.css">
	<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-1.8.3.min.js"></script>
</head>
<body>
	<?php if($type == 'open'):?>
	<h1>页面统计已开启</h1>
	<script>
		setTimeout(function(){
			location.href = '?PFM_STAT';
		}, 1000);
	</script>
	<a href="?PFM_STAT" class="switch-btn">刷新</a>

	<?php elseif($type == 'close'):?>
	<h1>页面统计已关闭</h1>
	<a href="?PFM_STAT=open" class="switch-btn">开启页面统计</a>

	<?php
	else:
		$time_list = $static_data['time_list'];
		$database_stat = $static_data['database_stat'];
		$page_summary = $static_data['page_summary'];
	?>
	<h1>页面性能统计结果</h1>
	<a href="?PFM_STAT" class="switch-btn" style="margin-right:120px;">刷新结果</a>
	<a href="?PFM_STAT=close" class="switch-btn">关闭页面统计</a>
	<div class="flex">
		<div>
			<h2>页面性能</h2>
			<table class="info-tbl">
				<tbody>

				<tr>
					<th>访问路径</th>
					<td style="word-break:break-all">
						<a href="<?=ha($page_summary['visit_page_url']);?>" target="_blank"><?=h($page_summary['visit_page_url']);?></a> [<?=h($page_summary['visit_page_method']);?>]
					</td>
				</tr>
				<tr>
					<th>服务器请求接收时间</th>
					<td style="word-break:break-all">
						<?=date('H:i:s', $page_summary['request_time_float']).'.'.end(explode('.', $page_summary['request_time_float'].''));?>
					</td>
				</tr>
				<tr>
					<th>LitePHP开始时间</th>
					<td style="word-break:break-all">
						<?=microtime_to_date($page_summary['app_init_time'], 'H:i:s');?>
					</td>
				</tr>
				<tr>
					<th>统计结束时间</th>
					<td style="word-break:break-all">
						<?=microtime_to_date($page_summary['statistics_ending_time'], 'H:i:s');?>
					</td>
				</tr>
				<tr>
					<th>服务器总耗时</th>
					<td style="word-break:break-all">
						<?php
						$pt = microtime_diff($page_summary['request_time_float'], $page_summary['statistics_ending_time'])*1000;
						$level = Performance::getPageTimeLevel($pt);
						?><span class="level-<?=$level;?>"><?=number_format($pt, 2, null, '');?>ms</span>
					</td>
				</tr>
				<tr>
					<th>请求消耗内存</th>
					<td style="word-break:break-all">
						<?=format_size($page_summary['server_ending_memory_usage'] - $page_summary['server_start_memory_usage']);?>
					</td>
				</tr>
				<tr>
					<th>COOKIE</th>
					<td style="word-break:break-all">
						<?='<textarea readonly>'.h(json_encode($page_summary['cookie'])).'</textarea>';?>
					</td>
				</tr>
				<tr>
					<th>SESSION</th>
					<td style="word-break:break-all">
						<?='<textarea readonly>'.h(json_encode($page_summary['session'])).'</textarea>';?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<div>
			<h2>数据库查询</h2>
			<table class="info-tbl">
				<tbody>
				<tr>
					<th>执行次数：</th>
					<td><strong><?= $database_stat['DB_QUERY_COUNT']; ?></strong>次</td>
				</tr>
				<tr>
					<th>总耗时：</th>
					<td><strong><?= round($database_stat['DB_QUERY_TIME']*1000, 1); ?></strong>ms</td>
				</tr>
				<tr>
					<th>内存总消耗：</th>
					<td><strong><?= format_size($database_stat['DB_QUERY_MEM']); ?></strong></td>
				</tr>
				<tr>
					<th>去重次数：</th>
					<td><strong><?= $database_stat['DB_QUERY_DEDUPLICATION_COUNT']; ?></strong>次
						（<?=round(100*$database_stat['DB_QUERY_DEDUPLICATION_COUNT']/($database_stat['DB_QUERY_DEDUPLICATION_COUNT']+$database_stat['DB_QUERY_COUNT']), 2);?>%）
					</td>
				</tr>
				</tbody>
			</table>
			<div class="exclude-rules">
				<h2>
					排除规则
					<span style="color:gray;">每行一条规则，规则用于匹配请求REQUEST_URI。</span>
				</h2>
				<form id="ignore-filters-wrap" action="?PFM_STAT" method="POST">
					<textarea name="ignore_rules" id="ignore-filters"><?=htmlspecialchars(isset($ignore_rules) ? $ignore_rules : '=json');?></textarea>
					<input type="submit" value="保存" id="ignore-filters-save">
				</form>
			</div>
		</div>
	</div>

	<table class="data-tbl" id="node-list">
		<caption>
			节点列表
			<select id="lv-sel">
				<option value="" style="color:black;">所有级别记录</option>
				<?php foreach(Performance::LEVEL_MAP as $lv => $n): ?>
					<option value="<?= $lv; ?>" style="color:<?= Performance::$COLOR_MAP[$lv]; ?>"><?= $n; ?>
						(&gt;<?= Performance::$QUERY_TIME_THRESHOLD[$lv]; ?>ms)
					</option>
				<?php endforeach; ?>
			</select>
		</caption>
		<thead>
		<tr>
			<th class="cell-idx">序号</th>
			<th>时间</th>
			<th class="cell-num">耗时</th>
			<th class="cell-num">内存</th>
			<th>内容</th>
			<th class="col-tag">标记</th>
		</tr>
		</thead>
		<tbody>
			<?php if($time_list):?>
			<tr>
				<td class="cell-idx">0</td>
				<td class="time-cell"><?=date('H:i:s', $page_summary['request_time_float']).'.'.end(explode('.', $page_summary['request_time_float'].''));?></td>
				<td class="cell-num">-</td>
				<td class="cell-num">-</td>
				<td style="word-break:break-all">服务器请求接收时间</td>
				<td></td>
			</tr>
			<tr>
				<td class="cell-idx">
					1
				</td>
				<td class="time-cell">
					<?=microtime_to_date($page_summary['app_init_time'], 'H:i:s');?>
				</td>
				<td class="cell-num"><?=display_time_point_text($page_summary['request_time_float'],$page_summary['app_init_time']);?></td>
				<td class="cell-num">-</td>
				<td style="word-break:break-all">LitePHP开始时间</td>
				<td></td>
			</tr>
			<?php
			$last_tm = $page_summary['app_init_time'];
				foreach($time_list as $idx => $item):
			?>
			<tr class="level-<?= Performance::getQueryTimeLevel($item['time_used']*1000); ?>">
				<td class="cell-idx">
					<?= $idx+2; ?>
				</td>
				<td class="time-cell">
					<?= microtime_to_date($item['time_point'], 'H:i:s'); ?>
					<?=isset($item['time_point_end']) ? '<div>'.microtime_to_date($item['time_point_end'], 'H:i:s').'</div>' : '';?>
				</td>
				<td class="cell-num">
					<?= isset($item['time_used']) ? round($item['time_used']*1000, 1).'ms' : '-'; ?>
					<?=display_time_point_text($last_tm,$item['time_point']);?>
				</td>
				<td class="cell-num"><?= format_size($item['mem_used']); ?></td>
				<td>
					<span class="msg msg-hide-more"><?= nl2br($item['msg']); ?></span>
					<?php if($item['file']):?>
					<div class="loc">
						<span class="file"><?=$item['file'];?> #<?=$item['line'];?></span>
						<span class="callee"><?=$item['callee'];?></span>
					</div>
					<?php endif;?>
				</td>
				<td class="col-tag"><?= nl2br($item['tag']); ?></td>
			</tr>
			<?php
				$last_tm = $item['time_point_end'] ?: $item['time_point'];
			endforeach; ?>
			<tr>
				<td class="cell-idx"><?=$idx+3;?></td>
				<td class="time-cell">
					<?=microtime_to_date($page_summary['statistics_ending_time'], 'H:i:s');?>
				</td>
				<td class="cell-num"><?=display_time_point_text($last_tm,$page_summary['statistics_ending_time']);?></td>
				<td class="cell-num">-</td>
				<td style="word-break:break-all">统计结束时间</td>
				<td></td>
			</tr>
			<?php endif;?>
		</tbody>
	</table>
	<script>
		var LEVEL_MAP = <?=json_encode(Performance::LEVEL_MAP);?>;
		var $node_list = $('#node-list');
		$('#lv-sel').change(function(){
			$node_list.find('tbody tr').show();
			var style = $(this.options[this.selectedIndex]).attr('style') || '';
			console.log(style);
			this.style.cssText = style;
			if(this.value){
				var matched = false;
				for(var i in LEVEL_MAP){
					if(i === this.value){
						matched = true;
					}
					if(!matched){
						$node_list.find('tbody tr.level-' + i).hide();
					}
				}
			}
		});
		$('.data-tbl .msg').dblclick(function(){
			$(this).toggleClass('msg-hide-more');;
		});
	</script>
	<?php endif;?>
</body>
</html>
