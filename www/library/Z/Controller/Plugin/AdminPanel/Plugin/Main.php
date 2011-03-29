<?php

class Z_Controller_Plugin_AdminPanel_Plugin_Main implements Z_Controller_Plugin_AdminPanel_Plugin_Interface
{
	protected $_version = '0.1';

	/**
     * Create Z_Controller_Plugin_AdminPanel_Plugin_Main
     *
     * @param string $tab
     * @paran string $panel
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'main';
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        return "Z Администратор";
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        return '<h4>ZAdminPanel v'.$this->_version.'</h4>'.
        	'Панель администрирования сайтом';
    }

}