<?
class Admin_Bands_ItemsController extends Z_Admin_Controller_Datacontrol_Abstract
{

    protected $myBandinfo;

    public function init()
    {
        $bandId = $this->_getParam('bands_items_parentid');
        $modelBands = new Site_Model_Bands();
        $bandInfo = $this->myBandinfo = $modelBands->find($bandId)->current()->toArray();


        if (trim($bandInfo['band_file'],' *'))
        {
            $eval = '
            if (!"{{file}}") return "";
            $storage = new Z_File_Storage();
            $fileObject = $storage->getFile({{file}});
            $path_parts = pathinfo((string)$fileObject);
            if (in_array(strtolower($path_parts["extension"]),array("jpg","jpeg","png","gif","bmp")))
            {
                return \'<img src="\'.$this->z_Preview($fileObject->getFullName(),array("h"=>50)).\'" />\';
            }
            else
            {
                return \'<a href="\'.(string)$fileObject.\'" >\'.$path_parts["basename"].\'</a> \';
            }
            ';

            $this->z_columns['file'] = array(
			'title'			=>	trim($bandInfo['band_file'],' *'),
			'width'			=>	'',
			'orderlink'		=>	false,
			'orderdir'		=>	'',
			'ordered'		=>	false,
			'template'		=>	false,
			'eval'			=>	$eval,
			'escape'		=>	false,
			'filter'		=>	'',
			'filter_value'	=>	'',
			'filter_items'	=>	false,
			'on_have_subcat'=>	false,
			'visible'		=>	true,
            );
        }
        if (trim($bandInfo['band_title'],' *'))
        {
            $this->z_columns['title'] = array(
			'title'			=>	trim($bandInfo['band_title'],' *'),
			'width'			=>	'',
			'orderlink'		=>	false,
			'orderdir'		=>	'',
			'ordered'		=>	false,
			'template'		=>	false,
			'eval'			=>	false,
			'escape'		=>	true,
			'filter'		=>	'',
			'filter_value'	=>	'',
			'filter_items'	=>	false,
			'on_have_subcat'=>	false,
			'visible'		=>	true,
            );
        }
        if (trim($bandInfo['band_date'],' *'))
        {
            $this->z_columns['date'] = array(
			'title'			=>	trim($bandInfo['band_date'],' *'),
			'width'			=>	'',
			'orderlink'		=>	false,
			'orderdir'		=>	'',
			'ordered'		=>	false,
			'template'		=>	false,
			'eval'			=>	false,
			'escape'		=>	true,
			'filter'		=>	'',
			'filter_value'	=>	'',
			'filter_items'	=>	false,
			'on_have_subcat'=>	false,
			'visible'		=>	true,
            );
        }

        if ($bandInfo['band_order'] == 'orderid')
        {
            $this->z_sortable = true;
            $this->z_sortable_position = $bandInfo['band_orderdir']=='ASC'?'bottom':'top';
        }
        else
        {
            $this->z_order[] = $bandInfo['band_order'].' '.$bandInfo['band_orderdir'];
        }

    }

    public function getForm(Z_Admin_Form $form)
    {
        $fields = array(
            'title'      =>  'Text',
            'date'       =>  'Date',
            'file'       =>  'File',
            'url'        =>  'Text',
            'description'   =>  'Mce',
            'text'      =>  'Mce',
            'param1'    =>  'Text',
            'param2'    =>  'Text',
            'param3'    =>  'Text',
            'text1'     =>  'Textarea',
            'text2'     =>  'Textarea',
            'text3'     =>  'Textarea',
        );

        $bandInfo = $this->myBandinfo;

        foreach($fields as $fieldName=>$fieldType)
        {
            if (isset($bandInfo['band_'.$fieldName]) && trim($bandInfo['band_'.$fieldName],' *'))
            {
                $elementLabel = $bandInfo['band_'.$fieldName];
                $elementRequired = strpos($elementLabel,'*')===false?false:true;
                $elementLabel = trim($elementLabel,' *').(APPLICATION_ENV=='development'?' ('.$fieldName.')':'');
                $elementClassName = 'Z_Admin_Form_Element_'.$fieldType;

                $elementOptions = array(
                    'label'     => $elementLabel,
                    'required'  => $elementRequired,
                );
                if ($fieldName == 'url')
                {
                    /**
                     * ToDo
                     * Нужно сделать валидацию урла
                     */
                }

                $element = new $elementClassName($fieldName,$elementOptions);
                $form->addElement($element);

            }
        }

    }

}
