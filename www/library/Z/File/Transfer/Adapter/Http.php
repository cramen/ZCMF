<?php
class Z_File_Transfer_Adapter_Http extends Zend_File_Transfer_Adapter_Http
{
	protected $_processedFiles = array();
//------------------------------------------------------------------------------
    /**
     * Constructor for Http File Transfers
     *
     * @param array $options OPTIONAL Options to set
     */
    public function __construct($options = array())
    {
        if (ini_get('file_uploads') == false) {
            require_once 'Zend/File/Transfer/Exception.php';
            throw new Zend_File_Transfer_Exception('File uploads are not allowed in your php config!');
        }

        $this->setOptions($options);
        $this->_prepareFiles();
		$this->addValidator(new Z_Admin_Validate_File_Upload(), false, $this->_files);
    }
//------------------------------------------------------------------------------
    /**
     * Remove an individual validator
     *
     * @param  string $name
     * @return Z_File_Transfer_Adapter_Http
     */
    public function removeValidator($name)
    {
        if ($name == 'Z_Admin_Validate_File_Upload') {
            return $this;
        }
        if (false === ($key = $this->_getValidatorIdentifier($name))) {
            return $this;
        }

        unset($this->_validators[$key]);
        foreach (array_keys($this->_files) as $file) {
            if (empty($this->_files[$file]['validators'])) {
                continue;
            }

            $index = array_search($key, $this->_files[$file]['validators']);
            if ($index === false) {
                continue;
            }

            unset($this->_files[$file]['validators'][$index]);
            $this->_files[$file]['validated'] = false;
        }

        return $this;
    }
//------------------------------------------------------------------------------
    /**
     * Remove all validators
     *
     * @param  string $name
     * @return Z_File_Transfer_Adapter_Http
     */
    public function clearValidators()
    {
        $this->_validators = array();
        foreach (array_keys($this->_files) as $file) {
            $this->_files[$file]['validators'] = array();
            $this->_files[$file]['validated']  = false;
        }
		$this->addValidator(new Z_Admin_Validate_File_Upload(), false, $this->_files);
        return $this;
    }
//------------------------------------------------------------------------------
    /**
     * Checks if the files are valid
     *
     * @param  string|array $files (Optional) Files to check
     * @return boolean True if all checks are valid
     */
    public function isValid($files = null)
    {
        // Workaround for WebServer not conforming HTTP and omitting CONTENT_LENGTH
        $content = 0;
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $content = $_SERVER['CONTENT_LENGTH'];
        } else if (!empty($_POST)) {
            $content = serialize($_POST);
        }

        // Workaround for a PHP error returning empty $_FILES when form data exceeds php settings
        if (empty($this->_files) && ($content > 0)) {
            if (is_array($files)) {
                $files = current($files);
            }

            $temp = array($files => array(
                'name'  => $files,
                'error' => 1));
			$validator = $this->_validators['Z_Admin_Validate_File_Upload'];
            $validator->setFiles($temp)
                      ->isValid($files, null);
            $this->_messages += $validator->getMessages();
            return false;
        }
        $check = $this->_getFiles($files, false, true);
        if (empty($check)) {
            return false;
        }

        $translator      = $this->getTranslator();
        $this->_messages = array();
        $break           = false;
        foreach($check as $key => $content) {
            if (array_key_exists('validators', $content) &&
                in_array('Zend_Validate_File_Count', $content['validators'])) {
                $validator = $this->_validators['Zend_Validate_File_Count'];
                if (array_key_exists('destination', $content)) {
                    $checkit = $content['destination'];
                } else {
                    $checkit = dirname($content['tmp_name']);
                }

                $checkit .= DIRECTORY_SEPARATOR . $content['name'];
                $validator->addFile($checkit);
                $count = $content;
            }
        }

        if (isset($count)) {
            if (!$validator->isValid($count['tmp_name'], $count)) {
                $this->_messages += $validator->getMessages();
            }
        }

