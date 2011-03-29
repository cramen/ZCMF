z_htmlspecialchars_decode = function(string, quote_style) {
    var optTemp = 0, i = 0, noquotes= false;
    if (typeof quote_style === 'undefined') {
        quote_style = 2;
    }
    string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE' : 1,
        'ENT_HTML_QUOTE_DOUBLE' : 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE' : 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i=0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/&#0*39;/g, "'"); // PHP doesn't currently escape if more than one 0, but it should
        // string = string.replace(/&apos;|&#x0*27;/g, "'"); // This would also be useful here, but not a part of PHP
    }
    if (!noquotes) {
        string = string.replace(/&quot;/g, '"');
    }
    // Put this in last place to avoid escape being double-decoded
    string = string.replace(/&amp;/g, '&');
 
    return string;
}

$.fn.extend( {
	z_admin_file_uploader : function(obj) {
		return this.each(function() {
			var id = this.id;
			if (typeof this.id == "object") {
				id = $(this).attr("id")
			}
			if (!obj)
				obj = {};
		})
	},
	z_admin_file_uploader_form : function(callback) {
		
		return this.each(function() {
			var form = this;
			var id = this.id;
			var action = this.action;
			if (typeof this.id == "object") {
				id = $(this).attr("id")
			}
			var data = $.extend( {}, {
				iframe : id + "_iframe"
			}, data);
			
			$("#"+data.iframe).remove();
			$("body").append('<iframe class="z-admin-file-uploader-iframes" name="' + data.iframe + '" id="' + data.iframe + '"></iframe>');
			
			$(form).find('.z-ajax-form-toremove').remove();
			$(form).append("<input type=hidden name=z-ajax-form value=1 class=z-ajax-form-toremove />");
			
			$("#" + data.iframe).css( {
				position : "absolute",
				left : "-1000px",
				top : "-1000px",
				width : "0px",
				height : "0px"
			});
			
			$(this).unbind('submit').attr("target", data.iframe).submit(
					function() {
						z_overlay_show();
						$("#" + data.iframe).load(
								function() {
									var response = $("#"+data.iframe).contents().find("body").html();
									response = z_htmlspecialchars_decode(response, 'ENT_NOQUOTES');
									response = jQuery.parseJSON(response); 
									php.success(response);
									$("#" + data.iframe).unbind("load");
									window.scroll(0, 0);
									$("#"+data.iframe).remove();
									z_overlay_hide();
								})
					});
			return true;
		})
	}
});