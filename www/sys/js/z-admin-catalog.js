
z_admin_catalog_init = function()
{	
	$(".z-catalog-open").unbind("click").click(function(){
		var toggle_div = $(this).parent().parent().parent().parent().parent().parent().find(">div>ul");
		toggle_div.toggle();
		var id = toggle_div.attr("id");
		var remember_url = $(this).attr("rel");
		var subcat_url = $(this).attr("href");
		var state = toggle_div.css("display");
		
		z_ajax_go(remember_url,{
			"id":id,
			"state":state
		});

		if (state=="block")
		{
			$(this).addClass("ui-icon-folder-open");
			$(this).removeClass("ui-icon-folder-collapsed");
			if (toggle_div.find(">li").length == 0)
				z_ajax_go(subcat_url);
		}
		if (state=="none")
		{
			$(this).addClass("ui-icon-folder-collapsed");
			$(this).removeClass("ui-icon-folder-open");
		}
		
		return false;
	});
}

z_admin_catalog_sortable = function(id)
{
	$("ul#catalog"+id).sortable({
		items: ">li",
		handle: "a.move"+id,
		forceHelperSize: true,
		forcePlaceholderSize: true,
		placeholder: 'ui-state-highlight ui-corner-all',
		tolerance: 'pointer',
		distance: 2,
		opacity: 0.6,
		axis: 'y',
		stop: function (event,ui) {
			var reorderArr = [];
			var url = $(this).attr('rel');
			var rows = $(this).find(">li");
			for (var i=0; i<rows.length; i++) {
				reorderArr[i] = rows[i].id;
			}
			z_ajax_go(url,{
				"ids":reorderArr
			});
		}
	});
	$(".sortable").disableSelection();
}
