$(function(){
	z_jqphp_init();
	z_ajax_go(window.location.toString());
//	z_admin_init();
	z_flash_init();
	$.datepicker.setDefaults($.datepicker.regional['ru']);
})

//аякс
z_ajax_go = function(url,param)
{
	$.php(url+"?"+Math.random(),param);
}

z_menu_init = function()
{
	$("#z-menu .navigation").find("ul").hide();
	$("#z-menu .navigation .z-admin-menu-path").unbind("click").click(function(){
		$(this).parent().find(">ul").toggle(0);
		return false;
	})
}

//инициализирует необходимые для работы аякса компоненты
z_admin_init = function()
{
	z_ajax_init();
	z_ajax_form_init();
}


z_ajax_init = function()
{
	$(".z-ajax").unbind('click').click(function(){
		var target = $(this).attr('href');
		if (target)
		{
			z_ajax_go(target);
		}
		return false;
	});
}


z_ajax_form_init = function()
{
	if ($(".z-form").length)
	{
		$(".z-form-apply").unbind("click").click(function(){
			$(this).parents('form:first').append("<input type=hidden name=z-ajax-form-applyonly value=1 class=toremove />");
		});
		$(".z-form").z_admin_file_uploader_form();
		
//		$(".z-form").z_admin_file_uploader_form(function(form,data){
////			var form_data = $(form).serialize(true);
////			var form_action = $(form).attr('action');
////			$(form).find('.toremove').remove();
////			z_ajax_go(form_action,form_data);
//		});
	}
}



//Настройка паратетров jqphp
z_jqphp_init = function()
{
	php.beforeSend = function() {
		z_overlay_show();
	};
	php.complete   = function(XMLHttpRequest, textStatus) {
		z_overlay_hide();
	};
	php.error = function(xmlEr, typeEr, except) {
        var exObj = except ? except : false;
        
        // error report for popup window coocking
        var printStr  = 
            "Ошибка AJAX запроса<br />\n";
        
        // XMLHttpRequest.readyState status
        switch (xmlEr.readyState) {
            case 0:
                readyStDesc = "not initialize";
                break;
            case 1: 
                readyStDesc = "open";
                break;
            case 2: 
                readyStDesc = "data transfer";
                break;
            case 3: 
                readyStDesc = "loading";
                break;
            case 4: 
                readyStDesc = "finish";
                break;
            default:
                return "uncknown state";  
        }
        
        printStr += readyStDesc+" ("+xmlEr.readyState+")";
        printStr += "<br/>\n";
        
        if (exObj!=false) {
            printStr += "Исключение: "+except.toString();
            printStr += "<br/>\n";
        }
        
        // add http status description
        printStr += "<b>HTTP статус</b>: "+xmlEr.status +" - "+xmlEr.statusText;
        printStr += "<br/>\n";
        // add response text
        if (xmlEr.status=='404')
        {
        	printStr += "Страница не найдена";
        }
        else
        {
	        printStr += "<textarea class='ui-state-error ui-corner-all' style='width:100%;height:150px;'>"+ xmlEr.responseText+"</textarea>";
        }
        
        z_flash_show_message(printStr,'60s');
       	
	};	
}


//оверлей
var z_overlay_count = 0;
z_overlay_show = function()
{
	var ovcount = $("#z-overlay").length;
	if (ovcount==0)
	{
		$("body").append('<div class="ui-widget-overlay z-overlay" id="z-overlay" style="z-index: 10000;"></div>');
	}
	z_overlay_count++;
}

z_overlay_hide = function()
{
	z_overlay_count--;
	if (z_overlay_count==0)
	{
		$("#z-overlay").remove();
		z_admin_init();
	}
	$(".z-button").not(".ui-button").button();
}

//флэш мессенджер
z_flash_init = function()
{
	$("#z-flash-hide").click(function(){
		$("#z-flash-container").stopTime('timer-flash');
		$("#z-flash-container").stop();
		z_flash_hide();
		return false;
	})
}

z_flash_hide = function(html)
{
	$("#z-flash-container").hide('blind',300);
}

z_flash_show_message = function(html,time)
{
	var flashTime = time?time:'10s';
	$("#z-flash-container").hide();
	$("#z-flash-container").stop();
	$("#z-flash-container").stopTime('timer-flash');
	
	$("#z-flash-content").html(html);
	$("#z-flash-container").show('blind',300);
	$("#z-flash-container").oneTime(flashTime, 'timer-flash', function() {
		z_flash_hide();
	});	
}


z_mce_save = function(inst) {
//	var length = $("textarea.tinymce").length;
//	if (length>1)
//	{
//		$('#' + inst.id).val(inst.getContent());
//	}
}

z_mce_save_event = function(editor_id, elm, command) {
//	var length = $("textarea.tinymce").length;
//    if (command == 'mceRepaint') {
//    	if (length>1)
//    	{
//            var inst = tinyMCE.getInstanceById(editor_id);
//            $('#' + editor_id).val(inst.getContent());
//    	}
//    }
}

z_editarea_change_event = function(id)
{
	$("#"+id).attr('value',editAreaLoader.getValue(id));
}
