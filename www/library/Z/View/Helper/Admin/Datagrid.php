<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

class Z_View_Helper_Admin_Datagrid extends Zend_View_Helper_Abstract
{
	public function admin_datagrid($data,$columns,$options=array())
	{
		jQuery::evalScript(
			'$("#z-grid tbody").selectable({
				filter: "tr",
				cancel: "a",
				selected: function(event, ui)
				{
					$("#"+ui.selected.id).addClass("ui-state-highlight");
				},
				selecting: function(event, ui)
				{
					$("#"+ui.selecting.id).addClass("ui-state-highlight");
				},
				unselected: function(event, ui)
				{
					$("#"+ui.unselected.id).removeClass("ui-state-highlight");
				},
				unselecting: function(event, ui)
				{
					$("#"+ui.unselecting.id).removeClass("ui-state-highlight");
				}			
			});'
		);
	
		jQuery::evalScript(
		'$("#z-grid").tableDnD({
			onDragClass: "ui-state-highlight",
			dragHandle: "drad-handle",
			scrollAmount: "20",
			onDrop: function(table, row){
				if ($(row).hasClass("ui-selected")) $(row).addClass("ui-state-highlight");
				var urlTemplate = $(table).attr("rel");
				var reorderAr = [];
				var rows = table.tBodies[0].rows;
				for (var i=0; i<rows.length; i++) {
					reorderAr[i] = rows[i].id;
				}
				z_ajax_go(urlTemplate,{
					ids:reorderAr
				});
			}
		});'
		);		

		jQuery::evalScript('
			$("input.z-grid-filter-input").keyup(function(event) {
				if (event.keyCode == "13"){
					var url = $(this).attr("rel");
					var name = $(this).attr("name");
					var param = {};
					param[name] = $(this).attr("value");
					z_ajax_go(url,param);
				}
			});
			$("select.z-grid-filter-input").change(function(event) {
				var url = $(this).attr("rel");
				var name = $(this).attr("name");
				var param = {};
				param[name] = $(this).attr("value");
				z_ajax_go(url,param);
			});
		');		
		
		$this->view->z_grid_data = $data;
		$this->view->z_grid_columns = $columns;
		return $this->view->render('admin/datagrid.phtml');
	}
}

?>