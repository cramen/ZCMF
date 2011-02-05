<?php

require_once 'Z/Controller/Plugin/AdminPanel/Plugin/Interface.php';

require_once 'Z/Controller/Plugin/AdminPanel/Plugin/Main.php';

class Z_Controller_Plugin_AdminPanel extends Zend_Controller_Plugin_Abstract
{
    protected $_plugins = array();

    protected $_options = array(
        'plugins'           => array('Main','Seo','Html'),
        'z-index'           => 255
    );
    
    public static $standardPlugins = array('Main','Seo','Html');

    /**
     * Creates a new instance of the Debug Bar
     *
     * @param array|Zend_Config $options
     * @throws Zend_Controller_Exception
     * @return void
     */
    public function __construct($options = null)
    {
        if (isset($options)) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }

            /*
             * Verify that adapter parameters are in an array.
             */
            if (!is_array($options)) {
                throw new Zend_Exception('Debug parameters must be in an array or a Zend_Config object');
            }

            $this->setOptions($options);
        }
        
        /**
         * Creating ZF Version Tab always shown
         */
//        $main = new Z_Controller_Plugin_AdminPanel_Plugin_Main();
//        $this->registerPlugin($main);

        /**
         * Loading aready defined plugins
         */
        $this->_loadPlugins();
    }
    
    /**
     * Sets options of the Debug Bar
     *
     * @param array $options
     * @return ZAdminPanel_Controller_Plugin_Debug
     */
    public function setOptions(array $options = array())
    {
        if (isset($options['z-index'])) {
            $this->_options['z-index'] = $options['z-index'];
        }

        if (isset($options['plugins'])) {
        	$this->_options['plugins'] = $options['plugins'];
        }
        return $this;
    }

    /**
     * Register a new plugin in the Debug Bar
     *
     * @param ZAdminPanel_Controller_Plugin_Debug_Plugin_Interface
     * @return ZAdminPanel_Controller_Plugin_Debug
     */
    public function registerPlugin(Z_Controller_Plugin_AdminPanel_Plugin_Interface $plugin)
    {
        $this->_plugins[$plugin->getIdentifier()] = $plugin;
        return $this;
    }

    /**
     * Unregister a plugin in the Debug Bar
     *
     * @param string $plugin
     * @return ZAdminPanel_Controller_Plugin_Debug
     */
    public function unregisterPlugin($plugin)
    {
        if (false !== strpos($plugin, '_')) {
            foreach ($this->_plugins as $key => $_plugin) {
                if ($plugin == get_class($_plugin)) {
                    unset($this->_plugins[$key]);
                }
            }
        } else {
            $plugin = strtolower($plugin);
            if (isset($this->_plugins[$plugin])) {
                unset($this->_plugins[$plugin]);
            }
        }
        return $this;
    }
    
    /**
     * Get a registered plugin in the Debug Bar
     *
     * @param string $identifier
     * @return ZAdminPanel_Controller_Plugin_Debug_Plugin_Interface
     */
    public function getPlugin($identifier)
    {
        $identifier = strtolower($identifier);
        if (isset($this->_plugins[$identifier])) {
            return $this->_plugins[$identifier];
        }
        return false;
    }
    
    /**
     * Defined by Zend_Controller_Plugin_Abstract
     */
    public function dispatchLoopShutdown()
    {
        $html = '';

        if ($this->getRequest()->isXmlHttpRequest() || isset($_POST['z-ajax-form'])) return;
        if (Zend_Controller_Front::getInstance()->getRequest()->getModuleName()=='admin') return;
        if (!Z_Acl::getInstance()->isAllowed(Z_Auth::getInstance()->getUser()->getRole(),'z_adminpanel')) return;
        

        /**
         * Creating menu tab for all registered plugins
         */
        foreach ($this->_plugins as $plugin)
        {
            $panel = $plugin->getPanel();
            if ($panel == '') {
                continue;
            }

            /* @var $plugin ZAdminPanel_Controller_Plugin_Debug_Plugin_Interface */
            $html .= '<div id="ZAdminPanel_' . $plugin->getIdentifier()
                  . '" class="ZAdminPanel_panel">' . $panel . '</div>';
        }

        $html .= '<div id="ZAdminPanel_info">';

        /**
         * Creating panel content for all registered plugins
         */
        foreach ($this->_plugins as $plugin)
        {
            $tab = $plugin->getTab();
            if ($tab == '') {
                continue;
            }

            /* @var $plugin ZAdminPanel_Controller_Plugin_Debug_Plugin_Interface */
            $html .= '<span class="ZAdminPanel_span clickable" onclick="ZAdminPanelPanel(\'ZAdminPanel_' . $plugin->getIdentifier() . '\');">';
            $html .= '<img src="' . $this->_icon($plugin->getIdentifier()) . '" style="vertical-align:middle" alt="' . $plugin->getIdentifier() . '" title="' . $plugin->getIdentifier() . '" /> ';
            $html .= $tab . '</span>';
        }

        $html .= '<span class="ZAdminPanel_span ZAdminPanel_last clickable" id="ZAdminPanel_toggler" onclick="ZAdminPanelSlideBar()">&#171;</span>';

        $html .= '</div>';
        $this->_output($html);
    }

    ### INTERNAL METHODS BELOW ###

    /**
     * Load plugins set in config option
     *
     * @return void;
     */
    protected function _loadPlugins()
    {
    	foreach($this->_options['plugins'] as $plugin => $options) {
    	    if (is_numeric($plugin)) {
    	        # Plugin passed as array value instead of key
    	        $plugin = $options;
    	        $options = array();
    	    }
    	    $plugin = (string)$plugin;
    	    if (in_array($plugin, Z_Controller_Plugin_AdminPanel::$standardPlugins)) {
    	        // standard plugin
                $pluginClass = 'Z_Controller_Plugin_AdminPanel_Plugin_' . $plugin;
    	    } else {
    	        // we use a custom plugin
                if (!preg_match('~^[\w]+$~D', $plugin)) {
                    throw new Zend_Exception("ZAdminPanel: Invalid plugin name [$plugin]");
                }
                $pluginClass = $plugin;
            }

            require_once str_replace('_', DIRECTORY_SEPARATOR, $pluginClass) . '.php';
            $object = new $pluginClass($options);
    		$this->registerPlugin($object);
    	}
    }

    /**
     * Returns path to the specific icon
     *
     * @return string
     */
    protected function _icon($kind)
    {
        switch ($kind) {
            case 'main':
                return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ6SURBVDjLpZNZSNRRGMV//2XGsjFrMg2z0so2K21xIFpepYUiAsGIICLffI8eWiBBeg3qQV+KwBYKLB8qpHUmrahcKLc0QsxldNSxdPz/79LD1ChBUXTh8sG93POdc75zDa01/7NsgGvPR09rzQmpVZZSCqlAKIWUCqk0QqoZWyKFRir1uvxIbsAGUFqXHQqkpP1L57M3Pm5MMJBKpQHUdF9BKIGQAlcJXOlOVykSdye3leO6MmkGQNyHw+uO/1X3bzGBK+S0B1IqAKqDg3986HeCZPffwvJtoNT7lOZLvUdtAPEDAKBkRzo3QwMUb89InN1uGGD3spdE214xe8MRUnM2MfppNW0Pqy7YAK5UKK2xLbhdP4hlmdxpGMQwwQT8ziNiI534c7cT6WrFazikzF2Eb8HS1IQEDdiWwcHAQmpehTkQSAcgNvSMiYFW5uUUMdV3HW+ywefGNqITJsbUUL75k4FWYJtQ+yaMZcXrk1ANk/33mbdiD7EvlRieETy+FJLkMFcjRRSW3emIAwiF1hqPBfu2LGSWbbA1uZ41SfWkrtxPrPcypsfFiWYzFGzGKTjFV28WEJeIUHETLdOgrmkI1VdHpCdEet5enP4qLK9mKrqMgedv6cyrAP+qxOTiUxAi7oEJi8frELoFoTLpa7nI/HQvscgSRt+0kV1SSW7qYtp7xrBMphm4Mi5h/VIfTcEq1u0oJaknSEdNiMYHET7UvcMpPEN31Ed7zxgASmk1I0g6dK66s8CRak5mVxjnfS05+TsZCw/T9baTx1nnGb47DrQksjE6HrsHYPz6nYt3+Sc3L8+wA2tz0J6pF5OD4WP7Kpq7f5fO79DfSxjdtCtDAAAAAElFTkSuQmCC';
                break;
            case 'seo':
                return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAAZiS0dEAAAAAAAA+UO7fwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9oIGgwxJIPhc9QAAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAAwElEQVQ4y+3SO0pDYRQE4O9eUoSIkCJrCCSdaGPnOtLkIXYWaSKk+YW/cAMWKty8mizBHdhbRTtLG1vBzhQeswLBJtOcw5xhGIbDHv+PQnaNS7RAUsg6mOEEbxhJnmTHmKOLDQYlrjBFQ1KE8QoPOMQ4zGCBCs2YyxIXOMe7LIfwCEt84RHt4DuoJJ+/SUrJWnKKM0xC+IwBDiSFpAz+FX1ZAyNsarLvOH7gJvYe7nAvq++6+Uk6wy1eMNy/0R9gC984J39fe1ywAAAAAElFTkSuQmCC';
                break;
            case 'html':
                return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEdSURBVDjLjZIxTgNBDEXfbDZIlIgmCKWgSpMGxEk4AHehgavQcJY0KRKJJiBQLkCR7PxvmiTsbrJoLY1sy/Ibe+an9XodtqkfSUd+Op0mTlgpidFodKpGRAAwn8/pstI2AHvfbi6KAkndgHZx31iP2/CTE3Q1A0ji6fUjsiFn8fJ4k44mSCmR0sl3QhJXF2fYwftXPl5hsVg0Xr0d2yZnIwWbqrlyOZlMDtc+v33H9eUQO7ACOZAC2Ye8qqIJqCfZRtnIIBnVQH8AdQOqylTZWPBwX+zGj93ZrXU7ZLlcxj5vArYi5/Iweh+BNQCbrVl8/uAMvjvvJbBU/++6rVarGI/HB0BbI4PBgNlsRtGlsL4CK7sAfQX2L6CPwH4BZf1E9tbX5ioAAAAASUVORK5CYII=';
                break;
            default:
                return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHhSURBVDjLpZI9SJVxFMZ/r2YFflw/kcQsiJt5b1ije0tDtbQ3GtFQYwVNFbQ1ujRFa1MUJKQ4VhYqd7K4gopK3UIly+57nnMaXjHjqotnOfDnnOd/nt85SURwkDi02+ODqbsldxUlD0mvHw09ubSXQF1t8512nGJ/Uz/5lnxi0tB+E9QI3D//+EfVqhtppGxUNzCzmf0Ekojg4fS9cBeSoyzHQNuZxNyYXp5ZM5Mk1ZkZT688b6thIBenG/N4OB5B4InciYBCVyGnEBHO+/LH3SFKQuF4OEs/51ndXMXC8Ajqknrcg1O5PGa2h4CJUqVES0OO7sYevv2qoFBmJ/4gF4boaOrg6rPLYWaYiVfDo0my8w5uj12PQleB0vcp5I6HsHAUoqUhR29zH+5B4IxNTvDmxljy3x2YCYUwZVlbzXJh9UKeQY6t2m0Lt94Oh5loPdqK3EkjzZi4MM/Y9Db3MTv/mYWVxaqkw9IOATNR7B5ABHPrZQrtg9sb8XDKa1+QOwsri4zeHD9SAzE1wxBTXz9xtvMc5ZU5lirLSKIz18nJnhOZjb22YKkhd4odg5icpcoyL669TAAujlyIvmPHSWXY1ti1AmZ8mJ3ElP1ips1/YM3H300g+W+51nc95YPEX8fEbdA2ReVYAAAAAElFTkSuQmCC';
                break;
        }
    }

    /**
     * Returns html header for the Debug Bar
     *
     * @return string
     */
    protected function _headerOutput() {
        $collapsed = isset($_COOKIE['ZAdminPanelCollapsed']) ? $_COOKIE['ZAdminPanelCollapsed'] : 0;

        return ('
            <style type="text/css" media="screen">
                #ZAdminPanel_debug { font: 11px/1.4em Lucida Grande, Lucida Sans Unicode, sans-serif; position:fixed; top:7px; left:5px; color:#000; z-index: ' . $this->_options['z-index'] . ';}
                #ZAdminPanel_debug ol {margin:10px 0px; padding:0 25px}
                #ZAdminPanel_debug li {margin:0 0 10px 0;}
                #ZAdminPanel_debug .clickable {cursor:pointer}
                #ZAdminPanel_toggler { font-weight:bold; background:#BFBFBF; }
                .ZAdminPanel_span { border: 1px solid #999; border-right:0px; background:#DFDFDF; padding: 5px 5px; }
                .ZAdminPanel_last { border: 1px solid #999; }
                .ZAdminPanel_panel { text-align:left; position:absolute;top:21px;width:600px; max-height:400px; overflow:auto; display:none; background:#E8E8E8; padding:5px; border: 1px solid #999; }
                .ZAdminPanel_panel .pre {font: 11px/1.4em Monaco, Lucida Console, monospace; margin:0 0 0 22px}
                #ZAdminPanel_exception { border:1px solid #CD0A0A;display: block; }
            </style>
            <script type="text/javascript" charset="utf-8">
                var ZAdminPanelLoad = window.onload;
                window.onload = function(){
                    if (ZAdminPanelLoad) {
                        ZAdminPanelLoad();
                    }
                    jQuery.noConflict();
                    ZAdminPanelCollapsed();
                };
                
                function ZAdminPanelCollapsed() {
                    if ('.$collapsed.' == 1) {
                        ZAdminPanelPanel();
                        jQuery("#ZAdminPanel_toggler").html("&#187;");
                        return jQuery("#ZAdminPanel_debug").css("left", "-"+parseInt(jQuery("#ZAdminPanel_debug").outerWidth()-jQuery("#ZAdminPanel_toggler").outerWidth()+1)+"px");
                    }
                }
                
                function ZAdminPanelPanel(name) {
                    jQuery(".ZAdminPanel_panel").each(function(i){
                        if(jQuery(this).css("display") == "block") {
                            jQuery(this).slideUp();
                        } else {
                            if (jQuery(this).attr("id") == name)
                                jQuery(this).slideDown();
                            else
                                jQuery(this).slideUp();
                        }
                    });
                }

                function ZAdminPanelSlideBar() {
                    if (jQuery("#ZAdminPanel_debug").position().left > 0) {
                        document.cookie = "ZAdminPanelCollapsed=1;expires=;path=/";
                        ZAdminPanelPanel();
                        jQuery("#ZAdminPanel_toggler").html("&#187;");
                        return jQuery("#ZAdminPanel_debug").animate({left:"-"+parseInt(jQuery("#ZAdminPanel_debug").outerWidth()-jQuery("#ZAdminPanel_toggler").outerWidth()+1)+"px"}, "normal", "swing");
                    } else {
                        document.cookie = "ZAdminPanelCollapsed=0;expires=;path=/";
                        jQuery("#ZAdminPanel_toggler").html("&#171;");
                        return jQuery("#ZAdminPanel_debug").animate({left:"5px"}, "normal", "swing");
                    }
                }

                function ZAdminPanelToggleElement(name, whenHidden, whenVisible){
                    if(jQuery(name).css("display")=="none"){
                        jQuery(whenVisible).show();
                        jQuery(whenHidden).hide();
                    } else {
                        jQuery(whenVisible).hide();
                        jQuery(whenHidden).show();
                    }
                    jQuery(name).slideToggle();
                }
            </script>');
    }

    /**
     * Appends Debug Bar html output to the original page
     *
     * @param string $html
     * @return void
     */
    protected function _output($html)
    {
        $response = $this->getResponse();
        $response->setBody(preg_replace('/(<head.*>)/i', '$1' . $this->_headerOutput(), $response->getBody()));
        $response->setBody(str_ireplace('</body>', '<div id="ZAdminPanel_debug">'.$html.'</div></body>', $response->getBody()));
    }
}