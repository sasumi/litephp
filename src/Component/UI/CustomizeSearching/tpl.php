<?php
use Lite\Component\UI\CustomizeSearching\SearchForm;
use Lite\Component\UI\CustomizeSearching\SearchQuery;

/** @var SearchQuery[] $query_list */
?>
<style>
	.search-query-list { display:inline-block; vertical-align:middle}
	.search-query-list > li {float:left; margin-right:0.25em;}
	.search-query-list > li > label {opacity:0; transition:all 0.1s linear; position:absolute; padding:1px; color:#aaa; background-color:#ffffff; font-size:12px; transform:scale(0.9); margin:-20px 0 0 0; cursor:default}
	.search-query-list:hover > li > label {opacity:1;}
	.search-query-list > li:hover > label {color:black;}
	
	.search-form-adder { position:relative; display:inline-block; vertical-align:middle; margin-right:0.25em;}
	.search-form-adder dt {width:27px; height:27px; overflow:hidden; font-size:14px; box-sizing:border-box; border:1px solid #cccccc; background-color:#eeeeee; cursor:pointer;}
	.search-form-adder dt:hover {background-color:#dddddd;}
	.search-form-adder dt:before {content:"\f0b0"; display:block; font-family:FontAwesome, serif; width:100%; height:100%; line-height:27px; text-align:center;}
	.search-form-adder dd {display:none; flex-flow:row wrap; position:absolute; margin-top:-1px; min-width:320px; background-color:#ffffff; padding:0.5em; border:1px solid #ccc; box-shadow:1px 1px 10px #ddd; z-index:2;}
	.search-form-adder dd > span {display:block; flex:1 1 300px; max-width:100px; overflow:hidden; box-sizing:border-box; margin-right:2px; padding:0.5em 1em; white-space:nowrap; border-bottom:1px solid #eeeeee; cursor:pointer;}
	.search-form-adder dd > span:last-child {border:none;}
	.search-form-adder dd > span:hover {background-color:#eeeeee;}
	.search-form-adder dd > span:before {content:"\f046"; color:#dddddd; display:inline-block; width:12px; height:12px; font-family:FontAwesome, serif; margin-right:0.2em;}
	.search-form-adder dd > span.active:before {color:inherit;}
	.search-form-adder:hover dd { display:flex;}
</style>
<dl class="search-form-adder">
	<dt title="更多搜索条件">Add</dt>
	<dd>
		<?php foreach($query_list as $query):?>
		<span class="<?=$query->active ? 'active' : '';?>"
		      data-query-id="<?=$query->id;?>">
			<?=$query->title;?>
		</span>
		<?php endforeach;?>
	</dd>
</dl>
	
<ul class="search-query-list">
	<?php
	$active_queries = [];
	foreach($query_list as $query){
		if($query->active){
			$active_queries[] = $query->id;
		}
		?>
		<li style="<?= $query->active ? '' : 'display:none;'; ?>" data-query-id="<?= $query->id; ?>">
			<label><?= $query->title; ?></label>
			<?= $query->html; ?>
		</li>
	<?php } ?>
</ul>

<input type="hidden" name="<?=SearchForm::$GET_KEY;?>" value="<?=join(',', $active_queries);?>">

<script>
	window.addEventListener('load', function(){
		if(!window.$){
			console.error('jQuery required');
			return;
		}

		var $query_list = $('.search-query-list');
		var array_remove = function(arr, val){
			var n = [];
			arr.forEach(function(v){
				if(v !== val){
					n.push(v);
				}
			});
			return n;
		};

		$query_list.find('li:hidden :input').each(function(){
			$(this).attr('disabled', true);
		});

		$('.search-form-adder dd>span').click(function(){
			var $this = $(this);
			var query_id = $this.data('query-id');
			var to_check = !$this.hasClass('active');
			var $query = $query_list.find('[data-query-id=' + query_id + ']');
			var $filter_input = $(this).closest('form').find('[name=<?=SearchForm::$GET_KEY;?>]');

			$this[to_check ? 'addClass' : 'removeClass']('active');
			$query[to_check ? 'show' : 'hide']();
			$query.find(':input')[to_check ? 'removeAttr' : 'attr']('disabled', true);
			if(to_check){
				$query.find(':input:visible:first').focus();
			}

			var orgs = $filter_input.val().split(',');
			orgs = array_remove(orgs, query_id);
			if(to_check){
				orgs.push(query_id);
			}
			$filter_input.val(orgs.length > 1 ? orgs.join(',') : orgs[0]);
		});
	}, false);
</script>