        foreach ($check as $key => $content) {
            $fileerrors  = array();
            if (array_key_exists('validators', $content) && $content['validated']) {
                continue;
            }

            if (array_key_exists('validators', $content)) {
                foreach ($content['validators'] as $class) {
                    $validator = $this->_validators[$class];
                    if (method_exists($validator, 'setTranslator')) {
                        $validator->setTranslator($translator);
                    }

                    if (($class === 'Z_Admin_Validate_File_Upload') and (empty($content['tmp_name']))) {
                        $tocheck = $key;
                    } else {
                        $tocheck = $content['tmp_name'];
                    }
					//$tocheck .= $this->_getFileValidationPostfix($class);
                    if (!$validator->isValid($tocheck, $content)) {
                        $fileerrors += $validator->getMessages();
                    }

                    if (!empty($content['options']['ignoreNoFile']) and (isset($fileerrors['fileUploadErrorNoFile']))) {
                        unset($fileerrors['fileUploadErrorNoFile']);
                        break;
                    }

                    if (($class === 'Z_Admin_Validate_File_Upload') and (count($fileerrors) > 0)) {
                        break;
                    }

                    if (($this->_break[$class]) and (count($fileerrors) > 0)) {
                        $break = true;
                        break;
                    }
                }
            }

            if (count($fileerrors) > 0) {
                $this->_files[$key]['validated'] = false;
            } else {
                $this->_files[$key]['validated'] = true;
            }

            $this->_messages += $fileerrors;
            if ($break) {
                break;
            }
        }

        if (count($this->_messages) > 0) {
            return false;
        }

        return true;
    }
//------------------------------------------------------------------------------
    /**
     * Receive the file from the client (Upload)
     *
     * @param  string|array $files (Optional) Files to receive
     * @return bool
     */
    public function receive($files = null, $storedId = null)
    {
        if (!$this->isValid($files)) {
            return false;
        }
		$_processedFiles = array();
		$check = $this->_getFiles($files);
        foreach ($check as $file => $content) {
            if (!$content['received']) {
                $directory   = '';
                $destination = $this->getDestination($file);
                if ($destination !== null) {
                    $directory = $destination . DIRECTORY_SEPARATOR;
                }

                $filename = $directory . $content['name'];
                $rename   = $this->getFilter('Rename');
				$storage = new Z_File_Storage();
				
                if ($rename !== null) {
                    $tmp = $rename->getNewName($content['tmp_name']);
                    if ($tmp != $content['tmp_name']) {
                        $filename = $tmp;
                    }

                    if (dirname($filename) == '.') {
                        $filename = $directory . $filename;
                    }

                    $key = array_search(get_class($rename), $this->_files[$file]['filters']);
                    unset($this->_files[$file]['filters'][$key]);
                }
				elseif($storedId == null)
				{
					$tmp = $storage->getNewFolderName();
					if(!$tmp)
						throw new Exception("Не удалось выбрать имя для папки.<br>Попробуйте использовать более длинное имя или увеличьте количество попыток для подбора");
					$filename = $tmp;
				}
                // Should never return false when it's tested by the upload validator
				$lid = $storedId == null ?
					$storage->create($content['tmp_name'], array(
					'filename' => $filename,
					'realname' => $content['name'],
					)) :
					$storage->replace($storedId, $content['tmp_name'], $content['name']);
                if(!$lid)
				{
					if ($content['options']['ignoreNoFile'])
					{
						$this->_files[$file]['received'] = true;
						$this->_files[$file]['filtered'] = true;
						continue;
					}
                    $this->_files[$file]['received'] = false;
                    return false;
				}
				$this->_processedFiles[$file] = $storedId == null ? $lid : $storedId;
                if ($rename !== null) {
                    $this->_files[$file]['destination'] = dirname($filename);
                    $this->_files[$file]['name']        = basename($filename);
                }

                $this->_files[$file]['tmp_name'] = $filename;
                $this->_files[$file]['received'] = true;
            }

            if (!$content['filtered']) {
                if (!$this->_filter($file)) {
                    $this->_files[$file]['filtered'] = false;
                    return false;
                }

                $this->_files[$file]['filtered'] = true;
            }
        }

        return true;
    }
//------------------------------------------------------------------------------
	/**
	 * @return array Возвращает массив с id сохраненных файлов
	 */
	public function getProcessedFiles()
	{
		return $this->_processedFiles;
	}
//------------------------------------------------------------------------------
	/**
	 * Получить id сохраненного файла по имени элемента Form_File
	 * @param string $fieldName Имя элеменета формы File
	 * @return id файла, если он был обработан. В случае неудачи - false
	 */
	public function getProcessedFileId($fieldName)
	{
		return isset($this->_processedFiles[$fieldName]) ? $this->_processedFiles[$fieldName] : false;
	}
}
