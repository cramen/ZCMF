<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Core
 * @package    Core_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2008 Anton Shevchuk (http://anton.shevchuk.com)
 * @version    $Id: Ajax.php 2008-10-09 10:56:06 $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * Helper for making easy links and getting urls that depend on the routes and router
 *
 * @package    ZendY_JQuery_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2008 Anton Shevchuk (http://anton.shevchuk.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZendY_JQuery_View_Helper_Ajax extends Zend_View_Helper_Abstract
{
    /**
     * Generates a javascript
     *
     * @access public
     *
     * @param  array $urlOptions Options passed to the assemble method of the Route object.
     * @param  array $urlData    Data.
     * @return string JavaScript for the link onclick attribute.
     */
    public function ajax(array $urlOptions = array(), array $urlData = array(), $onclick = false, $alternative = false)
    {
        $url  = $this->view->url($urlOptions);
        $data =  Zend_Json::encode($urlData);
        
        if ($onclick) {
            if ($alternative) {
                return 'href="'.$url.'" onclick=\'javascript:$.php("'.$url.'",'.$data.');return false;\'';
            } else {
                return 'onclick=\'javascript:$.php("'.$url.'",'.$data.');return false;\'';
            }
            
        } else {
            return '$.php("'.$url.'",'.$data.')';
        }
    }
}
