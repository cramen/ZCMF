jQuery.tableDnD={currentTable:null,dragObject:null,mouseOffset:null,oldY:0,build:function(a){this.each(function(){this.tableDnDConfig=jQuery.extend({onDragStyle:null,onDropStyle:null,onDragClass:"tDnD_whileDrag",onDrop:null,onDragStart:null,scrollAmount:5,serializeRegexp:/[^\-]*$/,serializeParamName:null,dragHandle:null},a||{});jQuery.tableDnD.makeDraggable(this)});jQuery(document).bind("mousemove",jQuery.tableDnD.mousemove).bind("mouseup",jQuery.tableDnD.mouseup);return this},makeDraggable:function(c){var b=c.tableDnDConfig;if(c.tableDnDConfig.dragHandle){var a=jQuery("td."+c.tableDnDConfig.dragHandle,c);a.each(function(){jQuery(this).mousedown(function(e){jQuery.tableDnD.dragObject=this.parentNode;jQuery.tableDnD.currentTable=c;jQuery.tableDnD.mouseOffset=jQuery.tableDnD.getMouseOffset(this,e);if(b.onDragStart){b.onDragStart(c,this)}return false})})}else{var d=jQuery("tr",c);d.each(function(){var e=jQuery(this);if(!e.hasClass("nodrag")){e.mousedown(function(f){if(f.target.tagName=="TD"){jQuery.tableDnD.dragObject=this;jQuery.tableDnD.currentTable=c;jQuery.tableDnD.mouseOffset=jQuery.tableDnD.getMouseOffset(this,f);if(b.onDragStart){b.onDragStart(c,this)}return false}}).css("cursor","move")}})}},updateTables:function(){this.each(function(){if(this.tableDnDConfig){jQuery.tableDnD.makeDraggable(this)}})},mouseCoords:function(a){if(a.pageX||a.pageY){return{x:a.pageX,y:a.pageY}}return{x:a.clientX+document.body.scrollLeft-document.body.clientLeft,y:a.clientY+document.body.scrollTop-document.body.clientTop}},getMouseOffset:function(d,c){c=c||window.event;var b=this.getPosition(d);var a=this.mouseCoords(c);return{x:a.x-b.x,y:a.y-b.y}},getPosition:function(c){var b=0;var a=0;if(c.offsetHeight==0){c=c.firstChild}while(c.offsetParent){b+=c.offsetLeft;a+=c.offsetTop;c=c.offsetParent}b+=c.offsetLeft;a+=c.offsetTop;return{x:b,y:a}},mousemove:function(g){if(jQuery.tableDnD.dragObject==null){return}var d=jQuery(jQuery.tableDnD.dragObject);var b=jQuery.tableDnD.currentTable.tableDnDConfig;var i=jQuery.tableDnD.mouseCoords(g);var f=i.y-jQuery.tableDnD.mouseOffset.y;var c=window.pageYOffset;if(document.all){if(typeof document.compatMode!="undefined"&&document.compatMode!="BackCompat"){c=document.documentElement.scrollTop}else{if(typeof document.body!="undefined"){c=document.body.scrollTop}}}if(i.y-c<b.scrollAmount){window.scrollBy(0,-b.scrollAmount)}else{var a=window.innerHeight?window.innerHeight:document.documentElement.clientHeight?document.documentElement.clientHeight:document.body.clientHeight;if(a-(i.y-c)<b.scrollAmount){window.scrollBy(0,b.scrollAmount)}}if(f!=jQuery.tableDnD.oldY){var e=f>jQuery.tableDnD.oldY;jQuery.tableDnD.oldY=f;if(b.onDragClass){d.addClass(b.onDragClass)}else{d.css(b.onDragStyle)}var h=jQuery.tableDnD.findDropTargetRow(d,f);if(h){if(e&&jQuery.tableDnD.dragObject!=h){jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject,h.nextSibling)}else{if(!e&&jQuery.tableDnD.dragObject!=h){jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject,h)}}}}return false},findDropTargetRow:function(f,g){var j=jQuery.tableDnD.currentTable.rows;for(var e=0;e<j.length;e++){var h=j[e];var b=this.getPosition(h).y;var a=parseInt(h.offsetHeight)/2;if(h.offsetHeight==0){b=this.getPosition(h.firstChild).y;a=parseInt(h.firstChild.offsetHeight)/2}if((g>b-a)&&(g<(b+a))){if(h==f){return null}var c=jQuery.tableDnD.currentTable.tableDnDConfig;if(c.onAllowDrop){if(c.onAllowDrop(f,h)){return h}else{return null}}else{var d=jQuery(h).hasClass("nodrop");if(!d){return h}else{return null}}return h}}return null},mouseup:function(c){if(jQuery.tableDnD.currentTable&&jQuery.tableDnD.dragObject){var b=jQuery.tableDnD.dragObject;var a=jQuery.tableDnD.currentTable.tableDnDConfig;if(a.onDragClass){jQuery(b).removeClass(a.onDragClass)}else{jQuery(b).css(a.onDropStyle)}jQuery.tableDnD.dragObject=null;if(a.onDrop){a.onDrop(jQuery.tableDnD.currentTable,b)}jQuery.tableDnD.currentTable=null}},serialize:function(){if(jQuery.tableDnD.currentTable){return jQuery.tableDnD.serializeTable(jQuery.tableDnD.currentTable)}else{return"Error: No Table id set, you need to set an id on your table and every row"}},serializeTable:function(d){var a="";var c=d.id;var e=d.rows;for(var b=0;b<e.length;b++){if(a.length>0){a+="&"}var f=e[b].id;if(f&&f&&d.tableDnDConfig&&d.tableDnDConfig.serializeRegexp){f=f.match(d.tableDnDConfig.serializeRegexp)[0]}a+=c+"[]="+f}return a},serializeTables:function(){var a="";this.each(function(){a+=jQuery.tableDnD.serializeTable(this)});return a}};jQuery.fn.extend({tableDnD:jQuery.tableDnD.build,tableDnDUpdate:jQuery.tableDnD.updateTables,tableDnDSerialize:jQuery.tableDnD.serializeTables});