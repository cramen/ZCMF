<?php

class Z_Controller_Plugin_AdminPanel_Plugin_Text implements Z_Controller_Plugin_AdminPanel_Plugin_Interface
{
    /**
     * @var string
     */
    protected $_tab = '';

    /**
     * @var string
     */
    protected $_panel = '';

    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'text';

    /**
     * Create Z_Controller_Plugin_AdminPanel_Plugin_Text
     *
     * @param string $tab
     * @paran string $panel
     * @return void
     */
    public function __construct(array $options = array())
    {
        if (isset($options['tab'])) {
            $this->setTab($tab);
        }
        if (isset($options['panel'])) {
            $this->setPanel($panel);
        }
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Sets identifier for this plugin
     *
     * @param string $name
     * @return Z_Controller_Plugin_AdminPanel_Plugin_Text Provides a fluent interface
     */
    public function setIdentifier($name)
    {
        $this->_identifier = $name;
        return $this;
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        return $this->_tab;
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        return $this->_panel;
    }

    /**
     * Sets tab content
     *
     * @param string $tab
     * @return Z_Controller_Plugin_AdminPanel_Plugin_Text Provides a fluent interface
     */
    public function setTab($tab)
    {
        $this->_tab = $tab;
        return $this;
    }

    /**
     * Sets panel content
     *
     * @param string $panel
     * @return Z_Controller_Plugin_AdminPanel_Plugin_Text Provides a fluent interface
     */
    public function setPanel($panel)
    {
        $this->_panel = $panel;
        return $this;
    }
}