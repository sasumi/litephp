seajs.use('jquery', function($){
	var gid = 1;
	var toc_html = '<nav class="toc"><ul>';
	toc_html += '<li><a href="#top">页面顶部</a></li>';
	$('h2,h3').each(function(){
		var id = '_toc_'+(gid++);
		$('<a name="'+id+'">').insertBefore(this);
		var title = $(this).text();
		var level = this.tagName == 'H2' ? 2 : 3;
		toc_html += '<li class="level-'+level+'"><a href="#'+id+'">'+title+'</a></li>';
	});
	toc_html += '</ul></nav>';
	$(toc_html).appendTo('body');
});