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
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Session.php 24196 2011-07-05 15:58:11Z matthew $
 * @since      Preview Release 0.2
 */


/**
 * @see Zend_Session_Abstract
 */
require_once 'Zend/Session/Abstract.php';

/**
 * @see Zend_Session_Namespace
 */
require_once 'Zend/Session/Namespace.php';

/**
 * @see Zend_Session_SaveHandler_Interface
 */
require_once 'Zend/Session/SaveHandler/Interface.php';


/**
 * Zend_Session
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Session extends Zend_Session_Abstract
{
    /**
     * Whether or not Zend_Session is being used with unit tests
     *
     * @internal
     * @var bool
     */
    public static $_unitTestEnabled = false;

    /**
     * $_throwStartupException
     *
     * @var bool|bitset This could also be a combiniation of error codes to catch
     */
    protected static $_throwStartupExceptions = true;

    /**
     * Check whether or not the session was started
     *
     * @var bool
     */
    private static $_sessionStarted = false;

    /**
     * Whether or not the session id has been regenerated this request.
     *
     * Id regeneration state
     * <0 - regenerate requested when session is started
     * 0  - do nothing
     * >0 - already called session_regenerate_id()
     *
     * @var int
     */
    private static $_regenerateIdState = 0;

    /**
     * Private list of php's ini values for ext/session
     * null values will default to the php.ini value, otherwise
     * the value below will overwrite the default ini value, unless
     * the user has set an option explicity with setOptions()
     *
     * @var array
     */
    private static $_defaultOptions = array(
        'save_path'                 => null,
        'name'                      => null, /* this should be set to a unique value for each application */
        'save_handler'              => null,
        //'auto_start'                => null, /* intentionally excluded (see manual) */
        'gc_probability'            => null,
        'gc_divisor'                => null,
        'gc_maxlifetime'            => null,
        'serialize_handler'         => null,
        'cookie_lifetime'           => null,
        'cookie_path'               => null,
        'cookie_domain'             => null,
        'cookie_secure'             => null,
        'cookie_httponly'           => null,
        'use_cookies'               => null,
        'use_only_cookies'          => 'on',
        'referer_check'             => null,
        'entropy_file'              => null,
        'entropy_length'            => null,
        'cache_limiter'             => null,
        'cache_expire'              => null,
        'use_trans_sid'             => null,
        'bug_compat_42'             => null,
        'bug_compat_warn'           => null,
        'hash_function'             => null,
        'hash_bits_per_character'   => null
    );

    /**
     * List of options pertaining to Zend_Session that can be set by developers
     * using Zend_Session::setOptions(). This list intentionally duplicates
     * the individual declaration of static "class" variables by the same names.
     *
     * @var array
     */
    private static $_localOptions = array(
        'strict'                => '_strict',
        'remember_me_seconds'   => '_rememberMeSeconds',
        'throw_startup_exceptions' => '_throwStartupExceptions'
    );

    /**
     * Whether or not write close has been performed.
     *
     * @var bool
     */
    private static $_writeClosed = false;

    /**
     * Whether or not session id cookie has been deleted
     *
     * @var bool
     */
    private static $_sessionCookieDeleted = false;

    /**
     * Whether or not session has been destroyed via session_destroy()
     *
     * @var bool
     */
    private static $_destroyed = false;

    /**
     * Whether or not session must be initiated before usage
     *
     * @var bool
     */
    private static $_strict = false;

    /**
     * Default number of seconds the session will be remembered for when asked to be remembered
     *
     * @var int
     */
    private static $_rememberMeSeconds = 1209600; // 2 weeks

    /**
     * Whether the default options listed in Zend_Session::$_localOptions have been set
     *
     * @var bool
     */
    private static $_defaultOptionsSet = false;

    /**
     * A reference to the set session save handler
     *
     * @var Zend_Session_SaveHandler_Interface
     */
    private static $_saveHandler = null;


    /**
     * Constructor overriding - make sure that a developer cannot instantiate
     */
    protected function __construct()
    {
    }


    /**
     * setOptions - set both the class specified
     *
     * @param  array $userOptions - pass-by-keyword style array of <option name, option value> pairs
     * @throws Zend_Session_Exception
     * @return void
     */
    public static function setOptions(array $userOptions = array())
    {
        // set default options on first run only (before applying user settings)
        if (!self::$_defaultOptionsSet) {
            foreach (self::$_defaultOptions as $defaultOptionName => $defaultOptionValue) {
                if (isset(self::$_defaultOptions[$defaultOptionName])) {
                    ini_set("session.$defaultOptionName", $defaultOptionValue);
                }
            }

            self::$_defaultOptionsSet = true;
        }

        // set the options the user has requested to set
        foreach ($userOptions as $userOptionName => $userOptionValue) {

            $userOptionName = strtolower($userOptionName);

            // set the ini based values
            if (array_key_exists($userOptionName, self::$_defaultOptions)) {
                ini_set("session.$userOptionName", $userOptionValue);
            }
            elseif (isset(self::$_localOptions[$userOptionName])) {
                self::${self::$_localOptions[$userOptionName]} = $userOptionValue;
            }
            else {
                /** @see Zend_Session_Exception */
                require_once 'Zend/Session/Exception.php';
                throw new Zend_Session_Exception("Unknown option: $userOptionName = $userOptionValue");
            }
        }
    }

    /**
     * getOptions()
     *
     * @param string $optionName OPTIONAL
     * @return array|string
     */
    public static function getOptions($optionName = null)
    {
        $options = array();
        foreach (ini_get_all('session') as $sysOptionName => $sysOptionValues) {
            $options[substr($sysOptionName, 8)] = $sysOptionValues['local_value'];
        }
        foreach (self::$_localOptions as $localOptionName => $localOptionMemberName) {
            $options[$localOptionName] = self::${$localOptionMemberName};
        }

        if ($optionName) {
            if (array_key_exists($optionName, $options)) {
                return $options[$optionName];
            }
            return null;
        }

        return $options;
    }

    /**
     * setSaveHandler() - Session Save Handler assignment
     *
     * @param Zend_Session_SaveHandler_Interface $interface
     * @return void
     */
    public static function setSaveHandler(Zend_Session_SaveHandler_Interface $saveHandler)
    {
        self::$_saveHandler = $saveHandler;

        if (self::$_unitTestEnabled) {
            return;
        }

        session_set_save_handler(
            array(&$saveHandler, 'open'),
            array(&$saveHandler, 'close'),
            array(&$saveHandler, 'read'),
            array(&$saveHandler, 'write'),
            array(&$saveHandler, 'destroy'),
            array(&$saveHandler, 'gc')
            );
    }


    /**
     * getSaveHandler() - Get the session Save Handler
     *
     * @return Zend_Session_SaveHandler_Interface
     */
    public static function getSaveHandler()
    {
        return self::$_saveHandler;
    }


    /**
     * regenerateId() - Regenerate the session id.  Best practice is to call this after
     * session is started.  If called prior to session starting, session id will be regenerated
     * at start time.
     *
     * @throws Zend_Session_Exception
     * @return void
     */
    public static function regenerateId()
    {
        if (!self::$_unitTestEnabled && headers_sent($filename, $linenum)) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("You must call " . __CLASS__ . '::' . __FUNCTION__ .
                "() before any output has been sent to the browser; output started in {$filename}/{$linenum}");
        }

        if ( !self::$_sessionStarted ) {
            self::$_regenerateIdState = -1;
        } else {
            if (!self::$_unitTestEnabled) {
                session_regenerate_id(true);
            }
            self::$_regenerateIdState = 1;
        }
    }


    /**
     * rememberMe() - Write a persistent cookie that expires after a number of seconds in the future. If no number of
     * seconds is specified, then this defaults to self::$_rememberMeSeconds.  Due to clock errors on end users' systems,
     * large values are recommended to avoid undesirable expiration of session cookies.
     *
     * @param int $seconds OPTIONAL specifies TTL for cookie in seconds from present time
     * @return void
     */
    public static function rememberMe($seconds = null)
    {
        $seconds = (int) $seconds;
        $seconds = ($seconds > 0) ? $seconds : self::$_rememberMeSeconds;

        self::rememberUntil($seconds);
    }


    /**
     * forgetMe() - Write a volatile session cookie, removing any persistent cookie that may have existed. The session
     * would end upon, for example, termination of a web browser program.
     *
     * @return void
     */
    public static function forgetMe()
    {
        self::rememberUntil(0);
    }


    /**
     * rememberUntil() - This method does the work of changing the state of the session cookie and making
     * sure that it gets resent to the browser via regenerateId()
     *
     * @param int $seconds
     * @return void
     */
    public static function rememberUntil($seconds = 0)
    {
        if (self::$_unitTestEnabled) {
            self::regenerateId();
            return;
        }

        $cookieParams = session_get_cookie_params();

        session_set_cookie_params(
            $seconds,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure']
            );

        // normally "rememberMe()" represents a security context change, so should use new session id
        self::regenerateId();
    }


    /**
     * sessionExists() - whether or not a session exists for the current request
     *
     * @return bool
     */
    public static function sessionExists()
    {
        if (ini_get('session.use_cookies') == '1' && isset($_COOKIE[session_name()])) {
            return true;
        } elseif (!empty($_REQUEST[session_name()])) {
            return true;
        } elseif (self::$_unitTestEnabled) {
            return true;
        }

        return false;
    }


    /**
     * Whether or not session has been destroyed via session_destroy()
     *
     * @return bool
     */
    public static function isDestroyed()
    {
        return self::$_destroyed;
    }


    /**
     * start() - Start the session.
     *
     * @param bool|array $options  OPTIONAL Either user supplied options, or flag indicating if start initiated automatically
     * @throws Zend_Session_Exception
     * @return void
     */
    public static function start($options = false)
    {
        if (self::$_sessionStarted && self::$_destroyed) {
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('The session was explicitly destroyed during this request, attempting to re-start is not allowed.');
        }

        if (self::$_sessionStarted) {
            return; // already started
        }

        // make sure our default options (at the least) have been set
        if (!self::$_defaultOptionsSet) {
            self::setOptions(is_array($options) ? $options : array());
        }

        // In strict mode, do not allow auto-starting Zend_Session, such as via "new Zend_Session_Namespace()"
        if (self::$_strict && $options === true) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('You must explicitly start the session with Zend_Session::start() when session options are set to strict.');
        }

        $filename = $linenum = null;
        if (!self::$_unitTestEnabled && headers_sent($filename, $linenum)) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("Session must be started before any output has been sent to the browser;"
               . " output started in {$filename}/{$linenum}");
        }

        // See http://www.php.net/manual/en/ref.session.php for explanation
        if (!self::$_unitTestEnabled && defined('SID')) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('session has already been started by session.auto-start or session_start()');
        }

        /**
         * Hack to throw exceptions on start instead of php errors
         * @see http://framework.zend.com/issues/browse/ZF-1325
         */

        $errorLevel = (is_int(self::$_throwStartupExceptions)) ? self::$_throwStartupExceptions : E_ALL;

        /** @see Zend_Session_Exception */
        if (!self::$_unitTestEnabled) {

            if (self::$_throwStartupExceptions) {
                require_once 'Zend/Session/Exception.php';
                set_error_handler(array('Zend_Session_Exception', 'handleSessionStartError'), $errorLevel);
            }

            $startedCleanly = session_start();

            if (self::$_throwStartupExceptions) {
                restore_error_handler();
            }

            if (!$startedCleanly || Zend_Session_Exception::$sessionStartError != null) {
                if (self::$_throwStartupExceptions) {
                    set_error_handler(array('Zend_Session_Exception', 'handleSilentWriteClose'), $errorLevel);
                }
                session_write_close();
                if (self::$_throwStartupExceptions) {
                    restore_error_handler();
                    throw new Zend_Session_Exception(__CLASS__ . '::' . __FUNCTION__ . '() - ' . Zend_Session_Exception::$sessionStartError);
                }
            }
        }

        parent::$_readable = true;
        parent::$_writable = true;
        self::$_sessionStarted = true;
        if (self::$_regenerateIdState === -1) {
            self::regenerateId();
        }

        // run validators if they exist
        if (isset($_SESSION['__ZF']['VALID'])) {
            self::_processValidators();
        }

        self::_processStartupMetadataGlobal();
    }


    /**
     * _processGlobalMetadata() - this method initizes the sessions GLOBAL
     * metadata, mostly global data expiration calculations.
     *
     * @return void
     */
    private static function _processStartupMetadataGlobal()
    {
        // process global metadata
        if (isset($_SESSION['__ZF'])) {

            // expire globally expired values
            foreach ($_SESSION['__ZF'] as $namespace => $namespace_metadata) {

                // Expire Namespace by Time (ENT)
                if (isset($namespace_metadata['ENT']) && ($namespace_metadata['ENT'] > 0) && (time() > $namespace_metadata['ENT']) ) {
                    unset($_SESSION[$namespace]);
                    unset($_SESSION['__ZF'][$namespace]);
                }

                // Expire Namespace by Global Hop (ENGH) if it wasnt expired above
                if (isset($_SESSION['__ZF'][$namespace]) && isset($namespace_metadata['ENGH']) && $namespace_metadata['ENGH'] >= 1) {

                    $_SESSION['__ZF'][$namespace]['ENGH']--;

                    if ($_SESSION['__ZF'][$namespace]['ENGH'] === 0) {
                        if (isset($_SESSION[$namespace])) {
                            parent::$_expiringData[$namespace] = $_SESSION[$namespace];
                            unset($_SESSION[$namespace]);
                        }
                        unset($_SESSION['__ZF'][$namespace]);
                    }
                }

                // Expire Namespace Variables by Time (ENVT)
                if (isset($namespace_metadata['ENVT'])) {
                    foreach ($namespace_metadata['ENVT'] as $variable => $time) {
                        if (time() > $time) {
                            unset($_SESSION[$namespace][$variable]);
                            unset($_SESSION['__ZF'][$namespace]['ENVT'][$variable]);
                        }
                    }
                    if (empty($_SESSION['__ZF'][$namespace]['ENVT'])) {
                        unset($_SESSION['__ZF'][$namespace]['ENVT']);
                    }
                }

                // Expire Namespace Variables by Global Hop (ENVGH)
                if (isset($namespace_metadata['ENVGH'])) {
                    foreach ($namespace_metadata['ENVGH'] as $variable => $hops) {
                        $_SESSION['__ZF'][$namespace]['ENVGH'][$variable]--;

                        if ($_SESSION['__ZF'][$namespace]['ENVGH'][$variable] === 0) {
                            if (isset($_SESSION[$namespace][$variable])) {
                                parent::$_expiringData[$namespace][$variable] = $_SESSION[$namespace][$variable];
                                unset($_SESSION[$namespace][$variable]);
                            }
                            unset($_SESSION['__ZF'][$namespace]['ENVGH'][$variable]);
                        }
                    }
                    if (empty($_SESSION['__ZF'][$namespace]['ENVGH'])) {
                        unset($_SESSION['__ZF'][$namespace]['ENVGH']);
                    }
                }

                if (isset($namespace) && empty($_SESSION['__ZF'][$namespace])) {
                    unset($_SESSION['__ZF'][$namespace]);
                }
            }
        }

        if (isset($_SESSION['__ZF']) && empty($_SESSION['__ZF'])) {
            unset($_SESSION['__ZF']);
        }
    }


    /**
     * isStarted() - convenience method to determine if the session is already started.
     *
     * @return bool
     */
    public static function isStarted()
    {
        return self::$_sessionStarted;
    }


    /**
     * isRegenerated() - convenience method to determine if session_regenerate_id()
     * has been called during this request by Zend_Session.
     *
     * @return bool
     */
    public static function isRegenerated()
    {
        return ( (self::$_regenerateIdState > 0) ? true : false );
    }


    /**
     * getId() - get the current session id
     *
     * @return string
     */
    public static function getId()
    {
        return session_id();
    }


    /**
     * setId() - set an id to a user specified id
     *
     * @throws Zend_Session_Exception
     * @param string $id
     * @return void
     */
    public static function setId($id)
    {
        if (!self::$_unitTestEnabled && defined('SID')) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('The session has already been started.  The session id must be set first.');
        }

        if (!self::$_unitTestEnabled && headers_sent($filename, $linenum)) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("You must call ".__CLASS__.'::'.__FUNCTION__.
                "() before any output has been sent to the browser; output started in {$filename}/{$linenum}");
        }

        if (!is_string($id) || $id === '') {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('You must provide a non-empty string as a session identifier.');
        }

        session_id($id);
    }


    /**
     * registerValidator() - register a validator that will attempt to validate this session for
     * every future request
     *
     * @param Zend_Session_Validator_Interface $validator
     * @return void
     */
    public static function registerValidator(Zend_Session_Validator_Interface $validator)
    {
        $validator->setup();
    }


    /**
     * stop() - Disable write access.  Optionally disable read (not implemented).
     *
     * @return void
     */
    public static function stop()
    {
        parent::$_writable = false;
    }


    /**
     * writeClose() - Shutdown the sesssion, close writing and detach $_SESSION from the back-end storage mechanism.
     * This will complete the internal data transformation on this request.
     *
     * @param bool $readonly - OPTIONAL remove write access (i.e. throw error if Zend_Session's attempt writes)
     * @return void
     */
    public static function writeClose($readonly = true)
    {
        if (self::$_unitTestEnabled) {
            return;
        }

        if (self::$_writeClosed) {
            return;
        }

        if ($readonly) {
            parent::$_writable = false;
        }

        session_write_close();
        self::$_writeClosed = true;
    }


    /**
     * destroy() - This is used to destroy session data, and optionally, the session cookie itself
     *
     * @param bool $remove_cookie - OPTIONAL remove session id cookie, defaults to true (remove cookie)
     * @param bool $readonly - OPTIONAL remove write access (i.e. throw error if Zend_Session's attempt writes)
     * @return void
     */
    public static function destroy($remove_cookie = true, $readonly = true)
    {
        if (self::$_unitTestEnabled) {
            return;
        }

        if (self::$_destroyed) {
            return;
        }

        if ($readonly) {
            parent::$_writable = false;
        }

        session_destroy();
        self::$_destroyed = true;

        if ($remove_cookie) {
            self::expireSessionCookie();
        }
    }


    /**
     * expireSessionCookie() - Sends an expired session id cookie, causing the client to delete the session cookie
     *
     * @return void
     */
    public static function expireSessionCookie()
    {
        if (self::$_unitTestEnabled) {
            return;
        }

        if (self::$_sessionCookieDeleted) {
            return;
        }

        self::$_sessionCookieDeleted = true;

        if (isset($_COOKIE[session_name()])) {
            $cookie_params = session_get_cookie_params();

            setcookie(
                session_name(),
                false,
                315554400, // strtotime('1980-01-01'),
                $cookie_params['path'],
                $cookie_params['domain'],
                $cookie_params['secure']
                );
        }
    }


    /**
     * _processValidator() - internal function that is called in the existence of VALID metadata
     *
     * @throws Zend_Session_Exception
     * @return void
     */
    private static function _processValidators()
    {
        foreach ($_SESSION['__ZF']['VALID'] as $validator_name => $valid_data) {
            if (!class_exists($validator_name)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($validator_name);
            }
            $validator = new $validator_name;
            if ($validator->validate() === false) {
                /** @see Zend_Session_Exception */
                require_once 'Zend/Session/Exception.php';
                throw new Zend_Session_Exception("This session is not valid according to {$validator_name}.");
            }
        }
    }


    /**
     * namespaceIsset() - check to see if a namespace is set
     *
     * @param string $namespace
     * @return bool
     */
    public static function namespaceIsset($namespace)
    {
        return parent::_namespaceIsset($namespace);
    }


    /**
     * namespaceUnset() - unset a namespace or a variable within a namespace
     *
     * @param string $namespace
     * @throws Zend_Session_Exception
     * @return void
     */
    public static function namespaceUnset($namespace)
    {
        parent::_namespaceUnset($namespace);
        Zend_Session_Namespace::resetSingleInstance($namespace);
    }


    /**
     * namespaceGet() - get all variables in a namespace
     * Deprecated: Use getIterator() in Zend_Session_Namespace.
     *
     * @param string $namespace
     * @return array
     */
    public static function namespaceGet($namespace)
    {
        return parent::_namespaceGetAll($namespace);
    }


    /**
     * getIterator() - return an iteratable object for use in foreach and the like,
     * this completes the IteratorAggregate interface
     *
     * @throws Zend_Session_Exception
     * @return ArrayObject
     */
    public static function getIterator()
    {
        if (parent::$_readable === false) {
            /** @see Zend_Session_Exception */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(parent::_THROW_NOT_READABLE_MSG);
        }

        $spaces  = array();
        if (isset($_SESSION)) {
            $spaces = array_keys($_SESSION);
            foreach($spaces as $key => $space) {
                if (!strncmp($space, '__', 2) || !is_array($_SESSION[$space])) {
                    unset($spaces[$key]);
                }
            }
        }

        return new ArrayObject(array_merge($spaces, array_keys(parent::$_expiringData)));
    }


    /**
     * isWritable() - returns a boolean indicating if namespaces can write (use setters)
     *
     * @return bool
     */
    public static function isWritable()
    {
        return parent::$_writable;
    }


    /**
     * isReadable() - returns a boolean indicating if namespaces can write (use setters)
     *
     * @return bool
     */
    public static function isReadable()
    {
        return parent::$_readable;
    }

}


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
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 * @since      Preview Release 0.2
 */


/**
 * Zend_Session_Abstract
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Session_Abstract
{
    /**
     * Whether or not session permits writing (modification of $_SESSION[])
     *
     * @var bool
     */
    protected static $_writable = false;

    /**
     * Whether or not session permits reading (reading data in $_SESSION[])
     *
     * @var bool
     */
    protected static $_readable = false;

    /**
     * Since expiring data is handled at startup to avoid __destruct difficulties,
     * the data that will be expiring at end of this request is held here
     *
     * @var array
     */
    protected static $_expiringData = array();


    /**
     * Error message thrown when an action requires modification,
     * but current Zend_Session has been marked as read-only.
     */
    const _THROW_NOT_WRITABLE_MSG = 'Zend_Session is currently marked as read-only.';


    /**
     * Error message thrown when an action requires reading session data,
     * but current Zend_Session is not marked as readable.
     */
    const _THROW_NOT_READABLE_MSG = 'Zend_Session is not marked as readable.';


    /**
     * namespaceIsset() - check to see if a namespace or a variable within a namespace is set
     *
     * @param  string $namespace
     * @param  string $name
     * @return bool
     */
    protected static function _namespaceIsset($namespace, $name = null)
    {
        if (self::$_readable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(self::_THROW_NOT_READABLE_MSG);
        }

        if ($name === null) {
            return ( isset($_SESSION[$namespace]) || isset(self::$_expiringData[$namespace]) );
        } else {
            return ( isset($_SESSION[$namespace][$name]) || isset(self::$_expiringData[$namespace][$name]) );
        }
    }


    /**
     * namespaceUnset() - unset a namespace or a variable within a namespace
     *
     * @param  string $namespace
     * @param  string $name
     * @throws Zend_Session_Exception
     * @return void
     */
    protected static function _namespaceUnset($namespace, $name = null)
    {
        if (self::$_writable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(self::_THROW_NOT_WRITABLE_MSG);
        }

        $name = (string) $name;

        // check to see if the api wanted to remove a var from a namespace or a namespace
        if ($name === '') {
            unset($_SESSION[$namespace]);
            unset(self::$_expiringData[$namespace]);
        } else {
            unset($_SESSION[$namespace][$name]);
            unset(self::$_expiringData[$namespace]);
        }

        // if we remove the last value, remove namespace.
        if (empty($_SESSION[$namespace])) {
            unset($_SESSION[$namespace]);
        }
    }


    /**
     * namespaceGet() - Get $name variable from $namespace, returning by reference.
     *
     * @param  string $namespace
     * @param  string $name
     * @return mixed
     */
    protected static function & _namespaceGet($namespace, $name = null)
    {
        if (self::$_readable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(self::_THROW_NOT_READABLE_MSG);
        }

        if ($name === null) {
            if (isset($_SESSION[$namespace])) { // check session first for data requested
                return $_SESSION[$namespace];
            } elseif (isset(self::$_expiringData[$namespace])) { // check expiring data for data reqeusted
                return self::$_expiringData[$namespace];
            } else {
                return $_SESSION[$namespace]; // satisfy return by reference
            }
        } else {
            if (isset($_SESSION[$namespace][$name])) { // check session first
                return $_SESSION[$namespace][$name];
            } elseif (isset(self::$_expiringData[$namespace][$name])) { // check expiring data
                return self::$_expiringData[$namespace][$name];
            } else {
                return $_SESSION[$namespace][$name]; // satisfy return by reference
            }
        }
    }


    /**
     * namespaceGetAll() - Get an array containing $namespace, including expiring data.
     *
     * @param string $namespace
     * @param string $name
     * @return mixed
     */
    protected static function _namespaceGetAll($namespace)
    {
        $currentData  = (isset($_SESSION[$namespace]) && is_array($_SESSION[$namespace])) ?
            $_SESSION[$namespace] : array();
        $expiringData = (isset(self::$_expiringData[$namespace]) && is_array(self::$_expiringData[$namespace])) ?
            self::$_expiringData[$namespace] : array();
        return array_merge($currentData, $expiringData);
    }
}

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
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Namespace.php 23775 2011-03-01 17:25:24Z ralph $
 * @since      Preview Release 0.2
 */


/**
 * @see Zend_Session
 */
require_once 'Zend/Session.php';


/**
 * @see Zend_Session_Abstract
 */
require_once 'Zend/Session/Abstract.php';


/**
 * Zend_Session_Namespace
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Session_Namespace extends Zend_Session_Abstract implements IteratorAggregate
{

    /**
     * used as option to constructor to prevent additional instances to the same namespace
     */
    const SINGLE_INSTANCE = true;

    /**
     * Namespace - which namespace this instance of zend-session is saving-to/getting-from
     *
     * @var string
     */
    protected $_namespace = "Default";

    /**
     * Namespace locking mechanism
     *
     * @var array
     */
    protected static $_namespaceLocks = array();

    /**
     * Single instance namespace array to ensure data security.
     *
     * @var array
     */
    protected static $_singleInstances = array();

    /**
     * resetSingleInstance()
     *
     * @param string $namespaceName
     * @return null
     */
    public static function resetSingleInstance($namespaceName = null)
    {
        if ($namespaceName != null) {
            if (array_key_exists($namespaceName, self::$_singleInstances)) {
                unset(self::$_singleInstances[$namespaceName]);
            }
            return;
        }

        self::$_singleInstances = array();
        return;
    }

    /**
     * __construct() - Returns an instance object bound to a particular, isolated section
     * of the session, identified by $namespace name (defaulting to 'Default').
     * The optional argument $singleInstance will prevent construction of additional
     * instance objects acting as accessors to this $namespace.
     *
     * @param string $namespace       - programmatic name of the requested namespace
     * @param bool $singleInstance    - prevent creation of additional accessor instance objects for this namespace
     * @return void
     */
    public function __construct($namespace = 'Default', $singleInstance = false)
    {
        if ($namespace === '') {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('Session namespace must be a non-empty string.');
        }

        if ($namespace[0] == "_") {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('Session namespace must not start with an underscore.');
        }

        if (preg_match('#(^[0-9])#i', $namespace[0])) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('Session namespace must not start with a number.');
        }

        if (isset(self::$_singleInstances[$namespace])) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("A session namespace object already exists for this namespace ('$namespace'), and no additional accessors (session namespace objects) for this namespace are permitted.");
        }

        if ($singleInstance === true) {
            self::$_singleInstances[$namespace] = true;
        }

        $this->_namespace = $namespace;

        // Process metadata specific only to this namespace.
        Zend_Session::start(true); // attempt auto-start (throws exception if strict option set)

        if (self::$_readable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(self::_THROW_NOT_READABLE_MSG);
        }

        if (!isset($_SESSION['__ZF'])) {
            return; // no further processing needed
        }

        // do not allow write access to namespaces, after stop() or writeClose()
        if (parent::$_writable === true) {
            if (isset($_SESSION['__ZF'][$namespace])) {

                // Expire Namespace by Namespace Hop (ENNH)
                if (isset($_SESSION['__ZF'][$namespace]['ENNH'])) {
                    $_SESSION['__ZF'][$namespace]['ENNH']--;

                    if ($_SESSION['__ZF'][$namespace]['ENNH'] === 0) {
                        if (isset($_SESSION[$namespace])) {
                            self::$_expiringData[$namespace] = $_SESSION[$namespace];
                            unset($_SESSION[$namespace]);
                        }
                        unset($_SESSION['__ZF'][$namespace]);
                    }
                }

                // Expire Namespace Variables by Namespace Hop (ENVNH)
                if (isset($_SESSION['__ZF'][$namespace]['ENVNH'])) {
                    foreach ($_SESSION['__ZF'][$namespace]['ENVNH'] as $variable => $hops) {
                        $_SESSION['__ZF'][$namespace]['ENVNH'][$variable]--;

                        if ($_SESSION['__ZF'][$namespace]['ENVNH'][$variable] === 0) {
                            if (isset($_SESSION[$namespace][$variable])) {
                                self::$_expiringData[$namespace][$variable] = $_SESSION[$namespace][$variable];
                                unset($_SESSION[$namespace][$variable]);
                            }
                            unset($_SESSION['__ZF'][$namespace]['ENVNH'][$variable]);
                        }
                    }
                    if(empty($_SESSION['__ZF'][$namespace]['ENVNH'])) {
                        unset($_SESSION['__ZF'][$namespace]['ENVNH']);
                    }
                }
            }

            if (empty($_SESSION['__ZF'][$namespace])) {
                unset($_SESSION['__ZF'][$namespace]);
            }

            if (empty($_SESSION['__ZF'])) {
                unset($_SESSION['__ZF']);
            }
        }
    }


    /**
     * getIterator() - return an iteratable object for use in foreach and the like,
     * this completes the IteratorAggregate interface
     *
     * @return ArrayObject - iteratable container of the namespace contents
     */
    public function getIterator()
    {
        return new ArrayObject(parent::_namespaceGetAll($this->_namespace));
    }


    /**
     * lock() - mark a session/namespace as readonly
     *
     * @return void
     */
    public function lock()
    {
        self::$_namespaceLocks[$this->_namespace] = true;
    }


    /**
     * unlock() - unmark a session/namespace to enable read & write
     *
     * @return void
     */
    public function unlock()
    {
        unset(self::$_namespaceLocks[$this->_namespace]);
    }


    /**
     * unlockAll() - unmark all session/namespaces to enable read & write
     *
     * @return void
     */
    public static function unlockAll()
    {
        self::$_namespaceLocks = array();
    }


    /**
     * isLocked() - return lock status, true if, and only if, read-only
     *
     * @return bool
     */
    public function isLocked()
    {
        return isset(self::$_namespaceLocks[$this->_namespace]);
    }


    /**
     * unsetAll() - unset all variables in this namespace
     *
     * @return true
     */
    public function unsetAll()
    {
        return parent::_namespaceUnset($this->_namespace);
    }


    /**
     * __get() - method to get a variable in this object's current namespace
     *
     * @param string $name - programmatic name of a key, in a <key,value> pair in the current namespace
     * @return mixed
     */
    public function & __get($name)
    {
        if ($name === '') {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("The '$name' key must be a non-empty string");
        }

        return parent::_namespaceGet($this->_namespace, $name);
    }


    /**
     * __set() - method to set a variable/value in this object's namespace
     *
     * @param string $name - programmatic name of a key, in a <key,value> pair in the current namespace
     * @param mixed $value - value in the <key,value> pair to assign to the $name key
     * @throws Zend_Session_Exception
     * @return true
     */
    public function __set($name, $value)
    {
        if (isset(self::$_namespaceLocks[$this->_namespace])) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('This session/namespace has been marked as read-only.');
        }

        if ($name === '') {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("The '$name' key must be a non-empty string");
        }

        if (parent::$_writable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(parent::_THROW_NOT_WRITABLE_MSG);
        }

        $name = (string) $name;

        $_SESSION[$this->_namespace][$name] = $value;
    }


    /**
     * apply() - enables applying user-selected function, such as array_merge() to the namespace
     * Parameters following the $callback argument are passed to the callback function.
     * Caveat: ignores members expiring now.
     *
     * Example:
     *   $namespace->apply('array_merge', array('tree' => 'apple', 'fruit' => 'peach'), array('flower' => 'rose'));
     *   $namespace->apply('count');
     *
     * @param string|array $callback - callback function
     */
    public function apply($callback)
    {
        $arg_list = func_get_args();
        $arg_list[0] = $_SESSION[$this->_namespace];
        return call_user_func_array($callback, $arg_list);
    }


    /**
     * applySet() - enables applying user-selected function, and sets entire namespace to the result
     * Result of $callback must be an array.
     * Parameters following the $callback argument are passed to the callback function.
     * Caveat: ignores members expiring now.
     *
     * Example:
     *   $namespace->applySet('array_merge', array('tree' => 'apple', 'fruit' => 'peach'), array('flower' => 'rose'));
     *
     * @param string|array $callback - callback function
     */
    public function applySet($callback)
    {
        $arg_list = func_get_args();
        $arg_list[0] = $_SESSION[$this->_namespace];
        $result = call_user_func_array($callback, $arg_list);
        if (!is_array($result)) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('Result must be an array. Got: ' . gettype($result));
        }
        $_SESSION[$this->_namespace] = $result;
        return $result;
    }


    /**
     * __isset() - determine if a variable in this object's namespace is set
     *
     * @param string $name - programmatic name of a key, in a <key,value> pair in the current namespace
     * @return bool
     */
    public function __isset($name)
    {
        if ($name === '') {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("The '$name' key must be a non-empty string");
        }

        return parent::_namespaceIsset($this->_namespace, $name);
    }


    /**
     * __unset() - unset a variable in this object's namespace.
     *
     * @param string $name - programmatic name of a key, in a <key,value> pair in the current namespace
     * @return true
     */
    public function __unset($name)
    {
        if ($name === '') {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception("The '$name' key must be a non-empty string");
        }

        return parent::_namespaceUnset($this->_namespace, $name);
    }


    /**
     * setExpirationSeconds() - expire the namespace, or specific variables after a specified
     * number of seconds
     *
     * @param int $seconds     - expires in this many seconds
     * @param mixed $variables - OPTIONAL list of variables to expire (defaults to all)
     * @throws Zend_Session_Exception
     * @return void
     */
    public function setExpirationSeconds($seconds, $variables = null)
    {
        if (parent::$_writable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(parent::_THROW_NOT_WRITABLE_MSG);
        }

        if ($seconds <= 0) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('Seconds must be positive.');
        }

        if ($variables === null) {

            // apply expiration to entire namespace
            $_SESSION['__ZF'][$this->_namespace]['ENT'] = time() + $seconds;

        } else {

            if (is_string($variables)) {
                $variables = array($variables);
            }

            foreach ($variables as $variable) {
                if (!empty($variable)) {
                    $_SESSION['__ZF'][$this->_namespace]['ENVT'][$variable] = time() + $seconds;
                }
            }
        }
    }


    /**
     * setExpirationHops() - expire the namespace, or specific variables after a specified
     * number of page hops
     *
     * @param int $hops        - how many "hops" (number of subsequent requests) before expiring
     * @param mixed $variables - OPTIONAL list of variables to expire (defaults to all)
     * @param boolean $hopCountOnUsageOnly - OPTIONAL if set, only count a hop/request if this namespace is used
     * @throws Zend_Session_Exception
     * @return void
     */
    public function setExpirationHops($hops, $variables = null, $hopCountOnUsageOnly = false)
    {
        if (parent::$_writable === false) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception(parent::_THROW_NOT_WRITABLE_MSG);
        }

        if ($hops <= 0) {
            /**
             * @see Zend_Session_Exception
             */
            require_once 'Zend/Session/Exception.php';
            throw new Zend_Session_Exception('Hops must be positive number.');
        }

        if ($variables === null) {

            // apply expiration to entire namespace
            if ($hopCountOnUsageOnly === false) {
                $_SESSION['__ZF'][$this->_namespace]['ENGH'] = $hops;
            } else {
                $_SESSION['__ZF'][$this->_namespace]['ENNH'] = $hops;
            }

        } else {

            if (is_string($variables)) {
                $variables = array($variables);
            }

            foreach ($variables as $variable) {
                if (!empty($variable)) {
                    if ($hopCountOnUsageOnly === false) {
                        $_SESSION['__ZF'][$this->_namespace]['ENVGH'][$variable] = $hops;
                    } else {
                        $_SESSION['__ZF'][$this->_namespace]['ENVNH'][$variable] = $hops;
                    }
                }
            }
        }
    }

    /**
     * Returns the namespace name
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }
}

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
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 * @since      Preview Release 0.2
 */

/**
 * Zend_Session_SaveHandler_Interface
 *
 * @category   Zend
 * @package    Zend_Session
 * @subpackage SaveHandler
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://php.net/session_set_save_handler
 */
interface Zend_Session_SaveHandler_Interface
{

    /**
     * Open Session - retrieve resources
     *
     * @param string $save_path
     * @param string $name
     */
    public function open($save_path, $name);

    /**
     * Close Session - free resources
     *
     */
    public function close();

    /**
     * Read session data
     *
     * @param string $id
     */
    public function read($id);

    /**
     * Write Session - commit data to resource
     *
     * @param string $id
     * @param mixed $data
     */
    public function write($id, $data);

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $id
     */
    public function destroy($id);

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime);

}

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
 * @category   Zend
 * @package    Zend_Registry
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Registry.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Generic storage class helps to manage global data.
 *
 * @category   Zend
 * @package    Zend_Registry
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Registry extends ArrayObject
{
    /**
     * Class name of the singleton registry object.
     * @var string
     */
    private static $_registryClassName = 'Zend_Registry';

    /**
     * Registry object provides storage for shared objects.
     * @var Zend_Registry
     */
    private static $_registry = null;

    /**
     * Retrieves the default registry instance.
     *
     * @return Zend_Registry
     */
    public static function getInstance()
    {
        if (self::$_registry === null) {
            self::init();
        }

        return self::$_registry;
    }

    /**
     * Set the default registry instance to a specified instance.
     *
     * @param Zend_Registry $registry An object instance of type Zend_Registry,
     *   or a subclass.
     * @return void
     * @throws Zend_Exception if registry is already initialized.
     */
    public static function setInstance(Zend_Registry $registry)
    {
        if (self::$_registry !== null) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception('Registry is already initialized');
        }

        self::setClassName(get_class($registry));
        self::$_registry = $registry;
    }

    /**
     * Initialize the default registry instance.
     *
     * @return void
     */
    protected static function init()
    {
        self::setInstance(new self::$_registryClassName());
    }

    /**
     * Set the class name to use for the default registry instance.
     * Does not affect the currently initialized instance, it only applies
     * for the next time you instantiate.
     *
     * @param string $registryClassName
     * @return void
     * @throws Zend_Exception if the registry is initialized or if the
     *   class name is not valid.
     */
    public static function setClassName($registryClassName = 'Zend_Registry')
    {
        if (self::$_registry !== null) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception('Registry is already initialized');
        }

        if (!is_string($registryClassName)) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception("Argument is not a class name");
        }

        /**
         * @see Zend_Loader
         */
        if (!class_exists($registryClassName)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($registryClassName);
        }

        self::$_registryClassName = $registryClassName;
    }

    /**
     * Unset the default registry instance.
     * Primarily used in tearDown() in unit tests.
     * @returns void
     */
    public static function _unsetInstance()
    {
        self::$_registry = null;
    }

    /**
     * getter method, basically same as offsetGet().
     *
     * This method can be called from an object of type Zend_Registry, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index - get the value associated with $index
     * @return mixed
     * @throws Zend_Exception if no entry is registerd for $index.
     */
    public static function get($index)
    {
        $instance = self::getInstance();

        if (!$instance->offsetExists($index)) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception("No entry is registered for key '$index'");
        }

        return $instance->offsetGet($index);
    }

    /**
     * setter method, basically same as offsetSet().
     *
     * This method can be called from an object of type Zend_Registry, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index The location in the ArrayObject in which to store
     *   the value.
     * @param mixed $value The object to store in the ArrayObject.
     * @return void
     */
    public static function set($index, $value)
    {
        $instance = self::getInstance();
        $instance->offsetSet($index, $value);
    }

    /**
     * Returns TRUE if the $index is a named value in the registry,
     * or FALSE if $index was not found in the registry.
     *
     * @param  string $index
     * @return boolean
     */
    public static function isRegistered($index)
    {
        if (self::$_registry === null) {
            return false;
        }
        return self::$_registry->offsetExists($index);
    }

    /**
     * Constructs a parent ArrayObject with default
     * ARRAY_AS_PROPS to allow acces as an object
     *
     * @param array $array data array
     * @param integer $flags ArrayObject flags
     */
    public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }

    /**
     * @param string $index
     * @returns mixed
     *
     * Workaround for http://bugs.php.net/bug.php?id=40442 (ZF-960).
     */
    public function offsetExists($index)
    {
        return array_key_exists($index, $this);
    }

}

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
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 23775 2011-03-01 17:25:24Z ralph $
 * @since      Preview Release 0.2
 */


/**
 * @see Zend_Exception
 */
require_once 'Zend/Exception.php';


/**
 * Zend_Session_Exception
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Session_Exception extends Zend_Exception
{
    /**
     * sessionStartError
     *
     * @see http://framework.zend.com/issues/browse/ZF-1325
     * @var string PHP Error Message
     */
    static public $sessionStartError = null;

    /**
     * handleSessionStartError() - interface for set_error_handler()
     *
     * @see    http://framework.zend.com/issues/browse/ZF-1325
     * @param  int    $errno
     * @param  string $errstr
     * @return void
     */
    static public function handleSessionStartError($errno, $errstr, $errfile, $errline, $errcontext)
    {
        self::$sessionStartError = $errfile . '(Line:' . $errline . '): Error #' . $errno . ' ' . $errstr . ' ' . $errcontext;
    }

    /**
     * handleSilentWriteClose() - interface for set_error_handler()
     *
     * @see    http://framework.zend.com/issues/browse/ZF-1325
     * @param  int    $errno
     * @param  string $errstr
     * @return void
     */
    static public function handleSilentWriteClose($errno, $errstr, $errfile, $errline, $errcontext)
    {
        self::$sessionStartError .= PHP_EOL . $errfile . '(Line:' . $errline . '): Error #' . $errno . ' ' . $errstr . ' ' . $errcontext;
    }
}


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
 * @category   Zend
 * @package    Zend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
* @category   Zend
* @package    Zend
* @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
* @license    http://framework.zend.com/license/new-bsd     New BSD License
*/
class Zend_Exception extends Exception
{
    /**
     * @var null|Exception
     */
    private $_previous = null;

    /**
     * Construct the exception
     *
     * @param  string $msg
     * @param  int $code
     * @param  Exception $previous
     * @return void
     */
    public function __construct($msg = '', $code = 0, Exception $previous = null)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            parent::__construct($msg, (int) $code);
            $this->_previous = $previous;
        } else {
            parent::__construct($msg, (int) $code, $previous);
        }
    }

    /**
     * Overloading
     *
     * For PHP < 5.3.0, provides access to the getPrevious() method.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        if ('getprevious' == strtolower($method)) {
            return $this->_getPrevious();
        }
        return null;
    }

    /**
     * String representation of the exception
     *
     * @return string
     */
    public function __toString()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            if (null !== ($e = $this->getPrevious())) {
                return $e->__toString()
                       . "\n\nNext "
                       . parent::__toString();
            }
        }
        return parent::__toString();
    }

    /**
     * Returns previous Exception
     *
     * @return Exception|null
     */
    protected function _getPrevious()
    {
        return $this->_previous;
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: View.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * Abstract master class for extension.
 */
require_once 'Zend/View/Abstract.php';


/**
 * Concrete class for handling view scripts.
 *
 * @category   Zend
 * @package    Zend_View
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View extends Zend_View_Abstract
{
    /**
     * Whether or not to use streams to mimic short tags
     * @var bool
     */
    private $_useViewStream = false;

    /**
     * Whether or not to use stream wrapper if short_open_tag is false
     * @var bool
     */
    private $_useStreamWrapper = false;

    /**
     * Constructor
     *
     * Register Zend_View_Stream stream wrapper if short tags are disabled.
     *
     * @param  array $config
     * @return void
     */
    public function __construct($config = array())
    {
        $this->_useViewStream = (bool) ini_get('short_open_tag') ? false : true;
        if ($this->_useViewStream) {
            if (!in_array('zend.view', stream_get_wrappers())) {
                require_once 'Zend/View/Stream.php';
                stream_wrapper_register('zend.view', 'Zend_View_Stream');
            }
        }

        if (array_key_exists('useStreamWrapper', $config)) {
            $this->setUseStreamWrapper($config['useStreamWrapper']);
        }

        parent::__construct($config);
    }

    /**
     * Set flag indicating if stream wrapper should be used if short_open_tag is off
     *
     * @param  bool $flag
     * @return Zend_View
     */
    public function setUseStreamWrapper($flag)
    {
        $this->_useStreamWrapper = (bool) $flag;
        return $this;
    }

    /**
     * Should the stream wrapper be used if short_open_tag is off?
     *
     * @return bool
     */
    public function useStreamWrapper()
    {
        return $this->_useStreamWrapper;
    }

    /**
     * Includes the view script in a scope with only public $this variables.
     *
     * @param string The view script to execute.
     */
    protected function _run()
    {
        if ($this->_useViewStream && $this->useStreamWrapper()) {
            include 'zend.view://' . func_get_arg(0);
        } else {
            include func_get_arg(0);
        }
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23992 2011-05-04 03:32:01Z ralph $
 */

/** @see Zend_Loader */
require_once 'Zend/Loader.php';

/** @see Zend_Loader_PluginLoader */
require_once 'Zend/Loader/PluginLoader.php';

/** @see Zend_View_Interface */
require_once 'Zend/View/Interface.php';

/**
 * Abstract class for Zend_View to help enforce private constructs.
 *
 * @category   Zend
 * @package    Zend_View
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_View_Abstract implements Zend_View_Interface
{
    /**
     * Path stack for script, helper, and filter directories.
     *
     * @var array
     */
    private $_path = array(
        'script' => array(),
        'helper' => array(),
        'filter' => array(),
    );

    /**
     * Script file name to execute
     *
     * @var string
     */
    private $_file = null;

    /**
     * Instances of helper objects.
     *
     * @var array
     */
    private $_helper = array();

    /**
     * Map of helper => class pairs to help in determining helper class from
     * name
     * @var array
     */
    private $_helperLoaded = array();

    /**
     * Map of helper => classfile pairs to aid in determining helper classfile
     * @var array
     */
    private $_helperLoadedDir = array();

    /**
     * Stack of Zend_View_Filter names to apply as filters.
     * @var array
     */
    private $_filter = array();

    /**
     * Stack of Zend_View_Filter objects that have been loaded
     * @var array
     */
    private $_filterClass = array();

    /**
     * Map of filter => class pairs to help in determining filter class from
     * name
     * @var array
     */
    private $_filterLoaded = array();

    /**
     * Map of filter => classfile pairs to aid in determining filter classfile
     * @var array
     */
    private $_filterLoadedDir = array();

    /**
     * Callback for escaping.
     *
     * @var string
     */
    private $_escape = 'htmlspecialchars';

    /**
     * Encoding to use in escaping mechanisms; defaults to utf-8
     * @var string
     */
    private $_encoding = 'UTF-8';

    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
     * @var bool
     */
    private $_lfiProtectionOn = true;

    /**
     * Plugin loaders
     * @var array
     */
    private $_loaders = array();

    /**
     * Plugin types
     * @var array
     */
    private $_loaderTypes = array('filter', 'helper');

    /**
     * Strict variables flag; when on, undefined variables accessed in the view
     * scripts will trigger notices
     * @var boolean
     */
    private $_strictVars = false;

    /**
     * Constructor.
     *
     * @param array $config Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        // set inital paths and properties
        $this->setScriptPath(null);

        // $this->setHelperPath(null);
        $this->setFilterPath(null);

        // user-defined escaping callback
        if (array_key_exists('escape', $config)) {
            $this->setEscape($config['escape']);
        }

        // encoding
        if (array_key_exists('encoding', $config)) {
            $this->setEncoding($config['encoding']);
        }

        // base path
        if (array_key_exists('basePath', $config)) {
            $prefix = 'Zend_View';
            if (array_key_exists('basePathPrefix', $config)) {
                $prefix = $config['basePathPrefix'];
            }
            $this->setBasePath($config['basePath'], $prefix);
        }

        // user-defined view script path
        if (array_key_exists('scriptPath', $config)) {
            $this->addScriptPath($config['scriptPath']);
        }

        // user-defined helper path
        if (array_key_exists('helperPath', $config)) {
            if (is_array($config['helperPath'])) {
                foreach ($config['helperPath'] as $prefix => $path) {
                    $this->addHelperPath($path, $prefix);
                }
            } else {
                $prefix = 'Zend_View_Helper';
                if (array_key_exists('helperPathPrefix', $config)) {
                    $prefix = $config['helperPathPrefix'];
                }
                $this->addHelperPath($config['helperPath'], $prefix);
            }
        }

        // user-defined filter path
        if (array_key_exists('filterPath', $config)) {
            if (is_array($config['filterPath'])) {
                foreach ($config['filterPath'] as $prefix => $path) {
                    $this->addFilterPath($path, $prefix);
                }
            } else {
                $prefix = 'Zend_View_Filter';
                if (array_key_exists('filterPathPrefix', $config)) {
                    $prefix = $config['filterPathPrefix'];
                }
                $this->addFilterPath($config['filterPath'], $prefix);
            }
        }

        // user-defined filters
        if (array_key_exists('filter', $config)) {
            $this->addFilter($config['filter']);
        }

        // strict vars
        if (array_key_exists('strictVars', $config)) {
            $this->strictVars($config['strictVars']);
        }

        // LFI protection flag
        if (array_key_exists('lfiProtectionOn', $config)) {
            $this->setLfiProtection($config['lfiProtectionOn']);
        }

        if (array_key_exists('assign', $config)
            && is_array($config['assign'])
        ) {
            foreach ($config['assign'] as $key => $value) {
                $this->assign($key, $value);
            }
        }

        $this->init();
    }

    /**
     * Return the template engine object
     *
     * Returns the object instance, as it is its own template engine
     *
     * @return Zend_View_Abstract
     */
    public function getEngine()
    {
        return $this;
    }

    /**
     * Allow custom object initialization when extending Zend_View_Abstract or
     * Zend_View
     *
     * Triggered by {@link __construct() the constructor} as its final action.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Prevent E_NOTICE for nonexistent values
     *
     * If {@link strictVars()} is on, raises a notice.
     *
     * @param  string $key
     * @return null
     */
    public function __get($key)
    {
        if ($this->_strictVars) {
            trigger_error('Key "' . $key . '" does not exist', E_USER_NOTICE);
        }

        return null;
    }

    /**
     * Allows testing with empty() and isset() to work inside
     * templates.
     *
     * @param  string $key
     * @return boolean
     */
    public function __isset($key)
    {
        if ('_' != substr($key, 0, 1)) {
            return isset($this->$key);
        }

        return false;
    }

    /**
     * Directly assigns a variable to the view script.
     *
     * Checks first to ensure that the caller is not attempting to set a
     * protected or private member (by checking for a prefixed underscore); if
     * not, the public member is set; otherwise, an exception is raised.
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     * @throws Zend_View_Exception if an attempt to set a private or protected
     * member is detected
     */
    public function __set($key, $val)
    {
        if ('_' != substr($key, 0, 1)) {
            $this->$key = $val;
            return;
        }

        require_once 'Zend/View/Exception.php';
        $e = new Zend_View_Exception('Setting private or protected class members is not allowed');
        $e->setView($this);
        throw $e;
    }

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        if ('_' != substr($key, 0, 1) && isset($this->$key)) {
            unset($this->$key);
        }
    }

    /**
     * Accesses a helper object from within a script.
     *
     * If the helper class has a 'view' property, sets it with the current view
     * object.
     *
     * @param string $name The helper name.
     * @param array $args The parameters for the helper.
     * @return string The result of the helper output.
     */
    public function __call($name, $args)
    {
        // is the helper already loaded?
        $helper = $this->getHelper($name);

        // call the helper method
        return call_user_func_array(
            array($helper, $name),
            $args
        );
    }

    /**
     * Given a base path, sets the script, helper, and filter paths relative to it
     *
     * Assumes a directory structure of:
     * <code>
     * basePath/
     *     scripts/
     *     helpers/
     *     filters/
     * </code>
     *
     * @param  string $path
     * @param  string $prefix Prefix to use for helper and filter paths
     * @return Zend_View_Abstract
     */
    public function setBasePath($path, $classPrefix = 'Zend_View')
    {
        $path        = rtrim($path, '/');
        $path        = rtrim($path, '\\');
        $path       .= DIRECTORY_SEPARATOR;
        $classPrefix = rtrim($classPrefix, '_') . '_';
        $this->setScriptPath($path . 'scripts');
        $this->setHelperPath($path . 'helpers', $classPrefix . 'Helper');
        $this->setFilterPath($path . 'filters', $classPrefix . 'Filter');
        return $this;
    }

    /**
     * Given a base path, add script, helper, and filter paths relative to it
     *
     * Assumes a directory structure of:
     * <code>
     * basePath/
     *     scripts/
     *     helpers/
     *     filters/
     * </code>
     *
     * @param  string $path
     * @param  string $prefix Prefix to use for helper and filter paths
     * @return Zend_View_Abstract
     */
    public function addBasePath($path, $classPrefix = 'Zend_View')
    {
        $path        = rtrim($path, '/');
        $path        = rtrim($path, '\\');
        $path       .= DIRECTORY_SEPARATOR;
        $classPrefix = rtrim($classPrefix, '_') . '_';
        $this->addScriptPath($path . 'scripts');
        $this->addHelperPath($path . 'helpers', $classPrefix . 'Helper');
        $this->addFilterPath($path . 'filters', $classPrefix . 'Filter');
        return $this;
    }

    /**
     * Adds to the stack of view script paths in LIFO order.
     *
     * @param string|array The directory (-ies) to add.
     * @return Zend_View_Abstract
     */
    public function addScriptPath($path)
    {
        $this->_addPath('script', $path);
        return $this;
    }

    /**
     * Resets the stack of view script paths.
     *
     * To clear all paths, use Zend_View::setScriptPath(null).
     *
     * @param string|array The directory (-ies) to set as the path.
     * @return Zend_View_Abstract
     */
    public function setScriptPath($path)
    {
        $this->_path['script'] = array();
        $this->_addPath('script', $path);
        return $this;
    }

    /**
     * Return full path to a view script specified by $name
     *
     * @param  string $name
     * @return false|string False if script not found
     * @throws Zend_View_Exception if no script directory set
     */
    public function getScriptPath($name)
    {
        try {
            $path = $this->_script($name);
            return $path;
        } catch (Zend_View_Exception $e) {
            if (strstr($e->getMessage(), 'no view script directory set')) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Returns an array of all currently set script paths
     *
     * @return array
     */
    public function getScriptPaths()
    {
        return $this->_getPaths('script');
    }

    /**
     * Set plugin loader for a particular plugin type
     *
     * @param  Zend_Loader_PluginLoader $loader
     * @param  string $type
     * @return Zend_View_Abstract
     */
    public function setPluginLoader(Zend_Loader_PluginLoader $loader, $type)
    {
        $type = strtolower($type);
        if (!in_array($type, $this->_loaderTypes)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception(sprintf('Invalid plugin loader type "%s"', $type));
            $e->setView($this);
            throw $e;
        }

        $this->_loaders[$type] = $loader;
        return $this;
    }

    /**
     * Retrieve plugin loader for a specific plugin type
     *
     * @param  string $type
     * @return Zend_Loader_PluginLoader
     */
    public function getPluginLoader($type)
    {
        $type = strtolower($type);
        if (!in_array($type, $this->_loaderTypes)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception(sprintf('Invalid plugin loader type "%s"; cannot retrieve', $type));
            $e->setView($this);
            throw $e;
        }

        if (!array_key_exists($type, $this->_loaders)) {
            $prefix     = 'Zend_View_';
            $pathPrefix = 'Zend/View/';

            $pType = ucfirst($type);
            switch ($type) {
                case 'filter':
                case 'helper':
                default:
                    $prefix     .= $pType;
                    $pathPrefix .= $pType;
                    $loader = new Zend_Loader_PluginLoader(array(
                        $prefix => $pathPrefix
                    ));
                    $this->_loaders[$type] = $loader;
                    break;
            }
        }
        return $this->_loaders[$type];
    }

    /**
     * Adds to the stack of helper paths in LIFO order.
     *
     * @param string|array The directory (-ies) to add.
     * @param string $classPrefix Class prefix to use with classes in this
     * directory; defaults to Zend_View_Helper
     * @return Zend_View_Abstract
     */
    public function addHelperPath($path, $classPrefix = 'Zend_View_Helper_')
    {
        return $this->_addPluginPath('helper', $classPrefix, (array) $path);
    }

    /**
     * Resets the stack of helper paths.
     *
     * To clear all paths, use Zend_View::setHelperPath(null).
     *
     * @param string|array $path The directory (-ies) to set as the path.
     * @param string $classPrefix The class prefix to apply to all elements in
     * $path; defaults to Zend_View_Helper
     * @return Zend_View_Abstract
     */
    public function setHelperPath($path, $classPrefix = 'Zend_View_Helper_')
    {
        unset($this->_loaders['helper']);
        return $this->addHelperPath($path, $classPrefix);
    }

    /**
     * Get full path to a helper class file specified by $name
     *
     * @param  string $name
     * @return string|false False on failure, path on success
     */
    public function getHelperPath($name)
    {
        return $this->_getPluginPath('helper', $name);
    }

    /**
     * Returns an array of all currently set helper paths
     *
     * @return array
     */
    public function getHelperPaths()
    {
        return $this->getPluginLoader('helper')->getPaths();
    }

    /**
     * Registers a helper object, bypassing plugin loader
     *
     * @param  Zend_View_Helper_Abstract|object $helper
     * @param  string $name
     * @return Zend_View_Abstract
     * @throws Zend_View_Exception
     */
    public function registerHelper($helper, $name)
    {
        if (!is_object($helper)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('View helper must be an object');
            $e->setView($this);
            throw $e;
        }

        if (!$helper instanceof Zend_View_Interface) {
            if (!method_exists($helper, $name)) {
                require_once 'Zend/View/Exception.php';
                $e =  new Zend_View_Exception(
                    'View helper must implement Zend_View_Interface or have a method matching the name provided'
                );
                $e->setView($this);
                throw $e;
            }
        }

        if (method_exists($helper, 'setView')) {
            $helper->setView($this);
        }

        $name = ucfirst($name);
        $this->_helper[$name] = $helper;
        return $this;
    }

    /**
     * Get a helper by name
     *
     * @param  string $name
     * @return object
     */
    public function getHelper($name)
    {
        return $this->_getPlugin('helper', $name);
    }

    /**
     * Get array of all active helpers
     *
     * Only returns those that have already been instantiated.
     *
     * @return array
     */
    public function getHelpers()
    {
        return $this->_helper;
    }

    /**
     * Adds to the stack of filter paths in LIFO order.
     *
     * @param string|array The directory (-ies) to add.
     * @param string $classPrefix Class prefix to use with classes in this
     * directory; defaults to Zend_View_Filter
     * @return Zend_View_Abstract
     */
    public function addFilterPath($path, $classPrefix = 'Zend_View_Filter_')
    {
        return $this->_addPluginPath('filter', $classPrefix, (array) $path);
    }

    /**
     * Resets the stack of filter paths.
     *
     * To clear all paths, use Zend_View::setFilterPath(null).
     *
     * @param string|array The directory (-ies) to set as the path.
     * @param string $classPrefix The class prefix to apply to all elements in
     * $path; defaults to Zend_View_Filter
     * @return Zend_View_Abstract
     */
    public function setFilterPath($path, $classPrefix = 'Zend_View_Filter_')
    {
        unset($this->_loaders['filter']);
        return $this->addFilterPath($path, $classPrefix);
    }

    /**
     * Get full path to a filter class file specified by $name
     *
     * @param  string $name
     * @return string|false False on failure, path on success
     */
    public function getFilterPath($name)
    {
        return $this->_getPluginPath('filter', $name);
    }

    /**
     * Get a filter object by name
     *
     * @param  string $name
     * @return object
     */
    public function getFilter($name)
    {
        return $this->_getPlugin('filter', $name);
    }

    /**
     * Return array of all currently active filters
     *
     * Only returns those that have already been instantiated.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filter;
    }

    /**
     * Returns an array of all currently set filter paths
     *
     * @return array
     */
    public function getFilterPaths()
    {
        return $this->getPluginLoader('filter')->getPaths();
    }

    /**
     * Return associative array of path types => paths
     *
     * @return array
     */
    public function getAllPaths()
    {
        $paths = $this->_path;
        $paths['helper'] = $this->getHelperPaths();
        $paths['filter'] = $this->getFilterPaths();
        return $paths;
    }

    /**
     * Add one or more filters to the stack in FIFO order.
     *
     * @param string|array One or more filters to add.
     * @return Zend_View_Abstract
     */
    public function addFilter($name)
    {
        foreach ((array) $name as $val) {
            $this->_filter[] = $val;
        }
        return $this;
    }

    /**
     * Resets the filter stack.
     *
     * To clear all filters, use Zend_View::setFilter(null).
     *
     * @param string|array One or more filters to set.
     * @return Zend_View_Abstract
     */
    public function setFilter($name)
    {
        $this->_filter = array();
        $this->addFilter($name);
        return $this;
    }

    /**
     * Sets the _escape() callback.
     *
     * @param mixed $spec The callback for _escape() to use.
     * @return Zend_View_Abstract
     */
    public function setEscape($spec)
    {
        $this->_escape = $spec;
        return $this;
    }

    /**
     * Set LFI protection flag
     *
     * @param  bool $flag
     * @return Zend_View_Abstract
     */
    public function setLfiProtection($flag)
    {
        $this->_lfiProtectionOn = (bool) $flag;
        return $this;
    }

    /**
     * Return status of LFI protection flag
     *
     * @return bool
     */
    public function isLfiProtectionOn()
    {
        return $this->_lfiProtectionOn;
    }

    /**
     * Assigns variables to the view script via differing strategies.
     *
     * Zend_View::assign('name', $value) assigns a variable called 'name'
     * with the corresponding $value.
     *
     * Zend_View::assign($array) assigns the array keys as variable
     * names (with the corresponding array values).
     *
     * @see    __set()
     * @param  string|array The assignment strategy to use.
     * @param  mixed (Optional) If assigning a named variable, use this
     * as the value.
     * @return Zend_View_Abstract Fluent interface
     * @throws Zend_View_Exception if $spec is neither a string nor an array,
     * or if an attempt to set a private or protected member is detected
     */
    public function assign($spec, $value = null)
    {
        // which strategy to use?
        if (is_string($spec)) {
            // assign by name and value
            if ('_' == substr($spec, 0, 1)) {
                require_once 'Zend/View/Exception.php';
                $e = new Zend_View_Exception('Setting private or protected class members is not allowed');
                $e->setView($this);
                throw $e;
            }
            $this->$spec = $value;
        } elseif (is_array($spec)) {
            // assign from associative array
            $error = false;
            foreach ($spec as $key => $val) {
                if ('_' == substr($key, 0, 1)) {
                    $error = true;
                    break;
                }
                $this->$key = $val;
            }
            if ($error) {
                require_once 'Zend/View/Exception.php';
                $e = new Zend_View_Exception('Setting private or protected class members is not allowed');
                $e->setView($this);
                throw $e;
            }
        } else {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('assign() expects a string or array, received ' . gettype($spec));
            $e->setView($this);
            throw $e;
        }

        return $this;
    }

    /**
     * Return list of all assigned variables
     *
     * Returns all public properties of the object. Reflection is not used
     * here as testing reflection properties for visibility is buggy.
     *
     * @return array
     */
    public function getVars()
    {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ('_' == substr($key, 0, 1)) {
                unset($vars[$key]);
            }
        }

        return $vars;
    }

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to Zend_View either via {@link assign()} or
     * property overloading ({@link __set()}).
     *
     * @return void
     */
    public function clearVars()
    {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ('_' != substr($key, 0, 1)) {
                unset($this->$key);
            }
        }
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name The script name to process.
     * @return string The script output.
     */
    public function render($name)
    {
        // find the script file name using the parent private method
        $this->_file = $this->_script($name);
        unset($name); // remove $name from local scope

        ob_start();
        $this->_run($this->_file);

        return $this->_filter(ob_get_clean()); // filter output
    }

    /**
     * Escapes a value for output in a view script.
     *
     * If escaping mechanism is one of htmlspecialchars or htmlentities, uses
     * {@link $_encoding} setting.
     *
     * @param mixed $var The output to escape.
     * @return mixed The escaped value.
     */
    public function escape($var)
    {
        if (in_array($this->_escape, array('htmlspecialchars', 'htmlentities'))) {
            return call_user_func($this->_escape, $var, ENT_COMPAT, $this->_encoding);
        }

        if (1 == func_num_args()) {
            return call_user_func($this->_escape, $var);
        }
        $args = func_get_args();
        return call_user_func_array($this->_escape, $args);
    }

    /**
     * Set encoding to use with htmlentities() and htmlspecialchars()
     *
     * @param string $encoding
     * @return Zend_View_Abstract
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    /**
     * Return current escape encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Enable or disable strict vars
     *
     * If strict variables are enabled, {@link __get()} will raise a notice
     * when a variable is not defined.
     *
     * Use in conjunction with {@link Zend_View_Helper_DeclareVars the declareVars() helper}
     * to enforce strict variable handling in your view scripts.
     *
     * @param  boolean $flag
     * @return Zend_View_Abstract
     */
    public function strictVars($flag = true)
    {
        $this->_strictVars = ($flag) ? true : false;

        return $this;
    }

    /**
     * Finds a view script from the available directories.
     *
     * @param string $name The base name of the script.
     * @return void
     */
    protected function _script($name)
    {
        if ($this->isLfiProtectionOn() && preg_match('#\.\.[\\\/]#', $name)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Requested scripts may not include parent directory traversal ("../", "..\\" notation)');
            $e->setView($this);
            throw $e;
        }

        if (0 == count($this->_path['script'])) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('no view script directory set; unable to determine location for view script');
            $e->setView($this);
            throw $e;
        }

        foreach ($this->_path['script'] as $dir) {
            if (is_readable($dir . $name)) {
                return $dir . $name;
            }
        }

        require_once 'Zend/View/Exception.php';
        $message = "script '$name' not found in path ("
                 . implode(PATH_SEPARATOR, $this->_path['script'])
                 . ")";
        $e = new Zend_View_Exception($message);
        $e->setView($this);
        throw $e;
    }

    /**
     * Applies the filter callback to a buffer.
     *
     * @param string $buffer The buffer contents.
     * @return string The filtered buffer.
     */
    private function _filter($buffer)
    {
        // loop through each filter class
        foreach ($this->_filter as $name) {
            // load and apply the filter class
            $filter = $this->getFilter($name);
            $buffer = call_user_func(array($filter, 'filter'), $buffer);
        }

        // done!
        return $buffer;
    }

    /**
     * Adds paths to the path stack in LIFO order.
     *
     * Zend_View::_addPath($type, 'dirname') adds one directory
     * to the path stack.
     *
     * Zend_View::_addPath($type, $array) adds one directory for
     * each array element value.
     *
     * In the case of filter and helper paths, $prefix should be used to
     * specify what class prefix to use with the given path.
     *
     * @param string $type The path type ('script', 'helper', or 'filter').
     * @param string|array $path The path specification.
     * @param string $prefix Class prefix to use with path (helpers and filters
     * only)
     * @return void
     */
    private function _addPath($type, $path, $prefix = null)
    {
        foreach ((array) $path as $dir) {
            // attempt to strip any possible separator and
            // append the system directory separator
            $dir  = rtrim($dir, '/');
            $dir  = rtrim($dir, '\\');
            $dir .= '/';

            switch ($type) {
                case 'script':
                    // add to the top of the stack.
                    array_unshift($this->_path[$type], $dir);
                    break;
                case 'filter':
                case 'helper':
                default:
                    // add as array with prefix and dir keys
                    array_unshift($this->_path[$type], array('prefix' => $prefix, 'dir' => $dir));
                    break;
            }
        }
    }

    /**
     * Resets the path stack for helpers and filters.
     *
     * @param string $type The path type ('helper' or 'filter').
     * @param string|array $path The directory (-ies) to set as the path.
     * @param string $classPrefix Class prefix to apply to elements of $path
     */
    private function _setPath($type, $path, $classPrefix = null)
    {
        $dir = DIRECTORY_SEPARATOR . ucfirst($type) . DIRECTORY_SEPARATOR;

        switch ($type) {
            case 'script':
                $this->_path[$type] = array(dirname(__FILE__) . $dir);
                $this->_addPath($type, $path);
                break;
            case 'filter':
            case 'helper':
            default:
                $this->_path[$type] = array(array(
                    'prefix' => 'Zend_View_' . ucfirst($type) . '_',
                    'dir'    => dirname(__FILE__) . $dir
                ));
                $this->_addPath($type, $path, $classPrefix);
                break;
        }
    }

    /**
     * Return all paths for a given path type
     *
     * @param string $type The path type  ('helper', 'filter', 'script')
     * @return array
     */
    private function _getPaths($type)
    {
        return $this->_path[$type];
    }

    /**
     * Register helper class as loaded
     *
     * @param  string $name
     * @param  string $class
     * @param  string $file path to class file
     * @return void
     */
    private function _setHelperClass($name, $class, $file)
    {
        $this->_helperLoadedDir[$name] = $file;
        $this->_helperLoaded[$name]    = $class;
    }

    /**
     * Register filter class as loaded
     *
     * @param  string $name
     * @param  string $class
     * @param  string $file path to class file
     * @return void
     */
    private function _setFilterClass($name, $class, $file)
    {
        $this->_filterLoadedDir[$name] = $file;
        $this->_filterLoaded[$name]    = $class;
    }

    /**
     * Add a prefixPath for a plugin type
     *
     * @param  string $type
     * @param  string $classPrefix
     * @param  array $paths
     * @return Zend_View_Abstract
     */
    private function _addPluginPath($type, $classPrefix, array $paths)
    {
        $loader = $this->getPluginLoader($type);
        foreach ($paths as $path) {
            $loader->addPrefixPath($classPrefix, $path);
        }
        return $this;
    }

    /**
     * Get a path to a given plugin class of a given type
     *
     * @param  string $type
     * @param  string $name
     * @return string|false
     */
    private function _getPluginPath($type, $name)
    {
        $loader = $this->getPluginLoader($type);
        if ($loader->isLoaded($name)) {
            return $loader->getClassPath($name);
        }

        try {
            $loader->load($name);
            return $loader->getClassPath($name);
        } catch (Zend_Loader_Exception $e) {
            return false;
        }
    }

    /**
     * Retrieve a plugin object
     *
     * @param  string $type
     * @param  string $name
     * @return object
     */
    private function _getPlugin($type, $name)
    {
        $name = ucfirst($name);
        switch ($type) {
            case 'filter':
                $storeVar = '_filterClass';
                $store    = $this->_filterClass;
                break;
            case 'helper':
                $storeVar = '_helper';
                $store    = $this->_helper;
                break;
        }

        if (!isset($store[$name])) {
            $class = $this->getPluginLoader($type)->load($name);
            $store[$name] = new $class();
            if (method_exists($store[$name], 'setView')) {
                $store[$name]->setView($this);
            }
        }

        $this->$storeVar = $store;
        return $store[$name];
    }

    /**
     * Use to include the view script in a scope that only allows public
     * members.
     *
     * @return mixed
     */
    abstract protected function _run();
}

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
 * @category   Zend
 * @package    Zend_View
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * Interface class for Zend_View compatible template engine implementations
 *
 * @category   Zend
 * @package    Zend_View
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_View_Interface
{
    /**
     * Return the template engine object, if any
     *
     * If using a third-party template engine, such as Smarty, patTemplate,
     * phplib, etc, return the template engine object. Useful for calling
     * methods on these objects, such as for setting filters, modifiers, etc.
     *
     * @return mixed
     */
    public function getEngine();

    /**
     * Set the path to find the view script used by render()
     *
     * @param string|array The directory (-ies) to set as the path. Note that
     * the concrete view implentation may not necessarily support multiple
     * directories.
     * @return void
     */
    public function setScriptPath($path);

    /**
     * Retrieve all view script paths
     *
     * @return array
     */
    public function getScriptPaths();

    /**
     * Set a base path to all view resources
     *
     * @param  string $path
     * @param  string $classPrefix
     * @return void
     */
    public function setBasePath($path, $classPrefix = 'Zend_View');

    /**
     * Add an additional path to view resources
     *
     * @param  string $path
     * @param  string $classPrefix
     * @return void
     */
    public function addBasePath($path, $classPrefix = 'Zend_View');

    /**
     * Assign a variable to the view
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($key, $val);

    /**
     * Allows testing with empty() and isset() to work
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key);

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key);

    /**
     * Assign variables to the view script via differing strategies.
     *
     * Suggested implementation is to allow setting a specific key to the
     * specified value, OR passing an array of key => value pairs to set en
     * masse.
     *
     * @see __set()
     * @param string|array $spec The assignment strategy to use (key or array of key
     * => value pairs)
     * @param mixed $value (Optional) If assigning a named variable, use this
     * as the value.
     * @return void
     */
    public function assign($spec, $value = null);

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to Zend_View either via {@link assign()} or
     * property overloading ({@link __get()}/{@link __set()}).
     *
     * @return void
     */
    public function clearVars();

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name The script name to process.
     * @return string The script output.
     */
    public function render($name);
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Doctype.php 24201 2011-07-05 16:22:04Z matthew $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Registry */
require_once 'Zend/Registry.php';

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * Helper for setting and retrieving the doctype
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Doctype extends Zend_View_Helper_Abstract
{
    /**#@+
     * DocType constants
     */
    const XHTML11             = 'XHTML11';
    const XHTML1_STRICT       = 'XHTML1_STRICT';
    const XHTML1_TRANSITIONAL = 'XHTML1_TRANSITIONAL';
    const XHTML1_FRAMESET     = 'XHTML1_FRAMESET';
    const XHTML1_RDFA         = 'XHTML1_RDFA';
    const XHTML_BASIC1        = 'XHTML_BASIC1';
    const XHTML5              = 'XHTML5';
    const HTML4_STRICT        = 'HTML4_STRICT';
    const HTML4_LOOSE         = 'HTML4_LOOSE';
    const HTML4_FRAMESET      = 'HTML4_FRAMESET';
    const HTML5               = 'HTML5';
    const CUSTOM_XHTML        = 'CUSTOM_XHTML';
    const CUSTOM              = 'CUSTOM';
    /**#@-*/

    /**
     * Default DocType
     * @var string
     */
    protected $_defaultDoctype = self::HTML4_LOOSE;

    /**
     * Registry containing current doctype and mappings
     * @var ArrayObject
     */
    protected $_registry;

    /**
     * Registry key in which helper is stored
     * @var string
     */
    protected $_regKey = 'Zend_View_Helper_Doctype';

    /**
     * Constructor
     *
     * Map constants to doctype strings, and set default doctype
     *
     * @return void
     */
    public function __construct()
    {
        if (!Zend_Registry::isRegistered($this->_regKey)) {
            $this->_registry = new ArrayObject(array(
                'doctypes' => array(
                    self::XHTML11             => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
                    self::XHTML1_STRICT       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
                    self::XHTML1_TRANSITIONAL => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
                    self::XHTML1_FRAMESET     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
                    self::XHTML1_RDFA         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">',
                    self::XHTML_BASIC1        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">',
                    self::XHTML5              => '<!DOCTYPE html>',
                    self::HTML4_STRICT        => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
                    self::HTML4_LOOSE         => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
                    self::HTML4_FRAMESET      => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
                    self::HTML5               => '<!DOCTYPE html>',
                )
            ));
            Zend_Registry::set($this->_regKey, $this->_registry);
            $this->setDoctype($this->_defaultDoctype);
        } else {
            $this->_registry = Zend_Registry::get($this->_regKey);
        }
    }

    /**
     * Set or retrieve doctype
     *
     * @param  string $doctype
     * @return Zend_View_Helper_Doctype
     */
    public function doctype($doctype = null)
    {
        if (null !== $doctype) {
            switch ($doctype) {
                case self::XHTML11:
                case self::XHTML1_STRICT:
                case self::XHTML1_TRANSITIONAL:
                case self::XHTML1_FRAMESET:
                case self::XHTML_BASIC1:
                case self::XHTML1_RDFA:
                case self::XHTML5:
                case self::HTML4_STRICT:
                case self::HTML4_LOOSE:
                case self::HTML4_FRAMESET:
                case self::HTML5:
                    $this->setDoctype($doctype);
                    break;
                default:
                    if (substr($doctype, 0, 9) != '<!DOCTYPE') {
                        require_once 'Zend/View/Exception.php';
                        $e = new Zend_View_Exception('The specified doctype is malformed');
                        $e->setView($this->view);
                        throw $e;
                    }
                    if (stristr($doctype, 'xhtml')) {
                        $type = self::CUSTOM_XHTML;
                    } else {
                        $type = self::CUSTOM;
                    }
                    $this->setDoctype($type);
                    $this->_registry['doctypes'][$type] = $doctype;
                    break;
            }
        }

        return $this;
    }

    /**
     * Set doctype
     *
     * @param  string $doctype
     * @return Zend_View_Helper_Doctype
     */
    public function setDoctype($doctype)
    {
        $this->_registry['doctype'] = $doctype;
        return $this;
    }

    /**
     * Retrieve doctype
     *
     * @return string
     */
    public function getDoctype()
    {
        return $this->_registry['doctype'];
    }

    /**
     * Get doctype => string mappings
     *
     * @return array
     */
    public function getDoctypes()
    {
        return $this->_registry['doctypes'];
    }

    /**
     * Is doctype XHTML?
     *
     * @return boolean
     */
    public function isXhtml()
    {
        return (stristr($this->getDoctype(), 'xhtml') ? true : false);
    }

    /**
     * Is doctype strict?
     *
     * @return boolean
     */
    public function isStrict()
    {
        switch ( $this->getDoctype() )
        {
            case self::XHTML1_STRICT:
            case self::XHTML11:
            case self::HTML4_STRICT:
                return true;
            default: 
                return false;
        }
    }
    
    /**
     * Is doctype HTML5? (HeadMeta uses this for validation)
     *
     * @return booleean
     */
    public function isHtml5() {
        return (stristr($this->doctype(), '<!DOCTYPE html>') ? true : false);
    }
    
    /**
     * Is doctype RDFa?
     *
     * @return booleean
     */
    public function isRdfa() {
        return (stristr($this->getDoctype(), 'rdfa') ? true : false);
    }

    /**
     * String representation of doctype
     *
     * @return string
     */
    public function __toString()
    {
        $doctypes = $this->getDoctypes();
        return $doctypes[$this->getDoctype()];
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_View_Helper_Interface
 */
require_once 'Zend/View/Helper/Interface.php';

/**
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_View_Helper_Abstract implements Zend_View_Helper_Interface
{
    /**
     * View object
     *
     * @var Zend_View_Interface
     */
    public $view = null;

    /**
     * Set the View object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_View_Helper_Abstract
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Strategy pattern: currently unutilized
     *
     * @return void
     */
    public function direct()
    {
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_View_Helper_Interface
{
    /**
     * Set the View object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_View_Helper_Interface
     */
    public function setView(Zend_View_Interface $view);

    /**
     * Strategy pattern: helper method to invoke
     *
     * @return mixed
     */
    public function direct();
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: HelperBroker.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Controller_Action_HelperBroker_PriorityStack
 */
require_once 'Zend/Controller/Action/HelperBroker/PriorityStack.php';

/**
 * @see Zend_Loader
 */
require_once 'Zend/Loader.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Action_HelperBroker
{
    /**
     * $_actionController - ActionController reference
     *
     * @var Zend_Controller_Action
     */
    protected $_actionController;

    /**
     * @var Zend_Loader_PluginLoader_Interface
     */
    protected static $_pluginLoader;

    /**
     * $_helpers - Helper array
     *
     * @var Zend_Controller_Action_HelperBroker_PriorityStack
     */
    protected static $_stack = null;

    /**
     * Set PluginLoader for use with broker
     *
     * @param  Zend_Loader_PluginLoader_Interface $loader
     * @return void
     */
    public static function setPluginLoader($loader)
    {
        if ((null !== $loader) && (!$loader instanceof Zend_Loader_PluginLoader_Interface)) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Invalid plugin loader provided to HelperBroker');
        }
        self::$_pluginLoader = $loader;
    }

    /**
     * Retrieve PluginLoader
     *
     * @return Zend_Loader_PluginLoader
     */
    public static function getPluginLoader()
    {
        if (null === self::$_pluginLoader) {
            require_once 'Zend/Loader/PluginLoader.php';
            self::$_pluginLoader = new Zend_Loader_PluginLoader(array(
                'Zend_Controller_Action_Helper' => 'Zend/Controller/Action/Helper/',
            ));
        }
        return self::$_pluginLoader;
    }

    /**
     * addPrefix() - Add repository of helpers by prefix
     *
     * @param string $prefix
     */
    static public function addPrefix($prefix)
    {
        $prefix = rtrim($prefix, '_');
        $path   = str_replace('_', DIRECTORY_SEPARATOR, $prefix);
        self::getPluginLoader()->addPrefixPath($prefix, $path);
    }

    /**
     * addPath() - Add path to repositories where Action_Helpers could be found.
     *
     * @param string $path
     * @param string $prefix Optional; defaults to 'Zend_Controller_Action_Helper'
     * @return void
     */
    static public function addPath($path, $prefix = 'Zend_Controller_Action_Helper')
    {
        self::getPluginLoader()->addPrefixPath($prefix, $path);
    }

    /**
     * addHelper() - Add helper objects
     *
     * @param Zend_Controller_Action_Helper_Abstract $helper
     * @return void
     */
    static public function addHelper(Zend_Controller_Action_Helper_Abstract $helper)
    {
        self::getStack()->push($helper);
        return;
    }

    /**
     * resetHelpers()
     *
     * @return void
     */
    static public function resetHelpers()
    {
        self::$_stack = null;
        return;
    }

    /**
     * Retrieve or initialize a helper statically
     *
     * Retrieves a helper object statically, loading on-demand if the helper
     * does not already exist in the stack. Always returns a helper, unless
     * the helper class cannot be found.
     *
     * @param  string $name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public static function getStaticHelper($name)
    {
        $name  = self::_normalizeHelperName($name);
        $stack = self::getStack();

        if (!isset($stack->{$name})) {
            self::_loadHelper($name);
        }

        return $stack->{$name};
    }

    /**
     * getExistingHelper() - get helper by name
     *
     * Static method to retrieve helper object. Only retrieves helpers already
     * initialized with the broker (either via addHelper() or on-demand loading
     * via getHelper()).
     *
     * Throws an exception if the referenced helper does not exist in the
     * stack; use {@link hasHelper()} to check if the helper is registered
     * prior to retrieving it.
     *
     * @param  string $name
     * @return Zend_Controller_Action_Helper_Abstract
     * @throws Zend_Controller_Action_Exception
     */
    public static function getExistingHelper($name)
    {
        $name  = self::_normalizeHelperName($name);
        $stack = self::getStack();

        if (!isset($stack->{$name})) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Action helper "' . $name . '" has not been registered with the helper broker');
        }

        return $stack->{$name};
    }

    /**
     * Return all registered helpers as helper => object pairs
     *
     * @return array
     */
    public static function getExistingHelpers()
    {
        return self::getStack()->getHelpersByName();
    }

    /**
     * Is a particular helper loaded in the broker?
     *
     * @param  string $name
     * @return boolean
     */
    public static function hasHelper($name)
    {
        $name = self::_normalizeHelperName($name);
        return isset(self::getStack()->{$name});
    }

    /**
     * Remove a particular helper from the broker
     *
     * @param  string $name
     * @return boolean
     */
    public static function removeHelper($name)
    {
        $name = self::_normalizeHelperName($name);
        $stack = self::getStack();
        if (isset($stack->{$name})) {
            unset($stack->{$name});
        }

        return false;
    }

    /**
     * Lazy load the priority stack and return it
     *
     * @return Zend_Controller_Action_HelperBroker_PriorityStack
     */
    public static function getStack()
    {
        if (self::$_stack == null) {
            self::$_stack = new Zend_Controller_Action_HelperBroker_PriorityStack();
        }

        return self::$_stack;
    }

    /**
     * Constructor
     *
     * @param Zend_Controller_Action $actionController
     * @return void
     */
    public function __construct(Zend_Controller_Action $actionController)
    {
        $this->_actionController = $actionController;
        foreach (self::getStack() as $helper) {
            $helper->setActionController($actionController);
            $helper->init();
        }
    }

    /**
     * notifyPreDispatch() - called by action controller dispatch method
     *
     * @return void
     */
    public function notifyPreDispatch()
    {
        foreach (self::getStack() as $helper) {
            $helper->preDispatch();
        }
    }

    /**
     * notifyPostDispatch() - called by action controller dispatch method
     *
     * @return void
     */
    public function notifyPostDispatch()
    {
        foreach (self::getStack() as $helper) {
            $helper->postDispatch();
        }
    }

    /**
     * getHelper() - get helper by name
     *
     * @param  string $name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function getHelper($name)
    {
        $name  = self::_normalizeHelperName($name);
        $stack = self::getStack();

        if (!isset($stack->{$name})) {
            self::_loadHelper($name);
        }

        $helper = $stack->{$name};

        $initialize = false;
        if (null === ($actionController = $helper->getActionController())) {
            $initialize = true;
        } elseif ($actionController !== $this->_actionController) {
            $initialize = true;
        }

        if ($initialize) {
            $helper->setActionController($this->_actionController)
                   ->init();
        }

        return $helper;
    }

    /**
     * Method overloading
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     * @throws Zend_Controller_Action_Exception if helper does not have a direct() method
     */
    public function __call($method, $args)
    {
        $helper = $this->getHelper($method);
        if (!method_exists($helper, 'direct')) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Helper "' . $method . '" does not support overloading via direct()');
        }
        return call_user_func_array(array($helper, 'direct'), $args);
    }

    /**
     * Retrieve helper by name as object property
     *
     * @param  string $name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function __get($name)
    {
        return $this->getHelper($name);
    }

    /**
     * Normalize helper name for lookups
     *
     * @param  string $name
     * @return string
     */
    protected static function _normalizeHelperName($name)
    {
        if (strpos($name, '_') !== false) {
            $name = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        }

        return ucfirst($name);
    }

    /**
     * Load a helper
     *
     * @param  string $name
     * @return void
     */
    protected static function _loadHelper($name)
    {
        try {
            $class = self::getPluginLoader()->load($name);
        } catch (Zend_Loader_PluginLoader_Exception $e) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Action Helper by name ' . $name . ' not found', 0, $e);
        }

        $helper = new $class();

        if (!$helper instanceof Zend_Controller_Action_Helper_Abstract) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Helper name ' . $name . ' -> class ' . $class . ' is not of type Zend_Controller_Action_Helper_Abstract');
        }

        self::getStack()->push($helper);
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: PriorityStack.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Action_HelperBroker_PriorityStack implements IteratorAggregate, ArrayAccess, Countable
{

    protected $_helpersByPriority = array();
    protected $_helpersByNameRef  = array();
    protected $_nextDefaultPriority = 1;

    /**
     * Magic property overloading for returning helper by name
     *
     * @param string $helperName    The helper name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function __get($helperName)
    {
        if (!array_key_exists($helperName, $this->_helpersByNameRef)) {
            return false;
        }

        return $this->_helpersByNameRef[$helperName];
    }

    /**
     * Magic property overloading for returning if helper is set by name
     *
     * @param string $helperName    The helper name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function __isset($helperName)
    {
        return array_key_exists($helperName, $this->_helpersByNameRef);
    }

    /**
     * Magic property overloading for unsetting if helper is exists by name
     *
     * @param string $helperName    The helper name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function __unset($helperName)
    {
        return $this->offsetUnset($helperName);
    }

    /**
     * push helper onto the stack
     *
     * @param Zend_Controller_Action_Helper_Abstract $helper
     * @return Zend_Controller_Action_HelperBroker_PriorityStack
     */
    public function push(Zend_Controller_Action_Helper_Abstract $helper)
    {
        $this->offsetSet($this->getNextFreeHigherPriority(), $helper);
        return $this;
    }

    /**
     * Return something iterable
     *
     * @return array
     */
    public function getIterator()
    {
        return new ArrayObject($this->_helpersByPriority);
    }

    /**
     * offsetExists()
     *
     * @param int|string $priorityOrHelperName
     * @return Zend_Controller_Action_HelperBroker_PriorityStack
     */
    public function offsetExists($priorityOrHelperName)
    {
        if (is_string($priorityOrHelperName)) {
            return array_key_exists($priorityOrHelperName, $this->_helpersByNameRef);
        } else {
            return array_key_exists($priorityOrHelperName, $this->_helpersByPriority);
        }
    }

    /**
     * offsetGet()
     *
     * @param int|string $priorityOrHelperName
     * @return Zend_Controller_Action_HelperBroker_PriorityStack
     */
    public function offsetGet($priorityOrHelperName)
    {
        if (!$this->offsetExists($priorityOrHelperName)) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('A helper with priority ' . $priorityOrHelperName . ' does not exist.');
        }

        if (is_string($priorityOrHelperName)) {
            return $this->_helpersByNameRef[$priorityOrHelperName];
        } else {
            return $this->_helpersByPriority[$priorityOrHelperName];
        }
    }

    /**
     * offsetSet()
     *
     * @param int $priority
     * @param Zend_Controller_Action_Helper_Abstract $helper
     * @return Zend_Controller_Action_HelperBroker_PriorityStack
     */
    public function offsetSet($priority, $helper)
    {
        $priority = (int) $priority;

        if (!$helper instanceof Zend_Controller_Action_Helper_Abstract) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('$helper must extend Zend_Controller_Action_Helper_Abstract.');
        }

        if (array_key_exists($helper->getName(), $this->_helpersByNameRef)) {
            // remove any object with the same name to retain BC compailitbility
            // @todo At ZF 2.0 time throw an exception here.
            $this->offsetUnset($helper->getName());
        }

        if (array_key_exists($priority, $this->_helpersByPriority)) {
            $priority = $this->getNextFreeHigherPriority($priority);  // ensures LIFO
            trigger_error("A helper with the same priority already exists, reassigning to $priority", E_USER_WARNING);
        }

        $this->_helpersByPriority[$priority] = $helper;
        $this->_helpersByNameRef[$helper->getName()] = $helper;

        if ($priority == ($nextFreeDefault = $this->getNextFreeHigherPriority($this->_nextDefaultPriority))) {
            $this->_nextDefaultPriority = $nextFreeDefault;
        }

        krsort($this->_helpersByPriority);  // always make sure priority and LIFO are both enforced
        return $this;
    }

    /**
     * offsetUnset()
     *
     * @param int|string $priorityOrHelperName Priority integer or the helper name
     * @return Zend_Controller_Action_HelperBroker_PriorityStack
     */
    public function offsetUnset($priorityOrHelperName)
    {
        if (!$this->offsetExists($priorityOrHelperName)) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('A helper with priority or name ' . $priorityOrHelperName . ' does not exist.');
        }

        if (is_string($priorityOrHelperName)) {
            $helperName = $priorityOrHelperName;
            $helper = $this->_helpersByNameRef[$helperName];
            $priority = array_search($helper, $this->_helpersByPriority, true);
        } else {
            $priority = $priorityOrHelperName;
            $helperName = $this->_helpersByPriority[$priorityOrHelperName]->getName();
        }

        unset($this->_helpersByNameRef[$helperName]);
        unset($this->_helpersByPriority[$priority]);
        return $this;
    }

    /**
     * return the count of helpers
     *
     * @return int
     */
    public function count()
    {
        return count($this->_helpersByPriority);
    }

    /**
     * Find the next free higher priority.  If an index is given, it will
     * find the next free highest priority after it.
     *
     * @param int $indexPriority OPTIONAL
     * @return int
     */
    public function getNextFreeHigherPriority($indexPriority = null)
    {
        if ($indexPriority == null) {
            $indexPriority = $this->_nextDefaultPriority;
        }

        $priorities = array_keys($this->_helpersByPriority);

        while (in_array($indexPriority, $priorities)) {
            $indexPriority++;
        }

        return $indexPriority;
    }

    /**
     * Find the next free lower priority.  If an index is given, it will
     * find the next free lower priority before it.
     *
     * @param int $indexPriority
     * @return int
     */
    public function getNextFreeLowerPriority($indexPriority = null)
    {
        if ($indexPriority == null) {
            $indexPriority = $this->_nextDefaultPriority;
        }

        $priorities = array_keys($this->_helpersByPriority);

        while (in_array($indexPriority, $priorities)) {
            $indexPriority--;
        }

        return $indexPriority;
    }

    /**
     * return the highest priority
     *
     * @return int
     */
    public function getHighestPriority()
    {
        return max(array_keys($this->_helpersByPriority));
    }

    /**
     * return the lowest priority
     *
     * @return int
     */
    public function getLowestPriority()
    {
        return min(array_keys($this->_helpersByPriority));
    }

    /**
     * return the helpers referenced by name
     *
     * @return array
     */
    public function getHelpersByName()
    {
        return $this->_helpersByNameRef;
    }

}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: ViewRenderer.php 24282 2011-07-28 18:59:26Z matthew $
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * @see Zend_View
 */
require_once 'Zend/View.php';

/**
 * View script integration
 *
 * Zend_Controller_Action_Helper_ViewRenderer provides transparent view
 * integration for action controllers. It allows you to create a view object
 * once, and populate it throughout all actions. Several global options may be
 * set:
 *
 * - noController: if set true, render() will not look for view scripts in
 *   subdirectories named after the controller
 * - viewSuffix: what view script filename suffix to use
 *
 * The helper autoinitializes the action controller view preDispatch(). It
 * determines the path to the class file, and then determines the view base
 * directory from there. It also uses the module name as a class prefix for
 * helpers and views such that if your module name is 'Search', it will set the
 * helper class prefix to 'Search_View_Helper' and the filter class prefix to ;
 * 'Search_View_Filter'.
 *
 * Usage:
 * <code>
 * // In your bootstrap:
 * Zend_Controller_Action_HelperBroker::addHelper(new Zend_Controller_Action_Helper_ViewRenderer());
 *
 * // In your action controller methods:
 * $viewHelper = $this->_helper->getHelper('view');
 *
 * // Don't use controller subdirectories
 * $viewHelper->setNoController(true);
 *
 * // Specify a different script to render:
 * $this->_helper->viewRenderer('form');
 *
 * </code>
 *
 * @uses       Zend_Controller_Action_Helper_Abstract
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Action_Helper_ViewRenderer extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Zend_View_Interface
     */
    public $view;

    /**
     * Word delimiters
     * @var array
     */
    protected $_delimiters;

    /**
     * @var Zend_Filter_Inflector
     */
    protected $_inflector;

    /**
     * Inflector target
     * @var string
     */
    protected $_inflectorTarget = '';

    /**
     * Current module directory
     * @var string
     */
    protected $_moduleDir = '';

    /**
     * Whether or not to autorender using controller name as subdirectory;
     * global setting (not reset at next invocation)
     * @var boolean
     */
    protected $_neverController = false;

    /**
     * Whether or not to autorender postDispatch; global setting (not reset at
     * next invocation)
     * @var boolean
     */
    protected $_neverRender     = false;

    /**
     * Whether or not to use a controller name as a subdirectory when rendering
     * @var boolean
     */
    protected $_noController    = false;

    /**
     * Whether or not to autorender postDispatch; per controller/action setting (reset
     * at next invocation)
     * @var boolean
     */
    protected $_noRender        = false;

    /**
     * Characters representing path delimiters in the controller
     * @var string|array
     */
    protected $_pathDelimiters;

    /**
     * Which named segment of the response to utilize
     * @var string
     */
    protected $_responseSegment = null;

    /**
     * Which action view script to render
     * @var string
     */
    protected $_scriptAction    = null;

    /**
     * View object basePath
     * @var string
     */
    protected $_viewBasePathSpec = ':moduleDir/views';

    /**
     * View script path specification string
     * @var string
     */
    protected $_viewScriptPathSpec = ':controller/:action.:suffix';

    /**
     * View script path specification string, minus controller segment
     * @var string
     */
    protected $_viewScriptPathNoControllerSpec = ':action.:suffix';

    /**
     * View script suffix
     * @var string
     */
    protected $_viewSuffix      = 'phtml';

    /**
     * Constructor
     *
     * Optionally set view object and options.
     *
     * @param  Zend_View_Interface $view
     * @param  array               $options
     * @return void
     */
    public function __construct(Zend_View_Interface $view = null, array $options = array())
    {
        if (null !== $view) {
            $this->setView($view);
        }

        if (!empty($options)) {
            $this->_setOptions($options);
        }
    }

    /**
     * Clone - also make sure the view is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        if (isset($this->view) && $this->view instanceof Zend_View_Interface) {
            $this->view = clone $this->view;

        }
    }

    /**
     * Set the view object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Get current module name
     *
     * @return string
     */
    public function getModule()
    {
        $request = $this->getRequest();
        $module  = $request->getModuleName();
        if (null === $module) {
            $module = $this->getFrontController()->getDispatcher()->getDefaultModule();
        }

        return $module;
    }

    /**
     * Get module directory
     *
     * @throws Zend_Controller_Action_Exception
     * @return string
     */
    public function getModuleDirectory()
    {
        $module    = $this->getModule();
        $moduleDir = $this->getFrontController()->getControllerDirectory($module);
        if ((null === $moduleDir) || is_array($moduleDir)) {
            /**
             * @see Zend_Controller_Action_Exception
             */
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('ViewRenderer cannot locate module directory for module "' . $module . '"');
        }
        $this->_moduleDir = dirname($moduleDir);
        return $this->_moduleDir;
    }

    /**
     * Get inflector
     *
     * @return Zend_Filter_Inflector
     */
    public function getInflector()
    {
        if (null === $this->_inflector) {
            /**
             * @see Zend_Filter_Inflector
             */
            require_once 'Zend/Filter/Inflector.php';
            /**
             * @see Zend_Filter_PregReplace
             */
            require_once 'Zend/Filter/PregReplace.php';
            /**
             * @see Zend_Filter_Word_UnderscoreToSeparator
             */
            require_once 'Zend/Filter/Word/UnderscoreToSeparator.php';
            $this->_inflector = new Zend_Filter_Inflector();
            $this->_inflector->setStaticRuleReference('moduleDir', $this->_moduleDir) // moduleDir must be specified before the less specific 'module'
                 ->addRules(array(
                     ':module'     => array('Word_CamelCaseToDash', 'StringToLower'),
                     ':controller' => array('Word_CamelCaseToDash', new Zend_Filter_Word_UnderscoreToSeparator('/'), 'StringToLower', new Zend_Filter_PregReplace('/\./', '-')),
                     ':action'     => array('Word_CamelCaseToDash', new Zend_Filter_PregReplace('#[^a-z0-9' . preg_quote('/', '#') . ']+#i', '-'), 'StringToLower'),
                 ))
                 ->setStaticRuleReference('suffix', $this->_viewSuffix)
                 ->setTargetReference($this->_inflectorTarget);
        }

        // Ensure that module directory is current
        $this->getModuleDirectory();

        return $this->_inflector;
    }

    /**
     * Set inflector
     *
     * @param  Zend_Filter_Inflector $inflector
     * @param  boolean               $reference Whether the moduleDir, target, and suffix should be set as references to ViewRenderer properties
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setInflector(Zend_Filter_Inflector $inflector, $reference = false)
    {
        $this->_inflector = $inflector;
        if ($reference) {
            $this->_inflector->setStaticRuleReference('suffix', $this->_viewSuffix)
                 ->setStaticRuleReference('moduleDir', $this->_moduleDir)
                 ->setTargetReference($this->_inflectorTarget);
        }
        return $this;
    }

    /**
     * Set inflector target
     *
     * @param  string $target
     * @return void
     */
    protected function _setInflectorTarget($target)
    {
        $this->_inflectorTarget = (string) $target;
    }

    /**
     * Set internal module directory representation
     *
     * @param  string $dir
     * @return void
     */
    protected function _setModuleDir($dir)
    {
        $this->_moduleDir = (string) $dir;
    }

    /**
     * Get internal module directory representation
     *
     * @return string
     */
    protected function _getModuleDir()
    {
        return $this->_moduleDir;
    }

    /**
     * Generate a class prefix for helper and filter classes
     *
     * @return string
     */
    protected function _generateDefaultPrefix()
    {
        $default = 'Zend_View';
        if (null === $this->_actionController) {
            return $default;
        }

        $class = get_class($this->_actionController);

        if (!strstr($class, '_')) {
            return $default;
        }

        $module = $this->getModule();
        if ('default' == $module) {
            return $default;
        }

        $prefix = substr($class, 0, strpos($class, '_')) . '_View';

        return $prefix;
    }

    /**
     * Retrieve base path based on location of current action controller
     *
     * @return string
     */
    protected function _getBasePath()
    {
        if (null === $this->_actionController) {
            return './views';
        }

        $inflector = $this->getInflector();
        $this->_setInflectorTarget($this->getViewBasePathSpec());

        $dispatcher = $this->getFrontController()->getDispatcher();
        $request = $this->getRequest();

        $parts = array(
            'module'     => (($moduleName = $request->getModuleName()) != '') ? $dispatcher->formatModuleName($moduleName) : $moduleName,
            'controller' => $request->getControllerName(),
            'action'     => $dispatcher->formatActionName($request->getActionName())
            );

        $path = $inflector->filter($parts);
        return $path;
    }

    /**
     * Set options
     *
     * @param  array $options
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    protected function _setOptions(array $options)
    {
        foreach ($options as $key => $value)
        {
            switch ($key) {
                case 'neverRender':
                case 'neverController':
                case 'noController':
                case 'noRender':
                    $property = '_' . $key;
                    $this->{$property} = ($value) ? true : false;
                    break;
                case 'responseSegment':
                case 'scriptAction':
                case 'viewBasePathSpec':
                case 'viewScriptPathSpec':
                case 'viewScriptPathNoControllerSpec':
                case 'viewSuffix':
                    $property = '_' . $key;
                    $this->{$property} = (string) $value;
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    /**
     * Initialize the view object
     *
     * $options may contain the following keys:
     * - neverRender - flag dis/enabling postDispatch() autorender (affects all subsequent calls)
     * - noController - flag indicating whether or not to look for view scripts in subdirectories named after the controller
     * - noRender - flag indicating whether or not to autorender postDispatch()
     * - responseSegment - which named response segment to render a view script to
     * - scriptAction - what action script to render
     * - viewBasePathSpec - specification to use for determining view base path
     * - viewScriptPathSpec - specification to use for determining view script paths
     * - viewScriptPathNoControllerSpec - specification to use for determining view script paths when noController flag is set
     * - viewSuffix - what view script filename suffix to use
     *
     * @param  string $path
     * @param  string $prefix
     * @param  array  $options
     * @throws Zend_Controller_Action_Exception
     * @return void
     */
    public function initView($path = null, $prefix = null, array $options = array())
    {
        if (null === $this->view) {
            $this->setView(new Zend_View());
        }

        // Reset some flags every time
        $options['noController'] = (isset($options['noController'])) ? $options['noController'] : false;
        $options['noRender']     = (isset($options['noRender'])) ? $options['noRender'] : false;
        $this->_scriptAction     = null;
        $this->_responseSegment  = null;

        // Set options first; may be used to determine other initializations
        $this->_setOptions($options);

        // Get base view path
        if (empty($path)) {
            $path = $this->_getBasePath();
            if (empty($path)) {
                /**
                 * @see Zend_Controller_Action_Exception
                 */
                require_once 'Zend/Controller/Action/Exception.php';
                throw new Zend_Controller_Action_Exception('ViewRenderer initialization failed: retrieved view base path is empty');
            }
        }

        if (null === $prefix) {
            $prefix = $this->_generateDefaultPrefix();
        }

        // Determine if this path has already been registered
        $currentPaths = $this->view->getScriptPaths();
        $path         = str_replace(array('/', '\\'), '/', $path);
        $pathExists   = false;
        foreach ($currentPaths as $tmpPath) {
            $tmpPath = str_replace(array('/', '\\'), '/', $tmpPath);
            if (strstr($tmpPath, $path)) {
                $pathExists = true;
                break;
            }
        }
        if (!$pathExists) {
            $this->view->addBasePath($path, $prefix);
        }

        // Register view with action controller (unless already registered)
        if ((null !== $this->_actionController) && (null === $this->_actionController->view)) {
            $this->_actionController->view       = $this->view;
            $this->_actionController->viewSuffix = $this->_viewSuffix;
        }
    }

    /**
     * init - initialize view
     *
     * @return void
     */
    public function init()
    {
        if ($this->getFrontController()->getParam('noViewRenderer')) {
            return;
        }

        $this->initView();
    }

    /**
     * Set view basePath specification
     *
     * Specification can contain one or more of the following:
     * - :moduleDir - current module directory
     * - :controller - name of current controller in the request
     * - :action - name of current action in the request
     * - :module - name of current module in the request
     *
     * @param  string $path
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setViewBasePathSpec($path)
    {
        $this->_viewBasePathSpec = (string) $path;
        return $this;
    }

    /**
     * Retrieve the current view basePath specification string
     *
     * @return string
     */
    public function getViewBasePathSpec()
    {
        return $this->_viewBasePathSpec;
    }

    /**
     * Set view script path specification
     *
     * Specification can contain one or more of the following:
     * - :moduleDir - current module directory
     * - :controller - name of current controller in the request
     * - :action - name of current action in the request
     * - :module - name of current module in the request
     *
     * @param  string $path
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setViewScriptPathSpec($path)
    {
        $this->_viewScriptPathSpec = (string) $path;
        return $this;
    }

    /**
     * Retrieve the current view script path specification string
     *
     * @return string
     */
    public function getViewScriptPathSpec()
    {
        return $this->_viewScriptPathSpec;
    }

    /**
     * Set view script path specification (no controller variant)
     *
     * Specification can contain one or more of the following:
     * - :moduleDir - current module directory
     * - :controller - name of current controller in the request
     * - :action - name of current action in the request
     * - :module - name of current module in the request
     *
     * :controller will likely be ignored in this variant.
     *
     * @param  string $path
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setViewScriptPathNoControllerSpec($path)
    {
        $this->_viewScriptPathNoControllerSpec = (string) $path;
        return $this;
    }

    /**
     * Retrieve the current view script path specification string (no controller variant)
     *
     * @return string
     */
    public function getViewScriptPathNoControllerSpec()
    {
        return $this->_viewScriptPathNoControllerSpec;
    }

    /**
     * Get a view script based on an action and/or other variables
     *
     * Uses values found in current request if no values passed in $vars.
     *
     * If {@link $_noController} is set, uses {@link $_viewScriptPathNoControllerSpec};
     * otherwise, uses {@link $_viewScriptPathSpec}.
     *
     * @param  string $action
     * @param  array  $vars
     * @return string
     */
    public function getViewScript($action = null, array $vars = array())
    {
        $request = $this->getRequest();
        if ((null === $action) && (!isset($vars['action']))) {
            $action = $this->getScriptAction();
            if (null === $action) {
                $action = $request->getActionName();
            }
            $vars['action'] = $action;
        } elseif (null !== $action) {
            $vars['action'] = $action;
        }
        
        $replacePattern = array('/[^a-z0-9]+$/i', '/^[^a-z0-9]+/i');
        $vars['action'] = preg_replace($replacePattern, '', $vars['action']);

        $inflector = $this->getInflector();
        if ($this->getNoController() || $this->getNeverController()) {
            $this->_setInflectorTarget($this->getViewScriptPathNoControllerSpec());
        } else {
            $this->_setInflectorTarget($this->getViewScriptPathSpec());
        }
        return $this->_translateSpec($vars);
    }

    /**
     * Set the neverRender flag (i.e., globally dis/enable autorendering)
     *
     * @param  boolean $flag
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setNeverRender($flag = true)
    {
        $this->_neverRender = ($flag) ? true : false;
        return $this;
    }

    /**
     * Retrieve neverRender flag value
     *
     * @return boolean
     */
    public function getNeverRender()
    {
        return $this->_neverRender;
    }

    /**
     * Set the noRender flag (i.e., whether or not to autorender)
     *
     * @param  boolean $flag
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setNoRender($flag = true)
    {
        $this->_noRender = ($flag) ? true : false;
        return $this;
    }

    /**
     * Retrieve noRender flag value
     *
     * @return boolean
     */
    public function getNoRender()
    {
        return $this->_noRender;
    }

    /**
     * Set the view script to use
     *
     * @param  string $name
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setScriptAction($name)
    {
        $this->_scriptAction = (string) $name;
        return $this;
    }

    /**
     * Retrieve view script name
     *
     * @return string
     */
    public function getScriptAction()
    {
        return $this->_scriptAction;
    }

    /**
     * Set the response segment name
     *
     * @param  string $name
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setResponseSegment($name)
    {
        if (null === $name) {
            $this->_responseSegment = null;
        } else {
            $this->_responseSegment = (string) $name;
        }

        return $this;
    }

    /**
     * Retrieve named response segment name
     *
     * @return string
     */
    public function getResponseSegment()
    {
        return $this->_responseSegment;
    }

    /**
     * Set the noController flag (i.e., whether or not to render into controller subdirectories)
     *
     * @param  boolean $flag
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setNoController($flag = true)
    {
        $this->_noController = ($flag) ? true : false;
        return $this;
    }

    /**
     * Retrieve noController flag value
     *
     * @return boolean
     */
    public function getNoController()
    {
        return $this->_noController;
    }

    /**
     * Set the neverController flag (i.e., whether or not to render into controller subdirectories)
     *
     * @param  boolean $flag
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setNeverController($flag = true)
    {
        $this->_neverController = ($flag) ? true : false;
        return $this;
    }

    /**
     * Retrieve neverController flag value
     *
     * @return boolean
     */
    public function getNeverController()
    {
        return $this->_neverController;
    }

    /**
     * Set view script suffix
     *
     * @param  string $suffix
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setViewSuffix($suffix)
    {
        $this->_viewSuffix = (string) $suffix;
        return $this;
    }

    /**
     * Get view script suffix
     *
     * @return string
     */
    public function getViewSuffix()
    {
        return $this->_viewSuffix;
    }

    /**
     * Set options for rendering a view script
     *
     * @param  string  $action       View script to render
     * @param  string  $name         Response named segment to render to
     * @param  boolean $noController Whether or not to render within a subdirectory named after the controller
     * @return Zend_Controller_Action_Helper_ViewRenderer Provides a fluent interface
     */
    public function setRender($action = null, $name = null, $noController = null)
    {
        if (null !== $action) {
            $this->setScriptAction($action);
        }

        if (null !== $name) {
            $this->setResponseSegment($name);
        }

        if (null !== $noController) {
            $this->setNoController($noController);
        }

        return $this;
    }

    /**
     * Inflect based on provided vars
     *
     * Allowed variables are:
     * - :moduleDir - current module directory
     * - :module - current module name
     * - :controller - current controller name
     * - :action - current action name
     * - :suffix - view script file suffix
     *
     * @param  array $vars
     * @return string
     */
    protected function _translateSpec(array $vars = array())
    {
        $inflector  = $this->getInflector();
        $request    = $this->getRequest();
        $dispatcher = $this->getFrontController()->getDispatcher();
        $module     = $dispatcher->formatModuleName($request->getModuleName());
        $controller = $request->getControllerName();
        $action     = $dispatcher->formatActionName($request->getActionName());

        $params     = compact('module', 'controller', 'action');
        foreach ($vars as $key => $value) {
            switch ($key) {
                case 'module':
                case 'controller':
                case 'action':
                case 'moduleDir':
                case 'suffix':
                    $params[$key] = (string) $value;
                    break;
                default:
                    break;
            }
        }

        if (isset($params['suffix'])) {
            $origSuffix = $this->getViewSuffix();
            $this->setViewSuffix($params['suffix']);
        }
        if (isset($params['moduleDir'])) {
            $origModuleDir = $this->_getModuleDir();
            $this->_setModuleDir($params['moduleDir']);
        }

        $filtered = $inflector->filter($params);

        if (isset($params['suffix'])) {
            $this->setViewSuffix($origSuffix);
        }
        if (isset($params['moduleDir'])) {
            $this->_setModuleDir($origModuleDir);
        }

        return $filtered;
    }

    /**
     * Render a view script (optionally to a named response segment)
     *
     * Sets the noRender flag to true when called.
     *
     * @param  string $script
     * @param  string $name
     * @return void
     */
    public function renderScript($script, $name = null)
    {
        if (null === $name) {
            $name = $this->getResponseSegment();
        }

        $this->getResponse()->appendBody(
            $this->view->render($script),
            $name
        );

        $this->setNoRender();
    }

    /**
     * Render a view based on path specifications
     *
     * Renders a view based on the view script path specifications.
     *
     * @param  string  $action
     * @param  string  $name
     * @param  boolean $noController
     * @return void
     */
    public function render($action = null, $name = null, $noController = null)
    {
        $this->setRender($action, $name, $noController);
        $path = $this->getViewScript();
        $this->renderScript($path, $name);
    }

    /**
     * Render a script based on specification variables
     *
     * Pass an action, and one or more specification variables (view script suffix)
     * to determine the view script path, and render that script.
     *
     * @param  string $action
     * @param  array  $vars
     * @param  string $name
     * @return void
     */
    public function renderBySpec($action = null, array $vars = array(), $name = null)
    {
        if (null !== $name) {
            $this->setResponseSegment($name);
        }

        $path = $this->getViewScript($action, $vars);

        $this->renderScript($path);
    }

    /**
     * postDispatch - auto render a view
     *
     * Only autorenders if:
     * - _noRender is false
     * - action controller is present
     * - request has not been re-dispatched (i.e., _forward() has not been called)
     * - response is not a redirect
     *
     * @return void
     */
    public function postDispatch()
    {
        if ($this->_shouldRender()) {
            $this->render();
        }
    }

    /**
     * Should the ViewRenderer render a view script?
     *
     * @return boolean
     */
    protected function _shouldRender()
    {
        return (!$this->getFrontController()->getParam('noViewRenderer')
            && !$this->_neverRender
            && !$this->_noRender
            && (null !== $this->_actionController)
            && $this->getRequest()->isDispatched()
            && !$this->getResponse()->isRedirect()
        );
    }

    /**
     * Use this helper as a method; proxies to setRender()
     *
     * @param  string  $action
     * @param  string  $name
     * @param  boolean $noController
     * @return void
     */
    public function direct($action = null, $name = null, $noController = null)
    {
        $this->setRender($action, $name, $noController);
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Controller_Action
 */
require_once 'Zend/Controller/Action.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Action_Helper_Abstract
{
    /**
     * $_actionController
     *
     * @var Zend_Controller_Action $_actionController
     */
    protected $_actionController = null;

    /**
     * @var mixed $_frontController
     */
    protected $_frontController = null;

    /**
     * setActionController()
     *
     * @param  Zend_Controller_Action $actionController
     * @return Zend_Controller_ActionHelper_Abstract Provides a fluent interface
     */
    public function setActionController(Zend_Controller_Action $actionController = null)
    {
        $this->_actionController = $actionController;
        return $this;
    }

    /**
     * Retrieve current action controller
     *
     * @return Zend_Controller_Action
     */
    public function getActionController()
    {
        return $this->_actionController;
    }

    /**
     * Retrieve front controller instance
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        return Zend_Controller_Front::getInstance();
    }

    /**
     * Hook into action controller initialization
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Hook into action controller preDispatch() workflow
     *
     * @return void
     */
    public function preDispatch()
    {
    }

    /**
     * Hook into action controller postDispatch() workflow
     *
     * @return void
     */
    public function postDispatch()
    {
    }

    /**
     * getRequest() -
     *
     * @return Zend_Controller_Request_Abstract $request
     */
    public function getRequest()
    {
        $controller = $this->getActionController();
        if (null === $controller) {
            $controller = $this->getFrontController();
        }

        return $controller->getRequest();
    }

    /**
     * getResponse() -
     *
     * @return Zend_Controller_Response_Abstract $response
     */
    public function getResponse()
    {
        $controller = $this->getActionController();
        if (null === $controller) {
            $controller = $this->getFrontController();
        }

        return $controller->getResponse();
    }

    /**
     * getName()
     *
     * @return string
     */
    public function getName()
    {
        $fullClassName = get_class($this);
        if (strpos($fullClassName, '_') !== false) {
            $helperName = strrchr($fullClassName, '_');
            return ltrim($helperName, '_');
        } elseif (strpos($fullClassName, '\\') !== false) {
            $helperName = strrchr($fullClassName, '\\');
            return ltrim($helperName, '\\');
        } else {
            return $fullClassName;
        }
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Action.php 24253 2011-07-22 00:15:05Z adamlundrigan $
 */

/**
 * @see Zend_Controller_Action_HelperBroker
 */
require_once 'Zend/Controller/Action/HelperBroker.php';

/**
 * @see Zend_Controller_Action_Interface
 */
require_once 'Zend/Controller/Action/Interface.php';

/**
 * @see Zend_Controller_Front
 */
require_once 'Zend/Controller/Front.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Action implements Zend_Controller_Action_Interface
{
    /**
     * @var array of existing class methods
     */
    protected $_classMethods;

    /**
     * Word delimiters (used for normalizing view script paths)
     * @var array
     */
    protected $_delimiters;

    /**
     * Array of arguments provided to the constructor, minus the
     * {@link $_request Request object}.
     * @var array
     */
    protected $_invokeArgs = array();

    /**
     * Front controller instance
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * Zend_Controller_Request_Abstract object wrapping the request environment
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request = null;

    /**
     * Zend_Controller_Response_Abstract object wrapping the response
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response = null;

    /**
     * View script suffix; defaults to 'phtml'
     * @see {render()}
     * @var string
     */
    public $viewSuffix = 'phtml';

    /**
     * View object
     * @var Zend_View_Interface
     */
    public $view;

    /**
     * Helper Broker to assist in routing help requests to the proper object
     *
     * @var Zend_Controller_Action_HelperBroker
     */
    protected $_helper = null;

    /**
     * Class constructor
     *
     * The request and response objects should be registered with the
     * controller, as should be any additional optional arguments; these will be
     * available via {@link getRequest()}, {@link getResponse()}, and
     * {@link getInvokeArgs()}, respectively.
     *
     * When overriding the constructor, please consider this usage as a best
     * practice and ensure that each is registered appropriately; the easiest
     * way to do so is to simply call parent::__construct($request, $response,
     * $invokeArgs).
     *
     * After the request, response, and invokeArgs are set, the
     * {@link $_helper helper broker} is initialized.
     *
     * Finally, {@link init()} is called as the final action of
     * instantiation, and may be safely overridden to perform initialization
     * tasks; as a general rule, override {@link init()} instead of the
     * constructor to customize an action controller's instantiation.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs Any additional invocation arguments
     * @return void
     */
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        $this->setRequest($request)
             ->setResponse($response)
             ->_setInvokeArgs($invokeArgs);
        $this->_helper = new Zend_Controller_Action_HelperBroker($this);
        $this->init();
    }

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Initialize View object
     *
     * Initializes {@link $view} if not otherwise a Zend_View_Interface.
     *
     * If {@link $view} is not otherwise set, instantiates a new Zend_View
     * object, using the 'views' subdirectory at the same level as the
     * controller directory for the current module as the base directory.
     * It uses this to set the following:
     * - script path = views/scripts/
     * - helper path = views/helpers/
     * - filter path = views/filters/
     *
     * @return Zend_View_Interface
     * @throws Zend_Controller_Exception if base view directory does not exist
     */
    public function initView()
    {
        if (!$this->getInvokeArg('noViewRenderer') && $this->_helper->hasHelper('viewRenderer')) {
            return $this->view;
        }

        require_once 'Zend/View/Interface.php';
        if (isset($this->view) && ($this->view instanceof Zend_View_Interface)) {
            return $this->view;
        }

        $request = $this->getRequest();
        $module  = $request->getModuleName();
        $dirs    = $this->getFrontController()->getControllerDirectory();
        if (empty($module) || !isset($dirs[$module])) {
            $module = $this->getFrontController()->getDispatcher()->getDefaultModule();
        }
        $baseDir = dirname($dirs[$module]) . DIRECTORY_SEPARATOR . 'views';
        if (!file_exists($baseDir) || !is_dir($baseDir)) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Missing base view directory ("' . $baseDir . '")');
        }

        require_once 'Zend/View.php';
        $this->view = new Zend_View(array('basePath' => $baseDir));

        return $this->view;
    }

    /**
     * Render a view
     *
     * Renders a view. By default, views are found in the view script path as
     * <controller>/<action>.phtml. You may change the script suffix by
     * resetting {@link $viewSuffix}. You may omit the controller directory
     * prefix by specifying boolean true for $noController.
     *
     * By default, the rendered contents are appended to the response. You may
     * specify the named body content segment to set by specifying a $name.
     *
     * @see Zend_Controller_Response_Abstract::appendBody()
     * @param  string|null $action Defaults to action registered in request object
     * @param  string|null $name Response object named path segment to use; defaults to null
     * @param  bool $noController  Defaults to false; i.e. use controller name as subdir in which to search for view script
     * @return void
     */
    public function render($action = null, $name = null, $noController = false)
    {
        if (!$this->getInvokeArg('noViewRenderer') && $this->_helper->hasHelper('viewRenderer')) {
            return $this->_helper->viewRenderer->render($action, $name, $noController);
        }

        $view   = $this->initView();
        $script = $this->getViewScript($action, $noController);

        $this->getResponse()->appendBody(
            $view->render($script),
            $name
        );
    }

    /**
     * Render a given view script
     *
     * Similar to {@link render()}, this method renders a view script. Unlike render(),
     * however, it does not autodetermine the view script via {@link getViewScript()},
     * but instead renders the script passed to it. Use this if you know the
     * exact view script name and path you wish to use, or if using paths that do not
     * conform to the spec defined with getViewScript().
     *
     * By default, the rendered contents are appended to the response. You may
     * specify the named body content segment to set by specifying a $name.
     *
     * @param  string $script
     * @param  string $name
     * @return void
     */
    public function renderScript($script, $name = null)
    {
        if (!$this->getInvokeArg('noViewRenderer') && $this->_helper->hasHelper('viewRenderer')) {
            return $this->_helper->viewRenderer->renderScript($script, $name);
        }

        $view = $this->initView();
        $this->getResponse()->appendBody(
            $view->render($script),
            $name
        );
    }

    /**
     * Construct view script path
     *
     * Used by render() to determine the path to the view script.
     *
     * @param  string $action Defaults to action registered in request object
     * @param  bool $noController  Defaults to false; i.e. use controller name as subdir in which to search for view script
     * @return string
     * @throws Zend_Controller_Exception with bad $action
     */
    public function getViewScript($action = null, $noController = null)
    {
        if (!$this->getInvokeArg('noViewRenderer') && $this->_helper->hasHelper('viewRenderer')) {
            $viewRenderer = $this->_helper->getHelper('viewRenderer');
            if (null !== $noController) {
                $viewRenderer->setNoController($noController);
            }
            return $viewRenderer->getViewScript($action);
        }

        $request = $this->getRequest();
        if (null === $action) {
            $action = $request->getActionName();
        } elseif (!is_string($action)) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Invalid action specifier for view render');
        }

        if (null === $this->_delimiters) {
            $dispatcher = Zend_Controller_Front::getInstance()->getDispatcher();
            $wordDelimiters = $dispatcher->getWordDelimiter();
            $pathDelimiters = $dispatcher->getPathDelimiter();
            $this->_delimiters = array_unique(array_merge($wordDelimiters, (array) $pathDelimiters));
        }

        $action = str_replace($this->_delimiters, '-', $action);
        $script = $action . '.' . $this->viewSuffix;

        if (!$noController) {
            $controller = $request->getControllerName();
            $controller = str_replace($this->_delimiters, '-', $controller);
            $script = $controller . DIRECTORY_SEPARATOR . $script;
        }

        return $script;
    }

    /**
     * Return the Request object
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set the Request object
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return Zend_Controller_Action
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Return the Response object
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Set the Response object
     *
     * @param Zend_Controller_Response_Abstract $response
     * @return Zend_Controller_Action
     */
    public function setResponse(Zend_Controller_Response_Abstract $response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Set invocation arguments
     *
     * @param array $args
     * @return Zend_Controller_Action
     */
    protected function _setInvokeArgs(array $args = array())
    {
        $this->_invokeArgs = $args;
        return $this;
    }

    /**
     * Return the array of constructor arguments (minus the Request object)
     *
     * @return array
     */
    public function getInvokeArgs()
    {
        return $this->_invokeArgs;
    }

    /**
     * Return a single invocation argument
     *
     * @param string $key
     * @return mixed
     */
    public function getInvokeArg($key)
    {
        if (isset($this->_invokeArgs[$key])) {
            return $this->_invokeArgs[$key];
        }

        return null;
    }

    /**
     * Get a helper by name
     *
     * @param  string $helperName
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function getHelper($helperName)
    {
        return $this->_helper->{$helperName};
    }

    /**
     * Get a clone of a helper by name
     *
     * @param  string $helperName
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function getHelperCopy($helperName)
    {
        return clone $this->_helper->{$helperName};
    }

    /**
     * Set the front controller instance
     *
     * @param Zend_Controller_Front $front
     * @return Zend_Controller_Action
     */
    public function setFrontController(Zend_Controller_Front $front)
    {
        $this->_frontController = $front;
        return $this;
    }

    /**
     * Retrieve Front Controller
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        // Used cache version if found
        if (null !== $this->_frontController) {
            return $this->_frontController;
        }

        // Grab singleton instance, if class has been loaded
        if (class_exists('Zend_Controller_Front')) {
            $this->_frontController = Zend_Controller_Front::getInstance();
            return $this->_frontController;
        }

        // Throw exception in all other cases
        require_once 'Zend/Controller/Exception.php';
        throw new Zend_Controller_Exception('Front controller class has not been loaded');
    }

    /**
     * Pre-dispatch routines
     *
     * Called before action method. If using class with
     * {@link Zend_Controller_Front}, it may modify the
     * {@link $_request Request object} and reset its dispatched flag in order
     * to skip processing the current action.
     *
     * @return void
     */
    public function preDispatch()
    {
    }

    /**
     * Post-dispatch routines
     *
     * Called after action method execution. If using class with
     * {@link Zend_Controller_Front}, it may modify the
     * {@link $_request Request object} and reset its dispatched flag in order
     * to process an additional action.
     *
     * Common usages for postDispatch() include rendering content in a sitewide
     * template, link url correction, setting headers, etc.
     *
     * @return void
     */
    public function postDispatch()
    {
    }

    /**
     * Proxy for undefined methods.  Default behavior is to throw an
     * exception on undefined methods, however this function can be
     * overridden to implement magic (dynamic) actions, or provide run-time
     * dispatching.
     *
     * @param  string $methodName
     * @param  array $args
     * @return void
     * @throws Zend_Controller_Action_Exception
     */
    public function __call($methodName, $args)
    {
        require_once 'Zend/Controller/Action/Exception.php';
        if ('Action' == substr($methodName, -6)) {
            $action = substr($methodName, 0, strlen($methodName) - 6);
            throw new Zend_Controller_Action_Exception(sprintf('Action "%s" does not exist and was not trapped in __call()', $action), 404);
        }

        throw new Zend_Controller_Action_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }

    /**
     * Dispatch the requested action
     *
     * @param string $action Method name of action
     * @return void
     */
    public function dispatch($action)
    {
        // Notify helpers of action preDispatch state
        $this->_helper->notifyPreDispatch();

        $this->preDispatch();
        if ($this->getRequest()->isDispatched()) {
            if (null === $this->_classMethods) {
                $this->_classMethods = get_class_methods($this);
            }

            // If pre-dispatch hooks introduced a redirect then stop dispatch
            // @see ZF-7496
            if (!($this->getResponse()->isRedirect())) {
                // preDispatch() didn't change the action, so we can continue
                if ($this->getInvokeArg('useCaseSensitiveActions') || in_array($action, $this->_classMethods)) {
                    if ($this->getInvokeArg('useCaseSensitiveActions')) {
                        trigger_error('Using case sensitive actions without word separators is deprecated; please do not rely on this "feature"');
                    }
                    $this->$action();
                } else {
                    $this->__call($action, array());
                }
            }
            $this->postDispatch();
        }

        // whats actually important here is that this action controller is
        // shutting down, regardless of dispatching; notify the helpers of this
        // state
        $this->_helper->notifyPostDispatch();
    }

    /**
     * Call the action specified in the request object, and return a response
     *
     * Not used in the Action Controller implementation, but left for usage in
     * Page Controller implementations. Dispatches a method based on the
     * request.
     *
     * Returns a Zend_Controller_Response_Abstract object, instantiating one
     * prior to execution if none exists in the controller.
     *
     * {@link preDispatch()} is called prior to the action,
     * {@link postDispatch()} is called following it.
     *
     * @param null|Zend_Controller_Request_Abstract $request Optional request
     * object to use
     * @param null|Zend_Controller_Response_Abstract $response Optional response
     * object to use
     * @return Zend_Controller_Response_Abstract
     */
    public function run(Zend_Controller_Request_Abstract $request = null, Zend_Controller_Response_Abstract $response = null)
    {
        if (null !== $request) {
            $this->setRequest($request);
        } else {
            $request = $this->getRequest();
        }

        if (null !== $response) {
            $this->setResponse($response);
        }

        $action = $request->getActionName();
        if (empty($action)) {
            $action = 'index';
        }
        $action = $action . 'Action';

        $request->setDispatched(true);
        $this->dispatch($action);

        return $this->getResponse();
    }

    /**
     * Gets a parameter from the {@link $_request Request object}.  If the
     * parameter does not exist, NULL will be returned.
     *
     * If the parameter does not exist and $default is set, then
     * $default will be returned instead of NULL.
     *
     * @param string $paramName
     * @param mixed $default
     * @return mixed
     */
    protected function _getParam($paramName, $default = null)
    {
        $value = $this->getRequest()->getParam($paramName);
         if ((null === $value || '' === $value) && (null !== $default)) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set a parameter in the {@link $_request Request object}.
     *
     * @param string $paramName
     * @param mixed $value
     * @return Zend_Controller_Action
     */
    protected function _setParam($paramName, $value)
    {
        $this->getRequest()->setParam($paramName, $value);

        return $this;
    }

    /**
     * Determine whether a given parameter exists in the
     * {@link $_request Request object}.
     *
     * @param string $paramName
     * @return boolean
     */
    protected function _hasParam($paramName)
    {
        return null !== $this->getRequest()->getParam($paramName);
    }

    /**
     * Return all parameters in the {@link $_request Request object}
     * as an associative array.
     *
     * @return array
     */
    protected function _getAllParams()
    {
        return $this->getRequest()->getParams();
    }


    /**
     * Forward to another controller/action.
     *
     * It is important to supply the unformatted names, i.e. "article"
     * rather than "ArticleController".  The dispatcher will do the
     * appropriate formatting when the request is received.
     *
     * If only an action name is provided, forwards to that action in this
     * controller.
     *
     * If an action and controller are specified, forwards to that action and
     * controller in this module.
     *
     * Specifying an action, controller, and module is the most specific way to
     * forward.
     *
     * A fourth argument, $params, will be used to set the request parameters.
     * If either the controller or module are unnecessary for forwarding,
     * simply pass null values for them before specifying the parameters.
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return void
     */
    final protected function _forward($action, $controller = null, $module = null, array $params = null)
    {
        $request = $this->getRequest();

        if (null !== $params) {
            $request->setParams($params);
        }

        if (null !== $controller) {
            $request->setControllerName($controller);

            // Module should only be reset if controller has been specified
            if (null !== $module) {
                $request->setModuleName($module);
            }
        }

        $request->setActionName($action)
                ->setDispatched(false);
    }

    /**
     * Redirect to another URL
     *
     * Proxies to {@link Zend_Controller_Action_Helper_Redirector::gotoUrl()}.
     *
     * @param string $url
     * @param array $options Options to be used when redirecting
     * @return void
     */
    protected function _redirect($url, array $options = array())
    {
        $this->_helper->redirector->gotoUrl($url, $options);
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Controller_Action_Interface
{
    /**
     * Class constructor
     *
     * The request and response objects should be registered with the
     * controller, as should be any additional optional arguments; these will be
     * available via {@link getRequest()}, {@link getResponse()}, and
     * {@link getInvokeArgs()}, respectively.
     *
     * When overriding the constructor, please consider this usage as a best
     * practice and ensure that each is registered appropriately; the easiest
     * way to do so is to simply call parent::__construct($request, $response,
     * $invokeArgs).
     *
     * After the request, response, and invokeArgs are set, the
     * {@link $_helper helper broker} is initialized.
     *
     * Finally, {@link init()} is called as the final action of
     * instantiation, and may be safely overridden to perform initialization
     * tasks; as a general rule, override {@link init()} instead of the
     * constructor to customize an action controller's instantiation.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs Any additional invocation arguments
     * @return void
     */
    public function __construct(Zend_Controller_Request_Abstract $request,
                                Zend_Controller_Response_Abstract $response,
                                array $invokeArgs = array());

    /**
     * Dispatch the requested action
     *
     * @param string $action Method name of action
     * @return void
     */
    public function dispatch($action);
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Front.php 23775 2011-03-01 17:25:24Z ralph $
 */


/** Zend_Loader */
require_once 'Zend/Loader.php';

/** Zend_Controller_Action_HelperBroker */
require_once 'Zend/Controller/Action/HelperBroker.php';

/** Zend_Controller_Plugin_Broker */
require_once 'Zend/Controller/Plugin/Broker.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Front
{
    /**
     * Base URL
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Directory|ies where controllers are stored
     *
     * @var string|array
     */
    protected $_controllerDir = null;

    /**
     * Instance of Zend_Controller_Dispatcher_Interface
     * @var Zend_Controller_Dispatcher_Interface
     */
    protected $_dispatcher = null;

    /**
     * Singleton instance
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var Zend_Controller_Front
     */
    protected static $_instance = null;

    /**
     * Array of invocation parameters to use when instantiating action
     * controllers
     * @var array
     */
    protected $_invokeParams = array();

    /**
     * Subdirectory within a module containing controllers; defaults to 'controllers'
     * @var string
     */
    protected $_moduleControllerDirectoryName = 'controllers';

    /**
     * Instance of Zend_Controller_Plugin_Broker
     * @var Zend_Controller_Plugin_Broker
     */
    protected $_plugins = null;

    /**
     * Instance of Zend_Controller_Request_Abstract
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request = null;

    /**
     * Instance of Zend_Controller_Response_Abstract
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response = null;

    /**
     * Whether or not to return the response prior to rendering output while in
     * {@link dispatch()}; default is to send headers and render output.
     * @var boolean
     */
    protected $_returnResponse = false;

    /**
     * Instance of Zend_Controller_Router_Interface
     * @var Zend_Controller_Router_Interface
     */
    protected $_router = null;

    /**
     * Whether or not exceptions encountered in {@link dispatch()} should be
     * thrown or trapped in the response object
     * @var boolean
     */
    protected $_throwExceptions = false;

    /**
     * Constructor
     *
     * Instantiate using {@link getInstance()}; front controller is a singleton
     * object.
     *
     * Instantiates the plugin broker.
     *
     * @return void
     */
    protected function __construct()
    {
        $this->_plugins = new Zend_Controller_Plugin_Broker();
    }

    /**
     * Enforce singleton; disallow cloning
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Zend_Controller_Front
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Resets all object properties of the singleton instance
     *
     * Primarily used for testing; could be used to chain front controllers.
     *
     * Also resets action helper broker, clearing all registered helpers.
     *
     * @return void
     */
    public function resetInstance()
    {
        $reflection = new ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            switch ($name) {
                case '_instance':
                    break;
                case '_controllerDir':
                case '_invokeParams':
                    $this->{$name} = array();
                    break;
                case '_plugins':
                    $this->{$name} = new Zend_Controller_Plugin_Broker();
                    break;
                case '_throwExceptions':
                case '_returnResponse':
                    $this->{$name} = false;
                    break;
                case '_moduleControllerDirectoryName':
                    $this->{$name} = 'controllers';
                    break;
                default:
                    $this->{$name} = null;
                    break;
            }
        }
        Zend_Controller_Action_HelperBroker::resetHelpers();
    }

    /**
     * Convenience feature, calls setControllerDirectory()->setRouter()->dispatch()
     *
     * In PHP 5.1.x, a call to a static method never populates $this -- so run()
     * may actually be called after setting up your front controller.
     *
     * @param string|array $controllerDirectory Path to Zend_Controller_Action
     * controller classes or array of such paths
     * @return void
     * @throws Zend_Controller_Exception if called from an object instance
     */
    public static function run($controllerDirectory)
    {
        self::getInstance()
            ->setControllerDirectory($controllerDirectory)
            ->dispatch();
    }

    /**
     * Add a controller directory to the controller directory stack
     *
     * If $args is presented and is a string, uses it for the array key mapping
     * to the directory specified.
     *
     * @param string $directory
     * @param string $module Optional argument; module with which to associate directory. If none provided, assumes 'default'
     * @return Zend_Controller_Front
     * @throws Zend_Controller_Exception if directory not found or readable
     */
    public function addControllerDirectory($directory, $module = null)
    {
        $this->getDispatcher()->addControllerDirectory($directory, $module);
        return $this;
    }

    /**
     * Set controller directory
     *
     * Stores controller directory(ies) in dispatcher. May be an array of
     * directories or a string containing a single directory.
     *
     * @param string|array $directory Path to Zend_Controller_Action controller
     * classes or array of such paths
     * @param  string $module Optional module name to use with string $directory
     * @return Zend_Controller_Front
     */
    public function setControllerDirectory($directory, $module = null)
    {
        $this->getDispatcher()->setControllerDirectory($directory, $module);
        return $this;
    }

    /**
     * Retrieve controller directory
     *
     * Retrieves:
     * - Array of all controller directories if no $name passed
     * - String path if $name passed and exists as a key in controller directory array
     * - null if $name passed but does not exist in controller directory keys
     *
     * @param  string $name Default null
     * @return array|string|null
     */
    public function getControllerDirectory($name = null)
    {
        return $this->getDispatcher()->getControllerDirectory($name);
    }

    /**
     * Remove a controller directory by module name
     *
     * @param  string $module
     * @return bool
     */
    public function removeControllerDirectory($module)
    {
        return $this->getDispatcher()->removeControllerDirectory($module);
    }

    /**
     * Specify a directory as containing modules
     *
     * Iterates through the directory, adding any subdirectories as modules;
     * the subdirectory within each module named after {@link $_moduleControllerDirectoryName}
     * will be used as the controller directory path.
     *
     * @param  string $path
     * @return Zend_Controller_Front
     */
    public function addModuleDirectory($path)
    {
        try{
            $dir = new DirectoryIterator($path);
        } catch(Exception $e) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception("Directory $path not readable", 0, $e);
        }
        foreach ($dir as $file) {
            if ($file->isDot() || !$file->isDir()) {
                continue;
            }

            $module    = $file->getFilename();

            // Don't use SCCS directories as modules
            if (preg_match('/^[^a-z]/i', $module) || ('CVS' == $module)) {
                continue;
            }

            $moduleDir = $file->getPathname() . DIRECTORY_SEPARATOR . $this->getModuleControllerDirectoryName();
            $this->addControllerDirectory($moduleDir, $module);
        }

        return $this;
    }

    /**
     * Return the path to a module directory (but not the controllers directory within)
     *
     * @param  string $module
     * @return string|null
     */
    public function getModuleDirectory($module = null)
    {
        if (null === $module) {
            $request = $this->getRequest();
            if (null !== $request) {
                $module = $this->getRequest()->getModuleName();
            }
            if (empty($module)) {
                $module = $this->getDispatcher()->getDefaultModule();
            }
        }

        $controllerDir = $this->getControllerDirectory($module);

        if ((null === $controllerDir) || !is_string($controllerDir)) {
            return null;
        }

        return dirname($controllerDir);
    }

    /**
     * Set the directory name within a module containing controllers
     *
     * @param  string $name
     * @return Zend_Controller_Front
     */
    public function setModuleControllerDirectoryName($name = 'controllers')
    {
        $this->_moduleControllerDirectoryName = (string) $name;

        return $this;
    }

    /**
     * Return the directory name within a module containing controllers
     *
     * @return string
     */
    public function getModuleControllerDirectoryName()
    {
        return $this->_moduleControllerDirectoryName;
    }

    /**
     * Set the default controller (unformatted string)
     *
     * @param string $controller
     * @return Zend_Controller_Front
     */
    public function setDefaultControllerName($controller)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->setDefaultControllerName($controller);
        return $this;
    }

    /**
     * Retrieve the default controller (unformatted string)
     *
     * @return string
     */
    public function getDefaultControllerName()
    {
        return $this->getDispatcher()->getDefaultControllerName();
    }

    /**
     * Set the default action (unformatted string)
     *
     * @param string $action
     * @return Zend_Controller_Front
     */
    public function setDefaultAction($action)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->setDefaultAction($action);
        return $this;
    }

    /**
     * Retrieve the default action (unformatted string)
     *
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->getDispatcher()->getDefaultAction();
    }

    /**
     * Set the default module name
     *
     * @param string $module
     * @return Zend_Controller_Front
     */
    public function setDefaultModule($module)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->setDefaultModule($module);
        return $this;
    }

    /**
     * Retrieve the default module
     *
     * @return string
     */
    public function getDefaultModule()
    {
        return $this->getDispatcher()->getDefaultModule();
    }

    /**
     * Set request class/object
     *
     * Set the request object.  The request holds the request environment.
     *
     * If a class name is provided, it will instantiate it
     *
     * @param string|Zend_Controller_Request_Abstract $request
     * @throws Zend_Controller_Exception if invalid request class
     * @return Zend_Controller_Front
     */
    public function setRequest($request)
    {
        if (is_string($request)) {
            if (!class_exists($request)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($request);
            }
            $request = new $request();
        }
        if (!$request instanceof Zend_Controller_Request_Abstract) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Invalid request class');
        }

        $this->_request = $request;

        return $this;
    }

    /**
     * Return the request object.
     *
     * @return null|Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set router class/object
     *
     * Set the router object.  The router is responsible for mapping
     * the request to a controller and action.
     *
     * If a class name is provided, instantiates router with any parameters
     * registered via {@link setParam()} or {@link setParams()}.
     *
     * @param string|Zend_Controller_Router_Interface $router
     * @throws Zend_Controller_Exception if invalid router class
     * @return Zend_Controller_Front
     */
    public function setRouter($router)
    {
        if (is_string($router)) {
            if (!class_exists($router)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($router);
            }
            $router = new $router();
        }

        if (!$router instanceof Zend_Controller_Router_Interface) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Invalid router class');
        }

        $router->setFrontController($this);
        $this->_router = $router;

        return $this;
    }

    /**
     * Return the router object.
     *
     * Instantiates a Zend_Controller_Router_Rewrite object if no router currently set.
     *
     * @return Zend_Controller_Router_Interface
     */
    public function getRouter()
    {
        if (null == $this->_router) {
            require_once 'Zend/Controller/Router/Rewrite.php';
            $this->setRouter(new Zend_Controller_Router_Rewrite());
        }

        return $this->_router;
    }

    /**
     * Set the base URL used for requests
     *
     * Use to set the base URL segment of the REQUEST_URI to use when
     * determining PATH_INFO, etc. Examples:
     * - /admin
     * - /myapp
     * - /subdir/index.php
     *
     * Note that the URL should not include the full URI. Do not use:
     * - http://example.com/admin
     * - http://example.com/myapp
     * - http://example.com/subdir/index.php
     *
     * If a null value is passed, this can be used as well for autodiscovery (default).
     *
     * @param string $base
     * @return Zend_Controller_Front
     * @throws Zend_Controller_Exception for non-string $base
     */
    public function setBaseUrl($base = null)
    {
        if (!is_string($base) && (null !== $base)) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Rewrite base must be a string');
        }

        $this->_baseUrl = $base;

        if ((null !== ($request = $this->getRequest())) && (method_exists($request, 'setBaseUrl'))) {
            $request->setBaseUrl($base);
        }

        return $this;
    }

    /**
     * Retrieve the currently set base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $request = $this->getRequest();
        if ((null !== $request) && method_exists($request, 'getBaseUrl')) {
            return $request->getBaseUrl();
        }

        return $this->_baseUrl;
    }

    /**
     * Set the dispatcher object.  The dispatcher is responsible for
     * taking a Zend_Controller_Dispatcher_Token object, instantiating the controller, and
     * call the action method of the controller.
     *
     * @param Zend_Controller_Dispatcher_Interface $dispatcher
     * @return Zend_Controller_Front
     */
    public function setDispatcher(Zend_Controller_Dispatcher_Interface $dispatcher)
    {
        $this->_dispatcher = $dispatcher;
        return $this;
    }

    /**
     * Return the dispatcher object.
     *
     * @return Zend_Controller_Dispatcher_Interface
     */
    public function getDispatcher()
    {
        /**
         * Instantiate the default dispatcher if one was not set.
         */
        if (!$this->_dispatcher instanceof Zend_Controller_Dispatcher_Interface) {
            require_once 'Zend/Controller/Dispatcher/Standard.php';
            $this->_dispatcher = new Zend_Controller_Dispatcher_Standard();
        }
        return $this->_dispatcher;
    }

    /**
     * Set response class/object
     *
     * Set the response object.  The response is a container for action
     * responses and headers. Usage is optional.
     *
     * If a class name is provided, instantiates a response object.
     *
     * @param string|Zend_Controller_Response_Abstract $response
     * @throws Zend_Controller_Exception if invalid response class
     * @return Zend_Controller_Front
     */
    public function setResponse($response)
    {
        if (is_string($response)) {
            if (!class_exists($response)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($response);
            }
            $response = new $response();
        }
        if (!$response instanceof Zend_Controller_Response_Abstract) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Invalid response class');
        }

        $this->_response = $response;

        return $this;
    }

    /**
     * Return the response object.
     *
     * @return null|Zend_Controller_Response_Abstract
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Add or modify a parameter to use when instantiating an action controller
     *
     * @param string $name
     * @param mixed $value
     * @return Zend_Controller_Front
     */
    public function setParam($name, $value)
    {
        $name = (string) $name;
        $this->_invokeParams[$name] = $value;
        return $this;
    }

    /**
     * Set parameters to pass to action controller constructors
     *
     * @param array $params
     * @return Zend_Controller_Front
     */
    public function setParams(array $params)
    {
        $this->_invokeParams = array_merge($this->_invokeParams, $params);
        return $this;
    }

    /**
     * Retrieve a single parameter from the controller parameter stack
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        if(isset($this->_invokeParams[$name])) {
            return $this->_invokeParams[$name];
        }

        return null;
    }

    /**
     * Retrieve action controller instantiation parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_invokeParams;
    }

    /**
     * Clear the controller parameter stack
     *
     * By default, clears all parameters. If a parameter name is given, clears
     * only that parameter; if an array of parameter names is provided, clears
     * each.
     *
     * @param null|string|array single key or array of keys for params to clear
     * @return Zend_Controller_Front
     */
    public function clearParams($name = null)
    {
        if (null === $name) {
            $this->_invokeParams = array();
        } elseif (is_string($name) && isset($this->_invokeParams[$name])) {
            unset($this->_invokeParams[$name]);
        } elseif (is_array($name)) {
            foreach ($name as $key) {
                if (is_string($key) && isset($this->_invokeParams[$key])) {
                    unset($this->_invokeParams[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Register a plugin.
     *
     * @param  Zend_Controller_Plugin_Abstract $plugin
     * @param  int $stackIndex Optional; stack index for plugin
     * @return Zend_Controller_Front
     */
    public function registerPlugin(Zend_Controller_Plugin_Abstract $plugin, $stackIndex = null)
    {
        $this->_plugins->registerPlugin($plugin, $stackIndex);
        return $this;
    }

    /**
     * Unregister a plugin.
     *
     * @param  string|Zend_Controller_Plugin_Abstract $plugin Plugin class or object to unregister
     * @return Zend_Controller_Front
     */
    public function unregisterPlugin($plugin)
    {
        $this->_plugins->unregisterPlugin($plugin);
        return $this;
    }

    /**
     * Is a particular plugin registered?
     *
     * @param  string $class
     * @return bool
     */
    public function hasPlugin($class)
    {
        return $this->_plugins->hasPlugin($class);
    }

    /**
     * Retrieve a plugin or plugins by class
     *
     * @param  string $class
     * @return false|Zend_Controller_Plugin_Abstract|array
     */
    public function getPlugin($class)
    {
        return $this->_plugins->getPlugin($class);
    }

    /**
     * Retrieve all plugins
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->_plugins->getPlugins();
    }

    /**
     * Set the throwExceptions flag and retrieve current status
     *
     * Set whether exceptions encounted in the dispatch loop should be thrown
     * or caught and trapped in the response object.
     *
     * Default behaviour is to trap them in the response object; call this
     * method to have them thrown.
     *
     * Passing no value will return the current value of the flag; passing a
     * boolean true or false value will set the flag and return the current
     * object instance.
     *
     * @param boolean $flag Defaults to null (return flag state)
     * @return boolean|Zend_Controller_Front Used as a setter, returns object; as a getter, returns boolean
     */
    public function throwExceptions($flag = null)
    {
        if ($flag !== null) {
            $this->_throwExceptions = (bool) $flag;
            return $this;
        }

        return $this->_throwExceptions;
    }

    /**
     * Set whether {@link dispatch()} should return the response without first
     * rendering output. By default, output is rendered and dispatch() returns
     * nothing.
     *
     * @param boolean $flag
     * @return boolean|Zend_Controller_Front Used as a setter, returns object; as a getter, returns boolean
     */
    public function returnResponse($flag = null)
    {
        if (true === $flag) {
            $this->_returnResponse = true;
            return $this;
        } elseif (false === $flag) {
            $this->_returnResponse = false;
            return $this;
        }

        return $this->_returnResponse;
    }

    /**
     * Dispatch an HTTP request to a controller/action.
     *
     * @param Zend_Controller_Request_Abstract|null $request
     * @param Zend_Controller_Response_Abstract|null $response
     * @return void|Zend_Controller_Response_Abstract Returns response object if returnResponse() is true
     */
    public function dispatch(Zend_Controller_Request_Abstract $request = null, Zend_Controller_Response_Abstract $response = null)
    {
        if (!$this->getParam('noErrorHandler') && !$this->_plugins->hasPlugin('Zend_Controller_Plugin_ErrorHandler')) {
            // Register with stack index of 100
            require_once 'Zend/Controller/Plugin/ErrorHandler.php';
            $this->_plugins->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(), 100);
        }

        if (!$this->getParam('noViewRenderer') && !Zend_Controller_Action_HelperBroker::hasHelper('viewRenderer')) {
            require_once 'Zend/Controller/Action/Helper/ViewRenderer.php';
            Zend_Controller_Action_HelperBroker::getStack()->offsetSet(-80, new Zend_Controller_Action_Helper_ViewRenderer());
        }

        /**
         * Instantiate default request object (HTTP version) if none provided
         */
        if (null !== $request) {
            $this->setRequest($request);
        } elseif ((null === $request) && (null === ($request = $this->getRequest()))) {
            require_once 'Zend/Controller/Request/Http.php';
            $request = new Zend_Controller_Request_Http();
            $this->setRequest($request);
        }

        /**
         * Set base URL of request object, if available
         */
        if (is_callable(array($this->_request, 'setBaseUrl'))) {
            if (null !== $this->_baseUrl) {
                $this->_request->setBaseUrl($this->_baseUrl);
            }
        }

        /**
         * Instantiate default response object (HTTP version) if none provided
         */
        if (null !== $response) {
            $this->setResponse($response);
        } elseif ((null === $this->_response) && (null === ($this->_response = $this->getResponse()))) {
            require_once 'Zend/Controller/Response/Http.php';
            $response = new Zend_Controller_Response_Http();
            $this->setResponse($response);
        }

        /**
         * Register request and response objects with plugin broker
         */
        $this->_plugins
             ->setRequest($this->_request)
             ->setResponse($this->_response);

        /**
         * Initialize router
         */
        $router = $this->getRouter();
        $router->setParams($this->getParams());

        /**
         * Initialize dispatcher
         */
        $dispatcher = $this->getDispatcher();
        $dispatcher->setParams($this->getParams())
                   ->setResponse($this->_response);

        // Begin dispatch
        try {
            /**
             * Route request to controller/action, if a router is provided
             */

            /**
            * Notify plugins of router startup
            */
            $this->_plugins->routeStartup($this->_request);

            try {
                $router->route($this->_request);
            }  catch (Exception $e) {
                if ($this->throwExceptions()) {
                    throw $e;
                }

                $this->_response->setException($e);
            }

            /**
            * Notify plugins of router completion
            */
            $this->_plugins->routeShutdown($this->_request);

            /**
             * Notify plugins of dispatch loop startup
             */
            $this->_plugins->dispatchLoopStartup($this->_request);

            /**
             *  Attempt to dispatch the controller/action. If the $this->_request
             *  indicates that it needs to be dispatched, move to the next
             *  action in the request.
             */
            do {
                $this->_request->setDispatched(true);

                /**
                 * Notify plugins of dispatch startup
                 */
                $this->_plugins->preDispatch($this->_request);

                /**
                 * Skip requested action if preDispatch() has reset it
                 */
                if (!$this->_request->isDispatched()) {
                    continue;
                }

                /**
                 * Dispatch request
                 */
                try {
                    $dispatcher->dispatch($this->_request, $this->_response);
                } catch (Exception $e) {
                    if ($this->throwExceptions()) {
                        throw $e;
                    }
                    $this->_response->setException($e);
                }

                /**
                 * Notify plugins of dispatch completion
                 */
                $this->_plugins->postDispatch($this->_request);
            } while (!$this->_request->isDispatched());
        } catch (Exception $e) {
            if ($this->throwExceptions()) {
                throw $e;
            }

            $this->_response->setException($e);
        }

        /**
         * Notify plugins of dispatch loop completion
         */
        try {
            $this->_plugins->dispatchLoopShutdown();
        } catch (Exception $e) {
            if ($this->throwExceptions()) {
                throw $e;
            }

            $this->_response->setException($e);
        }

        if ($this->returnResponse()) {
            return $this->_response;
        }

        $this->_response->sendResponse();
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Broker.php 24241 2011-07-14 08:09:41Z bate $
 */

/** Zend_Controller_Plugin_Abstract */
require_once 'Zend/Controller/Plugin/Abstract.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Plugin_Broker extends Zend_Controller_Plugin_Abstract
{

    /**
     * Array of instance of objects extending Zend_Controller_Plugin_Abstract
     *
     * @var array
     */
    protected $_plugins = array();


    /**
     * Register a plugin.
     *
     * @param  Zend_Controller_Plugin_Abstract $plugin
     * @param  int $stackIndex
     * @return Zend_Controller_Plugin_Broker
     */
    public function registerPlugin(Zend_Controller_Plugin_Abstract $plugin, $stackIndex = null)
    {
        if (false !== array_search($plugin, $this->_plugins, true)) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Plugin already registered');
        }

        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_plugins[$stackIndex])) {
                require_once 'Zend/Controller/Exception.php';
                throw new Zend_Controller_Exception('Plugin with stackIndex "' . $stackIndex . '" already registered');
            }
            $this->_plugins[$stackIndex] = $plugin;
        } else {
            $stackIndex = count($this->_plugins);
            while (isset($this->_plugins[$stackIndex])) {
                ++$stackIndex;
            }
            $this->_plugins[$stackIndex] = $plugin;
        }

        $request = $this->getRequest();
        if ($request) {
            $this->_plugins[$stackIndex]->setRequest($request);
        }
        $response = $this->getResponse();
        if ($response) {
            $this->_plugins[$stackIndex]->setResponse($response);
        }

        ksort($this->_plugins);

        return $this;
    }

    /**
     * Unregister a plugin.
     *
     * @param string|Zend_Controller_Plugin_Abstract $plugin Plugin object or class name
     * @return Zend_Controller_Plugin_Broker
     */
    public function unregisterPlugin($plugin)
    {
        if ($plugin instanceof Zend_Controller_Plugin_Abstract) {
            // Given a plugin object, find it in the array
            $key = array_search($plugin, $this->_plugins, true);
            if (false === $key) {
                require_once 'Zend/Controller/Exception.php';
                throw new Zend_Controller_Exception('Plugin never registered.');
            }
            unset($this->_plugins[$key]);
        } elseif (is_string($plugin)) {
            // Given a plugin class, find all plugins of that class and unset them
            foreach ($this->_plugins as $key => $_plugin) {
                $type = get_class($_plugin);
                if ($plugin == $type) {
                    unset($this->_plugins[$key]);
                }
            }
        }
        return $this;
    }

    /**
     * Is a plugin of a particular class registered?
     *
     * @param  string $class
     * @return bool
     */
    public function hasPlugin($class)
    {
        foreach ($this->_plugins as $plugin) {
            $type = get_class($plugin);
            if ($class == $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve a plugin or plugins by class
     *
     * @param  string $class Class name of plugin(s) desired
     * @return false|Zend_Controller_Plugin_Abstract|array Returns false if none found, plugin if only one found, and array of plugins if multiple plugins of same class found
     */
    public function getPlugin($class)
    {
        $found = array();
        foreach ($this->_plugins as $plugin) {
            $type = get_class($plugin);
            if ($class == $type) {
                $found[] = $plugin;
            }
        }

        switch (count($found)) {
            case 0:
                return false;
            case 1:
                return $found[0];
            default:
                return $found;
        }
    }

    /**
     * Retrieve all plugins
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->_plugins;
    }

    /**
     * Set request object, and register with each plugin
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return Zend_Controller_Plugin_Broker
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;

        foreach ($this->_plugins as $plugin) {
            $plugin->setRequest($request);
        }

        return $this;
    }

    /**
     * Get request object
     *
     * @return Zend_Controller_Request_Abstract $request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set response object
     *
     * @param Zend_Controller_Response_Abstract $response
     * @return Zend_Controller_Plugin_Broker
     */
    public function setResponse(Zend_Controller_Response_Abstract $response)
    {
        $this->_response = $response;

        foreach ($this->_plugins as $plugin) {
            $plugin->setResponse($response);
        }


        return $this;
    }

    /**
     * Get response object
     *
     * @return Zend_Controller_Response_Abstract $response
     */
    public function getResponse()
    {
        return $this->_response;
    }


    /**
     * Called before Zend_Controller_Front begins evaluating the
     * request against its routes.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->routeStartup($request);
            } catch (Exception $e) {
                if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                    throw new Zend_Controller_Exception($e->getMessage() . $e->getTraceAsString(), $e->getCode(), $e);
                } else {
                    $this->getResponse()->setException($e);
                }
            }
        }
    }


    /**
     * Called before Zend_Controller_Front exits its iterations over
     * the route set.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->routeShutdown($request);
            } catch (Exception $e) {
                if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                    throw new Zend_Controller_Exception($e->getMessage() . $e->getTraceAsString(), $e->getCode(), $e);
                } else {
                    $this->getResponse()->setException($e);
                }
            }
        }
    }


    /**
     * Called before Zend_Controller_Front enters its dispatch loop.
     *
     * During the dispatch loop, Zend_Controller_Front keeps a
     * Zend_Controller_Request_Abstract object, and uses
     * Zend_Controller_Dispatcher to dispatch the
     * Zend_Controller_Request_Abstract object to controllers/actions.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->dispatchLoopStartup($request);
            } catch (Exception $e) {
                if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                    throw new Zend_Controller_Exception($e->getMessage() . $e->getTraceAsString(), $e->getCode(), $e);
                } else {
                    $this->getResponse()->setException($e);
                }
            }
        }
    }


    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preDispatch($request);
            } catch (Exception $e) {
                if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                    throw new Zend_Controller_Exception($e->getMessage() . $e->getTraceAsString(), $e->getCode(), $e);
                } else {
                    $this->getResponse()->setException($e);
					// skip rendering of normal dispatch give the error handler a try
					$this->getRequest()->setDispatched(false);
                }
            }
        }
    }


    /**
     * Called after an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postDispatch($request);
            } catch (Exception $e) {
                if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                    throw new Zend_Controller_Exception($e->getMessage() . $e->getTraceAsString(), $e->getCode(), $e);
                } else {
                    $this->getResponse()->setException($e);
                }
            }
        }
    }


    /**
     * Called before Zend_Controller_Front exits its dispatch loop.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopShutdown()
    {
       foreach ($this->_plugins as $plugin) {
           try {
                $plugin->dispatchLoopShutdown();
            } catch (Exception $e) {
                if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                    throw new Zend_Controller_Exception($e->getMessage() . $e->getTraceAsString(), $e->getCode(), $e);
                } else {
                    $this->getResponse()->setException($e);
                }
            }
       }
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    /**
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response;

    /**
     * Set request object
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return Zend_Controller_Plugin_Abstract
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Get request object
     *
     * @return Zend_Controller_Request_Abstract $request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set response object
     *
     * @param Zend_Controller_Response_Abstract $response
     * @return Zend_Controller_Plugin_Abstract
     */
    public function setResponse(Zend_Controller_Response_Abstract $response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Get response object
     *
     * @return Zend_Controller_Response_Abstract $response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Called before Zend_Controller_Front begins evaluating the
     * request against its routes.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {}

    /**
     * Called after Zend_Controller_Router exits.
     *
     * Called after Zend_Controller_Front exits from the router.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {}

    /**
     * Called before Zend_Controller_Front enters its dispatch loop.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {}

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior.  By altering the
     * request and resetting its dispatched flag (via
     * {@link Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * the current action may be skipped.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {}

    /**
     * Called after an action is dispatched by Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the
     * request and resetting its dispatched flag (via
     * {@link Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * a new action may be specified for dispatching.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {}

    /**
     * Called before Zend_Controller_Front exits its dispatch loop.
     *
     * @return void
     */
    public function dispatchLoopShutdown()
    {}
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: HeadMeta.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Zend_Layout_View_Helper_HeadMeta
 *
 * @see        http://www.w3.org/TR/xhtml1/dtds.html
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_HeadMeta extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * Types of attributes
     * @var array
     */
    protected $_typeKeys     = array('name', 'http-equiv', 'charset', 'property');
    protected $_requiredKeys = array('content');
    protected $_modifierKeys = array('lang', 'scheme');

    /**
     * @var string registry key
     */
    protected $_regKey = 'Zend_View_Helper_HeadMeta';

    /**
     * Constructor
     *
     * Set separator to PHP_EOL
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSeparator(PHP_EOL);
    }

    /**
     * Retrieve object instance; optionally add meta tag
     *
     * @param  string $content
     * @param  string $keyValue
     * @param  string $keyType
     * @param  array $modifiers
     * @param  string $placement
     * @return Zend_View_Helper_HeadMeta
     */
    public function headMeta($content = null, $keyValue = null, $keyType = 'name', $modifiers = array(), $placement = Zend_View_Helper_Placeholder_Container_Abstract::APPEND)
    {
        if ((null !== $content) && (null !== $keyValue)) {
            $item   = $this->createData($keyType, $keyValue, $content, $modifiers);
            $action = strtolower($placement);
            switch ($action) {
                case 'append':
                case 'prepend':
                case 'set':
                    $this->$action($item);
                    break;
                default:
                    $this->append($item);
                    break;
            }
        }

        return $this;
    }

    protected function _normalizeType($type)
    {
        switch ($type) {
            case 'Name':
                return 'name';
            case 'HttpEquiv':
                return 'http-equiv';
            case 'Property':
                return 'property';
            default:
                require_once 'Zend/View/Exception.php';
                $e = new Zend_View_Exception(sprintf('Invalid type "%s" passed to _normalizeType', $type));
                $e->setView($this->view);
                throw $e;
        }
    }

    /**
     * Overload method access
     *
     * Allows the following 'virtual' methods:
     * - appendName($keyValue, $content, $modifiers = array())
     * - offsetGetName($index, $keyValue, $content, $modifers = array())
     * - prependName($keyValue, $content, $modifiers = array())
     * - setName($keyValue, $content, $modifiers = array())
     * - appendHttpEquiv($keyValue, $content, $modifiers = array())
     * - offsetGetHttpEquiv($index, $keyValue, $content, $modifers = array())
     * - prependHttpEquiv($keyValue, $content, $modifiers = array())
     * - setHttpEquiv($keyValue, $content, $modifiers = array())
     * - appendProperty($keyValue, $content, $modifiers = array())
     * - offsetGetProperty($index, $keyValue, $content, $modifiers = array())
     * - prependProperty($keyValue, $content, $modifiers = array())
     * - setProperty($keyValue, $content, $modifiers = array())
     *
     * @param  string $method
     * @param  array $args
     * @return Zend_View_Helper_HeadMeta
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(pre|ap)pend|offsetSet)(?P<type>Name|HttpEquiv|Property)$/', $method, $matches)) {
            $action = $matches['action'];
            $type   = $this->_normalizeType($matches['type']);
            $argc   = count($args);
            $index  = null;

            if ('offsetSet' == $action) {
                if (0 < $argc) {
                    $index = array_shift($args);
                    --$argc;
                }
            }

            if (2 > $argc) {
                require_once 'Zend/View/Exception.php';
                $e = new Zend_View_Exception('Too few arguments provided; requires key value, and content');
                $e->setView($this->view);
                throw $e;
            }

            if (3 > $argc) {
                $args[] = array();
            }

            $item  = $this->createData($type, $args[0], $args[1], $args[2]);

            if ('offsetSet' == $action) {
                return $this->offsetSet($index, $item);
            }

            $this->$action($item);
            return $this;
        }

        return parent::__call($method, $args);
    }

	/**
	 * Create an HTML5-style meta charset tag. Something like <meta charset="utf-8">
	 *
	 * Not valid in a non-HTML5 doctype
	 *
	 * @param string $charset
	 * @return Zend_View_Helper_HeadMeta Provides a fluent interface
	 */
    public function setCharset($charset)
    {
        $item = new stdClass;
        $item->type = 'charset';
        $item->charset = $charset;
        $item->content = null;
        $item->modifiers = array();
        $this->set($item);
        return $this;
    }

    /**
     * Determine if item is valid
     *
     * @param  mixed $item
     * @return boolean
     */
    protected function _isValid($item)
    {
        if ((!$item instanceof stdClass)
            || !isset($item->type)
            || !isset($item->modifiers))
        {
            return false;
        }

        if (!isset($item->content)
        && (! $this->view->doctype()->isHtml5()
        || (! $this->view->doctype()->isHtml5() && $item->type !== 'charset'))) {
            return false;
        }

        // <meta property= ... /> is only supported with doctype RDFa
        if (!$this->view->doctype()->isRdfa()
            && $item->type === 'property') {
            return false;
        }

        return true;
    }

    /**
     * Append
     *
     * @param  string $value
     * @return void
     * @throws Zend_View_Exception
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid value passed to append; please use appendMeta()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->append($value);
    }

    /**
     * OffsetSet
     *
     * @param  string|int $index
     * @param  string $value
     * @return void
     * @throws Zend_View_Exception
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e =  new Zend_View_Exception('Invalid value passed to offsetSet; please use offsetSetName() or offsetSetHttpEquiv()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->offsetSet($index, $value);
    }

    /**
     * OffsetUnset
     *
     * @param  string|int $index
     * @return void
     * @throws Zend_View_Exception
     */
    public function offsetUnset($index)
    {
        if (!in_array($index, $this->getContainer()->getKeys())) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid index passed to offsetUnset()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->offsetUnset($index);
    }

    /**
     * Prepend
     *
     * @param  string $value
     * @return void
     * @throws Zend_View_Exception
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid value passed to prepend; please use prependMeta()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->prepend($value);
    }

    /**
     * Set
     *
     * @param  string $value
     * @return void
     * @throws Zend_View_Exception
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid value passed to set; please use setMeta()');
            $e->setView($this->view);
            throw $e;
        }

        $container = $this->getContainer();
        foreach ($container->getArrayCopy() as $index => $item) {
            if ($item->type == $value->type && $item->{$item->type} == $value->{$value->type}) {
                $this->offsetUnset($index);
            }
        }

        return $this->append($value);
    }

    /**
     * Build meta HTML string
     *
     * @param  string $type
     * @param  string $typeValue
     * @param  string $content
     * @param  array $modifiers
     * @return string
     */
    public function itemToString(stdClass $item)
    {
        if (!in_array($item->type, $this->_typeKeys)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception(sprintf('Invalid type "%s" provided for meta', $item->type));
            $e->setView($this->view);
            throw $e;
        }
        $type = $item->type;

        $modifiersString = '';
        foreach ($item->modifiers as $key => $value) {
            if ($this->view->doctype()->isHtml5()
            && $key == 'scheme') {
                require_once 'Zend/View/Exception.php';
                throw new Zend_View_Exception('Invalid modifier '
                . '"scheme" provided; not supported by HTML5');
            }
            if (!in_array($key, $this->_modifierKeys)) {
                continue;
            }
            $modifiersString .= $key . '="' . $this->_escape($value) . '" ';
        }

        if ($this->view instanceof Zend_View_Abstract) {
            if ($this->view->doctype()->isHtml5()
            && $type == 'charset') {
                $tpl = ($this->view->doctype()->isXhtml())
                    ? '<meta %s="%s"/>'
                    : '<meta %s="%s">';
            } elseif ($this->view->doctype()->isXhtml()) {
                $tpl = '<meta %s="%s" content="%s" %s/>';
            } else {
                $tpl = '<meta %s="%s" content="%s" %s>';
            }
        } else {
            $tpl = '<meta %s="%s" content="%s" %s/>';
        }

        $meta = sprintf(
            $tpl,
            $type,
            $this->_escape($item->$type),
            $this->_escape($item->content),
            $modifiersString
        );
        return $meta;
    }

    /**
     * Render placeholder as string
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        $items = array();
        $this->getContainer()->ksort();
        try {
            foreach ($this as $item) {
                $items[] = $this->itemToString($item);
            }
        } catch (Zend_View_Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
        return $indent . implode($this->_escape($this->getSeparator()) . $indent, $items);
    }

    /**
     * Create data item for inserting into stack
     *
     * @param  string $type
     * @param  string $typeValue
     * @param  string $content
     * @param  array $modifiers
     * @return stdClass
     */
    public function createData($type, $typeValue, $content, array $modifiers)
    {
        $data            = new stdClass;
        $data->type      = $type;
        $data->$type     = $typeValue;
        $data->content   = $content;
        $data->modifiers = $modifiers;
        return $data;
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Standalone.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Registry */
require_once 'Zend/View/Helper/Placeholder/Registry.php';

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * Base class for targetted placeholder helpers
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_View_Helper_Placeholder_Container_Standalone extends Zend_View_Helper_Abstract implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var Zend_View_Helper_Placeholder_Container_Abstract
     */
    protected $_container;

    /**
     * @var Zend_View_Helper_Placeholder_Registry
     */
    protected $_registry;

    /**
     * Registry key under which container registers itself
     * @var string
     */
    protected $_regKey;

    /**
     * Flag wheter to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overriden
     * @var book
     */
    protected $_autoEscape = true;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->setRegistry(Zend_View_Helper_Placeholder_Registry::getRegistry());
        $this->setContainer($this->getRegistry()->getContainer($this->_regKey));
    }

    /**
     * Retrieve registry
     *
     * @return Zend_View_Helper_Placeholder_Registry
     */
    public function getRegistry()
    {
        return $this->_registry;
    }

    /**
     * Set registry object
     *
     * @param  Zend_View_Helper_Placeholder_Registry $registry
     * @return Zend_View_Helper_Placeholder_Container_Standalone
     */
    public function setRegistry(Zend_View_Helper_Placeholder_Registry $registry)
    {
        $this->_registry = $registry;
        return $this;
    }

    /**
     * Set whether or not auto escaping should be used
     *
     * @param  bool $autoEscape whether or not to auto escape output
     * @return Zend_View_Helper_Placeholder_Container_Standalone
     */
    public function setAutoEscape($autoEscape = true)
    {
        $this->_autoEscape = ($autoEscape) ? true : false;
        return $this;
    }

    /**
     * Return whether autoEscaping is enabled or disabled
     *
     * return bool
     */
    public function getAutoEscape()
    {
        return $this->_autoEscape;
    }

    /**
     * Escape a string
     *
     * @param  string $string
     * @return string
     */
    protected function _escape($string)
    {
        $enc = 'UTF-8';
        if ($this->view instanceof Zend_View_Interface
            && method_exists($this->view, 'getEncoding')
        ) {
            $enc = $this->view->getEncoding();
        }

        return htmlspecialchars((string) $string, ENT_COMPAT, $enc);
    }

    /**
     * Set container on which to operate
     *
     * @param  Zend_View_Helper_Placeholder_Container_Abstract $container
     * @return Zend_View_Helper_Placeholder_Container_Standalone
     */
    public function setContainer(Zend_View_Helper_Placeholder_Container_Abstract $container)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * Retrieve placeholder container
     *
     * @return Zend_View_Helper_Placeholder_Container_Abstract
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Overloading: set property value
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $container = $this->getContainer();
        $container[$key] = $value;
    }

    /**
     * Overloading: retrieve property
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        $container = $this->getContainer();
        if (isset($container[$key])) {
            return $container[$key];
        }

        return null;
    }

    /**
     * Overloading: check if property is set
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        $container = $this->getContainer();
        return isset($container[$key]);
    }

    /**
     * Overloading: unset property
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        $container = $this->getContainer();
        if (isset($container[$key])) {
            unset($container[$key]);
        }
    }

    /**
     * Overload
     *
     * Proxy to container methods
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $container = $this->getContainer();
        if (method_exists($container, $method)) {
            $return = call_user_func_array(array($container, $method), $args);
            if ($return === $container) {
                // If the container is returned, we really want the current object
                return $this;
            }
            return $return;
        }

        require_once 'Zend/View/Exception.php';
        $e = new Zend_View_Exception('Method "' . $method . '" does not exist');
        $e->setView($this->view);
        throw $e;
    }

    /**
     * String representation
     *
     * @return string
     */
    public function toString()
    {
        return $this->getContainer()->toString();
    }

    /**
     * Cast to string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Countable
     *
     * @return int
     */
    public function count()
    {
        $container = $this->getContainer();
        return count($container);
    }

    /**
     * ArrayAccess: offsetExists
     *
     * @param  string|int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->getContainer()->offsetExists($offset);
    }

    /**
     * ArrayAccess: offsetGet
     *
     * @param  string|int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getContainer()->offsetGet($offset);
    }

    /**
     * ArrayAccess: offsetSet
     *
     * @param  string|int $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * ArrayAccess: offsetUnset
     *
     * @param  string|int $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        return $this->getContainer()->offsetUnset($offset);
    }

    /**
     * IteratorAggregate: get Iterator
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return $this->getContainer()->getIterator();
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Registry.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Registry */
require_once 'Zend/Registry.php';

/** Zend_View_Helper_Placeholder_Container_Abstract */
require_once 'Zend/View/Helper/Placeholder/Container/Abstract.php';

/** Zend_View_Helper_Placeholder_Container */
require_once 'Zend/View/Helper/Placeholder/Container.php';

/**
 * Registry for placeholder containers
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Placeholder_Registry
{
    /**
     * Zend_Registry key under which placeholder registry exists
     * @const string
     */
    const REGISTRY_KEY = 'Zend_View_Helper_Placeholder_Registry';

    /**
     * Default container class
     * @var string
     */
    protected $_containerClass = 'Zend_View_Helper_Placeholder_Container';

    /**
     * Placeholder containers
     * @var array
     */
    protected $_items = array();

    /**
     * Retrieve or create registry instnace
     *
     * @return void
     */
    public static function getRegistry()
    {
        if (Zend_Registry::isRegistered(self::REGISTRY_KEY)) {
            $registry = Zend_Registry::get(self::REGISTRY_KEY);
        } else {
            $registry = new self();
            Zend_Registry::set(self::REGISTRY_KEY, $registry);
        }

        return $registry;
    }

    /**
     * createContainer
     *
     * @param  string $key
     * @param  array $value
     * @return Zend_View_Helper_Placeholder_Container_Abstract
     */
    public function createContainer($key, array $value = array())
    {
        $key = (string) $key;

        $this->_items[$key] = new $this->_containerClass($value);
        return $this->_items[$key];
    }

    /**
     * Retrieve a placeholder container
     *
     * @param  string $key
     * @return Zend_View_Helper_Placeholder_Container_Abstract
     */
    public function getContainer($key)
    {
        $key = (string) $key;
        if (isset($this->_items[$key])) {
            return $this->_items[$key];
        }

        $container = $this->createContainer($key);

        return $container;
    }

    /**
     * Does a particular container exist?
     *
     * @param  string $key
     * @return bool
     */
    public function containerExists($key)
    {
        $key = (string) $key;
        $return =  array_key_exists($key, $this->_items);
        return $return;
    }

    /**
     * Set the container for an item in the registry
     *
     * @param  string $key
     * @param  Zend_View_Placeholder_Container_Abstract $container
     * @return Zend_View_Placeholder_Registry
     */
    public function setContainer($key, Zend_View_Helper_Placeholder_Container_Abstract $container)
    {
        $key = (string) $key;
        $this->_items[$key] = $container;
        return $this;
    }

    /**
     * Delete a container
     *
     * @param  string $key
     * @return bool
     */
    public function deleteContainer($key)
    {
        $key = (string) $key;
        if (isset($this->_items[$key])) {
            unset($this->_items[$key]);
            return true;
        }

        return false;
    }

    /**
     * Set the container class to use
     *
     * @param  string $name
     * @return Zend_View_Helper_Placeholder_Registry
     */
    public function setContainerClass($name)
    {
        if (!class_exists($name)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($name);
        }

        $reflection = new ReflectionClass($name);
        if (!$reflection->isSubclassOf(new ReflectionClass('Zend_View_Helper_Placeholder_Container_Abstract'))) {
            require_once 'Zend/View/Helper/Placeholder/Registry/Exception.php';
            $e = new Zend_View_Helper_Placeholder_Registry_Exception('Invalid Container class specified');
            $e->setView($this->view);
            throw $e;
        }

        $this->_containerClass = $name;
        return $this;
    }

    /**
     * Retrieve the container class
     *
     * @return string
     */
    public function getContainerClass()
    {
        return $this->_containerClass;
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Abstract class representing container for placeholder values
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_View_Helper_Placeholder_Container_Abstract extends ArrayObject
{
    /**
     * Whether or not to override all contents of placeholder
     * @const string
     */
    const SET    = 'SET';

    /**
     * Whether or not to append contents to placeholder
     * @const string
     */
    const APPEND = 'APPEND';

    /**
     * Whether or not to prepend contents to placeholder
     * @const string
     */
    const PREPEND = 'PREPEND';

    /**
     * What text to prefix the placeholder with when rendering
     * @var string
     */
    protected $_prefix    = '';

    /**
     * What text to append the placeholder with when rendering
     * @var string
     */
    protected $_postfix   = '';

    /**
     * What string to use between individual items in the placeholder when rendering
     * @var string
     */
    protected $_separator = '';

    /**
     * What string to use as the indentation of output, this will typically be spaces. Eg: '    '
     * @var string
     */
    protected $_indent = '';

    /**
     * Whether or not we're already capturing for this given container
     * @var bool
     */
    protected $_captureLock = false;

    /**
     * What type of capture (overwrite (set), append, prepend) to use
     * @var string
     */
    protected $_captureType;

    /**
     * Key to which to capture content
     * @var string
     */
    protected $_captureKey;

    /**
     * Constructor - This is needed so that we can attach a class member as the ArrayObject container
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(array(), parent::ARRAY_AS_PROPS);
    }

    /**
     * Set a single value
     *
     * @param  mixed $value
     * @return void
     */
    public function set($value)
    {
        $this->exchangeArray(array($value));
    }

    /**
     * Prepend a value to the top of the container
     *
     * @param  mixed $value
     * @return void
     */
    public function prepend($value)
    {
        $values = $this->getArrayCopy();
        array_unshift($values, $value);
        $this->exchangeArray($values);
    }

    /**
     * Retrieve container value
     *
     * If single element registered, returns that element; otherwise,
     * serializes to array.
     *
     * @return mixed
     */
    public function getValue()
    {
        if (1 == count($this)) {
            $keys = $this->getKeys();
            $key  = array_shift($keys);
            return $this[$key];
        }

        return $this->getArrayCopy();
    }

    /**
     * Set prefix for __toString() serialization
     *
     * @param  string $prefix
     * @return Zend_View_Helper_Placeholder_Container
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = (string) $prefix;
        return $this;
    }

    /**
     * Retrieve prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Set postfix for __toString() serialization
     *
     * @param  string $postfix
     * @return Zend_View_Helper_Placeholder_Container
     */
    public function setPostfix($postfix)
    {
        $this->_postfix = (string) $postfix;
        return $this;
    }

    /**
     * Retrieve postfix
     *
     * @return string
     */
    public function getPostfix()
    {
        return $this->_postfix;
    }

    /**
     * Set separator for __toString() serialization
     *
     * Used to implode elements in container
     *
     * @param  string $separator
     * @return Zend_View_Helper_Placeholder_Container
     */
    public function setSeparator($separator)
    {
        $this->_separator = (string) $separator;
        return $this;
    }

    /**
     * Retrieve separator
     *
     * @return string
     */
    public function getSeparator()
    {
        return $this->_separator;
    }

    /**
     * Set the indentation string for __toString() serialization,
     * optionally, if a number is passed, it will be the number of spaces
     *
     * @param  string|int $indent
     * @return Zend_View_Helper_Placeholder_Container_Abstract
     */
    public function setIndent($indent)
    {
        $this->_indent = $this->getWhitespace($indent);
        return $this;
    }

    /**
     * Retrieve indentation
     *
     * @return string
     */
    public function getIndent()
    {
        return $this->_indent;
    }

    /**
     * Retrieve whitespace representation of $indent
     *
     * @param  int|string $indent
     * @return string
     */
    public function getWhitespace($indent)
    {
        if (is_int($indent)) {
            $indent = str_repeat(' ', $indent);
        }

        return (string) $indent;
    }

    /**
     * Start capturing content to push into placeholder
     *
     * @param  int $type How to capture content into placeholder; append, prepend, or set
     * @return void
     * @throws Zend_View_Helper_Placeholder_Exception if nested captures detected
     */
    public function captureStart($type = Zend_View_Helper_Placeholder_Container_Abstract::APPEND, $key = null)
    {
        if ($this->_captureLock) {
            require_once 'Zend/View/Helper/Placeholder/Container/Exception.php';
            $e = new Zend_View_Helper_Placeholder_Container_Exception('Cannot nest placeholder captures for the same placeholder');
            $e->setView($this->view);
            throw $e;
        }

        $this->_captureLock = true;
        $this->_captureType = $type;
        if ((null !== $key) && is_scalar($key)) {
            $this->_captureKey = (string) $key;
        }
        ob_start();
    }

    /**
     * End content capture
     *
     * @return void
     */
    public function captureEnd()
    {
        $data               = ob_get_clean();
        $key                = null;
        $this->_captureLock = false;
        if (null !== $this->_captureKey) {
            $key = $this->_captureKey;
        }
        switch ($this->_captureType) {
            case self::SET:
                if (null !== $key) {
                    $this[$key] = $data;
                } else {
                    $this->exchangeArray(array($data));
                }
                break;
            case self::PREPEND:
                if (null !== $key) {
                    $array  = array($key => $data);
                    $values = $this->getArrayCopy();
                    $final  = $array + $values;
                    $this->exchangeArray($final);
                } else {
                    $this->prepend($data);
                }
                break;
            case self::APPEND:
            default:
                if (null !== $key) {
                    if (empty($this[$key])) {
                        $this[$key] = $data;
                    } else {
                        $this[$key] .= $data;
                    }
                } else {
                    $this[$this->nextIndex()] = $data;
                }
                break;
        }
    }

    /**
     * Get keys
     *
     * @return array
     */
    public function getKeys()
    {
        $array = $this->getArrayCopy();
        return array_keys($array);
    }

    /**
     * Next Index
     *
     * as defined by the PHP manual
     * @return int
     */
    public function nextIndex()
    {
        $keys = $this->getKeys();
        if (0 == count($keys)) {
            return 0;
        }

        return $nextIndex = max($keys) + 1;
    }

    /**
     * Render the placeholder
     *
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = ($indent !== null)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        $items  = $this->getArrayCopy();
        $return = $indent
                . $this->getPrefix()
                . implode($this->getSeparator(), $items)
                . $this->getPostfix();
        $return = preg_replace("/(\r\n?|\n)/", '$1' . $indent, $return);
        return $return;
    }

    /**
     * Serialize object to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Container.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Abstract */
require_once 'Zend/View/Helper/Placeholder/Container/Abstract.php';

/**
 * Container for placeholder values
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Placeholder_Container extends Zend_View_Helper_Placeholder_Container_Abstract
{
}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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
/**
 *
 * User: cramen
 */
 
class Z_Version {

    public static $value = '20110827';

}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Standard.php 23775 2011-03-01 17:25:24Z ralph $
 */

/** Zend_Loader */
require_once 'Zend/Loader.php';

/** Zend_Controller_Dispatcher_Abstract */
require_once 'Zend/Controller/Dispatcher/Abstract.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Dispatcher_Standard extends Zend_Controller_Dispatcher_Abstract
{
    /**
     * Current dispatchable directory
     * @var string
     */
    protected $_curDirectory;

    /**
     * Current module (formatted)
     * @var string
     */
    protected $_curModule;

    /**
     * Controller directory(ies)
     * @var array
     */
    protected $_controllerDirectory = array();

    /**
     * Constructor: Set current module to default value
     *
     * @param  array $params
     * @return void
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
        $this->_curModule = $this->getDefaultModule();
    }

    /**
     * Add a single path to the controller directory stack
     *
     * @param string $path
     * @param string $module
     * @return Zend_Controller_Dispatcher_Standard
     */
    public function addControllerDirectory($path, $module = null)
    {
        if (null === $module) {
            $module = $this->_defaultModule;
        }

        $module = (string) $module;
        $path   = rtrim((string) $path, '/\\');

        $this->_controllerDirectory[$module] = $path;
        return $this;
    }

    /**
     * Set controller directory
     *
     * @param array|string $directory
     * @return Zend_Controller_Dispatcher_Standard
     */
    public function setControllerDirectory($directory, $module = null)
    {
        $this->_controllerDirectory = array();

        if (is_string($directory)) {
            $this->addControllerDirectory($directory, $module);
        } elseif (is_array($directory)) {
            foreach ((array) $directory as $module => $path) {
                $this->addControllerDirectory($path, $module);
            }
        } else {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Controller directory spec must be either a string or an array');
        }

        return $this;
    }

    /**
     * Return the currently set directories for Zend_Controller_Action class
     * lookup
     *
     * If a module is specified, returns just that directory.
     *
     * @param  string $module Module name
     * @return array|string Returns array of all directories by default, single
     * module directory if module argument provided
     */
    public function getControllerDirectory($module = null)
    {
        if (null === $module) {
            return $this->_controllerDirectory;
        }

        $module = (string) $module;
        if (array_key_exists($module, $this->_controllerDirectory)) {
            return $this->_controllerDirectory[$module];
        }

        return null;
    }

    /**
     * Remove a controller directory by module name
     *
     * @param  string $module
     * @return bool
     */
    public function removeControllerDirectory($module)
    {
        $module = (string) $module;
        if (array_key_exists($module, $this->_controllerDirectory)) {
            unset($this->_controllerDirectory[$module]);
            return true;
        }
        return false;
    }

    /**
     * Format the module name.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatModuleName($unformatted)
    {
        if (($this->_defaultModule == $unformatted) && !$this->getParam('prefixDefaultModule')) {
            return $unformatted;
        }

        return ucfirst($this->_formatName($unformatted));
    }

    /**
     * Format action class name
     *
     * @param string $moduleName Name of the current module
     * @param string $className Name of the action class
     * @return string Formatted class name
     */
    public function formatClassName($moduleName, $className)
    {
        return $this->formatModuleName($moduleName) . '_' . $className;
    }

    /**
     * Convert a class name to a filename
     *
     * @param string $class
     * @return string
     */
    public function classToFilename($class)
    {
        return str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
    }

    /**
     * Returns TRUE if the Zend_Controller_Request_Abstract object can be
     * dispatched to a controller.
     *
     * Use this method wisely. By default, the dispatcher will fall back to the
     * default controller (either in the module specified or the global default)
     * if a given controller does not exist. This method returning false does
     * not necessarily indicate the dispatcher will not still dispatch the call.
     *
     * @param Zend_Controller_Request_Abstract $action
     * @return boolean
     */
    public function isDispatchable(Zend_Controller_Request_Abstract $request)
    {
        $className = $this->getControllerClass($request);
        if (!$className) {
            return false;
        }

        $finalClass  = $className;
        if (($this->_defaultModule != $this->_curModule)
            || $this->getParam('prefixDefaultModule'))
        {
            $finalClass = $this->formatClassName($this->_curModule, $className);
        }
        if (class_exists($finalClass, false)) {
            return true;
        }

        $fileSpec    = $this->classToFilename($className);
        $dispatchDir = $this->getDispatchDirectory();
        $test        = $dispatchDir . DIRECTORY_SEPARATOR . $fileSpec;
        return Zend_Loader::isReadable($test);
    }

    /**
     * Dispatch to a controller/action
     *
     * By default, if a controller is not dispatchable, dispatch() will throw
     * an exception. If you wish to use the default controller instead, set the
     * param 'useDefaultControllerAlways' via {@link setParam()}.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @return void
     * @throws Zend_Controller_Dispatcher_Exception
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
        $this->setResponse($response);

        /**
         * Get controller class
         */
        if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new Zend_Controller_Dispatcher_Exception('Invalid controller specified (' . $request->getControllerName() . ')');
            }

            $className = $this->getDefaultControllerClass($request);
        } else {
            $className = $this->getControllerClass($request);
            if (!$className) {
                $className = $this->getDefaultControllerClass($request);
            }
        }

        /**
         * Load the controller class file
         */
        $className = $this->loadClass($className);

        /**
         * Instantiate controller with request, response, and invocation
         * arguments; throw exception if it's not an action controller
         */
        $controller = new $className($request, $this->getResponse(), $this->getParams());
        if (!($controller instanceof Zend_Controller_Action_Interface) &&
            !($controller instanceof Zend_Controller_Action)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception(
                'Controller "' . $className . '" is not an instance of Zend_Controller_Action_Interface'
            );
        }

        /**
         * Retrieve the action name
         */
        $action = $this->getActionMethod($request);

        /**
         * Dispatch the method call
         */
        $request->setDispatched(true);

        // by default, buffer output
        $disableOb = $this->getParam('disableOutputBuffering');
        $obLevel   = ob_get_level();
        if (empty($disableOb)) {
            ob_start();
        }

        try {
            $controller->dispatch($action);
        } catch (Exception $e) {
            // Clean output buffer on error
            $curObLevel = ob_get_level();
            if ($curObLevel > $obLevel) {
                do {
                    ob_get_clean();
                    $curObLevel = ob_get_level();
                } while ($curObLevel > $obLevel);
            }
            throw $e;
        }

        if (empty($disableOb)) {
            $content = ob_get_clean();
            $response->appendBody($content);
        }

        // Destroy the page controller instance and reflection objects
        $controller = null;
    }

    /**
     * Load a controller class
     *
     * Attempts to load the controller class file from
     * {@link getControllerDirectory()}.  If the controller belongs to a
     * module, looks for the module prefix to the controller class.
     *
     * @param string $className
     * @return string Class name loaded
     * @throws Zend_Controller_Dispatcher_Exception if class not loaded
     */
    public function loadClass($className)
    {
        $finalClass  = $className;
        if (($this->_defaultModule != $this->_curModule)
            || $this->getParam('prefixDefaultModule'))
        {
            $finalClass = $this->formatClassName($this->_curModule, $className);
        }
        if (class_exists($finalClass, false)) {
            return $finalClass;
        }

        $dispatchDir = $this->getDispatchDirectory();
        $loadFile    = $dispatchDir . DIRECTORY_SEPARATOR . $this->classToFilename($className);

        if (Zend_Loader::isReadable($loadFile)) {
            include_once $loadFile;
        } else {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception('Cannot load controller class "' . $className . '" from file "' . $loadFile . "'");
        }

        if (!class_exists($finalClass, false)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception('Invalid controller class ("' . $finalClass . '")');
        }

        return $finalClass;
    }

    /**
     * Get controller class name
     *
     * Try request first; if not found, try pulling from request parameter;
     * if still not found, fallback to default
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string|false Returns class name on success
     */
    public function getControllerClass(Zend_Controller_Request_Abstract $request)
    {
        $controllerName = $request->getControllerName();
        if (empty($controllerName)) {
            if (!$this->getParam('useDefaultControllerAlways')) {
                return false;
            }
            $controllerName = $this->getDefaultControllerName();
            $request->setControllerName($controllerName);
        }

        $className = $this->formatControllerName($controllerName);

        $controllerDirs      = $this->getControllerDirectory();
        $module = $request->getModuleName();
        if ($this->isValidModule($module)) {
            $this->_curModule    = $module;
            $this->_curDirectory = $controllerDirs[$module];
        } elseif ($this->isValidModule($this->_defaultModule)) {
            $request->setModuleName($this->_defaultModule);
            $this->_curModule    = $this->_defaultModule;
            $this->_curDirectory = $controllerDirs[$this->_defaultModule];
        } else {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('No default module defined for this application');
        }

        return $className;
    }

    /**
     * Determine if a given module is valid
     *
     * @param  string $module
     * @return bool
     */
    public function isValidModule($module)
    {
        if (!is_string($module)) {
            return false;
        }

        $module        = strtolower($module);
        $controllerDir = $this->getControllerDirectory();
        foreach (array_keys($controllerDir) as $moduleName) {
            if ($module == strtolower($moduleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve default controller class
     *
     * Determines whether the default controller to use lies within the
     * requested module, or if the global default should be used.
     *
     * By default, will only use the module default unless that controller does
     * not exist; if this is the case, it falls back to the default controller
     * in the default module.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string
     */
    public function getDefaultControllerClass(Zend_Controller_Request_Abstract $request)
    {
        $controller = $this->getDefaultControllerName();
        $default    = $this->formatControllerName($controller);
        $request->setControllerName($controller)
                ->setActionName(null);

        $module              = $request->getModuleName();
        $controllerDirs      = $this->getControllerDirectory();
        $this->_curModule    = $this->_defaultModule;
        $this->_curDirectory = $controllerDirs[$this->_defaultModule];
        if ($this->isValidModule($module)) {
            $found = false;
            if (class_exists($default, false)) {
                $found = true;
            } else {
                $moduleDir = $controllerDirs[$module];
                $fileSpec  = $moduleDir . DIRECTORY_SEPARATOR . $this->classToFilename($default);
                if (Zend_Loader::isReadable($fileSpec)) {
                    $found = true;
                    $this->_curDirectory = $moduleDir;
                }
            }
            if ($found) {
                $request->setModuleName($module);
                $this->_curModule    = $this->formatModuleName($module);
            }
        } else {
            $request->setModuleName($this->_defaultModule);
        }

        return $default;
    }

    /**
     * Return the value of the currently selected dispatch directory (as set by
     * {@link getController()})
     *
     * @return string
     */
    public function getDispatchDirectory()
    {
        return $this->_curDirectory;
    }

    /**
     * Determine the action name
     *
     * First attempt to retrieve from request; then from request params
     * using action key; default to default action
     *
     * Returns formatted action name
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string
     */
    public function getActionMethod(Zend_Controller_Request_Abstract $request)
    {
        $action = $request->getActionName();
        if (empty($action)) {
            $action = $this->getDefaultAction();
            $request->setActionName($action);
        }

        return $this->formatActionName($action);
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/** Zend_Controller_Dispatcher_Interface */
require_once 'Zend/Controller/Dispatcher/Interface.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Dispatcher_Abstract implements Zend_Controller_Dispatcher_Interface
{
    /**
     * Default action
     * @var string
     */
    protected $_defaultAction = 'index';

    /**
     * Default controller
     * @var string
     */
    protected $_defaultController = 'index';

    /**
     * Default module
     * @var string
     */
    protected $_defaultModule = 'default';

    /**
     * Front Controller instance
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * Array of invocation parameters to use when instantiating action
     * controllers
     * @var array
     */
    protected $_invokeParams = array();

    /**
     * Path delimiter character
     * @var string
     */
    protected $_pathDelimiter = '_';

    /**
     * Response object to pass to action controllers, if any
     * @var Zend_Controller_Response_Abstract|null
     */
    protected $_response = null;

    /**
     * Word delimiter characters
     * @var array
     */
    protected $_wordDelimiter = array('-', '.');

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    /**
     * Formats a string into a controller name.  This is used to take a raw
     * controller name, such as one stored inside a Zend_Controller_Request_Abstract
     * object, and reformat it to a proper class name that a class extending
     * Zend_Controller_Action would use.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatControllerName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted)) . 'Controller';
    }

    /**
     * Formats a string into an action name.  This is used to take a raw
     * action name, such as one that would be stored inside a Zend_Controller_Request_Abstract
     * object, and reformat into a proper method name that would be found
     * inside a class extending Zend_Controller_Action.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatActionName($unformatted)
    {
        $formatted = $this->_formatName($unformatted, true);
        return strtolower(substr($formatted, 0, 1)) . substr($formatted, 1) . 'Action';
    }

    /**
     * Verify delimiter
     *
     * Verify a delimiter to use in controllers or actions. May be a single
     * string or an array of strings.
     *
     * @param string|array $spec
     * @return array
     * @throws Zend_Controller_Dispatcher_Exception with invalid delimiters
     */
    public function _verifyDelimiter($spec)
    {
        if (is_string($spec)) {
            return (array) $spec;
        } elseif (is_array($spec)) {
            $allStrings = true;
            foreach ($spec as $delim) {
                if (!is_string($delim)) {
                    $allStrings = false;
                    break;
                }
            }

            if (!$allStrings) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new Zend_Controller_Dispatcher_Exception('Word delimiter array must contain only strings');
            }

            return $spec;
        }

        require_once 'Zend/Controller/Dispatcher/Exception.php';
        throw new Zend_Controller_Dispatcher_Exception('Invalid word delimiter');
    }

    /**
     * Retrieve the word delimiter character(s) used in
     * controller or action names
     *
     * @return array
     */
    public function getWordDelimiter()
    {
        return $this->_wordDelimiter;
    }

    /**
     * Set word delimiter
     *
     * Set the word delimiter to use in controllers and actions. May be a
     * single string or an array of strings.
     *
     * @param string|array $spec
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setWordDelimiter($spec)
    {
        $spec = $this->_verifyDelimiter($spec);
        $this->_wordDelimiter = $spec;

        return $this;
    }

    /**
     * Retrieve the path delimiter character(s) used in
     * controller names
     *
     * @return array
     */
    public function getPathDelimiter()
    {
        return $this->_pathDelimiter;
    }

    /**
     * Set path delimiter
     *
     * Set the path delimiter to use in controllers. May be a single string or
     * an array of strings.
     *
     * @param string $spec
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setPathDelimiter($spec)
    {
        if (!is_string($spec)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception('Invalid path delimiter');
        }
        $this->_pathDelimiter = $spec;

        return $this;
    }

    /**
     * Formats a string from a URI into a PHP-friendly name.
     *
     * By default, replaces words separated by the word separator character(s)
     * with camelCaps. If $isAction is false, it also preserves replaces words
     * separated by the path separation character with an underscore, making
     * the following word Title cased. All non-alphanumeric characters are
     * removed.
     *
     * @param string $unformatted
     * @param boolean $isAction Defaults to false
     * @return string
     */
    protected function _formatName($unformatted, $isAction = false)
    {
        // preserve directories
        if (!$isAction) {
            $segments = explode($this->getPathDelimiter(), $unformatted);
        } else {
            $segments = (array) $unformatted;
        }

        foreach ($segments as $key => $segment) {
            $segment        = str_replace($this->getWordDelimiter(), ' ', strtolower($segment));
            $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
            $segments[$key] = str_replace(' ', '', ucwords($segment));
        }

        return implode('_', $segments);
    }

    /**
     * Retrieve front controller instance
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        if (null === $this->_frontController) {
            require_once 'Zend/Controller/Front.php';
            $this->_frontController = Zend_Controller_Front::getInstance();
        }

        return $this->_frontController;
    }

    /**
     * Set front controller instance
     *
     * @param Zend_Controller_Front $controller
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setFrontController(Zend_Controller_Front $controller)
    {
        $this->_frontController = $controller;
        return $this;
    }

    /**
     * Add or modify a parameter to use when instantiating an action controller
     *
     * @param string $name
     * @param mixed $value
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setParam($name, $value)
    {
        $name = (string) $name;
        $this->_invokeParams[$name] = $value;
        return $this;
    }

    /**
     * Set parameters to pass to action controller constructors
     *
     * @param array $params
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setParams(array $params)
    {
        $this->_invokeParams = array_merge($this->_invokeParams, $params);
        return $this;
    }

    /**
     * Retrieve a single parameter from the controller parameter stack
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        if(isset($this->_invokeParams[$name])) {
            return $this->_invokeParams[$name];
        }

        return null;
    }

    /**
     * Retrieve action controller instantiation parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_invokeParams;
    }

    /**
     * Clear the controller parameter stack
     *
     * By default, clears all parameters. If a parameter name is given, clears
     * only that parameter; if an array of parameter names is provided, clears
     * each.
     *
     * @param null|string|array single key or array of keys for params to clear
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function clearParams($name = null)
    {
        if (null === $name) {
            $this->_invokeParams = array();
        } elseif (is_string($name) && isset($this->_invokeParams[$name])) {
            unset($this->_invokeParams[$name]);
        } elseif (is_array($name)) {
            foreach ($name as $key) {
                if (is_string($key) && isset($this->_invokeParams[$key])) {
                    unset($this->_invokeParams[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Set response object to pass to action controllers
     *
     * @param Zend_Controller_Response_Abstract|null $response
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setResponse(Zend_Controller_Response_Abstract $response = null)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Return the registered response object
     *
     * @return Zend_Controller_Response_Abstract|null
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Set the default controller (minus any formatting)
     *
     * @param string $controller
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setDefaultControllerName($controller)
    {
        $this->_defaultController = (string) $controller;
        return $this;
    }

    /**
     * Retrieve the default controller name (minus formatting)
     *
     * @return string
     */
    public function getDefaultControllerName()
    {
        return $this->_defaultController;
    }

    /**
     * Set the default action (minus any formatting)
     *
     * @param string $action
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setDefaultAction($action)
    {
        $this->_defaultAction = (string) $action;
        return $this;
    }

    /**
     * Retrieve the default action name (minus formatting)
     *
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->_defaultAction;
    }

    /**
     * Set the default module
     *
     * @param string $module
     * @return Zend_Controller_Dispatcher_Abstract
     */
    public function setDefaultModule($module)
    {
        $this->_defaultModule = (string) $module;
        return $this;
    }

    /**
     * Retrieve the default module
     *
     * @return string
     */
    public function getDefaultModule()
    {
        return $this->_defaultModule;
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Zend_Controller_Request_Abstract
 */
require_once 'Zend/Controller/Request/Abstract.php';

/**
 * Zend_Controller_Response_Abstract
 */
require_once 'Zend/Controller/Response/Abstract.php';

/**
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Controller_Dispatcher_Interface
{
    /**
     * Formats a string into a controller name.  This is used to take a raw
     * controller name, such as one that would be packaged inside a request
     * object, and reformat it to a proper class name that a class extending
     * Zend_Controller_Action would use.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatControllerName($unformatted);

    /**
     * Formats a string into a module name.  This is used to take a raw
     * module name, such as one that would be packaged inside a request
     * object, and reformat it to a proper directory/class name that a class extending
     * Zend_Controller_Action would use.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatModuleName($unformatted);

    /**
     * Formats a string into an action name.  This is used to take a raw
     * action name, such as one that would be packaged inside a request
     * object, and reformat into a proper method name that would be found
     * inside a class extending Zend_Controller_Action.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatActionName($unformatted);

    /**
     * Returns TRUE if an action can be dispatched, or FALSE otherwise.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return boolean
     */
    public function isDispatchable(Zend_Controller_Request_Abstract $request);

    /**
     * Add or modify a parameter with which to instantiate an Action Controller
     *
     * @param string $name
     * @param mixed $value
     * @return Zend_Controller_Dispatcher_Interface
     */
    public function setParam($name, $value);

    /**
     * Set an array of a parameters to pass to the Action Controller constructor
     *
     * @param array $params
     * @return Zend_Controller_Dispatcher_Interface
     */
    public function setParams(array $params);

    /**
     * Retrieve a single parameter from the controller parameter stack
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name);

    /**
     * Retrieve the parameters to pass to the Action Controller constructor
     *
     * @return array
     */
    public function getParams();

    /**
     * Clear the controller parameter stack
     *
     * By default, clears all parameters. If a parameter name is given, clears
     * only that parameter; if an array of parameter names is provided, clears
     * each.
     *
     * @param null|string|array single key or array of keys for params to clear
     * @return Zend_Controller_Dispatcher_Interface
     */
    public function clearParams($name = null);

    /**
     * Set the response object to use, if any
     *
     * @param Zend_Controller_Response_Abstract|null $response
     * @return void
     */
    public function setResponse(Zend_Controller_Response_Abstract $response = null);

    /**
     * Retrieve the response object, if any
     *
     * @return Zend_Controller_Response_Abstract|null
     */
    public function getResponse();

    /**
     * Add a controller directory to the controller directory stack
     *
     * @param string $path
     * @param string $args
     * @return Zend_Controller_Dispatcher_Interface
     */
    public function addControllerDirectory($path, $args = null);

    /**
     * Set the directory where controller files are stored
     *
     * Specify a string or an array; if an array is specified, all paths will be
     * added.
     *
     * @param string|array $dir
     * @return Zend_Controller_Dispatcher_Interface
     */
    public function setControllerDirectory($path);

    /**
     * Return the currently set directory(ies) for controller file lookup
     *
     * @return array
     */
    public function getControllerDirectory();

    /**
     * Dispatches a request object to a controller/action.  If the action
     * requests a forward to another action, a new request will be returned.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @param  Zend_Controller_Response_Abstract $response
     * @return void
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response);

    /**
     * Whether or not a given module is valid
     *
     * @param string $module
     * @return boolean
     */
    public function isValidModule($module);

    /**
     * Retrieve the default module name
     *
     * @return string
     */
    public function getDefaultModule();

    /**
     * Retrieve the default controller name
     *
     * @return string
     */
    public function getDefaultControllerName();

    /**
     * Retrieve the default action
     *
     * @return string
     */
    public function getDefaultAction();
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Request_Abstract
{
    /**
     * Has the action been dispatched?
     * @var boolean
     */
    protected $_dispatched = false;

    /**
     * Module
     * @var string
     */
    protected $_module;

    /**
     * Module key for retrieving module from params
     * @var string
     */
    protected $_moduleKey = 'module';

    /**
     * Controller
     * @var string
     */
    protected $_controller;

    /**
     * Controller key for retrieving controller from params
     * @var string
     */
    protected $_controllerKey = 'controller';

    /**
     * Action
     * @var string
     */
    protected $_action;

    /**
     * Action key for retrieving action from params
     * @var string
     */
    protected $_actionKey = 'action';

    /**
     * Request parameters
     * @var array
     */
    protected $_params = array();

    /**
     * Retrieve the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        if (null === $this->_module) {
            $this->_module = $this->getParam($this->getModuleKey());
        }

        return $this->_module;
    }

    /**
     * Set the module name to use
     *
     * @param string $value
     * @return Zend_Controller_Request_Abstract
     */
    public function setModuleName($value)
    {
        $this->_module = $value;
        return $this;
    }

    /**
     * Retrieve the controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        if (null === $this->_controller) {
            $this->_controller = $this->getParam($this->getControllerKey());
        }

        return $this->_controller;
    }

    /**
     * Set the controller name to use
     *
     * @param string $value
     * @return Zend_Controller_Request_Abstract
     */
    public function setControllerName($value)
    {
        $this->_controller = $value;
        return $this;
    }

    /**
     * Retrieve the action name
     *
     * @return string
     */
    public function getActionName()
    {
        if (null === $this->_action) {
            $this->_action = $this->getParam($this->getActionKey());
        }

        return $this->_action;
    }

    /**
     * Set the action name
     *
     * @param string $value
     * @return Zend_Controller_Request_Abstract
     */
    public function setActionName($value)
    {
        $this->_action = $value;
        /**
         * @see ZF-3465
         */
        if (null === $value) {
            $this->setParam($this->getActionKey(), $value);
        }
        return $this;
    }

    /**
     * Retrieve the module key
     *
     * @return string
     */
    public function getModuleKey()
    {
        return $this->_moduleKey;
    }

    /**
     * Set the module key
     *
     * @param string $key
     * @return Zend_Controller_Request_Abstract
     */
    public function setModuleKey($key)
    {
        $this->_moduleKey = (string) $key;
        return $this;
    }

    /**
     * Retrieve the controller key
     *
     * @return string
     */
    public function getControllerKey()
    {
        return $this->_controllerKey;
    }

    /**
     * Set the controller key
     *
     * @param string $key
     * @return Zend_Controller_Request_Abstract
     */
    public function setControllerKey($key)
    {
        $this->_controllerKey = (string) $key;
        return $this;
    }

    /**
     * Retrieve the action key
     *
     * @return string
     */
    public function getActionKey()
    {
        return $this->_actionKey;
    }

    /**
     * Set the action key
     *
     * @param string $key
     * @return Zend_Controller_Request_Abstract
     */
    public function setActionKey($key)
    {
        $this->_actionKey = (string) $key;
        return $this;
    }

    /**
     * Get an action parameter
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $key = (string) $key;
        if (isset($this->_params[$key])) {
            return $this->_params[$key];
        }

        return $default;
    }

    /**
     * Retrieve only user params (i.e, any param specific to the object and not the environment)
     *
     * @return array
     */
    public function getUserParams()
    {
        return $this->_params;
    }

    /**
     * Retrieve a single user param (i.e, a param specific to the object and not the environment)
     *
     * @param string $key
     * @param string $default Default value to use if key not found
     * @return mixed
     */
    public function getUserParam($key, $default = null)
    {
        if (isset($this->_params[$key])) {
            return $this->_params[$key];
        }

        return $default;
    }

    /**
     * Set an action parameter
     *
     * A $value of null will unset the $key if it exists
     *
     * @param string $key
     * @param mixed $value
     * @return Zend_Controller_Request_Abstract
     */
    public function setParam($key, $value)
    {
        $key = (string) $key;

        if ((null === $value) && isset($this->_params[$key])) {
            unset($this->_params[$key]);
        } elseif (null !== $value) {
            $this->_params[$key] = $value;
        }

        return $this;
    }

    /**
     * Get all action parameters
     *
     * @return array
     */
     public function getParams()
     {
         return $this->_params;
     }

    /**
     * Set action parameters en masse; does not overwrite
     *
     * Null values will unset the associated key.
     *
     * @param array $array
     * @return Zend_Controller_Request_Abstract
     */
    public function setParams(array $array)
    {
        $this->_params = $this->_params + (array) $array;

        foreach ($this->_params as $key => $value) {
            if (null === $value) {
                unset($this->_params[$key]);
            }
        }

        return $this;
    }

    /**
     * Unset all user parameters
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function clearParams()
    {
        $this->_params = array();
        return $this;
    }

    /**
     * Set flag indicating whether or not request has been dispatched
     *
     * @param boolean $flag
     * @return Zend_Controller_Request_Abstract
     */
    public function setDispatched($flag = true)
    {
        $this->_dispatched = $flag ? true : false;
        return $this;
    }

    /**
     * Determine if the request has been dispatched
     *
     * @return boolean
     */
    public function isDispatched()
    {
        return $this->_dispatched;
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23967 2011-05-03 14:31:55Z adamlundrigan $
 */

/**
 * Zend_Controller_Response_Abstract
 *
 * Base class for Zend_Controller responses
 *
 * @package Zend_Controller
 * @subpackage Response
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Response_Abstract
{
    /**
     * Body content
     * @var array
     */
    protected $_body = array();

    /**
     * Exception stack
     * @var Exception
     */
    protected $_exceptions = array();

    /**
     * Array of headers. Each header is an array with keys 'name' and 'value'
     * @var array
     */
    protected $_headers = array();

    /**
     * Array of raw headers. Each header is a single string, the entire header to emit
     * @var array
     */
    protected $_headersRaw = array();

    /**
     * HTTP response code to use in headers
     * @var int
     */
    protected $_httpResponseCode = 200;

    /**
     * Flag; is this response a redirect?
     * @var boolean
     */
    protected $_isRedirect = false;

    /**
     * Whether or not to render exceptions; off by default
     * @var boolean
     */
    protected $_renderExceptions = false;

    /**
     * Flag; if true, when header operations are called after headers have been
     * sent, an exception will be raised; otherwise, processing will continue
     * as normal. Defaults to true.
     *
     * @see canSendHeaders()
     * @var boolean
     */
    public $headersSentThrowsException = true;

    /**
     * Normalize a header name
     *
     * Normalizes a header name to X-Capitalized-Names
     *
     * @param  string $name
     * @return string
     */
    protected function _normalizeHeader($name)
    {
        $filtered = str_replace(array('-', '_'), ' ', (string) $name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);
        return $filtered;
    }

    /**
     * Set a header
     *
     * If $replace is true, replaces any headers already defined with that
     * $name.
     *
     * @param string $name
     * @param string $value
     * @param boolean $replace
     * @return Zend_Controller_Response_Abstract
     */
    public function setHeader($name, $value, $replace = false)
    {
        $this->canSendHeaders(true);
        $name  = $this->_normalizeHeader($name);
        $value = (string) $value;

        if ($replace) {
            foreach ($this->_headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->_headers[$key]);
                }
            }
        }

        $this->_headers[] = array(
            'name'    => $name,
            'value'   => $value,
            'replace' => $replace
        );

        return $this;
    }

    /**
     * Set redirect URL
     *
     * Sets Location header and response code. Forces replacement of any prior
     * redirects.
     *
     * @param string $url
     * @param int $code
     * @return Zend_Controller_Response_Abstract
     */
    public function setRedirect($url, $code = 302)
    {
        $this->canSendHeaders(true);
        $this->setHeader('Location', $url, true)
             ->setHttpResponseCode($code);

        return $this;
    }

    /**
     * Is this a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->_isRedirect;
    }

    /**
     * Return array of headers; see {@link $_headers} for format
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Clear headers
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function clearHeaders()
    {
        $this->_headers = array();

        return $this;
    }

    /**
     * Clears the specified HTTP header
     *
     * @param  string $name
     * @return Zend_Controller_Response_Abstract
     */
    public function clearHeader($name)
    {
        if (! count($this->_headers)) {
            return $this;
        }

        foreach ($this->_headers as $index => $header) {
            if ($name == $header['name']) {
                unset($this->_headers[$index]);
            }
        }

        return $this;
    }

    /**
     * Set raw HTTP header
     *
     * Allows setting non key => value headers, such as status codes
     *
     * @param string $value
     * @return Zend_Controller_Response_Abstract
     */
    public function setRawHeader($value)
    {
        $this->canSendHeaders(true);
        if ('Location' == substr($value, 0, 8)) {
            $this->_isRedirect = true;
        }
        $this->_headersRaw[] = (string) $value;
        return $this;
    }

    /**
     * Retrieve all {@link setRawHeader() raw HTTP headers}
     *
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->_headersRaw;
    }

    /**
     * Clear all {@link setRawHeader() raw HTTP headers}
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function clearRawHeaders()
    {
        $this->_headersRaw = array();
        return $this;
    }

    /**
     * Clears the specified raw HTTP header
     *
     * @param  string $headerRaw
     * @return Zend_Controller_Response_Abstract
     */
    public function clearRawHeader($headerRaw)
    {
        if (! count($this->_headersRaw)) {
            return $this;
        }

        $key = array_search($headerRaw, $this->_headersRaw);
        if ($key !== false) {
            unset($this->_headersRaw[$key]);
        }

        return $this;
    }

    /**
     * Clear all headers, normal and raw
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function clearAllHeaders()
    {
        return $this->clearHeaders()
                    ->clearRawHeaders();
    }

    /**
     * Set HTTP response code to use with headers
     *
     * @param int $code
     * @return Zend_Controller_Response_Abstract
     */
    public function setHttpResponseCode($code)
    {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Invalid HTTP response code');
        }

        if ((300 <= $code) && (307 >= $code)) {
            $this->_isRedirect = true;
        } else {
            $this->_isRedirect = false;
        }

        $this->_httpResponseCode = $code;
        return $this;
    }

    /**
     * Retrieve HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return $this->_httpResponseCode;
    }

    /**
     * Can we send headers?
     *
     * @param boolean $throw Whether or not to throw an exception if headers have been sent; defaults to false
     * @return boolean
     * @throws Zend_Controller_Response_Exception
     */
    public function canSendHeaders($throw = false)
    {
        $ok = headers_sent($file, $line);
        if ($ok && $throw && $this->headersSentThrowsException) {
            require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }

        return !$ok;
    }

    /**
     * Send all headers
     *
     * Sends any headers specified. If an {@link setHttpResponseCode() HTTP response code}
     * has been specified, it is sent with the first header.
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function sendHeaders()
    {
        // Only check if we can send headers if we have headers to send
        if (count($this->_headersRaw) || count($this->_headers) || (200 != $this->_httpResponseCode)) {
            $this->canSendHeaders(true);
        } elseif (200 == $this->_httpResponseCode) {
            // Haven't changed the response code, and we have no headers
            return $this;
        }

        $httpCodeSent = false;

        foreach ($this->_headersRaw as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header, true, $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header);
            }
        }

        foreach ($this->_headers as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }

        if (!$httpCodeSent) {
            header('HTTP/1.1 ' . $this->_httpResponseCode);
            $httpCodeSent = true;
        }

        return $this;
    }

    /**
     * Set body content
     *
     * If $name is not passed, or is not a string, resets the entire body and
     * sets the 'default' key to $content.
     *
     * If $name is a string, sets the named segment in the body array to
     * $content.
     *
     * @param string $content
     * @param null|string $name
     * @return Zend_Controller_Response_Abstract
     */
    public function setBody($content, $name = null)
    {
        if ((null === $name) || !is_string($name)) {
            $this->_body = array('default' => (string) $content);
        } else {
            $this->_body[$name] = (string) $content;
        }

        return $this;
    }

    /**
     * Append content to the body content
     *
     * @param string $content
     * @param null|string $name
     * @return Zend_Controller_Response_Abstract
     */
    public function appendBody($content, $name = null)
    {
        if ((null === $name) || !is_string($name)) {
            if (isset($this->_body['default'])) {
                $this->_body['default'] .= (string) $content;
            } else {
                return $this->append('default', $content);
            }
        } elseif (isset($this->_body[$name])) {
            $this->_body[$name] .= (string) $content;
        } else {
            return $this->append($name, $content);
        }

        return $this;
    }

    /**
     * Clear body array
     *
     * With no arguments, clears the entire body array. Given a $name, clears
     * just that named segment; if no segment matching $name exists, returns
     * false to indicate an error.
     *
     * @param  string $name Named segment to clear
     * @return boolean
     */
    public function clearBody($name = null)
    {
        if (null !== $name) {
            $name = (string) $name;
            if (isset($this->_body[$name])) {
                unset($this->_body[$name]);
                return true;
            }

            return false;
        }

        $this->_body = array();
        return true;
    }

    /**
     * Return the body content
     *
     * If $spec is false, returns the concatenated values of the body content
     * array. If $spec is boolean true, returns the body content array. If
     * $spec is a string and matches a named segment, returns the contents of
     * that segment; otherwise, returns null.
     *
     * @param boolean $spec
     * @return string|array|null
     */
    public function getBody($spec = false)
    {
        if (false === $spec) {
            ob_start();
            $this->outputBody();
            return ob_get_clean();
        } elseif (true === $spec) {
            return $this->_body;
        } elseif (is_string($spec) && isset($this->_body[$spec])) {
            return $this->_body[$spec];
        }

        return null;
    }

    /**
     * Append a named body segment to the body content array
     *
     * If segment already exists, replaces with $content and places at end of
     * array.
     *
     * @param string $name
     * @param string $content
     * @return Zend_Controller_Response_Abstract
     */
    public function append($name, $content)
    {
        if (!is_string($name)) {
            require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Invalid body segment key ("' . gettype($name) . '")');
        }

        if (isset($this->_body[$name])) {
            unset($this->_body[$name]);
        }
        $this->_body[$name] = (string) $content;
        return $this;
    }

    /**
     * Prepend a named body segment to the body content array
     *
     * If segment already exists, replaces with $content and places at top of
     * array.
     *
     * @param string $name
     * @param string $content
     * @return void
     */
    public function prepend($name, $content)
    {
        if (!is_string($name)) {
            require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Invalid body segment key ("' . gettype($name) . '")');
        }

        if (isset($this->_body[$name])) {
            unset($this->_body[$name]);
        }

        $new = array($name => (string) $content);
        $this->_body = $new + $this->_body;

        return $this;
    }

    /**
     * Insert a named segment into the body content array
     *
     * @param  string $name
     * @param  string $content
     * @param  string $parent
     * @param  boolean $before Whether to insert the new segment before or
     * after the parent. Defaults to false (after)
     * @return Zend_Controller_Response_Abstract
     */
    public function insert($name, $content, $parent = null, $before = false)
    {
        if (!is_string($name)) {
            require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Invalid body segment key ("' . gettype($name) . '")');
        }

        if ((null !== $parent) && !is_string($parent)) {
            require_once 'Zend/Controller/Response/Exception.php';
            throw new Zend_Controller_Response_Exception('Invalid body segment parent key ("' . gettype($parent) . '")');
        }

        if (isset($this->_body[$name])) {
            unset($this->_body[$name]);
        }

        if ((null === $parent) || !isset($this->_body[$parent])) {
            return $this->append($name, $content);
        }

        $ins  = array($name => (string) $content);
        $keys = array_keys($this->_body);
        $loc  = array_search($parent, $keys);
        if (!$before) {
            // Increment location if not inserting before
            ++$loc;
        }

        if (0 === $loc) {
            // If location of key is 0, we're prepending
            $this->_body = $ins + $this->_body;
        } elseif ($loc >= (count($this->_body))) {
            // If location of key is maximal, we're appending
            $this->_body = $this->_body + $ins;
        } else {
            // Otherwise, insert at location specified
            $pre  = array_slice($this->_body, 0, $loc);
            $post = array_slice($this->_body, $loc);
            $this->_body = $pre + $ins + $post;
        }

        return $this;
    }

    /**
     * Echo the body segments
     *
     * @return void
     */
    public function outputBody()
    {
        $body = implode('', $this->_body);
        echo $body;
    }

    /**
     * Register an exception with the response
     *
     * @param Exception $e
     * @return Zend_Controller_Response_Abstract
     */
    public function setException(Exception $e)
    {
        $this->_exceptions[] = $e;
        return $this;
    }

    /**
     * Retrieve the exception stack
     *
     * @return array
     */
    public function getException()
    {
        return $this->_exceptions;
    }

    /**
     * Has an exception been registered with the response?
     *
     * @return boolean
     */
    public function isException()
    {
        return !empty($this->_exceptions);
    }

    /**
     * Does the response object contain an exception of a given type?
     *
     * @param  string $type
     * @return boolean
     */
    public function hasExceptionOfType($type)
    {
        foreach ($this->_exceptions as $e) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the response object contain an exception with a given message?
     *
     * @param  string $message
     * @return boolean
     */
    public function hasExceptionOfMessage($message)
    {
        foreach ($this->_exceptions as $e) {
            if ($message == $e->getMessage()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the response object contain an exception with a given code?
     *
     * @param  int $code
     * @return boolean
     */
    public function hasExceptionOfCode($code)
    {
        $code = (int) $code;
        foreach ($this->_exceptions as $e) {
            if ($code == $e->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve all exceptions of a given type
     *
     * @param  string $type
     * @return false|array
     */
    public function getExceptionByType($type)
    {
        $exceptions = array();
        foreach ($this->_exceptions as $e) {
            if ($e instanceof $type) {
                $exceptions[] = $e;
            }
        }

        if (empty($exceptions)) {
            $exceptions = false;
        }

        return $exceptions;
    }

    /**
     * Retrieve all exceptions of a given message
     *
     * @param  string $message
     * @return false|array
     */
    public function getExceptionByMessage($message)
    {
        $exceptions = array();
        foreach ($this->_exceptions as $e) {
            if ($message == $e->getMessage()) {
                $exceptions[] = $e;
            }
        }

        if (empty($exceptions)) {
            $exceptions = false;
        }

        return $exceptions;
    }

    /**
     * Retrieve all exceptions of a given code
     *
     * @param mixed $code
     * @return void
     */
    public function getExceptionByCode($code)
    {
        $code       = (int) $code;
        $exceptions = array();
        foreach ($this->_exceptions as $e) {
            if ($code == $e->getCode()) {
                $exceptions[] = $e;
            }
        }

        if (empty($exceptions)) {
            $exceptions = false;
        }

        return $exceptions;
    }

    /**
     * Whether or not to render exceptions (off by default)
     *
     * If called with no arguments or a null argument, returns the value of the
     * flag; otherwise, sets it and returns the current value.
     *
     * @param boolean $flag Optional
     * @return boolean
     */
    public function renderExceptions($flag = null)
    {
        if (null !== $flag) {
            $this->_renderExceptions = $flag ? true : false;
        }

        return $this->_renderExceptions;
    }

    /**
     * Send the response, including all headers, rendering exceptions if so
     * requested.
     *
     * @return void
     */
    public function sendResponse()
    {
        $this->sendHeaders();

        if ($this->isException() && $this->renderExceptions()) {
            $exceptions = '';
            foreach ($this->getException() as $e) {
                $exceptions .= $e->__toString() . "\n";
            }
            echo $exceptions;
            return;
        }

        $this->outputBody();
    }

    /**
     * Magic __toString functionality
     *
     * Proxies to {@link sendResponse()} and returns response value as string
     * using output buffering.
     *
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this->sendResponse();
        return ob_get_clean();
    }
}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Controller_Plugin_Statpage extends Zend_Controller_Plugin_Abstract
{
    protected static $is_check = false;
	
	public function __construct()
	{
	}
	
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {

    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {

        if ($this->getResponse()->getHttpResponseCode() == 404 && !self::$is_check)
        {
            self::$is_check = true;

            $path = $_SERVER['REQUEST_URI'];
            $path = explode('?',$path,2);
            $path = trim(urldecode($path[0]),'/');

            $sp = new Z_Statpage($path);
            if (!$sp->isError())
            {
                $request->setControllerName('page');
                $request->setActionName('show');
                $request->setParam('page',$sp);
                $request->setDispatched(false);
                $this->getResponse()->setBody('');
                $this->getResponse()->setHttpResponseCode(200);
            }

        }
    }
}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Controller_Plugin_DbUriTitle extends Zend_Controller_Plugin_Abstract
{
	
	public function __construct()
	{
	}
	
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    	if ($this->getRequest()->getModuleName()=='admin') return;
		$uri = $request->getRequestUri();
		
		if (!$titles = Z_Cache::getInstance()->load('z_titles'))
		{
			$table_titles = new Z_Model_Titles();
			$titles = $table_titles->fetchAll(NULL,'orderid asc');
			Z_Cache::getInstance()->save($titles,'z_titles');
		}

		foreach ($titles as $title) {
			if (strpos($uri,$title->uri)===0)
			{
				if ($title->title_block)
				{
					Z_Seo::addTitle($title->title);
				}
				else
				{
					Z_Seo::setTitle($title->title);
				}
			
				if ($title->description_block)
					Z_Seo::addDescription($title->description);
				else
				{
					Z_Seo::setDescription($title->description);
				}
				
				if ($title->keywords_block)
					Z_Seo::addKeywords($title->keywords);
				else
					Z_Seo::setKeywords($title->keywords);
			}
		}
		
    }
}

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
 * @category   Zend
 * @package    Zend_Layout
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Layout.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Provide Layout support for MVC applications
 *
 * @category   Zend
 * @package    Zend_Layout
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Layout
{
    /**
     * Placeholder container for layout variables
     * @var Zend_View_Helper_Placeholder_Container
     */
    protected $_container;

    /**
     * Key used to store content from 'default' named response segment
     * @var string
     */
    protected $_contentKey = 'content';

    /**
     * Are layouts enabled?
     * @var bool
     */
    protected $_enabled = true;

    /**
     * Helper class
     * @var string
     */
    protected $_helperClass = 'Zend_Layout_Controller_Action_Helper_Layout';

    /**
     * Inflector used to resolve layout script
     * @var Zend_Filter_Inflector
     */
    protected $_inflector;

    /**
     * Flag: is inflector enabled?
     * @var bool
     */
    protected $_inflectorEnabled = true;

    /**
     * Inflector target
     * @var string
     */
    protected $_inflectorTarget = ':script.:suffix';

    /**
     * Layout view
     * @var string
     */
    protected $_layout = 'layout';

    /**
     * Layout view script path
     * @var string
     */
    protected $_viewScriptPath = null;

    protected $_viewBasePath = null;
    protected $_viewBasePrefix = 'Layout_View';

    /**
     * Flag: is MVC integration enabled?
     * @var bool
     */
    protected $_mvcEnabled = true;

    /**
     * Instance registered with MVC, if any
     * @var Zend_Layout
     */
    protected static $_mvcInstance;

    /**
     * Flag: is MVC successful action only flag set?
     * @var bool
     */
    protected $_mvcSuccessfulActionOnly = true;

    /**
     * Plugin class
     * @var string
     */
    protected $_pluginClass = 'Zend_Layout_Controller_Plugin_Layout';

    /**
     * @var Zend_View_Interface
     */
    protected $_view;

    /**
     * View script suffix for layout script
     * @var string
     */
    protected $_viewSuffix = 'phtml';

    /**
     * Constructor
     *
     * Accepts either:
     * - A string path to layouts
     * - An array of options
     * - A Zend_Config object with options
     *
     * Layout script path, either as argument or as key in options, is
     * required.
     *
     * If mvcEnabled flag is false from options, simply sets layout script path.
     * Otherwise, also instantiates and registers action helper and controller
     * plugin.
     *
     * @param  string|array|Zend_Config $options
     * @return void
     */
    public function __construct($options = null, $initMvc = false)
    {
        if (null !== $options) {
            if (is_string($options)) {
                $this->setLayoutPath($options);
            } elseif (is_array($options)) {
                $this->setOptions($options);
            } elseif ($options instanceof Zend_Config) {
                $this->setConfig($options);
            } else {
                require_once 'Zend/Layout/Exception.php';
                throw new Zend_Layout_Exception('Invalid option provided to constructor');
            }
        }

        $this->_initVarContainer();

        if ($initMvc) {
            $this->_setMvcEnabled(true);
            $this->_initMvc();
        } else {
            $this->_setMvcEnabled(false);
        }
    }

    /**
     * Static method for initialization with MVC support
     *
     * @param  string|array|Zend_Config $options
     * @return Zend_Layout
     */
    public static function startMvc($options = null)
    {
        if (null === self::$_mvcInstance) {
            self::$_mvcInstance = new self($options, true);
        }

        if (is_string($options)) {
            self::$_mvcInstance->setLayoutPath($options);
        } elseif (is_array($options) || $options instanceof Zend_Config) {
            self::$_mvcInstance->setOptions($options);
        }

        return self::$_mvcInstance;
    }

    /**
     * Retrieve MVC instance of Zend_Layout object
     *
     * @return Zend_Layout|null
     */
    public static function getMvcInstance()
    {
        return self::$_mvcInstance;
    }

    /**
     * Reset MVC instance
     *
     * Unregisters plugins and helpers, and destroys MVC layout instance.
     *
     * @return void
     */
    public static function resetMvcInstance()
    {
        if (null !== self::$_mvcInstance) {
            $layout = self::$_mvcInstance;
            $pluginClass = $layout->getPluginClass();
            $front = Zend_Controller_Front::getInstance();
            if ($front->hasPlugin($pluginClass)) {
                $front->unregisterPlugin($pluginClass);
            }

            if (Zend_Controller_Action_HelperBroker::hasHelper('layout')) {
                Zend_Controller_Action_HelperBroker::removeHelper('layout');
            }

            unset($layout);
            self::$_mvcInstance = null;
        }
    }

    /**
     * Set options en masse
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function setOptions($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            require_once 'Zend/Layout/Exception.php';
            throw new Zend_Layout_Exception('setOptions() expects either an array or a Zend_Config object');
        }

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Initialize MVC integration
     *
     * @return void
     */
    protected function _initMvc()
    {
        $this->_initPlugin();
        $this->_initHelper();
    }

    /**
     * Initialize front controller plugin
     *
     * @return void
     */
    protected function _initPlugin()
    {
        $pluginClass = $this->getPluginClass();
        require_once 'Zend/Controller/Front.php';
        $front = Zend_Controller_Front::getInstance();
        if (!$front->hasPlugin($pluginClass)) {
            if (!class_exists($pluginClass)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($pluginClass);
            }
            $front->registerPlugin(
                // register to run last | BUT before the ErrorHandler (if its available)
                new $pluginClass($this),
                99
            );
        }
    }

    /**
     * Initialize action helper
     *
     * @return void
     */
    protected function _initHelper()
    {
        $helperClass = $this->getHelperClass();
        require_once 'Zend/Controller/Action/HelperBroker.php';
        if (!Zend_Controller_Action_HelperBroker::hasHelper('layout')) {
            if (!class_exists($helperClass)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($helperClass);
            }
            Zend_Controller_Action_HelperBroker::getStack()->offsetSet(-90, new $helperClass($this));
        }
    }

    /**
     * Set options from a config object
     *
     * @param  Zend_Config $config
     * @return Zend_Layout
     */
    public function setConfig(Zend_Config $config)
    {
        $this->setOptions($config->toArray());
        return $this;
    }

    /**
     * Initialize placeholder container for layout vars
     *
     * @return Zend_View_Helper_Placeholder_Container
     */
    protected function _initVarContainer()
    {
        if (null === $this->_container) {
            require_once 'Zend/View/Helper/Placeholder/Registry.php';
            $this->_container = Zend_View_Helper_Placeholder_Registry::getRegistry()->getContainer(__CLASS__);
        }

        return $this->_container;
    }

    /**
     * Set layout script to use
     *
     * Note: enables layout by default, can be disabled
     *
     * @param  string $name
     * @param  boolean $enabled
     * @return Zend_Layout
     */
    public function setLayout($name, $enabled = true)
    {
        $this->_layout = (string) $name;
        if ($enabled) {
            $this->enableLayout();
        }
        return $this;
    }

    /**
     * Get current layout script
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Disable layout
     *
     * @return Zend_Layout
     */
    public function disableLayout()
    {
        $this->_enabled = false;
        return $this;
    }

    /**
     * Enable layout
     *
     * @return Zend_Layout
     */
    public function enableLayout()
    {
        $this->_enabled = true;
        return $this;
    }

    /**
     * Is layout enabled?
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }


    public function setViewBasePath($path, $prefix = 'Layout_View')
    {
        $this->_viewBasePath = $path;
        $this->_viewBasePrefix = $prefix;
        return $this;
    }

    public function getViewBasePath()
    {
        return $this->_viewBasePath;
    }

    public function setViewScriptPath($path)
    {
        $this->_viewScriptPath = $path;
        return $this;
    }

    public function getViewScriptPath()
    {
        return $this->_viewScriptPath;
    }

    /**
     * Set layout script path
     *
     * @param  string $path
     * @return Zend_Layout
     */
    public function setLayoutPath($path)
    {
        return $this->setViewScriptPath($path);
    }

    /**
     * Get current layout script path
     *
     * @return string
     */
    public function getLayoutPath()
    {
        return $this->getViewScriptPath();
    }

    /**
     * Set content key
     *
     * Key in namespace container denoting default content
     *
     * @param  string $contentKey
     * @return Zend_Layout
     */
    public function setContentKey($contentKey)
    {
        $this->_contentKey = (string) $contentKey;
        return $this;
    }

    /**
     * Retrieve content key
     *
     * @return string
     */
    public function getContentKey()
    {
        return $this->_contentKey;
    }

    /**
     * Set MVC enabled flag
     *
     * @param  bool $mvcEnabled
     * @return Zend_Layout
     */
    protected function _setMvcEnabled($mvcEnabled)
    {
        $this->_mvcEnabled = ($mvcEnabled) ? true : false;
        return $this;
    }

    /**
     * Retrieve MVC enabled flag
     *
     * @return bool
     */
    public function getMvcEnabled()
    {
        return $this->_mvcEnabled;
    }

    /**
     * Set MVC Successful Action Only flag
     *
     * @param bool $successfulActionOnly
     * @return Zend_Layout
     */
    public function setMvcSuccessfulActionOnly($successfulActionOnly)
    {
        $this->_mvcSuccessfulActionOnly = ($successfulActionOnly) ? true : false;
        return $this;
    }

    /**
     * Get MVC Successful Action Only Flag
     *
     * @return bool
     */
    public function getMvcSuccessfulActionOnly()
    {
        return $this->_mvcSuccessfulActionOnly;
    }

    /**
     * Set view object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_Layout
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * Retrieve helper class
     *
     * @return string
     */
    public function getHelperClass()
    {
        return $this->_helperClass;
    }

    /**
     * Set helper class
     *
     * @param  string $helperClass
     * @return Zend_Layout
     */
    public function setHelperClass($helperClass)
    {
        $this->_helperClass = (string) $helperClass;
        return $this;
    }

    /**
     * Retrieve plugin class
     *
     * @return string
     */
    public function getPluginClass()
    {
        return $this->_pluginClass;
    }

    /**
     * Set plugin class
     *
     * @param  string $pluginClass
     * @return Zend_Layout
     */
    public function setPluginClass($pluginClass)
    {
        $this->_pluginClass = (string) $pluginClass;
        return $this;
    }

    /**
     * Get current view object
     *
     * If no view object currently set, retrieves it from the ViewRenderer.
     *
     * @todo Set inflector from view renderer at same time
     * @return Zend_View_Interface
     */
    public function getView()
    {
        if (null === $this->_view) {
            require_once 'Zend/Controller/Action/HelperBroker.php';
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $this->setView($viewRenderer->view);
        }
        return $this->_view;
    }

    /**
     * Set layout view script suffix
     *
     * @param  string $viewSuffix
     * @return Zend_Layout
     */
    public function setViewSuffix($viewSuffix)
    {
        $this->_viewSuffix = (string) $viewSuffix;
        return $this;
    }

    /**
     * Retrieve layout view script suffix
     *
     * @return string
     */
    public function getViewSuffix()
    {
        return $this->_viewSuffix;
    }

    /**
     * Retrieve inflector target
     *
     * @return string
     */
    public function getInflectorTarget()
    {
        return $this->_inflectorTarget;
    }

    /**
     * Set inflector target
     *
     * @param  string $inflectorTarget
     * @return Zend_Layout
     */
    public function setInflectorTarget($inflectorTarget)
    {
        $this->_inflectorTarget = (string) $inflectorTarget;
        return $this;
    }

    /**
     * Set inflector to use when resolving layout names
     *
     * @param  Zend_Filter_Inflector $inflector
     * @return Zend_Layout
     */
    public function setInflector(Zend_Filter_Inflector $inflector)
    {
        $this->_inflector = $inflector;
        return $this;
    }

    /**
     * Retrieve inflector
     *
     * @return Zend_Filter_Inflector
     */
    public function getInflector()
    {
        if (null === $this->_inflector) {
            require_once 'Zend/Filter/Inflector.php';
            $inflector = new Zend_Filter_Inflector();
            $inflector->setTargetReference($this->_inflectorTarget)
                      ->addRules(array(':script' => array('Word_CamelCaseToDash', 'StringToLower')))
                      ->setStaticRuleReference('suffix', $this->_viewSuffix);
            $this->setInflector($inflector);
        }

        return $this->_inflector;
    }

    /**
     * Enable inflector
     *
     * @return Zend_Layout
     */
    public function enableInflector()
    {
        $this->_inflectorEnabled = true;
        return $this;
    }

    /**
     * Disable inflector
     *
     * @return Zend_Layout
     */
    public function disableInflector()
    {
        $this->_inflectorEnabled = false;
        return $this;
    }

    /**
     * Return status of inflector enabled flag
     *
     * @return bool
     */
    public function inflectorEnabled()
    {
        return $this->_inflectorEnabled;
    }

    /**
     * Set layout variable
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_container[$key] = $value;
    }

    /**
     * Get layout variable
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_container[$key])) {
            return $this->_container[$key];
        }

        return null;
    }

    /**
     * Is a layout variable set?
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return (isset($this->_container[$key]));
    }

    /**
     * Unset a layout variable?
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        if (isset($this->_container[$key])) {
            unset($this->_container[$key]);
        }
    }

    /**
     * Assign one or more layout variables
     *
     * @param  mixed $spec Assoc array or string key; if assoc array, sets each
     * key as a layout variable
     * @param  mixed $value Value if $spec is a key
     * @return Zend_Layout
     * @throws Zend_Layout_Exception if non-array/string value passed to $spec
     */
    public function assign($spec, $value = null)
    {
        if (is_array($spec)) {
            $orig = $this->_container->getArrayCopy();
            $merged = array_merge($orig, $spec);
            $this->_container->exchangeArray($merged);
            return $this;
        }

        if (is_string($spec)) {
            $this->_container[$spec] = $value;
            return $this;
        }

        require_once 'Zend/Layout/Exception.php';
        throw new Zend_Layout_Exception('Invalid values passed to assign()');
    }

    /**
     * Render layout
     *
     * Sets internal script path as last path on script path stack, assigns
     * layout variables to view, determines layout name using inflector, and
     * renders layout view script.
     *
     * $name will be passed to the inflector as the key 'script'.
     *
     * @param  mixed $name
     * @return mixed
     */
    public function render($name = null)
    {
        if (null === $name) {
            $name = $this->getLayout();
        }

        if ($this->inflectorEnabled() && (null !== ($inflector = $this->getInflector())))
        {
            $name = $this->_inflector->filter(array('script' => $name));
        }

        $view = $this->getView();

        if (null !== ($path = $this->getViewScriptPath())) {
            if (method_exists($view, 'addScriptPath')) {
                $view->addScriptPath($path);
            } else {
                $view->setScriptPath($path);
            }
        } elseif (null !== ($path = $this->getViewBasePath())) {
            $view->addBasePath($path, $this->_viewBasePrefix);
        }

        return $view->render($name);
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Plugin_Abstract */
require_once 'Zend/Controller/Plugin/Abstract.php';

/**
 * Render layouts
 *
 * @uses       Zend_Controller_Plugin_Abstract
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Layout.php 23775 2011-03-01 17:25:24Z ralph $
 */
class Zend_Layout_Controller_Plugin_Layout extends Zend_Controller_Plugin_Abstract
{
    protected $_layoutActionHelper = null;

    /**
     * @var Zend_Layout
     */
    protected $_layout;

    /**
     * Constructor
     *
     * @param  Zend_Layout $layout
     * @return void
     */
    public function __construct(Zend_Layout $layout = null)
    {
        if (null !== $layout) {
            $this->setLayout($layout);
        }
    }

    /**
     * Retrieve layout object
     *
     * @return Zend_Layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Set layout object
     *
     * @param  Zend_Layout $layout
     * @return Zend_Layout_Controller_Plugin_Layout
     */
    public function setLayout(Zend_Layout $layout)
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * Set layout action helper
     *
     * @param  Zend_Layout_Controller_Action_Helper_Layout $layoutActionHelper
     * @return Zend_Layout_Controller_Plugin_Layout
     */
    public function setLayoutActionHelper(Zend_Layout_Controller_Action_Helper_Layout $layoutActionHelper)
    {
        $this->_layoutActionHelper = $layoutActionHelper;
        return $this;
    }

    /**
     * Retrieve layout action helper
     *
     * @return Zend_Layout_Controller_Action_Helper_Layout
     */
    public function getLayoutActionHelper()
    {
        return $this->_layoutActionHelper;
    }

    /**
     * postDispatch() plugin hook -- render layout
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $layout = $this->getLayout();
        $helper = $this->getLayoutActionHelper();

        // Return early if forward detected
        if (!$request->isDispatched()
            || $this->getResponse()->isRedirect()
            || ($layout->getMvcSuccessfulActionOnly()
                && (!empty($helper) && !$helper->isActionControllerSuccessful())))
        {
            return;
        }

        // Return early if layout has been disabled
        if (!$layout->isEnabled()) {
            return;
        }

        $response   = $this->getResponse();
        $content    = $response->getBody(true);
        $contentKey = $layout->getContentKey();

        if (isset($content['default'])) {
            $content[$contentKey] = $content['default'];
        }
        if ('default' != $contentKey) {
            unset($content['default']);
        }

        $layout->assign($content);

        $fullContent = null;
        $obStartLevel = ob_get_level();
        try {
            $fullContent = $layout->render();
            $response->setBody($fullContent);
        } catch (Exception $e) {
            while (ob_get_level() > $obStartLevel) {
                $fullContent .= ob_get_clean();
            }
            $request->setParam('layoutFullContent', $fullContent);
            $request->setParam('layoutContent', $layout->content);
            $response->setBody(null);
            throw $e;
        }

    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Layout.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Action_Helper_Abstract */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Helper for interacting with Zend_Layout objects
 *
 * @uses       Zend_Controller_Action_Helper_Abstract
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Zend_Controller_Action
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Layout_Controller_Action_Helper_Layout extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * @var Zend_Layout
     */
    protected $_layout;

    /**
     * @var bool
     */
    protected $_isActionControllerSuccessful = false;

    /**
     * Constructor
     *
     * @param  Zend_Layout $layout
     * @return void
     */
    public function __construct(Zend_Layout $layout = null)
    {
        if (null !== $layout) {
            $this->setLayoutInstance($layout);
        } else {
            /**
             * @see Zend_Layout
             */
            require_once 'Zend/Layout.php';
            $layout = Zend_Layout::getMvcInstance();
        }

        if (null !== $layout) {
            $pluginClass = $layout->getPluginClass();
            $front = $this->getFrontController();
            if ($front->hasPlugin($pluginClass)) {
                $plugin = $front->getPlugin($pluginClass);
                $plugin->setLayoutActionHelper($this);
            }
        }
    }

    public function init()
    {
        $this->_isActionControllerSuccessful = false;
    }

    /**
     * Get front controller instance
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        if (null === $this->_frontController) {
            /**
             * @see Zend_Controller_Front
             */
            require_once 'Zend/Controller/Front.php';
            $this->_frontController = Zend_Controller_Front::getInstance();
        }

        return $this->_frontController;
    }

    /**
     * Get layout object
     *
     * @return Zend_Layout
     */
    public function getLayoutInstance()
    {
        if (null === $this->_layout) {
            /**
             * @see Zend_Layout
             */
            require_once 'Zend/Layout.php';
            if (null === ($this->_layout = Zend_Layout::getMvcInstance())) {
                $this->_layout = new Zend_Layout();
            }
        }

        return $this->_layout;
    }

    /**
     * Set layout object
     *
     * @param  Zend_Layout $layout
     * @return Zend_Layout_Controller_Action_Helper_Layout
     */
    public function setLayoutInstance(Zend_Layout $layout)
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * Mark Action Controller (according to this plugin) as Running successfully
     *
     * @return Zend_Layout_Controller_Action_Helper_Layout
     */
    public function postDispatch()
    {
        $this->_isActionControllerSuccessful = true;
        return $this;
    }

    /**
     * Did the previous action successfully complete?
     *
     * @return bool
     */
    public function isActionControllerSuccessful()
    {
        return $this->_isActionControllerSuccessful;
    }

    /**
     * Strategy pattern; call object as method
     *
     * Returns layout object
     *
     * @return Zend_Layout
     */
    public function direct()
    {
        return $this->getLayoutInstance();
    }

    /**
     * Proxy method calls to layout object
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $layout = $this->getLayoutInstance();
        if (method_exists($layout, $method)) {
            return call_user_func_array(array($layout, $method), $args);
        }

        require_once 'Zend/Layout/Exception.php';
        throw new Zend_Layout_Exception(sprintf("Invalid method '%s' called on layout action helper", $method));
    }
}


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
 * @category   Zend
 * @package    Zend_Db
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Db.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * Class for connecting to SQL databases and performing common operations.
 *
 * @category   Zend
 * @package    Zend_Db
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db
{

    /**
     * Use the PROFILER constant in the config of a Zend_Db_Adapter.
     */
    const PROFILER = 'profiler';

    /**
     * Use the CASE_FOLDING constant in the config of a Zend_Db_Adapter.
     */
    const CASE_FOLDING = 'caseFolding';

    /**
     * Use the FETCH_MODE constant in the config of a Zend_Db_Adapter.
     */
    const FETCH_MODE = 'fetchMode';

    /**
     * Use the AUTO_QUOTE_IDENTIFIERS constant in the config of a Zend_Db_Adapter.
     */
    const AUTO_QUOTE_IDENTIFIERS = 'autoQuoteIdentifiers';

    /**
     * Use the ALLOW_SERIALIZATION constant in the config of a Zend_Db_Adapter.
     */
    const ALLOW_SERIALIZATION = 'allowSerialization';

    /**
     * Use the AUTO_RECONNECT_ON_UNSERIALIZE constant in the config of a Zend_Db_Adapter.
     */
    const AUTO_RECONNECT_ON_UNSERIALIZE = 'autoReconnectOnUnserialize';

    /**
     * Use the INT_TYPE, BIGINT_TYPE, and FLOAT_TYPE with the quote() method.
     */
    const INT_TYPE    = 0;
    const BIGINT_TYPE = 1;
    const FLOAT_TYPE  = 2;

    /**
     * PDO constant values discovered by this script result:
     *
     * $list = array(
     *    'PARAM_BOOL', 'PARAM_NULL', 'PARAM_INT', 'PARAM_STR', 'PARAM_LOB',
     *    'PARAM_STMT', 'PARAM_INPUT_OUTPUT', 'FETCH_LAZY', 'FETCH_ASSOC',
     *    'FETCH_NUM', 'FETCH_BOTH', 'FETCH_OBJ', 'FETCH_BOUND',
     *    'FETCH_COLUMN', 'FETCH_CLASS', 'FETCH_INTO', 'FETCH_FUNC',
     *    'FETCH_GROUP', 'FETCH_UNIQUE', 'FETCH_CLASSTYPE', 'FETCH_SERIALIZE',
     *    'FETCH_NAMED', 'ATTR_AUTOCOMMIT', 'ATTR_PREFETCH', 'ATTR_TIMEOUT',
     *    'ATTR_ERRMODE', 'ATTR_SERVER_VERSION', 'ATTR_CLIENT_VERSION',
     *    'ATTR_SERVER_INFO', 'ATTR_CONNECTION_STATUS', 'ATTR_CASE',
     *    'ATTR_CURSOR_NAME', 'ATTR_CURSOR', 'ATTR_ORACLE_NULLS',
     *    'ATTR_PERSISTENT', 'ATTR_STATEMENT_CLASS', 'ATTR_FETCH_TABLE_NAMES',
     *    'ATTR_FETCH_CATALOG_NAMES', 'ATTR_DRIVER_NAME',
     *    'ATTR_STRINGIFY_FETCHES', 'ATTR_MAX_COLUMN_LEN', 'ERRMODE_SILENT',
     *    'ERRMODE_WARNING', 'ERRMODE_EXCEPTION', 'CASE_NATURAL',
     *    'CASE_LOWER', 'CASE_UPPER', 'NULL_NATURAL', 'NULL_EMPTY_STRING',
     *    'NULL_TO_STRING', 'ERR_NONE', 'FETCH_ORI_NEXT',
     *    'FETCH_ORI_PRIOR', 'FETCH_ORI_FIRST', 'FETCH_ORI_LAST',
     *    'FETCH_ORI_ABS', 'FETCH_ORI_REL', 'CURSOR_FWDONLY', 'CURSOR_SCROLL',
     * );
     *
     * $const = array();
     * foreach ($list as $name) {
     *    $const[$name] = constant("PDO::$name");
     * }
     * var_export($const);
     */
    const ATTR_AUTOCOMMIT = 0;
    const ATTR_CASE = 8;
    const ATTR_CLIENT_VERSION = 5;
    const ATTR_CONNECTION_STATUS = 7;
    const ATTR_CURSOR = 10;
    const ATTR_CURSOR_NAME = 9;
    const ATTR_DRIVER_NAME = 16;
    const ATTR_ERRMODE = 3;
    const ATTR_FETCH_CATALOG_NAMES = 15;
    const ATTR_FETCH_TABLE_NAMES = 14;
    const ATTR_MAX_COLUMN_LEN = 18;
    const ATTR_ORACLE_NULLS = 11;
    const ATTR_PERSISTENT = 12;
    const ATTR_PREFETCH = 1;
    const ATTR_SERVER_INFO = 6;
    const ATTR_SERVER_VERSION = 4;
    const ATTR_STATEMENT_CLASS = 13;
    const ATTR_STRINGIFY_FETCHES = 17;
    const ATTR_TIMEOUT = 2;
    const CASE_LOWER = 2;
    const CASE_NATURAL = 0;
    const CASE_UPPER = 1;
    const CURSOR_FWDONLY = 0;
    const CURSOR_SCROLL = 1;
    const ERR_NONE = '00000';
    const ERRMODE_EXCEPTION = 2;
    const ERRMODE_SILENT = 0;
    const ERRMODE_WARNING = 1;
    const FETCH_ASSOC = 2;
    const FETCH_BOTH = 4;
    const FETCH_BOUND = 6;
    const FETCH_CLASS = 8;
    const FETCH_CLASSTYPE = 262144;
    const FETCH_COLUMN = 7;
    const FETCH_FUNC = 10;
    const FETCH_GROUP = 65536;
    const FETCH_INTO = 9;
    const FETCH_LAZY = 1;
    const FETCH_NAMED = 11;
    const FETCH_NUM = 3;
    const FETCH_OBJ = 5;
    const FETCH_ORI_ABS = 4;
    const FETCH_ORI_FIRST = 2;
    const FETCH_ORI_LAST = 3;
    const FETCH_ORI_NEXT = 0;
    const FETCH_ORI_PRIOR = 1;
    const FETCH_ORI_REL = 5;
    const FETCH_SERIALIZE = 524288;
    const FETCH_UNIQUE = 196608;
    const NULL_EMPTY_STRING = 1;
    const NULL_NATURAL = 0;
    const NULL_TO_STRING = NULL;
    const PARAM_BOOL = 5;
    const PARAM_INPUT_OUTPUT = -2147483648;
    const PARAM_INT = 1;
    const PARAM_LOB = 3;
    const PARAM_NULL = 0;
    const PARAM_STMT = 4;
    const PARAM_STR = 2;

    /**
     * Factory for Zend_Db_Adapter_Abstract classes.
     *
     * First argument may be a string containing the base of the adapter class
     * name, e.g. 'Mysqli' corresponds to class Zend_Db_Adapter_Mysqli.  This
     * name is currently case-insensitive, but is not ideal to rely on this behavior.
     * If your class is named 'My_Company_Pdo_Mysql', where 'My_Company' is the namespace
     * and 'Pdo_Mysql' is the adapter name, it is best to use the name exactly as it
     * is defined in the class.  This will ensure proper use of the factory API.
     *
     * First argument may alternatively be an object of type Zend_Config.
     * The adapter class base name is read from the 'adapter' property.
     * The adapter config parameters are read from the 'params' property.
     *
     * Second argument is optional and may be an associative array of key-value
     * pairs.  This is used as the argument to the adapter constructor.
     *
     * If the first argument is of type Zend_Config, it is assumed to contain
     * all parameters, and the second argument is ignored.
     *
     * @param  mixed $adapter String name of base adapter class, or Zend_Config object.
     * @param  mixed $config  OPTIONAL; an array or Zend_Config object with adapter parameters.
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Db_Exception
     */
    public static function factory($adapter, $config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        /*
         * Convert Zend_Config argument to plain string
         * adapter name and separate config object.
         */
        if ($adapter instanceof Zend_Config) {
            if (isset($adapter->params)) {
                $config = $adapter->params->toArray();
            }
            if (isset($adapter->adapter)) {
                $adapter = (string) $adapter->adapter;
            } else {
                $adapter = null;
            }
        }

        /*
         * Verify that adapter parameters are in an array.
         */
        if (!is_array($config)) {
            /**
             * @see Zend_Db_Exception
             */
            require_once 'Zend/Db/Exception.php';
            throw new Zend_Db_Exception('Adapter parameters must be in an array or a Zend_Config object');
        }

        /*
         * Verify that an adapter name has been specified.
         */
        if (!is_string($adapter) || empty($adapter)) {
            /**
             * @see Zend_Db_Exception
             */
            require_once 'Zend/Db/Exception.php';
            throw new Zend_Db_Exception('Adapter name must be specified in a string');
        }

        /*
         * Form full adapter class name
         */
        $adapterNamespace = 'Zend_Db_Adapter';
        if (isset($config['adapterNamespace'])) {
            if ($config['adapterNamespace'] != '') {
                $adapterNamespace = $config['adapterNamespace'];
            }
            unset($config['adapterNamespace']);
        }

        // Adapter no longer normalized- see http://framework.zend.com/issues/browse/ZF-5606
        $adapterName = $adapterNamespace . '_';
        $adapterName .= str_replace(' ', '_', ucwords(str_replace('_', ' ', strtolower($adapter))));

        /*
         * Load the adapter class.  This throws an exception
         * if the specified class cannot be loaded.
         */
        if (!class_exists($adapterName)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($adapterName);
        }

        /*
         * Create an instance of the adapter class.
         * Pass the config to the adapter class constructor.
         */
        $dbAdapter = new $adapterName($config);

        /*
         * Verify that the object created is a descendent of the abstract adapter type.
         */
        if (! $dbAdapter instanceof Zend_Db_Adapter_Abstract) {
            /**
             * @see Zend_Db_Exception
             */
            require_once 'Zend/Db/Exception.php';
            throw new Zend_Db_Exception("Adapter class '$adapterName' does not extend Zend_Db_Adapter_Abstract");
        }

        return $dbAdapter;
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Mysql.php 23986 2011-05-03 20:10:42Z ralph $
 */


/**
 * @see Zend_Db_Adapter_Pdo_Abstract
 */
require_once 'Zend/Db/Adapter/Pdo/Abstract.php';


/**
 * Class for connecting to MySQL databases and performing common operations.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Adapter_Pdo_Mysql extends Zend_Db_Adapter_Pdo_Abstract
{

    /**
     * PDO type.
     *
     * @var string
     */
    protected $_pdoType = 'mysql';

    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Zend_Db::INT_TYPE, Zend_Db::BIGINT_TYPE, or Zend_Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
        Zend_Db::INT_TYPE    => Zend_Db::INT_TYPE,
        Zend_Db::BIGINT_TYPE => Zend_Db::BIGINT_TYPE,
        Zend_Db::FLOAT_TYPE  => Zend_Db::FLOAT_TYPE,
        'INT'                => Zend_Db::INT_TYPE,
        'INTEGER'            => Zend_Db::INT_TYPE,
        'MEDIUMINT'          => Zend_Db::INT_TYPE,
        'SMALLINT'           => Zend_Db::INT_TYPE,
        'TINYINT'            => Zend_Db::INT_TYPE,
        'BIGINT'             => Zend_Db::BIGINT_TYPE,
        'SERIAL'             => Zend_Db::BIGINT_TYPE,
        'DEC'                => Zend_Db::FLOAT_TYPE,
        'DECIMAL'            => Zend_Db::FLOAT_TYPE,
        'DOUBLE'             => Zend_Db::FLOAT_TYPE,
        'DOUBLE PRECISION'   => Zend_Db::FLOAT_TYPE,
        'FIXED'              => Zend_Db::FLOAT_TYPE,
        'FLOAT'              => Zend_Db::FLOAT_TYPE
    );

    /**
     * Override _dsn() and ensure that charset is incorporated in mysql
     * @see Zend_Db_Adapter_Pdo_Abstract::_dsn()
     */
    protected function _dsn()
    {
        $dsn = parent::_dsn();
        if (isset($this->_config['charset'])) {
            $dsn .= ';charset=' . $this->_config['charset'];
        }
        return $dsn;
    }
    
    /**
     * Creates a PDO object and connects to the database.
     *
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _connect()
    {
        if ($this->_connection) {
            return;
        }

        if (!empty($this->_config['charset'])) {
            $initCommand = "SET NAMES '" . $this->_config['charset'] . "'";
            $this->_config['driver_options'][1002] = $initCommand; // 1002 = PDO::MYSQL_ATTR_INIT_COMMAND
        }

        parent::_connect();
    }

    /**
     * @return string
     */
    public function getQuoteIdentifierSymbol()
    {
        return "`";
    }

    /**
     * Returns a list of the tables in the database.
     *
     * @return array
     */
    public function listTables()
    {
        return $this->fetchCol('SHOW TABLES');
    }

    /**
     * Returns the column descriptions for a table.
     *
     * The return value is an associative array keyed by the column name,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string;
     * COLUMN_NAME      => string; column name
     * COLUMN_POSITION  => number; ordinal position of column in table
     * DATA_TYPE        => string; SQL datatype name of column
     * DEFAULT          => string; default expression of column, null if none
     * NULLABLE         => boolean; true if column can have nulls
     * LENGTH           => number; length of CHAR/VARCHAR
     * SCALE            => number; scale of NUMERIC/DECIMAL
     * PRECISION        => number; precision of NUMERIC/DECIMAL
     * UNSIGNED         => boolean; unsigned property of an integer type
     * PRIMARY          => boolean; true if column is part of the primary key
     * PRIMARY_POSITION => integer; position of column in primary key
     * IDENTITY         => integer; true if column is auto-generated with unique values
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     * @return array
     */
    public function describeTable($tableName, $schemaName = null)
    {
        // @todo  use INFORMATION_SCHEMA someday when MySQL's
        // implementation has reasonably good performance and
        // the version with this improvement is in wide use.

        if ($schemaName) {
            $sql = 'DESCRIBE ' . $this->quoteIdentifier("$schemaName.$tableName", true);
        } else {
            $sql = 'DESCRIBE ' . $this->quoteIdentifier($tableName, true);
        }
        $stmt = $this->query($sql);

        // Use FETCH_NUM so we are not dependent on the CASE attribute of the PDO connection
        $result = $stmt->fetchAll(Zend_Db::FETCH_NUM);

        $field   = 0;
        $type    = 1;
        $null    = 2;
        $key     = 3;
        $default = 4;
        $extra   = 5;

        $desc = array();
        $i = 1;
        $p = 1;
        foreach ($result as $row) {
            list($length, $scale, $precision, $unsigned, $primary, $primaryPosition, $identity)
                = array(null, null, null, null, false, null, false);
            if (preg_match('/unsigned/', $row[$type])) {
                $unsigned = true;
            }
            if (preg_match('/^((?:var)?char)\((\d+)\)/', $row[$type], $matches)) {
                $row[$type] = $matches[1];
                $length = $matches[2];
            } else if (preg_match('/^decimal\((\d+),(\d+)\)/', $row[$type], $matches)) {
                $row[$type] = 'decimal';
                $precision = $matches[1];
                $scale = $matches[2];
            } else if (preg_match('/^float\((\d+),(\d+)\)/', $row[$type], $matches)) {
                $row[$type] = 'float';
                $precision = $matches[1];
                $scale = $matches[2];
            } else if (preg_match('/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $row[$type], $matches)) {
                $row[$type] = $matches[1];
                // The optional argument of a MySQL int type is not precision
                // or length; it is only a hint for display width.
            }
            if (strtoupper($row[$key]) == 'PRI') {
                $primary = true;
                $primaryPosition = $p;
                if ($row[$extra] == 'auto_increment') {
                    $identity = true;
                } else {
                    $identity = false;
                }
                ++$p;
            }
            $desc[$this->foldCase($row[$field])] = array(
                'SCHEMA_NAME'      => null, // @todo
                'TABLE_NAME'       => $this->foldCase($tableName),
                'COLUMN_NAME'      => $this->foldCase($row[$field]),
                'COLUMN_POSITION'  => $i,
                'DATA_TYPE'        => $row[$type],
                'DEFAULT'          => $row[$default],
                'NULLABLE'         => (bool) ($row[$null] == 'YES'),
                'LENGTH'           => $length,
                'SCALE'            => $scale,
                'PRECISION'        => $precision,
                'UNSIGNED'         => $unsigned,
                'PRIMARY'          => $primary,
                'PRIMARY_POSITION' => $primaryPosition,
                'IDENTITY'         => $identity
            );
            ++$i;
        }
        return $desc;
    }

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @param  string $sql
     * @param  integer $count
     * @param  integer $offset OPTIONAL
     * @throws Zend_Db_Adapter_Exception
     * @return string
     */
     public function limit($sql, $count, $offset = 0)
     {
        $count = intval($count);
        if ($count <= 0) {
            /** @see Zend_Db_Adapter_Exception */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("LIMIT argument count=$count is not valid");
        }

        $offset = intval($offset);
        if ($offset < 0) {
            /** @see Zend_Db_Adapter_Exception */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("LIMIT argument offset=$offset is not valid");
        }

        $sql .= " LIMIT $count";
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }

        return $sql;
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @see Zend_Db_Adapter_Abstract
 */
require_once 'Zend/Db/Adapter/Abstract.php';


/**
 * @see Zend_Db_Statement_Pdo
 */
require_once 'Zend/Db/Statement/Pdo.php';


/**
 * Class for connecting to SQL databases and performing common operations using PDO.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Adapter_Pdo_Abstract extends Zend_Db_Adapter_Abstract
{

    /**
     * Default class name for a DB statement.
     *
     * @var string
     */
    protected $_defaultStmtClass = 'Zend_Db_Statement_Pdo';

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @return string
     */
    protected function _dsn()
    {
        // baseline of DSN parts
        $dsn = $this->_config;

        // don't pass the username, password, charset, persistent and driver_options in the DSN
        unset($dsn['username']);
        unset($dsn['password']);
        unset($dsn['options']);
        unset($dsn['charset']);
        unset($dsn['persistent']);
        unset($dsn['driver_options']);

        // use all remaining parts in the DSN
        foreach ($dsn as $key => $val) {
            $dsn[$key] = "$key=$val";
        }

        return $this->_pdoType . ':' . implode(';', $dsn);
    }

    /**
     * Creates a PDO object and connects to the database.
     *
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _connect()
    {
        // if we already have a PDO object, no need to re-connect.
        if ($this->_connection) {
            return;
        }

        // get the dsn first, because some adapters alter the $_pdoType
        $dsn = $this->_dsn();

        // check for PDO extension
        if (!extension_loaded('pdo')) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception('The PDO extension is required for this adapter but the extension is not loaded');
        }

        // check the PDO driver is available
        if (!in_array($this->_pdoType, PDO::getAvailableDrivers())) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception('The ' . $this->_pdoType . ' driver is not currently installed');
        }

        // create PDO connection
        $q = $this->_profiler->queryStart('connect', Zend_Db_Profiler::CONNECT);

        // add the persistence flag if we find it in our config array
        if (isset($this->_config['persistent']) && ($this->_config['persistent'] == true)) {
            $this->_config['driver_options'][PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $this->_connection = new PDO(
                $dsn,
                $this->_config['username'],
                $this->_config['password'],
                $this->_config['driver_options']
            );

            $this->_profiler->queryEnd($q);

            // set the PDO connection to perform case-folding on array keys, or not
            $this->_connection->setAttribute(PDO::ATTR_CASE, $this->_caseFolding);

            // always use exceptions.
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception($e->getMessage(), $e->getCode(), $e);
        }

    }

    /**
     * Test if a connection is active
     *
     * @return boolean
     */
    public function isConnected()
    {
        return ((bool) ($this->_connection instanceof PDO));
    }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection()
    {
        $this->_connection = null;
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return PDOStatement
     */
    public function prepare($sql)
    {
        $this->_connect();
        $stmtClass = $this->_defaultStmtClass;
        if (!class_exists($stmtClass)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($stmtClass);
        }
        $stmt = new $stmtClass($this, $sql);
        $stmt->setFetchMode($this->_fetchMode);
        return $stmt;
    }

    /**
     * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
     *
     * As a convention, on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2), this method forms the name of a sequence
     * from the arguments and returns the last id generated by that sequence.
     * On RDBMS brands that support IDENTITY/AUTOINCREMENT columns, this method
     * returns the last value generated for such a column, and the table name
     * argument is disregarded.
     *
     * On RDBMS brands that don't support sequences, $tableName and $primaryKey
     * are ignored.
     *
     * @param string $tableName   OPTIONAL Name of table.
     * @param string $primaryKey  OPTIONAL Name of primary key column.
     * @return string
     */
    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        $this->_connect();
        return $this->_connection->lastInsertId();
    }

    /**
     * Special handling for PDO query().
     * All bind parameter names must begin with ':'
     *
     * @param string|Zend_Db_Select $sql The SQL statement with placeholders.
     * @param array $bind An array of data to bind to the placeholders.
     * @return Zend_Db_Statement_Pdo
     * @throws Zend_Db_Adapter_Exception To re-throw PDOException.
     */
    public function query($sql, $bind = array())
    {
        if (empty($bind) && $sql instanceof Zend_Db_Select) {
            $bind = $sql->getBind();
        }

        if (is_array($bind)) {
            foreach ($bind as $name => $value) {
                if (!is_int($name) && !preg_match('/^:/', $name)) {
                    $newName = ":$name";
                    unset($bind[$name]);
                    $bind[$newName] = $value;
                }
            }
        }

        try {
            return parent::query($sql, $bind);
        } catch (PDOException $e) {
            /**
             * @see Zend_Db_Statement_Exception
             */
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Executes an SQL statement and return the number of affected rows
     *
     * @param  mixed  $sql  The SQL statement with placeholders.
     *                      May be a string or Zend_Db_Select.
     * @return integer      Number of rows that were modified
     *                      or deleted by the SQL statement
     */
    public function exec($sql)
    {
        if ($sql instanceof Zend_Db_Select) {
            $sql = $sql->assemble();
        }

        try {
            $affected = $this->getConnection()->exec($sql);

            if ($affected === false) {
                $errorInfo = $this->getConnection()->errorInfo();
                /**
                 * @see Zend_Db_Adapter_Exception
                 */
                require_once 'Zend/Db/Adapter/Exception.php';
                throw new Zend_Db_Adapter_Exception($errorInfo[2]);
            }

            return $affected;
        } catch (PDOException $e) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $this->_connect();
        return $this->_connection->quote($value);
    }

    /**
     * Begin a transaction.
     */
    protected function _beginTransaction()
    {
        $this->_connect();
        $this->_connection->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    protected function _commit()
    {
        $this->_connect();
        $this->_connection->commit();
    }

    /**
     * Roll-back a transaction.
     */
    protected function _rollBack() {
        $this->_connect();
        $this->_connection->rollBack();
    }

    /**
     * Set the PDO fetch mode.
     *
     * @todo Support FETCH_CLASS and FETCH_INTO.
     *
     * @param int $mode A PDO fetch mode.
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    public function setFetchMode($mode)
    {
        //check for PDO extension
        if (!extension_loaded('pdo')) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception('The PDO extension is required for this adapter but the extension is not loaded');
        }
        switch ($mode) {
            case PDO::FETCH_LAZY:
            case PDO::FETCH_ASSOC:
            case PDO::FETCH_NUM:
            case PDO::FETCH_BOTH:
            case PDO::FETCH_NAMED:
            case PDO::FETCH_OBJ:
                $this->_fetchMode = $mode;
                break;
            default:
                /**
                 * @see Zend_Db_Adapter_Exception
                 */
                require_once 'Zend/Db/Adapter/Exception.php';
                throw new Zend_Db_Adapter_Exception("Invalid fetch mode '$mode' specified");
                break;
        }
    }

    /**
     * Check if the adapter supports real SQL parameters.
     *
     * @param string $type 'positional' or 'named'
     * @return bool
     */
    public function supportsParameters($type)
    {
        switch ($type) {
            case 'positional':
            case 'named':
            default:
                return true;
        }
    }

    /**
     * Retrieve server version in PHP style
     *
     * @return string
     */
    public function getServerVersion()
    {
        $this->_connect();
        try {
            $version = $this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (PDOException $e) {
            // In case of the driver doesn't support getting attributes
            return null;
        }
        $matches = null;
        if (preg_match('/((?:[0-9]{1,2}\.){1,3}[0-9]{1,2})/', $version, $matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }
}


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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 24148 2011-06-21 15:14:00Z yoshida@zend.co.jp $
 */


/**
 * @see Zend_Db
 */
require_once 'Zend/Db.php';

/**
 * @see Zend_Db_Select
 */
require_once 'Zend/Db/Select.php';

/**
 * Class for connecting to SQL databases and performing common operations.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Adapter_Abstract
{

    /**
     * User-provided configuration
     *
     * @var array
     */
    protected $_config = array();

    /**
     * Fetch mode
     *
     * @var integer
     */
    protected $_fetchMode = Zend_Db::FETCH_ASSOC;

    /**
     * Query profiler object, of type Zend_Db_Profiler
     * or a subclass of that.
     *
     * @var Zend_Db_Profiler
     */
    protected $_profiler;

    /**
     * Default class name for a DB statement.
     *
     * @var string
     */
    protected $_defaultStmtClass = 'Zend_Db_Statement';

    /**
     * Default class name for the profiler object.
     *
     * @var string
     */
    protected $_defaultProfilerClass = 'Zend_Db_Profiler';

    /**
     * Database connection
     *
     * @var object|resource|null
     */
    protected $_connection = null;

    /**
     * Specifies the case of column names retrieved in queries
     * Options
     * Zend_Db::CASE_NATURAL (default)
     * Zend_Db::CASE_LOWER
     * Zend_Db::CASE_UPPER
     *
     * @var integer
     */
    protected $_caseFolding = Zend_Db::CASE_NATURAL;

    /**
     * Specifies whether the adapter automatically quotes identifiers.
     * If true, most SQL generated by Zend_Db classes applies
     * identifier quoting automatically.
     * If false, developer must quote identifiers themselves
     * by calling quoteIdentifier().
     *
     * @var bool
     */
    protected $_autoQuoteIdentifiers = true;

    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Zend_Db::INT_TYPE, Zend_Db::BIGINT_TYPE, or Zend_Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
        Zend_Db::INT_TYPE    => Zend_Db::INT_TYPE,
        Zend_Db::BIGINT_TYPE => Zend_Db::BIGINT_TYPE,
        Zend_Db::FLOAT_TYPE  => Zend_Db::FLOAT_TYPE
    );

    /** Weither or not that object can get serialized
     *
     * @var bool
     */
    protected $_allowSerialization = true;

    /**
     * Weither or not the database should be reconnected
     * to that adapter when waking up
     *
     * @var bool
     */
    protected $_autoReconnectOnUnserialize = false;

    /**
     * Constructor.
     *
     * $config is an array of key/value pairs or an instance of Zend_Config
     * containing configuration options.  These options are common to most adapters:
     *
     * dbname         => (string) The name of the database to user
     * username       => (string) Connect to the database as this username.
     * password       => (string) Password associated with the username.
     * host           => (string) What host to connect to, defaults to localhost
     *
     * Some options are used on a case-by-case basis by adapters:
     *
     * port           => (string) The port of the database
     * persistent     => (boolean) Whether to use a persistent connection or not, defaults to false
     * protocol       => (string) The network protocol, defaults to TCPIP
     * caseFolding    => (int) style of case-alteration used for identifiers
     *
     * @param  array|Zend_Config $config An array or instance of Zend_Config having configuration data
     * @throws Zend_Db_Adapter_Exception
     */
    public function __construct($config)
    {
        /*
         * Verify that adapter parameters are in an array.
         */
        if (!is_array($config)) {
            /*
             * Convert Zend_Config argument to a plain array.
             */
            if ($config instanceof Zend_Config) {
                $config = $config->toArray();
            } else {
                /**
                 * @see Zend_Db_Adapter_Exception
                 */
                require_once 'Zend/Db/Adapter/Exception.php';
                throw new Zend_Db_Adapter_Exception('Adapter parameters must be in an array or a Zend_Config object');
            }
        }

        $this->_checkRequiredOptions($config);

        $options = array(
            Zend_Db::CASE_FOLDING           => $this->_caseFolding,
            Zend_Db::AUTO_QUOTE_IDENTIFIERS => $this->_autoQuoteIdentifiers,
            Zend_Db::FETCH_MODE             => $this->_fetchMode,
        );
        $driverOptions = array();

        /*
         * normalize the config and merge it with the defaults
         */
        if (array_key_exists('options', $config)) {
            // can't use array_merge() because keys might be integers
            foreach ((array) $config['options'] as $key => $value) {
                $options[$key] = $value;
            }
        }
        if (array_key_exists('driver_options', $config)) {
            if (!empty($config['driver_options'])) {
                // can't use array_merge() because keys might be integers
                foreach ((array) $config['driver_options'] as $key => $value) {
                    $driverOptions[$key] = $value;
                }
            }
        }

        if (!isset($config['charset'])) {
            $config['charset'] = null;
        }

        if (!isset($config['persistent'])) {
            $config['persistent'] = false;
        }

        $this->_config = array_merge($this->_config, $config);
        $this->_config['options'] = $options;
        $this->_config['driver_options'] = $driverOptions;


        // obtain the case setting, if there is one
        if (array_key_exists(Zend_Db::CASE_FOLDING, $options)) {
            $case = (int) $options[Zend_Db::CASE_FOLDING];
            switch ($case) {
                case Zend_Db::CASE_LOWER:
                case Zend_Db::CASE_UPPER:
                case Zend_Db::CASE_NATURAL:
                    $this->_caseFolding = $case;
                    break;
                default:
                    /** @see Zend_Db_Adapter_Exception */
                    require_once 'Zend/Db/Adapter/Exception.php';
                    throw new Zend_Db_Adapter_Exception('Case must be one of the following constants: '
                        . 'Zend_Db::CASE_NATURAL, Zend_Db::CASE_LOWER, Zend_Db::CASE_UPPER');
            }
        }

        if (array_key_exists(Zend_Db::FETCH_MODE, $options)) {
            if (is_string($options[Zend_Db::FETCH_MODE])) {
                $constant = 'Zend_Db::FETCH_' . strtoupper($options[Zend_Db::FETCH_MODE]);
                if(defined($constant)) {
                    $options[Zend_Db::FETCH_MODE] = constant($constant);
                }
            }
            $this->setFetchMode((int) $options[Zend_Db::FETCH_MODE]);
        }

        // obtain quoting property if there is one
        if (array_key_exists(Zend_Db::AUTO_QUOTE_IDENTIFIERS, $options)) {
            $this->_autoQuoteIdentifiers = (bool) $options[Zend_Db::AUTO_QUOTE_IDENTIFIERS];
        }

        // obtain allow serialization property if there is one
        if (array_key_exists(Zend_Db::ALLOW_SERIALIZATION, $options)) {
            $this->_allowSerialization = (bool) $options[Zend_Db::ALLOW_SERIALIZATION];
        }

        // obtain auto reconnect on unserialize property if there is one
        if (array_key_exists(Zend_Db::AUTO_RECONNECT_ON_UNSERIALIZE, $options)) {
            $this->_autoReconnectOnUnserialize = (bool) $options[Zend_Db::AUTO_RECONNECT_ON_UNSERIALIZE];
        }

        // create a profiler object
        $profiler = false;
        if (array_key_exists(Zend_Db::PROFILER, $this->_config)) {
            $profiler = $this->_config[Zend_Db::PROFILER];
            unset($this->_config[Zend_Db::PROFILER]);
        }
        $this->setProfiler($profiler);
    }

    /**
     * Check for config options that are mandatory.
     * Throw exceptions if any are missing.
     *
     * @param array $config
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _checkRequiredOptions(array $config)
    {
        // we need at least a dbname
        if (! array_key_exists('dbname', $config)) {
            /** @see Zend_Db_Adapter_Exception */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'dbname' that names the database instance");
        }

        if (! array_key_exists('password', $config)) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'password' for login credentials");
        }

        if (! array_key_exists('username', $config)) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'username' for login credentials");
        }
    }

    /**
     * Returns the underlying database connection object or resource.
     * If not presently connected, this initiates the connection.
     *
     * @return object|resource|null
     */
    public function getConnection()
    {
        $this->_connect();
        return $this->_connection;
    }

    /**
     * Returns the configuration variables in this adapter.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Set the adapter's profiler object.
     *
     * The argument may be a boolean, an associative array, an instance of
     * Zend_Db_Profiler, or an instance of Zend_Config.
     *
     * A boolean argument sets the profiler to enabled if true, or disabled if
     * false.  The profiler class is the adapter's default profiler class,
     * Zend_Db_Profiler.
     *
     * An instance of Zend_Db_Profiler sets the adapter's instance to that
     * object.  The profiler is enabled and disabled separately.
     *
     * An associative array argument may contain any of the keys 'enabled',
     * 'class', and 'instance'. The 'enabled' and 'instance' keys correspond to the
     * boolean and object types documented above. The 'class' key is used to name a
     * class to use for a custom profiler. The class must be Zend_Db_Profiler or a
     * subclass. The class is instantiated with no constructor arguments. The 'class'
     * option is ignored when the 'instance' option is supplied.
     *
     * An object of type Zend_Config may contain the properties 'enabled', 'class', and
     * 'instance', just as if an associative array had been passed instead.
     *
     * @param  Zend_Db_Profiler|Zend_Config|array|boolean $profiler
     * @return Zend_Db_Adapter_Abstract Provides a fluent interface
     * @throws Zend_Db_Profiler_Exception if the object instance or class specified
     *         is not Zend_Db_Profiler or an extension of that class.
     */
    public function setProfiler($profiler)
    {
        $enabled          = null;
        $profilerClass    = $this->_defaultProfilerClass;
        $profilerInstance = null;

        if ($profilerIsObject = is_object($profiler)) {
            if ($profiler instanceof Zend_Db_Profiler) {
                $profilerInstance = $profiler;
            } else if ($profiler instanceof Zend_Config) {
                $profiler = $profiler->toArray();
            } else {
                /**
                 * @see Zend_Db_Profiler_Exception
                 */
                require_once 'Zend/Db/Profiler/Exception.php';
                throw new Zend_Db_Profiler_Exception('Profiler argument must be an instance of either Zend_Db_Profiler'
                    . ' or Zend_Config when provided as an object');
            }
        }

        if (is_array($profiler)) {
            if (isset($profiler['enabled'])) {
                $enabled = (bool) $profiler['enabled'];
            }
            if (isset($profiler['class'])) {
                $profilerClass = $profiler['class'];
            }
            if (isset($profiler['instance'])) {
                $profilerInstance = $profiler['instance'];
            }
        } else if (!$profilerIsObject) {
            $enabled = (bool) $profiler;
        }

        if ($profilerInstance === null) {
            if (!class_exists($profilerClass)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($profilerClass);
            }
            $profilerInstance = new $profilerClass();
        }

        if (!$profilerInstance instanceof Zend_Db_Profiler) {
            /** @see Zend_Db_Profiler_Exception */
            require_once 'Zend/Db/Profiler/Exception.php';
            throw new Zend_Db_Profiler_Exception('Class ' . get_class($profilerInstance) . ' does not extend '
                . 'Zend_Db_Profiler');
        }

        if (null !== $enabled) {
            $profilerInstance->setEnabled($enabled);
        }

        $this->_profiler = $profilerInstance;

        return $this;
    }


    /**
     * Returns the profiler for this adapter.
     *
     * @return Zend_Db_Profiler
     */
    public function getProfiler()
    {
        return $this->_profiler;
    }

    /**
     * Get the default statement class.
     *
     * @return string
     */
    public function getStatementClass()
    {
        return $this->_defaultStmtClass;
    }

    /**
     * Set the default statement class.
     *
     * @return Zend_Db_Adapter_Abstract Fluent interface
     */
    public function setStatementClass($class)
    {
        $this->_defaultStmtClass = $class;
        return $this;
    }

    /**
     * Prepares and executes an SQL statement with bound data.
     *
     * @param  mixed  $sql  The SQL statement with placeholders.
     *                      May be a string or Zend_Db_Select.
     * @param  mixed  $bind An array of data to bind to the placeholders.
     * @return Zend_Db_Statement_Interface
     */
    public function query($sql, $bind = array())
    {
        // connect to the database if needed
        $this->_connect();

        // is the $sql a Zend_Db_Select object?
        if ($sql instanceof Zend_Db_Select) {
            if (empty($bind)) {
                $bind = $sql->getBind();
            }

            $sql = $sql->assemble();
        }

        // make sure $bind to an array;
        // don't use (array) typecasting because
        // because $bind may be a Zend_Db_Expr object
        if (!is_array($bind)) {
            $bind = array($bind);
        }

        // prepare and execute the statement with profiling
        $stmt = $this->prepare($sql);
        $stmt->execute($bind);

        // return the results embedded in the prepared statement object
        $stmt->setFetchMode($this->_fetchMode);
        return $stmt;
    }

    /**
     * Leave autocommit mode and begin a transaction.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function beginTransaction()
    {
        $this->_connect();
        $q = $this->_profiler->queryStart('begin', Zend_Db_Profiler::TRANSACTION);
        $this->_beginTransaction();
        $this->_profiler->queryEnd($q);
        return $this;
    }

    /**
     * Commit a transaction and return to autocommit mode.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function commit()
    {
        $this->_connect();
        $q = $this->_profiler->queryStart('commit', Zend_Db_Profiler::TRANSACTION);
        $this->_commit();
        $this->_profiler->queryEnd($q);
        return $this;
    }

    /**
     * Roll back a transaction and return to autocommit mode.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function rollBack()
    {
        $this->_connect();
        $q = $this->_profiler->queryStart('rollback', Zend_Db_Profiler::TRANSACTION);
        $this->_rollBack();
        $this->_profiler->queryEnd($q);
        return $this;
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param mixed $table The table to insert data into.
     * @param array $bind Column-value pairs.
     * @return int The number of affected rows.
     * @throws Zend_Db_Adapter_Exception
     */
    public function insert($table, array $bind)
    {
        // extract and quote col names from the array keys
        $cols = array();
        $vals = array();
        $i = 0;
        foreach ($bind as $col => $val) {
            $cols[] = $this->quoteIdentifier($col, true);
            if ($val instanceof Zend_Db_Expr) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                if ($this->supportsParameters('positional')) {
                    $vals[] = '?';
                } else {
                    if ($this->supportsParameters('named')) {
                        unset($bind[$col]);
                        $bind[':col'.$i] = $val;
                        $vals[] = ':col'.$i;
                        $i++;
                    } else {
                        /** @see Zend_Db_Adapter_Exception */
                        require_once 'Zend/Db/Adapter/Exception.php';
                        throw new Zend_Db_Adapter_Exception(get_class($this) ." doesn't support positional or named binding");
                    }
                }
            }
        }

        // build the statement
        $sql = "INSERT INTO "
             . $this->quoteIdentifier($table, true)
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES (' . implode(', ', $vals) . ')';

        // execute the statement and return the number of affected rows
        if ($this->supportsParameters('positional')) {
            $bind = array_values($bind);
        }
        $stmt = $this->query($sql, $bind);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * @param  mixed        $table The table to update.
     * @param  array        $bind  Column-value pairs.
     * @param  mixed        $where UPDATE WHERE clause(s).
     * @return int          The number of affected rows.
     * @throws Zend_Db_Adapter_Exception
     */
    public function update($table, array $bind, $where = '')
    {
        /**
         * Build "col = ?" pairs for the statement,
         * except for Zend_Db_Expr which is treated literally.
         */
        $set = array();
        $i = 0;
        foreach ($bind as $col => $val) {
            if ($val instanceof Zend_Db_Expr) {
                $val = $val->__toString();
                unset($bind[$col]);
            } else {
                if ($this->supportsParameters('positional')) {
                    $val = '?';
                } else {
                    if ($this->supportsParameters('named')) {
                        unset($bind[$col]);
                        $bind[':col'.$i] = $val;
                        $val = ':col'.$i;
                        $i++;
                    } else {
                        /** @see Zend_Db_Adapter_Exception */
                        require_once 'Zend/Db/Adapter/Exception.php';
                        throw new Zend_Db_Adapter_Exception(get_class($this) ." doesn't support positional or named binding");
                    }
                }
            }
            $set[] = $this->quoteIdentifier($col, true) . ' = ' . $val;
        }

        $where = $this->_whereExpr($where);

        /**
         * Build the UPDATE statement
         */
        $sql = "UPDATE "
             . $this->quoteIdentifier($table, true)
             . ' SET ' . implode(', ', $set)
             . (($where) ? " WHERE $where" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        if ($this->supportsParameters('positional')) {
            $stmt = $this->query($sql, array_values($bind));
        } else {
            $stmt = $this->query($sql, $bind);
        }
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Deletes table rows based on a WHERE clause.
     *
     * @param  mixed        $table The table to update.
     * @param  mixed        $where DELETE WHERE clause(s).
     * @return int          The number of affected rows.
     */
    public function delete($table, $where = '')
    {
        $where = $this->_whereExpr($where);

        /**
         * Build the DELETE statement
         */
        $sql = "DELETE FROM "
             . $this->quoteIdentifier($table, true)
             . (($where) ? " WHERE $where" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        $stmt = $this->query($sql);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Convert an array, string, or Zend_Db_Expr object
     * into a string to put in a WHERE clause.
     *
     * @param mixed $where
     * @return string
     */
    protected function _whereExpr($where)
    {
        if (empty($where)) {
            return $where;
        }
        if (!is_array($where)) {
            $where = array($where);
        }
        foreach ($where as $cond => &$term) {
            // is $cond an int? (i.e. Not a condition)
            if (is_int($cond)) {
                // $term is the full condition
                if ($term instanceof Zend_Db_Expr) {
                    $term = $term->__toString();
                }
            } else {
                // $cond is the condition with placeholder,
                // and $term is quoted into the condition
                $term = $this->quoteInto($cond, $term);
            }
            $term = '(' . $term . ')';
        }

        $where = implode(' AND ', $where);
        return $where;
    }

    /**
     * Creates and returns a new Zend_Db_Select object for this adapter.
     *
     * @return Zend_Db_Select
     */
    public function select()
    {
        return new Zend_Db_Select($this);
    }

    /**
     * Get the fetch mode.
     *
     * @return int
     */
    public function getFetchMode()
    {
        return $this->_fetchMode;
    }

    /**
     * Fetches all SQL result rows as a sequential array.
     * Uses the current fetchMode for the adapter.
     *
     * @param string|Zend_Db_Select $sql  An SQL SELECT statement.
     * @param mixed                 $bind Data to bind into SELECT placeholders.
     * @param mixed                 $fetchMode Override current fetch mode.
     * @return array
     */
    public function fetchAll($sql, $bind = array(), $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchAll($fetchMode);
        return $result;
    }

    /**
     * Fetches the first row of the SQL result.
     * Uses the current fetchMode for the adapter.
     *
     * @param string|Zend_Db_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @param mixed                 $fetchMode Override current fetch mode.
     * @return array
     */
    public function fetchRow($sql, $bind = array(), $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetch($fetchMode);
        return $result;
    }

    /**
     * Fetches all SQL result rows as an associative array.
     *
     * The first column is the key, the entire row array is the
     * value.  You should construct the query to be sure that
     * the first column contains unique values, or else
     * rows with duplicate values in the first column will
     * overwrite previous data.
     *
     * @param string|Zend_Db_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return array
     */
    public function fetchAssoc($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $data = array();
        while ($row = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            $tmp = array_values(array_slice($row, 0, 1));
            $data[$tmp[0]] = $row;
        }
        return $data;
    }

    /**
     * Fetches the first column of all SQL result rows as an array.
     *
     * @param string|Zend_Db_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return array
     */
    public function fetchCol($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchAll(Zend_Db::FETCH_COLUMN, 0);
        return $result;
    }

    /**
     * Fetches all SQL result rows as an array of key-value pairs.
     *
     * The first column is the key, the second column is the
     * value.
     *
     * @param string|Zend_Db_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return array
     */
    public function fetchPairs($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $data = array();
        while ($row = $stmt->fetch(Zend_Db::FETCH_NUM)) {
            $data[$row[0]] = $row[1];
        }
        return $data;
    }

    /**
     * Fetches the first column of the first row of the SQL result.
     *
     * @param string|Zend_Db_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return string
     */
    public function fetchOne($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchColumn(0);
        return $result;
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        $this->_connect();

        if ($value instanceof Zend_Db_Select) {
            return '(' . $value->assemble() . ')';
        }

        if ($value instanceof Zend_Db_Expr) {
            return $value->__toString();
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val, $type);
            }
            return implode(', ', $value);
        }

        if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {
            $quotedValue = '0';
            switch ($this->_numericDataTypes[$type]) {
                case Zend_Db::INT_TYPE: // 32-bit integer
                    $quotedValue = (string) intval($value);
                    break;
                case Zend_Db::BIGINT_TYPE: // 64-bit integer
                    // ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
                    // are not supported here, because these are string
                    // literals, not numeric literals.
                    if (preg_match('/^(
                          [+-]?                  # optional sign
                          (?:
                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
                          )
                        )/x',
                        (string) $value, $matches)) {
                        $quotedValue = $matches[1];
                    }
                    break;
                case Zend_Db::FLOAT_TYPE: // float or decimal
                    $quotedValue = sprintf('%F', $value);
            }
            return $quotedValue;
        }

        return $this->_quote($value);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        if ($count === null) {
            return str_replace('?', $this->quote($value, $type), $text);
        } else {
            while ($count > 0) {
                if (strpos($text, '?') !== false) {
                    $text = substr_replace($text, $this->quote($value, $type), strpos($text, '?'), 1);
                }
                --$count;
            }
            return $text;
        }
    }

    /**
     * Quotes an identifier.
     *
     * Accepts a string representing a qualified indentifier. For Example:
     * <code>
     * $adapter->quoteIdentifier('myschema.mytable')
     * </code>
     * Returns: "myschema"."mytable"
     *
     * Or, an array of one or more identifiers that may form a qualified identifier:
     * <code>
     * $adapter->quoteIdentifier(array('myschema','my.table'))
     * </code>
     * Returns: "myschema"."my.table"
     *
     * The actual quote character surrounding the identifiers may vary depending on
     * the adapter.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($ident, $auto=false)
    {
        return $this->_quoteIdentifierAs($ident, null, $auto);
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias An alias for the column.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias, $auto=false)
    {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias An alias for the table.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote an identifier and an optional alias.
     *
     * @param string|array|Zend_Db_Expr $ident The identifier or expression.
     * @param string $alias An optional alias.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @param string $as The string to add between the identifier/expression and the alias.
     * @return string The quoted identifier and alias.
     */
    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ')
    {
        if ($ident instanceof Zend_Db_Expr) {
            $quoted = $ident->__toString();
        } elseif ($ident instanceof Zend_Db_Select) {
            $quoted = '(' . $ident->assemble() . ')';
        } else {
            if (is_string($ident)) {
                $ident = explode('.', $ident);
            }
            if (is_array($ident)) {
                $segments = array();
                foreach ($ident as $segment) {
                    if ($segment instanceof Zend_Db_Expr) {
                        $segments[] = $segment->__toString();
                    } else {
                        $segments[] = $this->_quoteIdentifier($segment, $auto);
                    }
                }
                if ($alias !== null && end($ident) == $alias) {
                    $alias = null;
                }
                $quoted = implode('.', $segments);
            } else {
                $quoted = $this->_quoteIdentifier($ident, $auto);
            }
        }
        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias, $auto);
        }
        return $quoted;
    }

    /**
     * Quote an identifier.
     *
     * @param  string $value The identifier or expression.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string        The quoted identifier and alias.
     */
    protected function _quoteIdentifier($value, $auto=false)
    {
        if ($auto === false || $this->_autoQuoteIdentifiers === true) {
            $q = $this->getQuoteIdentifierSymbol();
            return ($q . str_replace("$q", "$q$q", $value) . $q);
        }
        return $value;
    }

    /**
     * Returns the symbol the adapter uses for delimited identifiers.
     *
     * @return string
     */
    public function getQuoteIdentifierSymbol()
    {
        return '"';
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return string
     */
    public function lastSequenceId($sequenceName)
    {
        return null;
    }

    /**
     * Generate a new value from the specified sequence in the database, and return it.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return string
     */
    public function nextSequenceId($sequenceName)
    {
        return null;
    }

    /**
     * Helper method to change the case of the strings used
     * when returning result sets in FETCH_ASSOC and FETCH_BOTH
     * modes.
     *
     * This is not intended to be used by application code,
     * but the method must be public so the Statement class
     * can invoke it.
     *
     * @param string $key
     * @return string
     */
    public function foldCase($key)
    {
        switch ($this->_caseFolding) {
            case Zend_Db::CASE_LOWER:
                $value = strtolower((string) $key);
                break;
            case Zend_Db::CASE_UPPER:
                $value = strtoupper((string) $key);
                break;
            case Zend_Db::CASE_NATURAL:
            default:
                $value = (string) $key;
        }
        return $value;
    }

    /**
     * called when object is getting serialized
     * This disconnects the DB object that cant be serialized
     *
     * @throws Zend_Db_Adapter_Exception
     * @return array
     */
    public function __sleep()
    {
        if ($this->_allowSerialization == false) {
            /** @see Zend_Db_Adapter_Exception */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception(get_class($this) ." is not allowed to be serialized");
        }
        $this->_connection = false;
        return array_keys(array_diff_key(get_object_vars($this), array('_connection'=>false)));
    }

    /**
     * called when object is getting unserialized
     *
     * @return void
     */
    public function __wakeup()
    {
        if ($this->_autoReconnectOnUnserialize == true) {
            $this->getConnection();
        }
    }

    /**
     * Abstract Methods
     */

    /**
     * Returns a list of the tables in the database.
     *
     * @return array
     */
    abstract public function listTables();

    /**
     * Returns the column descriptions for a table.
     *
     * The return value is an associative array keyed by the column name,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME => string; name of database or schema
     * TABLE_NAME  => string;
     * COLUMN_NAME => string; column name
     * COLUMN_POSITION => number; ordinal position of column in table
     * DATA_TYPE   => string; SQL datatype name of column
     * DEFAULT     => string; default expression of column, null if none
     * NULLABLE    => boolean; true if column can have nulls
     * LENGTH      => number; length of CHAR/VARCHAR
     * SCALE       => number; scale of NUMERIC/DECIMAL
     * PRECISION   => number; precision of NUMERIC/DECIMAL
     * UNSIGNED    => boolean; unsigned property of an integer type
     * PRIMARY     => boolean; true if column is part of the primary key
     * PRIMARY_POSITION => integer; position of column in primary key
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     * @return array
     */
    abstract public function describeTable($tableName, $schemaName = null);

    /**
     * Creates a connection to the database.
     *
     * @return void
     */
    abstract protected function _connect();

    /**
     * Test if a connection is active
     *
     * @return boolean
     */
    abstract public function isConnected();

    /**
     * Force the connection to close.
     *
     * @return void
     */
    abstract public function closeConnection();

    /**
     * Prepare a statement and return a PDOStatement-like object.
     *
     * @param string|Zend_Db_Select $sql SQL query
     * @return Zend_Db_Statement|PDOStatement
     */
    abstract public function prepare($sql);

    /**
     * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
     *
     * As a convention, on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2), this method forms the name of a sequence
     * from the arguments and returns the last id generated by that sequence.
     * On RDBMS brands that support IDENTITY/AUTOINCREMENT columns, this method
     * returns the last value generated for such a column, and the table name
     * argument is disregarded.
     *
     * @param string $tableName   OPTIONAL Name of table.
     * @param string $primaryKey  OPTIONAL Name of primary key column.
     * @return string
     */
    abstract public function lastInsertId($tableName = null, $primaryKey = null);

    /**
     * Begin a transaction.
     */
    abstract protected function _beginTransaction();

    /**
     * Commit a transaction.
     */
    abstract protected function _commit();

    /**
     * Roll-back a transaction.
     */
    abstract protected function _rollBack();

    /**
     * Set the fetch mode.
     *
     * @param integer $mode
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    abstract public function setFetchMode($mode);

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @param mixed $sql
     * @param integer $count
     * @param integer $offset
     * @return string
     */
    abstract public function limit($sql, $count, $offset = 0);

    /**
     * Check if the adapter supports real SQL parameters.
     *
     * @param string $type 'positional' or 'named'
     * @return bool
     */
    abstract public function supportsParameters($type);

    /**
     * Retrieve server version in PHP style
     *
     * @return string
     */
    abstract public function getServerVersion();
}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Select
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Select.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @see Zend_Db_Adapter_Abstract
 */
require_once 'Zend/Db/Adapter/Abstract.php';

/**
 * @see Zend_Db_Expr
 */
require_once 'Zend/Db/Expr.php';


/**
 * Class for SQL SELECT generation and results.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Select
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Select
{

    const DISTINCT       = 'distinct';
    const COLUMNS        = 'columns';
    const FROM           = 'from';
    const UNION          = 'union';
    const WHERE          = 'where';
    const GROUP          = 'group';
    const HAVING         = 'having';
    const ORDER          = 'order';
    const LIMIT_COUNT    = 'limitcount';
    const LIMIT_OFFSET   = 'limitoffset';
    const FOR_UPDATE     = 'forupdate';

    const INNER_JOIN     = 'inner join';
    const LEFT_JOIN      = 'left join';
    const RIGHT_JOIN     = 'right join';
    const FULL_JOIN      = 'full join';
    const CROSS_JOIN     = 'cross join';
    const NATURAL_JOIN   = 'natural join';

    const SQL_WILDCARD   = '*';
    const SQL_SELECT     = 'SELECT';
    const SQL_UNION      = 'UNION';
    const SQL_UNION_ALL  = 'UNION ALL';
    const SQL_FROM       = 'FROM';
    const SQL_WHERE      = 'WHERE';
    const SQL_DISTINCT   = 'DISTINCT';
    const SQL_GROUP_BY   = 'GROUP BY';
    const SQL_ORDER_BY   = 'ORDER BY';
    const SQL_HAVING     = 'HAVING';
    const SQL_FOR_UPDATE = 'FOR UPDATE';
    const SQL_AND        = 'AND';
    const SQL_AS         = 'AS';
    const SQL_OR         = 'OR';
    const SQL_ON         = 'ON';
    const SQL_ASC        = 'ASC';
    const SQL_DESC       = 'DESC';

    /**
     * Bind variables for query
     *
     * @var array
     */
    protected $_bind = array();

    /**
     * Zend_Db_Adapter_Abstract object.
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_adapter;

    /**
     * The initial values for the $_parts array.
     * NOTE: It is important for the 'FOR_UPDATE' part to be last to ensure
     * meximum compatibility with database adapters.
     *
     * @var array
     */
    protected static $_partsInit = array(
        self::DISTINCT     => false,
        self::COLUMNS      => array(),
        self::UNION        => array(),
        self::FROM         => array(),
        self::WHERE        => array(),
        self::GROUP        => array(),
        self::HAVING       => array(),
        self::ORDER        => array(),
        self::LIMIT_COUNT  => null,
        self::LIMIT_OFFSET => null,
        self::FOR_UPDATE   => false
    );

    /**
     * Specify legal join types.
     *
     * @var array
     */
    protected static $_joinTypes = array(
        self::INNER_JOIN,
        self::LEFT_JOIN,
        self::RIGHT_JOIN,
        self::FULL_JOIN,
        self::CROSS_JOIN,
        self::NATURAL_JOIN,
    );

    /**
     * Specify legal union types.
     *
     * @var array
     */
    protected static $_unionTypes = array(
        self::SQL_UNION,
        self::SQL_UNION_ALL
    );

    /**
     * The component parts of a SELECT statement.
     * Initialized to the $_partsInit array in the constructor.
     *
     * @var array
     */
    protected $_parts = array();

    /**
     * Tracks which columns are being select from each table and join.
     *
     * @var array
     */
    protected $_tableCols = array();

    /**
     * Class constructor
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     */
    public function __construct(Zend_Db_Adapter_Abstract $adapter)
    {
        $this->_adapter = $adapter;
        $this->_parts = self::$_partsInit;
    }

    /**
     * Get bind variables
     *
     * @return array
     */
    public function getBind()
    {
        return $this->_bind;
    }

    /**
     * Set bind variables
     *
     * @param mixed $bind
     * @return Zend_Db_Select
     */
    public function bind($bind)
    {
        $this->_bind = $bind;

        return $this;
    }

    /**
     * Makes the query SELECT DISTINCT.
     *
     * @param bool $flag Whether or not the SELECT is DISTINCT (default true).
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function distinct($flag = true)
    {
        $this->_parts[self::DISTINCT] = (bool) $flag;
        return $this;
    }

    /**
     * Adds a FROM table and optional columns to the query.
     *
     * The first parameter $name can be a simple string, in which case the
     * correlation name is generated automatically.  If you want to specify
     * the correlation name, the first parameter must be an associative
     * array in which the key is the correlation name, and the value is
     * the physical table name.  For example, array('alias' => 'table').
     * The correlation name is prepended to all columns fetched for this
     * table.
     *
     * The second parameter can be a single string or Zend_Db_Expr object,
     * or else an array of strings or Zend_Db_Expr objects.
     *
     * The first parameter can be null or an empty string, in which case
     * no correlation name is generated or prepended to the columns named
     * in the second parameter.
     *
     * @param  array|string|Zend_Db_Expr $name The table name or an associative array
     *                                         relating correlation name to table name.
     * @param  array|string|Zend_Db_Expr $cols The columns to select from this table.
     * @param  string $schema The schema name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function from($name, $cols = '*', $schema = null)
    {
        return $this->_join(self::FROM, $name, null, $cols, $schema);
    }

    /**
     * Specifies the columns used in the FROM clause.
     *
     * The parameter can be a single string or Zend_Db_Expr object,
     * or else an array of strings or Zend_Db_Expr objects.
     *
     * @param  array|string|Zend_Db_Expr $cols The columns to select from this table.
     * @param  string $correlationName Correlation name of target table. OPTIONAL
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function columns($cols = '*', $correlationName = null)
    {
        if ($correlationName === null && count($this->_parts[self::FROM])) {
            $correlationNameKeys = array_keys($this->_parts[self::FROM]);
            $correlationName = current($correlationNameKeys);
        }

        if (!array_key_exists($correlationName, $this->_parts[self::FROM])) {
            /**
             * @see Zend_Db_Select_Exception
             */
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("No table has been specified for the FROM clause");
        }

        $this->_tableCols($correlationName, $cols);

        return $this;
    }

    /**
     * Adds a UNION clause to the query.
     *
     * The first parameter has to be an array of Zend_Db_Select or
     * sql query strings.
     *
     * <code>
     * $sql1 = $db->select();
     * $sql2 = "SELECT ...";
     * $select = $db->select()
     *      ->union(array($sql1, $sql2))
     *      ->order("id");
     * </code>
     *
     * @param  array $select Array of select clauses for the union.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function union($select = array(), $type = self::SQL_UNION)
    {
        if (!is_array($select)) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception(
                "union() only accepts an array of Zend_Db_Select instances of sql query strings."
            );
        }

        if (!in_array($type, self::$_unionTypes)) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid union type '{$type}'");
        }

        foreach ($select as $target) {
            $this->_parts[self::UNION][] = array($target, $type);
        }

        return $this;
    }

    /**
     * Adds a JOIN table and columns to the query.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function join($name, $cond, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->joinInner($name, $cond, $cols, $schema);
    }

    /**
     * Add an INNER JOIN table and colums to the query
     * Rows in both tables are matched according to the expression
     * in the $cond argument.  The result set is comprised
     * of all cases where rows from the left table match
     * rows from the right table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinInner($name, $cond, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->_join(self::INNER_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a LEFT OUTER JOIN table and colums to the query
     * All rows from the left operand table are included,
     * matching rows from the right operand table included,
     * and the columns from the right operand table are filled
     * with NULLs if no row exists matching the left table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinLeft($name, $cond, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->_join(self::LEFT_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a RIGHT OUTER JOIN table and colums to the query.
     * Right outer join is the complement of left outer join.
     * All rows from the right operand table are included,
     * matching rows from the left operand table included,
     * and the columns from the left operand table are filled
     * with NULLs if no row exists matching the right table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinRight($name, $cond, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->_join(self::RIGHT_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a FULL OUTER JOIN table and colums to the query.
     * A full outer join is like combining a left outer join
     * and a right outer join.  All rows from both tables are
     * included, paired with each other on the same row of the
     * result set if they satisfy the join condition, and otherwise
     * paired with NULLs in place of columns from the other table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinFull($name, $cond, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->_join(self::FULL_JOIN, $name, $cond, $cols, $schema);
    }

    /**
     * Add a CROSS JOIN table and colums to the query.
     * A cross join is a cartesian product; there is no join condition.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinCross($name, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->_join(self::CROSS_JOIN, $name, null, $cols, $schema);
    }

    /**
     * Add a NATURAL JOIN table and colums to the query.
     * A natural join assumes an equi-join across any column(s)
     * that appear with the same name in both tables.
     * Only natural inner joins are supported by this API,
     * even though SQL permits natural outer joins as well.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinNatural($name, $cols = self::SQL_WILDCARD, $schema = null)
    {
        return $this->_join(self::NATURAL_JOIN, $name, null, $cols, $schema);
    }

    /**
     * Adds a WHERE condition to the query by AND.
     *
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears. Array values are quoted and comma-separated.
     *
     * <code>
     * // simplest but non-secure
     * $select->where("id = $id");
     *
     * // secure (ID is quoted but matched anyway)
     * $select->where('id = ?', $id);
     *
     * // alternatively, with named binding
     * $select->where('id = :id');
     * </code>
     *
     * Note that it is more correct to use named bindings in your
     * queries for values other than strings. When you use named
     * bindings, don't forget to pass the values when actually
     * making a query:
     *
     * <code>
     * $db->fetchAll($select, array('id' => 5));
     * </code>
     *
     * @param string   $cond  The WHERE condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function where($cond, $value = null, $type = null)
    {
        $this->_parts[self::WHERE][] = $this->_where($cond, $value, $type, true);

        return $this;
    }

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * Otherwise identical to where().
     *
     * @param string   $cond  The WHERE condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Select This Zend_Db_Select object.
     *
     * @see where()
     */
    public function orWhere($cond, $value = null, $type = null)
    {
        $this->_parts[self::WHERE][] = $this->_where($cond, $value, $type, false);

        return $this;
    }

    /**
     * Adds grouping to the query.
     *
     * @param  array|string $spec The column(s) to group by.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function group($spec)
    {
        if (!is_array($spec)) {
            $spec = array($spec);
        }

        foreach ($spec as $val) {
            if (preg_match('/\(.*\)/', (string) $val)) {
                $val = new Zend_Db_Expr($val);
            }
            $this->_parts[self::GROUP][] = $val;
        }

        return $this;
    }

    /**
     * Adds a HAVING condition to the query by AND.
     *
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears. See {@link where()} for an example
     *
     * @param string $cond The HAVING condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function having($cond, $value = null, $type = null)
    {
        if ($value !== null) {
            $cond = $this->_adapter->quoteInto($cond, $value, $type);
        }

        if ($this->_parts[self::HAVING]) {
            $this->_parts[self::HAVING][] = self::SQL_AND . " ($cond)";
        } else {
            $this->_parts[self::HAVING][] = "($cond)";
        }

        return $this;
    }

    /**
     * Adds a HAVING condition to the query by OR.
     *
     * Otherwise identical to orHaving().
     *
     * @param string $cond The HAVING condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param int      $type  OPTIONAL The type of the given value
     * @return Zend_Db_Select This Zend_Db_Select object.
     *
     * @see having()
     */
    public function orHaving($cond, $value = null, $type = null)
    {
        if ($value !== null) {
            $cond = $this->_adapter->quoteInto($cond, $value, $type);
        }

        if ($this->_parts[self::HAVING]) {
            $this->_parts[self::HAVING][] = self::SQL_OR . " ($cond)";
        } else {
            $this->_parts[self::HAVING][] = "($cond)";
        }

        return $this;
    }

    /**
     * Adds a row order to the query.
     *
     * @param mixed $spec The column(s) and direction to order by.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function order($spec)
    {
        if (!is_array($spec)) {
            $spec = array($spec);
        }

        // force 'ASC' or 'DESC' on each order spec, default is ASC.
        foreach ($spec as $val) {
            if ($val instanceof Zend_Db_Expr) {
                $expr = $val->__toString();
                if (empty($expr)) {
                    continue;
                }
                $this->_parts[self::ORDER][] = $val;
            } else {
                if (empty($val)) {
                    continue;
                }
                $direction = self::SQL_ASC;
                if (preg_match('/(.*\W)(' . self::SQL_ASC . '|' . self::SQL_DESC . ')\b/si', $val, $matches)) {
                    $val = trim($matches[1]);
                    $direction = $matches[2];
                }
                if (preg_match('/\(.*\)/', $val)) {
                    $val = new Zend_Db_Expr($val);
                }
                $this->_parts[self::ORDER][] = array($val, $direction);
            }
        }

        return $this;
    }

    /**
     * Sets a limit count and offset to the query.
     *
     * @param int $count OPTIONAL The number of rows to return.
     * @param int $offset OPTIONAL Start returning after this many rows.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function limit($count = null, $offset = null)
    {
        $this->_parts[self::LIMIT_COUNT]  = (int) $count;
        $this->_parts[self::LIMIT_OFFSET] = (int) $offset;
        return $this;
    }

    /**
     * Sets the limit and count by page number.
     *
     * @param int $page Limit results to this page number.
     * @param int $rowCount Use this many rows per page.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function limitPage($page, $rowCount)
    {
        $page     = ($page > 0)     ? $page     : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        $this->_parts[self::LIMIT_COUNT]  = (int) $rowCount;
        $this->_parts[self::LIMIT_OFFSET] = (int) $rowCount * ($page - 1);
        return $this;
    }

    /**
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag Whether or not the SELECT is FOR UPDATE (default true).
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function forUpdate($flag = true)
    {
        $this->_parts[self::FOR_UPDATE] = (bool) $flag;
        return $this;
    }

    /**
     * Get part of the structured information for the currect query.
     *
     * @param string $part
     * @return mixed
     * @throws Zend_Db_Select_Exception
     */
    public function getPart($part)
    {
        $part = strtolower($part);
        if (!array_key_exists($part, $this->_parts)) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid Select part '$part'");
        }
        return $this->_parts[$part];
    }

    /**
     * Executes the current select object and returns the result
     *
     * @param integer $fetchMode OPTIONAL
     * @param  mixed  $bind An array of data to bind to the placeholders.
     * @return PDO_Statement|Zend_Db_Statement
     */
    public function query($fetchMode = null, $bind = array())
    {
        if (!empty($bind)) {
            $this->bind($bind);
        }

        $stmt = $this->_adapter->query($this);
        if ($fetchMode == null) {
            $fetchMode = $this->_adapter->getFetchMode();
        }
        $stmt->setFetchMode($fetchMode);
        return $stmt;
    }

    /**
     * Converts this object to an SQL SELECT string.
     *
     * @return string|null This object as a SELECT string. (or null if a string cannot be produced.)
     */
    public function assemble()
    {
        $sql = self::SQL_SELECT;
        foreach (array_keys(self::$_partsInit) as $part) {
            $method = '_render' . ucfirst($part);
            if (method_exists($this, $method)) {
                $sql = $this->$method($sql);
            }
        }
        return $sql;
    }

    /**
     * Clear parts of the Select object, or an individual part.
     *
     * @param string $part OPTIONAL
     * @return Zend_Db_Select
     */
    public function reset($part = null)
    {
        if ($part == null) {
            $this->_parts = self::$_partsInit;
        } else if (array_key_exists($part, self::$_partsInit)) {
            $this->_parts[$part] = self::$_partsInit[$part];
        }
        return $this;
    }

    /**
     * Gets the Zend_Db_Adapter_Abstract for this
     * particular Zend_Db_Select object.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * Populate the {@link $_parts} 'join' key
     *
     * Does the dirty work of populating the join key.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  null|string $type Type of join; inner, left, and null are currently supported
     * @param  array|string|Zend_Db_Expr $name Table name
     * @param  string $cond Join on this condition
     * @param  array|string $cols The columns to select from the joined table
     * @param  string $schema The database name to specify, if any.
     * @return Zend_Db_Select This Zend_Db_Select object
     * @throws Zend_Db_Select_Exception
     */
    protected function _join($type, $name, $cond, $cols, $schema = null)
    {
        if (!in_array($type, self::$_joinTypes) && $type != self::FROM) {
            /**
             * @see Zend_Db_Select_Exception
             */
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid join type '$type'");
        }

        if (count($this->_parts[self::UNION])) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid use of table with " . self::SQL_UNION);
        }

        if (empty($name)) {
            $correlationName = $tableName = '';
        } else if (is_array($name)) {
            // Must be array($correlationName => $tableName) or array($ident, ...)
            foreach ($name as $_correlationName => $_tableName) {
                if (is_string($_correlationName)) {
                    // We assume the key is the correlation name and value is the table name
                    $tableName = $_tableName;
                    $correlationName = $_correlationName;
                } else {
                    // We assume just an array of identifiers, with no correlation name
                    $tableName = $_tableName;
                    $correlationName = $this->_uniqueCorrelation($tableName);
                }
                break;
            }
        } else if ($name instanceof Zend_Db_Expr|| $name instanceof Zend_Db_Select) {
            $tableName = $name;
            $correlationName = $this->_uniqueCorrelation('t');
        } else if (preg_match('/^(.+)\s+AS\s+(.+)$/i', $name, $m)) {
            $tableName = $m[1];
            $correlationName = $m[2];
        } else {
            $tableName = $name;
            $correlationName = $this->_uniqueCorrelation($tableName);
        }

        // Schema from table name overrides schema argument
        if (!is_object($tableName) && false !== strpos($tableName, '.')) {
            list($schema, $tableName) = explode('.', $tableName);
        }

        $lastFromCorrelationName = null;
        if (!empty($correlationName)) {
            if (array_key_exists($correlationName, $this->_parts[self::FROM])) {
                /**
                 * @see Zend_Db_Select_Exception
                 */
                require_once 'Zend/Db/Select/Exception.php';
                throw new Zend_Db_Select_Exception("You cannot define a correlation name '$correlationName' more than once");
            }

            if ($type == self::FROM) {
                // append this from after the last from joinType
                $tmpFromParts = $this->_parts[self::FROM];
                $this->_parts[self::FROM] = array();
                // move all the froms onto the stack
                while ($tmpFromParts) {
                    $currentCorrelationName = key($tmpFromParts);
                    if ($tmpFromParts[$currentCorrelationName]['joinType'] != self::FROM) {
                        break;
                    }
                    $lastFromCorrelationName = $currentCorrelationName;
                    $this->_parts[self::FROM][$currentCorrelationName] = array_shift($tmpFromParts);
                }
            } else {
                $tmpFromParts = array();
            }
            $this->_parts[self::FROM][$correlationName] = array(
                'joinType'      => $type,
                'schema'        => $schema,
                'tableName'     => $tableName,
                'joinCondition' => $cond
                );
            while ($tmpFromParts) {
                $currentCorrelationName = key($tmpFromParts);
                $this->_parts[self::FROM][$currentCorrelationName] = array_shift($tmpFromParts);
            }
        }

        // add to the columns from this joined table
        if ($type == self::FROM && $lastFromCorrelationName == null) {
            $lastFromCorrelationName = true;
        }
        $this->_tableCols($correlationName, $cols, $lastFromCorrelationName);

        return $this;
    }

    /**
     * Handle JOIN... USING... syntax
     *
     * This is functionality identical to the existing JOIN methods, however
     * the join condition can be passed as a single column name. This method
     * then completes the ON condition by using the same field for the FROM
     * table and the JOIN table.
     *
     * <code>
     * $select = $db->select()->from('table1')
     *                        ->joinUsing('table2', 'column1');
     *
     * // SELECT * FROM table1 JOIN table2 ON table1.column1 = table2.column2
     * </code>
     *
     * These joins are called by the developer simply by adding 'Using' to the
     * method name. E.g.
     * * joinUsing
     * * joinInnerUsing
     * * joinFullUsing
     * * joinRightUsing
     * * joinLeftUsing
     *
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function _joinUsing($type, $name, $cond, $cols = '*', $schema = null)
    {
        if (empty($this->_parts[self::FROM])) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("You can only perform a joinUsing after specifying a FROM table");
        }

        $join  = $this->_adapter->quoteIdentifier(key($this->_parts[self::FROM]), true);
        $from  = $this->_adapter->quoteIdentifier($this->_uniqueCorrelation($name), true);

        $cond1 = $from . '.' . $cond;
        $cond2 = $join . '.' . $cond;
        $cond  = $cond1 . ' = ' . $cond2;

        return $this->_join($type, $name, $cond, $cols, $schema);
    }

    /**
     * Generate a unique correlation name
     *
     * @param string|array $name A qualified identifier.
     * @return string A unique correlation name.
     */
    private function _uniqueCorrelation($name)
    {
        if (is_array($name)) {
            $c = end($name);
        } else {
            // Extract just the last name of a qualified table name
            $dot = strrpos($name,'.');
            $c = ($dot === false) ? $name : substr($name, $dot+1);
        }
        for ($i = 2; array_key_exists($c, $this->_parts[self::FROM]); ++$i) {
            $c = $name . '_' . (string) $i;
        }
        return $c;
    }

    /**
     * Adds to the internal table-to-column mapping array.
     *
     * @param  string $tbl The table/join the columns come from.
     * @param  array|string $cols The list of columns; preferably as
     * an array, but possibly as a string containing one column.
     * @param  bool|string True if it should be prepended, a correlation name if it should be inserted
     * @return void
     */
    protected function _tableCols($correlationName, $cols, $afterCorrelationName = null)
    {
        if (!is_array($cols)) {
            $cols = array($cols);
        }

        if ($correlationName == null) {
            $correlationName = '';
        }

        $columnValues = array();

        foreach (array_filter($cols) as $alias => $col) {
            $currentCorrelationName = $correlationName;
            if (is_string($col)) {
                // Check for a column matching "<column> AS <alias>" and extract the alias name
                if (preg_match('/^(.+)\s+' . self::SQL_AS . '\s+(.+)$/i', $col, $m)) {
                    $col = $m[1];
                    $alias = $m[2];
                }
                // Check for columns that look like functions and convert to Zend_Db_Expr
                if (preg_match('/\(.*\)/', $col)) {
                    $col = new Zend_Db_Expr($col);
                } elseif (preg_match('/(.+)\.(.+)/', $col, $m)) {
                    $currentCorrelationName = $m[1];
                    $col = $m[2];
                }
            }
            $columnValues[] = array($currentCorrelationName, $col, is_string($alias) ? $alias : null);
        }

        if ($columnValues) {

            // should we attempt to prepend or insert these values?
            if ($afterCorrelationName === true || is_string($afterCorrelationName)) {
                $tmpColumns = $this->_parts[self::COLUMNS];
                $this->_parts[self::COLUMNS] = array();
            } else {
                $tmpColumns = array();
            }

            // find the correlation name to insert after
            if (is_string($afterCorrelationName)) {
                while ($tmpColumns) {
                    $this->_parts[self::COLUMNS][] = $currentColumn = array_shift($tmpColumns);
                    if ($currentColumn[0] == $afterCorrelationName) {
                        break;
                    }
                }
            }

            // apply current values to current stack
            foreach ($columnValues as $columnValue) {
                array_push($this->_parts[self::COLUMNS], $columnValue);
            }

            // finish ensuring that all previous values are applied (if they exist)
            while ($tmpColumns) {
                array_push($this->_parts[self::COLUMNS], array_shift($tmpColumns));
            }
        }
    }

    /**
     * Internal function for creating the where clause
     *
     * @param string   $condition
     * @param mixed    $value  optional
     * @param string   $type   optional
     * @param boolean  $bool  true = AND, false = OR
     * @return string  clause
     */
    protected function _where($condition, $value = null, $type = null, $bool = true)
    {
        if (count($this->_parts[self::UNION])) {
            require_once 'Zend/Db/Select/Exception.php';
            throw new Zend_Db_Select_Exception("Invalid use of where clause with " . self::SQL_UNION);
        }

        if ($value !== null) {
            $condition = $this->_adapter->quoteInto($condition, $value, $type);
        }

        $cond = "";
        if ($this->_parts[self::WHERE]) {
            if ($bool === true) {
                $cond = self::SQL_AND . ' ';
            } else {
                $cond = self::SQL_OR . ' ';
            }
        }

        return $cond . "($condition)";
    }

    /**
     * @return array
     */
    protected function _getDummyTable()
    {
        return array();
    }

    /**
     * Return a quoted schema name
     *
     * @param string   $schema  The schema name OPTIONAL
     * @return string|null
     */
    protected function _getQuotedSchema($schema = null)
    {
        if ($schema === null) {
            return null;
        }
        return $this->_adapter->quoteIdentifier($schema, true) . '.';
    }

    /**
     * Return a quoted table name
     *
     * @param string   $tableName        The table name
     * @param string   $correlationName  The correlation name OPTIONAL
     * @return string
     */
    protected function _getQuotedTable($tableName, $correlationName = null)
    {
        return $this->_adapter->quoteTableAs($tableName, $correlationName, true);
    }

    /**
     * Render DISTINCT clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderDistinct($sql)
    {
        if ($this->_parts[self::DISTINCT]) {
            $sql .= ' ' . self::SQL_DISTINCT;
        }

        return $sql;
    }

    /**
     * Render DISTINCT clause
     *
     * @param string   $sql SQL query
     * @return string|null
     */
    protected function _renderColumns($sql)
    {
        if (!count($this->_parts[self::COLUMNS])) {
            return null;
        }

        $columns = array();
        foreach ($this->_parts[self::COLUMNS] as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;
            if ($column instanceof Zend_Db_Expr) {
                $columns[] = $this->_adapter->quoteColumnAs($column, $alias, true);
            } else {
                if ($column == self::SQL_WILDCARD) {
                    $column = new Zend_Db_Expr(self::SQL_WILDCARD);
                    $alias = null;
                }
                if (empty($correlationName)) {
                    $columns[] = $this->_adapter->quoteColumnAs($column, $alias, true);
                } else {
                    $columns[] = $this->_adapter->quoteColumnAs(array($correlationName, $column), $alias, true);
                }
            }
        }

        return $sql .= ' ' . implode(', ', $columns);
    }

    /**
     * Render FROM clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderFrom($sql)
    {
        /*
         * If no table specified, use RDBMS-dependent solution
         * for table-less query.  e.g. DUAL in Oracle.
         */
        if (empty($this->_parts[self::FROM])) {
            $this->_parts[self::FROM] = $this->_getDummyTable();
        }

        $from = array();

        foreach ($this->_parts[self::FROM] as $correlationName => $table) {
            $tmp = '';

            $joinType = ($table['joinType'] == self::FROM) ? self::INNER_JOIN : $table['joinType'];

            // Add join clause (if applicable)
            if (! empty($from)) {
                $tmp .= ' ' . strtoupper($joinType) . ' ';
            }

            $tmp .= $this->_getQuotedSchema($table['schema']);
            $tmp .= $this->_getQuotedTable($table['tableName'], $correlationName);

            // Add join conditions (if applicable)
            if (!empty($from) && ! empty($table['joinCondition'])) {
                $tmp .= ' ' . self::SQL_ON . ' ' . $table['joinCondition'];
            }

            // Add the table name and condition add to the list
            $from[] = $tmp;
        }

        // Add the list of all joins
        if (!empty($from)) {
            $sql .= ' ' . self::SQL_FROM . ' ' . implode("\n", $from);
        }

        return $sql;
    }

    /**
     * Render UNION query
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderUnion($sql)
    {
        if ($this->_parts[self::UNION]) {
            $parts = count($this->_parts[self::UNION]);
            foreach ($this->_parts[self::UNION] as $cnt => $union) {
                list($target, $type) = $union;
                if ($target instanceof Zend_Db_Select) {
                    $target = $target->assemble();
                }
                $sql .= $target;
                if ($cnt < $parts - 1) {
                    $sql .= ' ' . $type . ' ';
                }
            }
        }

        return $sql;
    }

    /**
     * Render WHERE clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderWhere($sql)
    {
        if ($this->_parts[self::FROM] && $this->_parts[self::WHERE]) {
            $sql .= ' ' . self::SQL_WHERE . ' ' .  implode(' ', $this->_parts[self::WHERE]);
        }

        return $sql;
    }

    /**
     * Render GROUP clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderGroup($sql)
    {
        if ($this->_parts[self::FROM] && $this->_parts[self::GROUP]) {
            $group = array();
            foreach ($this->_parts[self::GROUP] as $term) {
                $group[] = $this->_adapter->quoteIdentifier($term, true);
            }
            $sql .= ' ' . self::SQL_GROUP_BY . ' ' . implode(",\n\t", $group);
        }

        return $sql;
    }

    /**
     * Render HAVING clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderHaving($sql)
    {
        if ($this->_parts[self::FROM] && $this->_parts[self::HAVING]) {
            $sql .= ' ' . self::SQL_HAVING . ' ' . implode(' ', $this->_parts[self::HAVING]);
        }

        return $sql;
    }

    /**
     * Render ORDER clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderOrder($sql)
    {
        if ($this->_parts[self::ORDER]) {
            $order = array();
            foreach ($this->_parts[self::ORDER] as $term) {
                if (is_array($term)) {
                    if(is_numeric($term[0]) && strval(intval($term[0])) == $term[0]) {
                        $order[] = (int)trim($term[0]) . ' ' . $term[1];
                    } else {
                        $order[] = $this->_adapter->quoteIdentifier($term[0], true) . ' ' . $term[1];
                    }
                } else if (is_numeric($term) && strval(intval($term)) == $term) {
                    $order[] = (int)trim($term);
                } else {
                    $order[] = $this->_adapter->quoteIdentifier($term, true);
                }
            }
            $sql .= ' ' . self::SQL_ORDER_BY . ' ' . implode(', ', $order);
        }

        return $sql;
    }

    /**
     * Render LIMIT OFFSET clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderLimitoffset($sql)
    {
        $count = 0;
        $offset = 0;

        if (!empty($this->_parts[self::LIMIT_OFFSET])) {
            $offset = (int) $this->_parts[self::LIMIT_OFFSET];
            $count = PHP_INT_MAX;
        }

        if (!empty($this->_parts[self::LIMIT_COUNT])) {
            $count = (int) $this->_parts[self::LIMIT_COUNT];
        }

        /*
         * Add limits clause
         */
        if ($count > 0) {
            $sql = trim($this->_adapter->limit($sql, $count, $offset));
        }

        return $sql;
    }

    /**
     * Render FOR UPDATE clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderForupdate($sql)
    {
        if ($this->_parts[self::FOR_UPDATE]) {
            $sql .= ' ' . self::SQL_FOR_UPDATE;
        }

        return $sql;
    }

    /**
     * Turn magic function calls into non-magic function calls
     * for joinUsing syntax
     *
     * @param string $method
     * @param array $args OPTIONAL Zend_Db_Table_Select query modifier
     * @return Zend_Db_Select
     * @throws Zend_Db_Select_Exception If an invalid method is called.
     */
    public function __call($method, array $args)
    {
        $matches = array();

        /**
         * Recognize methods for Has-Many cases:
         * findParent<Class>()
         * findParent<Class>By<Rule>()
         * Use the non-greedy pattern repeat modifier e.g. \w+?
         */
        if (preg_match('/^join([a-zA-Z]*?)Using$/', $method, $matches)) {
            $type = strtolower($matches[1]);
            if ($type) {
                $type .= ' join';
                if (!in_array($type, self::$_joinTypes)) {
                    require_once 'Zend/Db/Select/Exception.php';
                    throw new Zend_Db_Select_Exception("Unrecognized method '$method()'");
                }
                if (in_array($type, array(self::CROSS_JOIN, self::NATURAL_JOIN))) {
                    require_once 'Zend/Db/Select/Exception.php';
                    throw new Zend_Db_Select_Exception("Cannot perform a joinUsing with method '$method()'");
                }
            } else {
                $type = self::INNER_JOIN;
            }
            array_unshift($args, $type);
            return call_user_func_array(array($this, '_joinUsing'), $args);
        }

        require_once 'Zend/Db/Select/Exception.php';
        throw new Zend_Db_Select_Exception("Unrecognized method '$method()'");
    }

    /**
     * Implements magic method.
     *
     * @return string This object as a SELECT string.
     */
    public function __toString()
    {
        try {
            $sql = $this->assemble();
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            $sql = '';
        }
        return (string)$sql;
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Expr
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Expr.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * Class for SQL SELECT fragments.
 *
 * This class simply holds a string, so that fragments of SQL statements can be
 * distinguished from identifiers and values that should be implicitly quoted
 * when interpolated into SQL statements.
 *
 * For example, when specifying a primary key value when inserting into a new
 * row, some RDBMS brands may require you to use an expression to generate the
 * new value of a sequence.  If this expression is treated as an identifier,
 * it will be quoted and the expression will not be evaluated.  Another example
 * is that you can use Zend_Db_Expr in the Zend_Db_Select::order() method to
 * order by an expression instead of simply a column name.
 *
 * The way this works is that in each context in which a column name can be
 * specified to methods of Zend_Db classes, if the value is an instance of
 * Zend_Db_Expr instead of a plain string, then the expression is not quoted.
 * If it is a plain string, it is assumed to be a plain column name.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Expr
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Expr
{
    /**
     * Storage for the SQL expression.
     *
     * @var string
     */
    protected $_expression;

    /**
     * Instantiate an expression, which is just a string stored as
     * an instance member variable.
     *
     * @param string $expression The string containing a SQL expression.
     */
    public function __construct($expression)
    {
        $this->_expression = (string) $expression;
    }

    /**
     * @return string The string of the SQL expression stored in this object.
     */
    public function __toString()
    {
        return $this->_expression;
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Pdo.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Db_Statement
 */
require_once 'Zend/Db/Statement.php';

/**
 * Proxy class to wrap a PDOStatement object.
 * Matches the interface of PDOStatement.  All methods simply proxy to the
 * matching method in PDOStatement.  PDOExceptions thrown by PDOStatement
 * are re-thrown as Zend_Db_Statement_Exception.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Statement_Pdo extends Zend_Db_Statement implements IteratorAggregate
{

    /**
     * @var int
     */
    protected $_fetchMode = PDO::FETCH_ASSOC;

    /**
     * Prepare a string SQL statement and create a statement object.
     *
     * @param string $sql
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    protected function _prepare($sql)
    {
        try {
            $this->_stmt = $this->_adapter->getConnection()->prepare($sql);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Bind a column of the statement result set to a PHP variable.
     *
     * @param string $column Name the column in the result set, either by
     *                       position or by name.
     * @param mixed  $param  Reference to the PHP variable containing the value.
     * @param mixed  $type   OPTIONAL
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function bindColumn($column, &$param, $type = null)
    {
        try {
            if ($type === null) {
                return $this->_stmt->bindColumn($column, $param);
            } else {
                return $this->_stmt->bindColumn($column, $param, $type);
            }
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $variable  Reference to PHP variable containing the value.
     * @param mixed $type      OPTIONAL Datatype of SQL parameter.
     * @param mixed $length    OPTIONAL Length of SQL parameter.
     * @param mixed $options   OPTIONAL Other options.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    protected function _bindParam($parameter, &$variable, $type = null, $length = null, $options = null)
    {
        try {
            if ($type === null) {
                if (is_bool($variable)) {
                    $type = PDO::PARAM_BOOL;
                } elseif ($variable === null) {
                    $type = PDO::PARAM_NULL;
                } elseif (is_integer($variable)) {
                    $type = PDO::PARAM_INT;
                } else {
                    $type = PDO::PARAM_STR;
                }
            }
            return $this->_stmt->bindParam($parameter, $variable, $type, $length, $options);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Binds a value to a parameter.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $value     Scalar value to bind to the parameter.
     * @param mixed $type      OPTIONAL Datatype of the parameter.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function bindValue($parameter, $value, $type = null)
    {
        if (is_string($parameter) && $parameter[0] != ':') {
            $parameter = ":$parameter";
        }

        $this->_bindParam[$parameter] = $value;

        try {
            if ($type === null) {
                return $this->_stmt->bindValue($parameter, $value);
            } else {
                return $this->_stmt->bindValue($parameter, $value, $type);
            }
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Closes the cursor, allowing the statement to be executed again.
     *
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function closeCursor()
    {
        try {
            return $this->_stmt->closeCursor();
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the number of columns in the result set.
     * Returns null if the statement has no result set metadata.
     *
     * @return int The number of columns.
     * @throws Zend_Db_Statement_Exception
     */
    public function columnCount()
    {
        try {
            return $this->_stmt->columnCount();
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieves the error code, if any, associated with the last operation on
     * the statement handle.
     *
     * @return string error code.
     * @throws Zend_Db_Statement_Exception
     */
    public function errorCode()
    {
        try {
            return $this->_stmt->errorCode();
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieves an array of error information, if any, associated with the
     * last operation on the statement handle.
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function errorInfo()
    {
        try {
            return $this->_stmt->errorInfo();
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Executes a prepared statement.
     *
     * @param array $params OPTIONAL Values to bind to parameter placeholders.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function _execute(array $params = null)
    {
        try {
            if ($params !== null) {
                return $this->_stmt->execute($params);
            } else {
                return $this->_stmt->execute();
            }
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Fetches a row from the result set.
     *
     * @param int $style  OPTIONAL Fetch mode for this fetch operation.
     * @param int $cursor OPTIONAL Absolute, relative, or other.
     * @param int $offset OPTIONAL Number for absolute or relative cursors.
     * @return mixed Array, object, or scalar depending on fetch mode.
     * @throws Zend_Db_Statement_Exception
     */
    public function fetch($style = null, $cursor = null, $offset = null)
    {
        if ($style === null) {
            $style = $this->_fetchMode;
        }
        try {
            return $this->_stmt->fetch($style, $cursor, $offset);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Required by IteratorAggregate interface
     *
     * @return IteratorIterator
     */
    public function getIterator()
    {
        return new IteratorIterator($this->_stmt);
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int $style OPTIONAL Fetch mode.
     * @param int $col   OPTIONAL Column number, if fetch mode is by column.
     * @return array Collection of rows, each in a format by the fetch mode.
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchAll($style = null, $col = null)
    {
        if ($style === null) {
            $style = $this->_fetchMode;
        }
        try {
            if ($style == PDO::FETCH_COLUMN) {
                if ($col === null) {
                    $col = 0;
                }
                return $this->_stmt->fetchAll($style, $col);
            } else {
                return $this->_stmt->fetchAll($style);
            }
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $col OPTIONAL Position of the column to fetch.
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchColumn($col = 0)
    {
        try {
            return $this->_stmt->fetchColumn($col);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $class  OPTIONAL Name of the class to create.
     * @param array  $config OPTIONAL Constructor arguments for the class.
     * @return mixed One object instance of the specified class.
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchObject($class = 'stdClass', array $config = array())
    {
        try {
            return $this->_stmt->fetchObject($class, $config);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieve a statement attribute.
     *
     * @param integer $key Attribute name.
     * @return mixed      Attribute value.
     * @throws Zend_Db_Statement_Exception
     */
    public function getAttribute($key)
    {
        try {
            return $this->_stmt->getAttribute($key);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns metadata for a column in a result set.
     *
     * @param int $column
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     */
    public function getColumnMeta($column)
    {
        try {
            return $this->_stmt->getColumnMeta($column);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieves the next rowset (result set) for a SQL statement that has
     * multiple result sets.  An example is a stored procedure that returns
     * the results of multiple queries.
     *
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function nextRowset()
    {
        try {
            return $this->_stmt->nextRowset();
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the number of rows affected by the execution of the
     * last INSERT, DELETE, or UPDATE statement executed by this
     * statement object.
     *
     * @return int     The number of rows affected.
     * @throws Zend_Db_Statement_Exception
     */
    public function rowCount()
    {
        try {
            return $this->_stmt->rowCount();
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Set a statement attribute.
     *
     * @param string $key Attribute name.
     * @param mixed  $val Attribute value.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function setAttribute($key, $val)
    {
        try {
            return $this->_stmt->setAttribute($key, $val);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Set the default fetch mode for this statement.
     *
     * @param int   $mode The fetch mode.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function setFetchMode($mode)
    {
        $this->_fetchMode = $mode;
        try {
            return $this->_stmt->setFetchMode($mode);
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Statement.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Db
 */
require_once 'Zend/Db.php';

/**
 * @see Zend_Db_Statement_Interface
 */
require_once 'Zend/Db/Statement/Interface.php';

/**
 * Abstract class to emulate a PDOStatement for native database adapters.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Statement implements Zend_Db_Statement_Interface
{

    /**
     * @var resource|object The driver level statement object/resource
     */
    protected $_stmt = null;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_adapter = null;

    /**
     * The current fetch mode.
     *
     * @var integer
     */
    protected $_fetchMode = Zend_Db::FETCH_ASSOC;

    /**
     * Attributes.
     *
     * @var array
     */
    protected $_attribute = array();

    /**
     * Column result bindings.
     *
     * @var array
     */
    protected $_bindColumn = array();

    /**
     * Query parameter bindings; covers bindParam() and bindValue().
     *
     * @var array
     */
    protected $_bindParam = array();

    /**
     * SQL string split into an array at placeholders.
     *
     * @var array
     */
    protected $_sqlSplit = array();

    /**
     * Parameter placeholders in the SQL string by position in the split array.
     *
     * @var array
     */
    protected $_sqlParam = array();

    /**
     * @var Zend_Db_Profiler_Query
     */
    protected $_queryId = null;

    /**
     * Constructor for a statement.
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param mixed $sql Either a string or Zend_Db_Select.
     */
    public function __construct($adapter, $sql)
    {
        $this->_adapter = $adapter;
        if ($sql instanceof Zend_Db_Select) {
            $sql = $sql->assemble();
        }
        $this->_parseParameters($sql);
        $this->_prepare($sql);

        $this->_queryId = $this->_adapter->getProfiler()->queryStart($sql);
    }

    /**
     * Internal method called by abstract statment constructor to setup
     * the driver level statement
     *
     * @return void
     */
    protected function _prepare($sql)
    {
        return;
    }

    /**
     * @param string $sql
     * @return void
     */
    protected function _parseParameters($sql)
    {
        $sql = $this->_stripQuoted($sql);

        // split into text and params
        $this->_sqlSplit = preg_split('/(\?|\:[a-zA-Z0-9_]+)/',
            $sql, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

        // map params
        $this->_sqlParam = array();
        foreach ($this->_sqlSplit as $key => $val) {
            if ($val == '?') {
                if ($this->_adapter->supportsParameters('positional') === false) {
                    /**
                     * @see Zend_Db_Statement_Exception
                     */
                    require_once 'Zend/Db/Statement/Exception.php';
                    throw new Zend_Db_Statement_Exception("Invalid bind-variable position '$val'");
                }
            } else if ($val[0] == ':') {
                if ($this->_adapter->supportsParameters('named') === false) {
                    /**
                     * @see Zend_Db_Statement_Exception
                     */
                    require_once 'Zend/Db/Statement/Exception.php';
                    throw new Zend_Db_Statement_Exception("Invalid bind-variable name '$val'");
                }
            }
            $this->_sqlParam[] = $val;
        }

        // set up for binding
        $this->_bindParam = array();
    }

    /**
     * Remove parts of a SQL string that contain quoted strings
     * of values or identifiers.
     *
     * @param string $sql
     * @return string
     */
    protected function _stripQuoted($sql)
    {
        // get the character for delimited id quotes,
        // this is usually " but in MySQL is `
        $d = $this->_adapter->quoteIdentifier('a');
        $d = $d[0];

        // get the value used as an escaped delimited id quote,
        // e.g. \" or "" or \`
        $de = $this->_adapter->quoteIdentifier($d);
        $de = substr($de, 1, 2);
        $de = str_replace('\\', '\\\\', $de);

        // get the character for value quoting
        // this should be '
        $q = $this->_adapter->quote('a');
        $q = $q[0];

        // get the value used as an escaped quote,
        // e.g. \' or ''
        $qe = $this->_adapter->quote($q);
        $qe = substr($qe, 1, 2);
        $qe = str_replace('\\', '\\\\', $qe);

        // get a version of the SQL statement with all quoted
        // values and delimited identifiers stripped out
        // remove "foo\"bar"
        $sql = preg_replace("/$q($qe|\\\\{2}|[^$q])*$q/", '', $sql);
        // remove 'foo\'bar'
        if (!empty($q)) {
            $sql = preg_replace("/$q($qe|[^$q])*$q/", '', $sql);
        }

        return $sql;
    }

    /**
     * Bind a column of the statement result set to a PHP variable.
     *
     * @param string $column Name the column in the result set, either by
     *                       position or by name.
     * @param mixed  $param  Reference to the PHP variable containing the value.
     * @param mixed  $type   OPTIONAL
     * @return bool
     */
    public function bindColumn($column, &$param, $type = null)
    {
        $this->_bindColumn[$column] =& $param;
        return true;
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $variable  Reference to PHP variable containing the value.
     * @param mixed $type      OPTIONAL Datatype of SQL parameter.
     * @param mixed $length    OPTIONAL Length of SQL parameter.
     * @param mixed $options   OPTIONAL Other options.
     * @return bool
     */
    public function bindParam($parameter, &$variable, $type = null, $length = null, $options = null)
    {
        if (!is_int($parameter) && !is_string($parameter)) {
            /**
             * @see Zend_Db_Statement_Exception
             */
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception('Invalid bind-variable position');
        }

        $position = null;
        if (($intval = (int) $parameter) > 0 && $this->_adapter->supportsParameters('positional')) {
            if ($intval >= 1 || $intval <= count($this->_sqlParam)) {
                $position = $intval;
            }
        } else if ($this->_adapter->supportsParameters('named')) {
            if ($parameter[0] != ':') {
                $parameter = ':' . $parameter;
            }
            if (in_array($parameter, $this->_sqlParam) !== false) {
                $position = $parameter;
            }
        }

        if ($position === null) {
            /**
             * @see Zend_Db_Statement_Exception
             */
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception("Invalid bind-variable position '$parameter'");
        }

        // Finally we are assured that $position is valid
        $this->_bindParam[$position] =& $variable;
        return $this->_bindParam($position, $variable, $type, $length, $options);
    }

    /**
     * Binds a value to a parameter.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $value     Scalar value to bind to the parameter.
     * @param mixed $type      OPTIONAL Datatype of the parameter.
     * @return bool
     */
    public function bindValue($parameter, $value, $type = null)
    {
        return $this->bindParam($parameter, $value, $type);
    }

    /**
     * Executes a prepared statement.
     *
     * @param array $params OPTIONAL Values to bind to parameter placeholders.
     * @return bool
     */
    public function execute(array $params = null)
    {
        /*
         * Simple case - no query profiler to manage.
         */
        if ($this->_queryId === null) {
            return $this->_execute($params);
        }

        /*
         * Do the same thing, but with query profiler
         * management before and after the execute.
         */
        $prof = $this->_adapter->getProfiler();
        $qp = $prof->getQueryProfile($this->_queryId);
        if ($qp->hasEnded()) {
            $this->_queryId = $prof->queryClone($qp);
            $qp = $prof->getQueryProfile($this->_queryId);
        }
        if ($params !== null) {
            $qp->bindParams($params);
        } else {
            $qp->bindParams($this->_bindParam);
        }
        $qp->start($this->_queryId);

        $retval = $this->_execute($params);

        $prof->queryEnd($this->_queryId);

        return $retval;
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int $style OPTIONAL Fetch mode.
     * @param int $col   OPTIONAL Column number, if fetch mode is by column.
     * @return array Collection of rows, each in a format by the fetch mode.
     */
    public function fetchAll($style = null, $col = null)
    {
        $data = array();
        if ($style === Zend_Db::FETCH_COLUMN && $col === null) {
            $col = 0;
        }
        if ($col === null) {
            while ($row = $this->fetch($style)) {
                $data[] = $row;
            }
        } else {
            while (false !== ($val = $this->fetchColumn($col))) {
                $data[] = $val;
            }
        }
        return $data;
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $col OPTIONAL Position of the column to fetch.
     * @return string One value from the next row of result set, or false.
     */
    public function fetchColumn($col = 0)
    {
        $data = array();
        $col = (int) $col;
        $row = $this->fetch(Zend_Db::FETCH_NUM);
        if (!is_array($row)) {
            return false;
        }
        return $row[$col];
    }

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $class  OPTIONAL Name of the class to create.
     * @param array  $config OPTIONAL Constructor arguments for the class.
     * @return mixed One object instance of the specified class, or false.
     */
    public function fetchObject($class = 'stdClass', array $config = array())
    {
        $obj = new $class($config);
        $row = $this->fetch(Zend_Db::FETCH_ASSOC);
        if (!is_array($row)) {
            return false;
        }
        foreach ($row as $key => $val) {
            $obj->$key = $val;
        }
        return $obj;
    }

    /**
     * Retrieve a statement attribute.
     *
     * @param string $key Attribute name.
     * @return mixed      Attribute value.
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->_attribute)) {
            return $this->_attribute[$key];
        }
    }

    /**
     * Set a statement attribute.
     *
     * @param string $key Attribute name.
     * @param mixed  $val Attribute value.
     * @return bool
     */
    public function setAttribute($key, $val)
    {
        $this->_attribute[$key] = $val;
    }

    /**
     * Set the default fetch mode for this statement.
     *
     * @param int   $mode The fetch mode.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function setFetchMode($mode)
    {
        switch ($mode) {
            case Zend_Db::FETCH_NUM:
            case Zend_Db::FETCH_ASSOC:
            case Zend_Db::FETCH_BOTH:
            case Zend_Db::FETCH_OBJ:
                $this->_fetchMode = $mode;
                break;
            case Zend_Db::FETCH_BOUND:
            default:
                $this->closeCursor();
                /**
                 * @see Zend_Db_Statement_Exception
                 */
                require_once 'Zend/Db/Statement/Exception.php';
                throw new Zend_Db_Statement_Exception('invalid fetch mode');
                break;
        }
    }

    /**
     * Helper function to map retrieved row
     * to bound column variables
     *
     * @param array $row
     * @return bool True
     */
    public function _fetchBound($row)
    {
        foreach ($row as $key => $value) {
            // bindColumn() takes 1-based integer positions
            // but fetch() returns 0-based integer indexes
            if (is_int($key)) {
                $key++;
            }
            // set results only to variables that were bound previously
            if (isset($this->_bindColumn[$key])) {
                $this->_bindColumn[$key] = $value;
            }
        }
        return true;
    }

    /**
     * Gets the Zend_Db_Adapter_Abstract for this
     * particular Zend_Db_Statement object.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * Gets the resource or object setup by the
     * _parse
     * @return unknown_type
     */
    public function getDriverStatement()
    {
        return $this->_stmt;
    }
}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Emulates a PDOStatement for native database adapters.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Db_Statement_Interface
{

    /**
     * Bind a column of the statement result set to a PHP variable.
     *
     * @param string $column Name the column in the result set, either by
     *                       position or by name.
     * @param mixed  $param  Reference to the PHP variable containing the value.
     * @param mixed  $type   OPTIONAL
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function bindColumn($column, &$param, $type = null);

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $variable  Reference to PHP variable containing the value.
     * @param mixed $type      OPTIONAL Datatype of SQL parameter.
     * @param mixed $length    OPTIONAL Length of SQL parameter.
     * @param mixed $options   OPTIONAL Other options.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function bindParam($parameter, &$variable, $type = null, $length = null, $options = null);

    /**
     * Binds a value to a parameter.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $value     Scalar value to bind to the parameter.
     * @param mixed $type      OPTIONAL Datatype of the parameter.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function bindValue($parameter, $value, $type = null);

    /**
     * Closes the cursor, allowing the statement to be executed again.
     *
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function closeCursor();

    /**
     * Returns the number of columns in the result set.
     * Returns null if the statement has no result set metadata.
     *
     * @return int The number of columns.
     * @throws Zend_Db_Statement_Exception
     */
    public function columnCount();

    /**
     * Retrieves the error code, if any, associated with the last operation on
     * the statement handle.
     *
     * @return string error code.
     * @throws Zend_Db_Statement_Exception
     */
    public function errorCode();

    /**
     * Retrieves an array of error information, if any, associated with the
     * last operation on the statement handle.
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function errorInfo();

    /**
     * Executes a prepared statement.
     *
     * @param array $params OPTIONAL Values to bind to parameter placeholders.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function execute(array $params = array());

    /**
     * Fetches a row from the result set.
     *
     * @param int $style  OPTIONAL Fetch mode for this fetch operation.
     * @param int $cursor OPTIONAL Absolute, relative, or other.
     * @param int $offset OPTIONAL Number for absolute or relative cursors.
     * @return mixed Array, object, or scalar depending on fetch mode.
     * @throws Zend_Db_Statement_Exception
     */
    public function fetch($style = null, $cursor = null, $offset = null);

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int $style OPTIONAL Fetch mode.
     * @param int $col   OPTIONAL Column number, if fetch mode is by column.
     * @return array Collection of rows, each in a format by the fetch mode.
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchAll($style = null, $col = null);

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $col OPTIONAL Position of the column to fetch.
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchColumn($col = 0);

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $class  OPTIONAL Name of the class to create.
     * @param array  $config OPTIONAL Constructor arguments for the class.
     * @return mixed One object instance of the specified class.
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchObject($class = 'stdClass', array $config = array());

    /**
     * Retrieve a statement attribute.
     *
     * @param string $key Attribute name.
     * @return mixed      Attribute value.
     * @throws Zend_Db_Statement_Exception
     */
    public function getAttribute($key);

    /**
     * Retrieves the next rowset (result set) for a SQL statement that has
     * multiple result sets.  An example is a stored procedure that returns
     * the results of multiple queries.
     *
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function nextRowset();

    /**
     * Returns the number of rows affected by the execution of the
     * last INSERT, DELETE, or UPDATE statement executed by this
     * statement object.
     *
     * @return int     The number of rows affected.
     * @throws Zend_Db_Statement_Exception
     */
    public function rowCount();

    /**
     * Set a statement attribute.
     *
     * @param string $key Attribute name.
     * @param mixed  $val Attribute value.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function setAttribute($key, $val);

    /**
     * Set the default fetch mode for this statement.
     *
     * @param int   $mode The fetch mode.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function setFetchMode($mode);

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Profiler
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Profiler.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Profiler
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Profiler
{

    /**
     * A connection operation or selecting a database.
     */
    const CONNECT = 1;

    /**
     * Any general database query that does not fit into the other constants.
     */
    const QUERY = 2;

    /**
     * Adding new data to the database, such as SQL's INSERT.
     */
    const INSERT = 4;

    /**
     * Updating existing information in the database, such as SQL's UPDATE.
     *
     */
    const UPDATE = 8;

    /**
     * An operation related to deleting data in the database,
     * such as SQL's DELETE.
     */
    const DELETE = 16;

    /**
     * Retrieving information from the database, such as SQL's SELECT.
     */
    const SELECT = 32;

    /**
     * Transactional operation, such as start transaction, commit, or rollback.
     */
    const TRANSACTION = 64;

    /**
     * Inform that a query is stored (in case of filtering)
     */
    const STORED = 'stored';

    /**
     * Inform that a query is ignored (in case of filtering)
     */
    const IGNORED = 'ignored';

    /**
     * Array of Zend_Db_Profiler_Query objects.
     *
     * @var array
     */
    protected $_queryProfiles = array();

    /**
     * Stores enabled state of the profiler.  If set to False, calls to
     * queryStart() will simply be ignored.
     *
     * @var boolean
     */
    protected $_enabled = false;

    /**
     * Stores the number of seconds to filter.  NULL if filtering by time is
     * disabled.  If an integer is stored here, profiles whose elapsed time
     * is less than this value in seconds will be unset from
     * the self::$_queryProfiles array.
     *
     * @var integer
     */
    protected $_filterElapsedSecs = null;

    /**
     * Logical OR of any of the filter constants.  NULL if filtering by query
     * type is disable.  If an integer is stored here, it is the logical OR of
     * any of the query type constants.  When the query ends, if it is not
     * one of the types specified, it will be unset from the
     * self::$_queryProfiles array.
     *
     * @var integer
     */
    protected $_filterTypes = null;

    /**
     * Class constructor.  The profiler is disabled by default unless it is
     * specifically enabled by passing in $enabled here or calling setEnabled().
     *
     * @param  boolean $enabled
     * @return void
     */
    public function __construct($enabled = false)
    {
        $this->setEnabled($enabled);
    }

    /**
     * Enable or disable the profiler.  If $enable is false, the profiler
     * is disabled and will not log any queries sent to it.
     *
     * @param  boolean $enable
     * @return Zend_Db_Profiler Provides a fluent interface
     */
    public function setEnabled($enable)
    {
        $this->_enabled = (boolean) $enable;

        return $this;
    }

    /**
     * Get the current state of enable.  If True is returned,
     * the profiler is enabled.
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->_enabled;
    }

    /**
     * Sets a minimum number of seconds for saving query profiles.  If this
     * is set, only those queries whose elapsed time is equal or greater than
     * $minimumSeconds will be saved.  To save all queries regardless of
     * elapsed time, set $minimumSeconds to null.
     *
     * @param  integer $minimumSeconds OPTIONAL
     * @return Zend_Db_Profiler Provides a fluent interface
     */
    public function setFilterElapsedSecs($minimumSeconds = null)
    {
        if (null === $minimumSeconds) {
            $this->_filterElapsedSecs = null;
        } else {
            $this->_filterElapsedSecs = (integer) $minimumSeconds;
        }

        return $this;
    }

    /**
     * Returns the minimum number of seconds for saving query profiles, or null if
     * query profiles are saved regardless of elapsed time.
     *
     * @return integer|null
     */
    public function getFilterElapsedSecs()
    {
        return $this->_filterElapsedSecs;
    }

    /**
     * Sets the types of query profiles to save.  Set $queryType to one of
     * the Zend_Db_Profiler::* constants to only save profiles for that type of
     * query.  To save more than one type, logical OR them together.  To
     * save all queries regardless of type, set $queryType to null.
     *
     * @param  integer $queryTypes OPTIONAL
     * @return Zend_Db_Profiler Provides a fluent interface
     */
    public function setFilterQueryType($queryTypes = null)
    {
        $this->_filterTypes = $queryTypes;

        return $this;
    }

    /**
     * Returns the types of query profiles saved, or null if queries are saved regardless
     * of their types.
     *
     * @return integer|null
     * @see    Zend_Db_Profiler::setFilterQueryType()
     */
    public function getFilterQueryType()
    {
        return $this->_filterTypes;
    }

    /**
     * Clears the history of any past query profiles.  This is relentless
     * and will even clear queries that were started and may not have
     * been marked as ended.
     *
     * @return Zend_Db_Profiler Provides a fluent interface
     */
    public function clear()
    {
        $this->_queryProfiles = array();

        return $this;
    }

    /**
     * @param  integer $queryId
     * @return integer or null
     */
    public function queryClone(Zend_Db_Profiler_Query $query)
    {
        $this->_queryProfiles[] = clone $query;

        end($this->_queryProfiles);

        return key($this->_queryProfiles);
    }

    /**
     * Starts a query.  Creates a new query profile object (Zend_Db_Profiler_Query)
     * and returns the "query profiler handle".  Run the query, then call
     * queryEnd() and pass it this handle to make the query as ended and
     * record the time.  If the profiler is not enabled, this takes no
     * action and immediately returns null.
     *
     * @param  string  $queryText   SQL statement
     * @param  integer $queryType   OPTIONAL Type of query, one of the Zend_Db_Profiler::* constants
     * @return integer|null
     */
    public function queryStart($queryText, $queryType = null)
    {
        if (!$this->_enabled) {
            return null;
        }

        // make sure we have a query type
        if (null === $queryType) {
            switch (strtolower(substr(ltrim($queryText), 0, 6))) {
                case 'insert':
                    $queryType = self::INSERT;
                    break;
                case 'update':
                    $queryType = self::UPDATE;
                    break;
                case 'delete':
                    $queryType = self::DELETE;
                    break;
                case 'select':
                    $queryType = self::SELECT;
                    break;
                default:
                    $queryType = self::QUERY;
                    break;
            }
        }

        /**
         * @see Zend_Db_Profiler_Query
         */
        require_once 'Zend/Db/Profiler/Query.php';
        $this->_queryProfiles[] = new Zend_Db_Profiler_Query($queryText, $queryType);

        end($this->_queryProfiles);

        return key($this->_queryProfiles);
    }

    /**
     * Ends a query.  Pass it the handle that was returned by queryStart().
     * This will mark the query as ended and save the time.
     *
     * @param  integer $queryId
     * @throws Zend_Db_Profiler_Exception
     * @return void
     */
    public function queryEnd($queryId)
    {
        // Don't do anything if the Zend_Db_Profiler is not enabled.
        if (!$this->_enabled) {
            return self::IGNORED;
        }

        // Check for a valid query handle.
        if (!isset($this->_queryProfiles[$queryId])) {
            /**
             * @see Zend_Db_Profiler_Exception
             */
            require_once 'Zend/Db/Profiler/Exception.php';
            throw new Zend_Db_Profiler_Exception("Profiler has no query with handle '$queryId'.");
        }

        $qp = $this->_queryProfiles[$queryId];

        // Ensure that the query profile has not already ended
        if ($qp->hasEnded()) {
            /**
             * @see Zend_Db_Profiler_Exception
             */
            require_once 'Zend/Db/Profiler/Exception.php';
            throw new Zend_Db_Profiler_Exception("Query with profiler handle '$queryId' has already ended.");
        }

        // End the query profile so that the elapsed time can be calculated.
        $qp->end();

        /**
         * If filtering by elapsed time is enabled, only keep the profile if
         * it ran for the minimum time.
         */
        if (null !== $this->_filterElapsedSecs && $qp->getElapsedSecs() < $this->_filterElapsedSecs) {
            unset($this->_queryProfiles[$queryId]);
            return self::IGNORED;
        }

        /**
         * If filtering by query type is enabled, only keep the query if
         * it was one of the allowed types.
         */
        if (null !== $this->_filterTypes && !($qp->getQueryType() & $this->_filterTypes)) {
            unset($this->_queryProfiles[$queryId]);
            return self::IGNORED;
        }

        return self::STORED;
    }

    /**
     * Get a profile for a query.  Pass it the same handle that was returned
     * by queryStart() and it will return a Zend_Db_Profiler_Query object.
     *
     * @param  integer $queryId
     * @throws Zend_Db_Profiler_Exception
     * @return Zend_Db_Profiler_Query
     */
    public function getQueryProfile($queryId)
    {
        if (!array_key_exists($queryId, $this->_queryProfiles)) {
            /**
             * @see Zend_Db_Profiler_Exception
             */
            require_once 'Zend/Db/Profiler/Exception.php';
            throw new Zend_Db_Profiler_Exception("Query handle '$queryId' not found in profiler log.");
        }

        return $this->_queryProfiles[$queryId];
    }

    /**
     * Get an array of query profiles (Zend_Db_Profiler_Query objects).  If $queryType
     * is set to one of the Zend_Db_Profiler::* constants then only queries of that
     * type will be returned.  Normally, queries that have not yet ended will
     * not be returned unless $showUnfinished is set to True.  If no
     * queries were found, False is returned. The returned array is indexed by the query
     * profile handles.
     *
     * @param  integer $queryType
     * @param  boolean $showUnfinished
     * @return array|false
     */
    public function getQueryProfiles($queryType = null, $showUnfinished = false)
    {
        $queryProfiles = array();
        foreach ($this->_queryProfiles as $key => $qp) {
            if ($queryType === null) {
                $condition = true;
            } else {
                $condition = ($qp->getQueryType() & $queryType);
            }

            if (($qp->hasEnded() || $showUnfinished) && $condition) {
                $queryProfiles[$key] = $qp;
            }
        }

        if (empty($queryProfiles)) {
            $queryProfiles = false;
        }

        return $queryProfiles;
    }

    /**
     * Get the total elapsed time (in seconds) of all of the profiled queries.
     * Only queries that have ended will be counted.  If $queryType is set to
     * one or more of the Zend_Db_Profiler::* constants, the elapsed time will be calculated
     * only for queries of the given type(s).
     *
     * @param  integer $queryType OPTIONAL
     * @return float
     */
    public function getTotalElapsedSecs($queryType = null)
    {
        $elapsedSecs = 0;
        foreach ($this->_queryProfiles as $key => $qp) {
            if (null === $queryType) {
                $condition = true;
            } else {
                $condition = ($qp->getQueryType() & $queryType);
            }
            if (($qp->hasEnded()) && $condition) {
                $elapsedSecs += $qp->getElapsedSecs();
            }
        }
        return $elapsedSecs;
    }

    /**
     * Get the total number of queries that have been profiled.  Only queries that have ended will
     * be counted.  If $queryType is set to one of the Zend_Db_Profiler::* constants, only queries of
     * that type will be counted.
     *
     * @param  integer $queryType OPTIONAL
     * @return integer
     */
    public function getTotalNumQueries($queryType = null)
    {
        if (null === $queryType) {
            return count($this->_queryProfiles);
        }

        $numQueries = 0;
        foreach ($this->_queryProfiles as $qp) {
            if ($qp->hasEnded() && ($qp->getQueryType() & $queryType)) {
                $numQueries++;
            }
        }

        return $numQueries;
    }

    /**
     * Get the Zend_Db_Profiler_Query object for the last query that was run, regardless if it has
     * ended or not.  If the query has not ended, its end time will be null.  If no queries have
     * been profiled, false is returned.
     *
     * @return Zend_Db_Profiler_Query|false
     */
    public function getLastQueryProfile()
    {
        if (empty($this->_queryProfiles)) {
            return false;
        }

        end($this->_queryProfiles);

        return current($this->_queryProfiles);
    }

}


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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Table.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Db_Table_Abstract
 */
require_once 'Zend/Db/Table/Abstract.php';

/**
 * @see Zend_Db_Table_Definition
 */
require_once 'Zend/Db/Table/Definition.php';

/**
 * Class for SQL table interface.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Table extends Zend_Db_Table_Abstract
{

    /**
     * __construct() - For concrete implementation of Zend_Db_Table
     *
     * @param string|array $config string can reference a Zend_Registry key for a db adapter
     *                             OR it can reference the name of a table
     * @param array|Zend_Db_Table_Definition $definition
     */
    public function __construct($config = array(), $definition = null)
    {
        if ($definition !== null && is_array($definition)) {
            $definition = new Zend_Db_Table_Definition($definition);
        }

        if (is_string($config)) {
            if (Zend_Registry::isRegistered($config)) {
                trigger_error(__CLASS__ . '::' . __METHOD__ . '(\'registryName\') is not valid usage of Zend_Db_Table, '
                    . 'try extending Zend_Db_Table_Abstract in your extending classes.',
                    E_USER_NOTICE
                    );
                $config = array(self::ADAPTER => $config);
            } else {
                // process this as table with or without a definition
                if ($definition instanceof Zend_Db_Table_Definition
                    && $definition->hasTableConfig($config)) {
                    // this will have DEFINITION_CONFIG_NAME & DEFINITION
                    $config = $definition->getTableConfig($config);
                } else {
                    $config = array(self::NAME => $config);
                }
            }
        }

        parent::__construct($config);
    }
}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 24148 2011-06-21 15:14:00Z yoshida@zend.co.jp $
 */

/**
 * @see Zend_Db_Adapter_Abstract
 */
require_once 'Zend/Db/Adapter/Abstract.php';

/**
 * @see Zend_Db_Adapter_Abstract
 */
require_once 'Zend/Db/Select.php';

/**
 * @see Zend_Db
 */
require_once 'Zend/Db.php';

/**
 * Class for SQL table interface.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Table_Abstract
{

    const ADAPTER          = 'db';
    const DEFINITION        = 'definition';
    const DEFINITION_CONFIG_NAME = 'definitionConfigName';
    const SCHEMA           = 'schema';
    const NAME             = 'name';
    const PRIMARY          = 'primary';
    const COLS             = 'cols';
    const METADATA         = 'metadata';
    const METADATA_CACHE   = 'metadataCache';
    const METADATA_CACHE_IN_CLASS = 'metadataCacheInClass';
    const ROW_CLASS        = 'rowClass';
    const ROWSET_CLASS     = 'rowsetClass';
    const REFERENCE_MAP    = 'referenceMap';
    const DEPENDENT_TABLES = 'dependentTables';
    const SEQUENCE         = 'sequence';

    const COLUMNS          = 'columns';
    const REF_TABLE_CLASS  = 'refTableClass';
    const REF_COLUMNS      = 'refColumns';
    const ON_DELETE        = 'onDelete';
    const ON_UPDATE        = 'onUpdate';

    const CASCADE          = 'cascade';
    const RESTRICT         = 'restrict';
    const SET_NULL         = 'setNull';

    const DEFAULT_NONE     = 'defaultNone';
    const DEFAULT_CLASS    = 'defaultClass';
    const DEFAULT_DB       = 'defaultDb';

    const SELECT_WITH_FROM_PART    = true;
    const SELECT_WITHOUT_FROM_PART = false;

    /**
     * Default Zend_Db_Adapter_Abstract object.
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected static $_defaultDb;

    /**
     * Optional Zend_Db_Table_Definition object
     *
     * @var unknown_type
     */
    protected $_definition = null;

    /**
     * Optional definition config name used in concrete implementation
     *
     * @var string
     */
    protected $_definitionConfigName = null;

    /**
     * Default cache for information provided by the adapter's describeTable() method.
     *
     * @var Zend_Cache_Core
     */
    protected static $_defaultMetadataCache = null;

    /**
     * Zend_Db_Adapter_Abstract object.
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * The schema name (default null means current schema)
     *
     * @var array
     */
    protected $_schema = null;

    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = null;

    /**
     * The table column names derived from Zend_Db_Adapter_Abstract::describeTable().
     *
     * @var array
     */
    protected $_cols;

    /**
     * The primary key column or columns.
     * A compound key should be declared as an array.
     * You may declare a single-column primary key
     * as a string.
     *
     * @var mixed
     */
    protected $_primary = null;

    /**
     * If your primary key is a compound key, and one of the columns uses
     * an auto-increment or sequence-generated value, set _identity
     * to the ordinal index in the $_primary array for that column.
     * Note this index is the position of the column in the primary key,
     * not the position of the column in the table.  The primary key
     * array is 1-based.
     *
     * @var integer
     */
    protected $_identity = 1;

    /**
     * Define the logic for new values in the primary key.
     * May be a string, boolean true, or boolean false.
     *
     * @var mixed
     */
    protected $_sequence = true;

    /**
     * Information provided by the adapter's describeTable() method.
     *
     * @var array
     */
    protected $_metadata = array();

    /**
     * Cache for information provided by the adapter's describeTable() method.
     *
     * @var Zend_Cache_Core
     */
    protected $_metadataCache = null;

    /**
     * Flag: whether or not to cache metadata in the class
     * @var bool
     */
    protected $_metadataCacheInClass = true;

    /**
     * Classname for row
     *
     * @var string
     */
    protected $_rowClass = 'Zend_Db_Table_Row';

    /**
     * Classname for rowset
     *
     * @var string
     */
    protected $_rowsetClass = 'Zend_Db_Table_Rowset';

    /**
     * Associative array map of declarative referential integrity rules.
     * This array has one entry per foreign key in the current table.
     * Each key is a mnemonic name for one reference rule.
     *
     * Each value is also an associative array, with the following keys:
     * - columns       = array of names of column(s) in the child table.
     * - refTableClass = class name of the parent table.
     * - refColumns    = array of names of column(s) in the parent table,
     *                   in the same order as those in the 'columns' entry.
     * - onDelete      = "cascade" means that a delete in the parent table also
     *                   causes a delete of referencing rows in the child table.
     * - onUpdate      = "cascade" means that an update of primary key values in
     *                   the parent table also causes an update of referencing
     *                   rows in the child table.
     *
     * @var array
     */
    protected $_referenceMap = array();

    /**
     * Simple array of class names of tables that are "children" of the current
     * table, in other words tables that contain a foreign key to this one.
     * Array elements are not table names; they are class names of classes that
     * extend Zend_Db_Table_Abstract.
     *
     * @var array
     */
    protected $_dependentTables = array();


    protected $_defaultSource = self::DEFAULT_NONE;
    protected $_defaultValues = array();

    /**
     * Constructor.
     *
     * Supported params for $config are:
     * - db              = user-supplied instance of database connector,
     *                     or key name of registry instance.
     * - name            = table name.
     * - primary         = string or array of primary key(s).
     * - rowClass        = row class name.
     * - rowsetClass     = rowset class name.
     * - referenceMap    = array structure to declare relationship
     *                     to parent tables.
     * - dependentTables = array of child tables.
     * - metadataCache   = cache for information from adapter describeTable().
     *
     * @param  mixed $config Array of user-specified config options, or just the Db Adapter.
     * @return void
     */
    public function __construct($config = array())
    {
        /**
         * Allow a scalar argument to be the Adapter object or Registry key.
         */
        if (!is_array($config)) {
            $config = array(self::ADAPTER => $config);
        }

        if ($config) {
            $this->setOptions($config);
        }

        $this->_setup();
        $this->init();
    }

    /**
     * setOptions()
     *
     * @param array $options
     * @return Zend_Db_Table_Abstract
     */
    public function setOptions(Array $options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case self::ADAPTER:
                    $this->_setAdapter($value);
                    break;
                case self::DEFINITION:
                    $this->setDefinition($value);
                    break;
                case self::DEFINITION_CONFIG_NAME:
                    $this->setDefinitionConfigName($value);
                    break;
                case self::SCHEMA:
                    $this->_schema = (string) $value;
                    break;
                case self::NAME:
                    $this->_name = (string) $value;
                    break;
                case self::PRIMARY:
                    $this->_primary = (array) $value;
                    break;
                case self::ROW_CLASS:
                    $this->setRowClass($value);
                    break;
                case self::ROWSET_CLASS:
                    $this->setRowsetClass($value);
                    break;
                case self::REFERENCE_MAP:
                    $this->setReferences($value);
                    break;
                case self::DEPENDENT_TABLES:
                    $this->setDependentTables($value);
                    break;
                case self::METADATA_CACHE:
                    $this->_setMetadataCache($value);
                    break;
                case self::METADATA_CACHE_IN_CLASS:
                    $this->setMetadataCacheInClass($value);
                    break;
                case self::SEQUENCE:
                    $this->_setSequence($value);
                    break;
                default:
                    // ignore unrecognized configuration directive
                    break;
            }
        }

        return $this;
    }

    /**
     * setDefinition()
     *
     * @param Zend_Db_Table_Definition $definition
     * @return Zend_Db_Table_Abstract
     */
    public function setDefinition(Zend_Db_Table_Definition $definition)
    {
        $this->_definition = $definition;
        return $this;
    }

    /**
     * getDefinition()
     *
     * @return Zend_Db_Table_Definition|null
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /**
     * setDefinitionConfigName()
     *
     * @param string $definition
     * @return Zend_Db_Table_Abstract
     */
    public function setDefinitionConfigName($definitionConfigName)
    {
        $this->_definitionConfigName = $definitionConfigName;
        return $this;
    }

    /**
     * getDefinitionConfigName()
     *
     * @return string
     */
    public function getDefinitionConfigName()
    {
        return $this->_definitionConfigName;
    }

    /**
     * @param  string $classname
     * @return Zend_Db_Table_Abstract Provides a fluent interface
     */
    public function setRowClass($classname)
    {
        $this->_rowClass = (string) $classname;

        return $this;
    }

    /**
     * @return string
     */
    public function getRowClass()
    {
        return $this->_rowClass;
    }

    /**
     * @param  string $classname
     * @return Zend_Db_Table_Abstract Provides a fluent interface
     */
    public function setRowsetClass($classname)
    {
        $this->_rowsetClass = (string) $classname;

        return $this;
    }

    /**
     * @return string
     */
    public function getRowsetClass()
    {
        return $this->_rowsetClass;
    }

    /**
     * Add a reference to the reference map
     *
     * @param string $ruleKey
     * @param string|array $columns
     * @param string $refTableClass
     * @param string|array $refColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @return Zend_Db_Table_Abstract
     */
    public function addReference($ruleKey, $columns, $refTableClass, $refColumns,
                                 $onDelete = null, $onUpdate = null)
    {
        $reference = array(self::COLUMNS         => (array) $columns,
                           self::REF_TABLE_CLASS => $refTableClass,
                           self::REF_COLUMNS     => (array) $refColumns);

        if (!empty($onDelete)) {
            $reference[self::ON_DELETE] = $onDelete;
        }

        if (!empty($onUpdate)) {
            $reference[self::ON_UPDATE] = $onUpdate;
        }

        $this->_referenceMap[$ruleKey] = $reference;

        return $this;
    }

    /**
     * @param array $referenceMap
     * @return Zend_Db_Table_Abstract Provides a fluent interface
     */
    public function setReferences(array $referenceMap)
    {
        $this->_referenceMap = $referenceMap;

        return $this;
    }

    /**
     * @param string $tableClassname
     * @param string $ruleKey OPTIONAL
     * @return array
     * @throws Zend_Db_Table_Exception
     */
    public function getReference($tableClassname, $ruleKey = null)
    {
        $thisClass = get_class($this);
        if ($thisClass === 'Zend_Db_Table') {
            $thisClass = $this->_definitionConfigName;
        }
        $refMap = $this->_getReferenceMapNormalized();
        if ($ruleKey !== null) {
            if (!isset($refMap[$ruleKey])) {
                require_once "Zend/Db/Table/Exception.php";
                throw new Zend_Db_Table_Exception("No reference rule \"$ruleKey\" from table $thisClass to table $tableClassname");
            }
            if ($refMap[$ruleKey][self::REF_TABLE_CLASS] != $tableClassname) {
                require_once "Zend/Db/Table/Exception.php";
                throw new Zend_Db_Table_Exception("Reference rule \"$ruleKey\" does not reference table $tableClassname");
            }
            return $refMap[$ruleKey];
        }
        foreach ($refMap as $reference) {
            if ($reference[self::REF_TABLE_CLASS] == $tableClassname) {
                return $reference;
            }
        }
        require_once "Zend/Db/Table/Exception.php";
        throw new Zend_Db_Table_Exception("No reference from table $thisClass to table $tableClassname");
    }

    /**
     * @param  array $dependentTables
     * @return Zend_Db_Table_Abstract Provides a fluent interface
     */
    public function setDependentTables(array $dependentTables)
    {
        $this->_dependentTables = $dependentTables;

        return $this;
    }

    /**
     * @return array
     */
    public function getDependentTables()
    {
        return $this->_dependentTables;
    }

    /**
     * set the defaultSource property - this tells the table class where to find default values
     *
     * @param string $defaultSource
     * @return Zend_Db_Table_Abstract
     */
    public function setDefaultSource($defaultSource = self::DEFAULT_NONE)
    {
        if (!in_array($defaultSource, array(self::DEFAULT_CLASS, self::DEFAULT_DB, self::DEFAULT_NONE))) {
            $defaultSource = self::DEFAULT_NONE;
        }

        $this->_defaultSource = $defaultSource;
        return $this;
    }

    /**
     * returns the default source flag that determines where defaultSources come from
     *
     * @return unknown
     */
    public function getDefaultSource()
    {
        return $this->_defaultSource;
    }

    /**
     * set the default values for the table class
     *
     * @param array $defaultValues
     * @return Zend_Db_Table_Abstract
     */
    public function setDefaultValues(Array $defaultValues)
    {
        foreach ($defaultValues as $defaultName => $defaultValue) {
            if (array_key_exists($defaultName, $this->_metadata)) {
                $this->_defaultValues[$defaultName] = $defaultValue;
            }
        }
        return $this;
    }

    public function getDefaultValues()
    {
        return $this->_defaultValues;
    }


    /**
     * Sets the default Zend_Db_Adapter_Abstract for all Zend_Db_Table objects.
     *
     * @param  mixed $db Either an Adapter object, or a string naming a Registry key
     * @return void
     */
    public static function setDefaultAdapter($db = null)
    {
        self::$_defaultDb = self::_setupAdapter($db);
    }

    /**
     * Gets the default Zend_Db_Adapter_Abstract for all Zend_Db_Table objects.
     *
     * @return Zend_Db_Adapter_Abstract or null
     */
    public static function getDefaultAdapter()
    {
        return self::$_defaultDb;
    }

    /**
     * @param  mixed $db Either an Adapter object, or a string naming a Registry key
     * @return Zend_Db_Table_Abstract Provides a fluent interface
     */
    protected function _setAdapter($db)
    {
        $this->_db = self::_setupAdapter($db);
        return $this;
    }

    /**
     * Gets the Zend_Db_Adapter_Abstract for this particular Zend_Db_Table object.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_db;
    }

    /**
     * @param  mixed $db Either an Adapter object, or a string naming a Registry key
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Db_Table_Exception
     */
    protected static function _setupAdapter($db)
    {
        if ($db === null) {
            return null;
        }
        if (is_string($db)) {
            require_once 'Zend/Registry.php';
            $db = Zend_Registry::get($db);
        }
        if (!$db instanceof Zend_Db_Adapter_Abstract) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception('Argument must be of type Zend_Db_Adapter_Abstract, or a Registry key where a Zend_Db_Adapter_Abstract object is stored');
        }
        return $db;
    }

    /**
     * Sets the default metadata cache for information returned by Zend_Db_Adapter_Abstract::describeTable().
     *
     * If $defaultMetadataCache is null, then no metadata cache is used by default.
     *
     * @param  mixed $metadataCache Either a Cache object, or a string naming a Registry key
     * @return void
     */
    public static function setDefaultMetadataCache($metadataCache = null)
    {
        self::$_defaultMetadataCache = self::_setupMetadataCache($metadataCache);
    }

    /**
     * Gets the default metadata cache for information returned by Zend_Db_Adapter_Abstract::describeTable().
     *
     * @return Zend_Cache_Core or null
     */
    public static function getDefaultMetadataCache()
    {
        return self::$_defaultMetadataCache;
    }

    /**
     * Sets the metadata cache for information returned by Zend_Db_Adapter_Abstract::describeTable().
     *
     * If $metadataCache is null, then no metadata cache is used. Since there is no opportunity to reload metadata
     * after instantiation, this method need not be public, particularly because that it would have no effect
     * results in unnecessary API complexity. To configure the metadata cache, use the metadataCache configuration
     * option for the class constructor upon instantiation.
     *
     * @param  mixed $metadataCache Either a Cache object, or a string naming a Registry key
     * @return Zend_Db_Table_Abstract Provides a fluent interface
     */
    protected function _setMetadataCache($metadataCache)
    {
        $this->_metadataCache = self::_setupMetadataCache($metadataCache);
        return $this;
    }

    /**
     * Gets the metadata cache for information returned by Zend_Db_Adapter_Abstract::describeTable().
     *
     * @return Zend_Cache_Core or null
     */
    public function getMetadataCache()
    {
        return $this->_metadataCache;
    }

    /**
     * Indicate whether metadata should be cached in the class for the duration
     * of the instance
     *
     * @param  bool $flag
     * @return Zend_Db_Table_Abstract
     */
    public function setMetadataCacheInClass($flag)
    {
        $this->_metadataCacheInClass = (bool) $flag;
        return $this;
    }

    /**
     * Retrieve flag indicating if metadata should be cached for duration of
     * instance
     *
     * @return bool
     */
    public function metadataCacheInClass()
    {
        return $this->_metadataCacheInClass;
    }

    /**
     * @param mixed $metadataCache Either a Cache object, or a string naming a Registry key
     * @return Zend_Cache_Core
     * @throws Zend_Db_Table_Exception
     */
    protected static function _setupMetadataCache($metadataCache)
    {
        if ($metadataCache === null) {
            return null;
        }
        if (is_string($metadataCache)) {
            require_once 'Zend/Registry.php';
            $metadataCache = Zend_Registry::get($metadataCache);
        }
        if (!$metadataCache instanceof Zend_Cache_Core) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception('Argument must be of type Zend_Cache_Core, or a Registry key where a Zend_Cache_Core object is stored');
        }
        return $metadataCache;
    }

    /**
     * Sets the sequence member, which defines the behavior for generating
     * primary key values in new rows.
     * - If this is a string, then the string names the sequence object.
     * - If this is boolean true, then the key uses an auto-incrementing
     *   or identity mechanism.
     * - If this is boolean false, then the key is user-defined.
     *   Use this for natural keys, for example.
     *
     * @param mixed $sequence
     * @return Zend_Db_Table_Adapter_Abstract Provides a fluent interface
     */
    protected function _setSequence($sequence)
    {
        $this->_sequence = $sequence;

        return $this;
    }

    /**
     * Turnkey for initialization of a table object.
     * Calls other protected methods for individual tasks, to make it easier
     * for a subclass to override part of the setup logic.
     *
     * @return void
     */
    protected function _setup()
    {
        $this->_setupDatabaseAdapter();
        $this->_setupTableName();
    }

    /**
     * Initialize database adapter.
     *
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    protected function _setupDatabaseAdapter()
    {
        if (! $this->_db) {
            $this->_db = self::getDefaultAdapter();
            if (!$this->_db instanceof Zend_Db_Adapter_Abstract) {
                require_once 'Zend/Db/Table/Exception.php';
                throw new Zend_Db_Table_Exception('No adapter found for ' . get_class($this));
            }
        }
    }

    /**
     * Initialize table and schema names.
     *
     * If the table name is not set in the class definition,
     * use the class name itself as the table name.
     *
     * A schema name provided with the table name (e.g., "schema.table") overrides
     * any existing value for $this->_schema.
     *
     * @return void
     */
    protected function _setupTableName()
    {
        if (! $this->_name) {
            $this->_name = get_class($this);
        } else if (strpos($this->_name, '.')) {
            list($this->_schema, $this->_name) = explode('.', $this->_name);
        }
    }

    /**
     * Initializes metadata.
     *
     * If metadata cannot be loaded from cache, adapter's describeTable() method is called to discover metadata
     * information. Returns true if and only if the metadata are loaded from cache.
     *
     * @return boolean
     * @throws Zend_Db_Table_Exception
     */
    protected function _setupMetadata()
    {
        if ($this->metadataCacheInClass() && (count($this->_metadata) > 0)) {
            return true;
        }

        // Assume that metadata will be loaded from cache
        $isMetadataFromCache = true;

        // If $this has no metadata cache but the class has a default metadata cache
        if (null === $this->_metadataCache && null !== self::$_defaultMetadataCache) {
            // Make $this use the default metadata cache of the class
            $this->_setMetadataCache(self::$_defaultMetadataCache);
        }

        // If $this has a metadata cache
        if (null !== $this->_metadataCache) {
            // Define the cache identifier where the metadata are saved

            //get db configuration
            $dbConfig = $this->_db->getConfig();

            $port = isset($dbConfig['options']['port'])
                  ? ':'.$dbConfig['options']['port']
                  : (isset($dbConfig['port'])
                  ? ':'.$dbConfig['port']
                  : null);

            $host = isset($dbConfig['options']['host'])
                  ? ':'.$dbConfig['options']['host']
                  : (isset($dbConfig['host'])
                  ? ':'.$dbConfig['host']
                  : null);

            // Define the cache identifier where the metadata are saved
            $cacheId = md5( // port:host/dbname:schema.table (based on availabilty)
                    $port . $host . '/'. $dbConfig['dbname'] . ':'
                  . $this->_schema. '.' . $this->_name
            );
        }

        // If $this has no metadata cache or metadata cache misses
        if (null === $this->_metadataCache || !($metadata = $this->_metadataCache->load($cacheId))) {
            // Metadata are not loaded from cache
            $isMetadataFromCache = false;
            // Fetch metadata from the adapter's describeTable() method
            $metadata = $this->_db->describeTable($this->_name, $this->_schema);
            // If $this has a metadata cache, then cache the metadata
            if (null !== $this->_metadataCache && !$this->_metadataCache->save($metadata, $cacheId)) {
                trigger_error('Failed saving metadata to metadataCache', E_USER_NOTICE);
            }
        }

        // Assign the metadata to $this
        $this->_metadata = $metadata;

        // Return whether the metadata were loaded from cache
        return $isMetadataFromCache;
    }

    /**
     * Retrieve table columns
     *
     * @return array
     */
    protected function _getCols()
    {
        if (null === $this->_cols) {
            $this->_setupMetadata();
            $this->_cols = array_keys($this->_metadata);
        }
        return $this->_cols;
    }

    /**
     * Initialize primary key from metadata.
     * If $_primary is not defined, discover primary keys
     * from the information returned by describeTable().
     *
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    protected function _setupPrimaryKey()
    {
        if (!$this->_primary) {
            $this->_setupMetadata();
            $this->_primary = array();
            foreach ($this->_metadata as $col) {
                if ($col['PRIMARY']) {
                    $this->_primary[ $col['PRIMARY_POSITION'] ] = $col['COLUMN_NAME'];
                    if ($col['IDENTITY']) {
                        $this->_identity = $col['PRIMARY_POSITION'];
                    }
                }
            }
            // if no primary key was specified and none was found in the metadata
            // then throw an exception.
            if (empty($this->_primary)) {
                require_once 'Zend/Db/Table/Exception.php';
                throw new Zend_Db_Table_Exception('A table must have a primary key, but none was found');
            }
        } else if (!is_array($this->_primary)) {
            $this->_primary = array(1 => $this->_primary);
        } else if (isset($this->_primary[0])) {
            array_unshift($this->_primary, null);
            unset($this->_primary[0]);
        }

        $cols = $this->_getCols();
        if (! array_intersect((array) $this->_primary, $cols) == (array) $this->_primary) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Primary key column(s) ("
                . implode(',', (array) $this->_primary)
                . ") are not columns in this table ("
                . implode(',', $cols)
                . ")");
        }

        $primary    = (array) $this->_primary;
        $pkIdentity = $primary[(int) $this->_identity];

        /**
         * Special case for PostgreSQL: a SERIAL key implicitly uses a sequence
         * object whose name is "<table>_<column>_seq".
         */
        if ($this->_sequence === true && $this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $this->_sequence = $this->_db->quoteIdentifier("{$this->_name}_{$pkIdentity}_seq");
            if ($this->_schema) {
                $this->_sequence = $this->_db->quoteIdentifier($this->_schema) . '.' . $this->_sequence;
            }
        }
    }

    /**
     * Returns a normalized version of the reference map
     *
     * @return array
     */
    protected function _getReferenceMapNormalized()
    {
        $referenceMapNormalized = array();

        foreach ($this->_referenceMap as $rule => $map) {

            $referenceMapNormalized[$rule] = array();

            foreach ($map as $key => $value) {
                switch ($key) {

                    // normalize COLUMNS and REF_COLUMNS to arrays
                    case self::COLUMNS:
                    case self::REF_COLUMNS:
                        if (!is_array($value)) {
                            $referenceMapNormalized[$rule][$key] = array($value);
                        } else {
                            $referenceMapNormalized[$rule][$key] = $value;
                        }
                        break;

                    // other values are copied as-is
                    default:
                        $referenceMapNormalized[$rule][$key] = $value;
                        break;
                }
            }
        }

        return $referenceMapNormalized;
    }

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Returns table information.
     *
     * You can elect to return only a part of this information by supplying its key name,
     * otherwise all information is returned as an array.
     *
     * @param  string $key The specific info part to return OPTIONAL
     * @return mixed
     * @throws Zend_Db_Table_Exception
     */
    public function info($key = null)
    {
        $this->_setupPrimaryKey();

        $info = array(
            self::SCHEMA           => $this->_schema,
            self::NAME             => $this->_name,
            self::COLS             => $this->_getCols(),
            self::PRIMARY          => (array) $this->_primary,
            self::METADATA         => $this->_metadata,
            self::ROW_CLASS        => $this->getRowClass(),
            self::ROWSET_CLASS     => $this->getRowsetClass(),
            self::REFERENCE_MAP    => $this->_referenceMap,
            self::DEPENDENT_TABLES => $this->_dependentTables,
            self::SEQUENCE         => $this->_sequence
        );

        if ($key === null) {
            return $info;
        }

        if (!array_key_exists($key, $info)) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception('There is no table information for the key "' . $key . '"');
        }

        return $info[$key];
    }

    /**
     * Returns an instance of a Zend_Db_Table_Select object.
     *
     * @param bool $withFromPart Whether or not to include the from part of the select based on the table
     * @return Zend_Db_Table_Select
     */
    public function select($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
        require_once 'Zend/Db/Table/Select.php';
        $select = new Zend_Db_Table_Select($this);
        if ($withFromPart == self::SELECT_WITH_FROM_PART) {
            $select->from($this->info(self::NAME), Zend_Db_Table_Select::SQL_WILDCARD, $this->info(self::SCHEMA));
        }
        return $select;
    }

    /**
     * Inserts a new row.
     *
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
    public function insert(array $data)
    {
        $this->_setupPrimaryKey();

        /**
         * Zend_Db_Table assumes that if you have a compound primary key
         * and one of the columns in the key uses a sequence,
         * it's the _first_ column in the compound key.
         */
        $primary = (array) $this->_primary;
        $pkIdentity = $primary[(int)$this->_identity];

        /**
         * If this table uses a database sequence object and the data does not
         * specify a value, then get the next ID from the sequence and add it
         * to the row.  We assume that only the first column in a compound
         * primary key takes a value from a sequence.
         */
        if (is_string($this->_sequence) && !isset($data[$pkIdentity])) {
            $data[$pkIdentity] = $this->_db->nextSequenceId($this->_sequence);
            $pkSuppliedBySequence = true;
        }

        /**
         * If the primary key can be generated automatically, and no value was
         * specified in the user-supplied data, then omit it from the tuple.
         *
         * Note: this checks for sensible values in the supplied primary key
         * position of the data.  The following values are considered empty:
         *   null, false, true, '', array()
         */
        if (!isset($pkSuppliedBySequence) && array_key_exists($pkIdentity, $data)) {
            if ($data[$pkIdentity] === null                                        // null
                || $data[$pkIdentity] === ''                                       // empty string
                || is_bool($data[$pkIdentity])                                     // boolean
                || (is_array($data[$pkIdentity]) && empty($data[$pkIdentity]))) {  // empty array
                unset($data[$pkIdentity]);
            }
        }

        /**
         * INSERT the new row.
         */
        $tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
        $this->_db->insert($tableSpec, $data);

        /**
         * Fetch the most recent ID generated by an auto-increment
         * or IDENTITY column, unless the user has specified a value,
         * overriding the auto-increment mechanism.
         */
        if ($this->_sequence === true && !isset($data[$pkIdentity])) {
            $data[$pkIdentity] = $this->_db->lastInsertId();
        }

        /**
         * Return the primary key value if the PK is a single column,
         * else return an associative array of the PK column/value pairs.
         */
        $pkData = array_intersect_key($data, array_flip($primary));
        if (count($primary) == 1) {
            reset($pkData);
            return current($pkData);
        }

        return $pkData;
    }

    /**
     * Check if the provided column is an identity of the table
     *
     * @param  string $column
     * @throws Zend_Db_Table_Exception
     * @return boolean
     */
    public function isIdentity($column)
    {
        $this->_setupPrimaryKey();

        if (!isset($this->_metadata[$column])) {
            /**
             * @see Zend_Db_Table_Exception
             */
            require_once 'Zend/Db/Table/Exception.php';

            throw new Zend_Db_Table_Exception('Column "' . $column . '" not found in table.');
        }

        return (bool) $this->_metadata[$column]['IDENTITY'];
    }

    /**
     * Updates existing rows.
     *
     * @param  array        $data  Column-value pairs.
     * @param  array|string $where An SQL WHERE clause, or an array of SQL WHERE clauses.
     * @return int          The number of rows updated.
     */
    public function update(array $data, $where)
    {
        $tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
        return $this->_db->update($tableSpec, $data, $where);
    }

    /**
     * Called by a row object for the parent table's class during save() method.
     *
     * @param  string $parentTableClassname
     * @param  array  $oldPrimaryKey
     * @param  array  $newPrimaryKey
     * @return int
     */
    public function _cascadeUpdate($parentTableClassname, array $oldPrimaryKey, array $newPrimaryKey)
    {
        $this->_setupMetadata();
        $rowsAffected = 0;
        foreach ($this->_getReferenceMapNormalized() as $map) {
            if ($map[self::REF_TABLE_CLASS] == $parentTableClassname && isset($map[self::ON_UPDATE])) {
                switch ($map[self::ON_UPDATE]) {
                    case self::CASCADE:
                        $newRefs = array();
                        $where = array();
                        for ($i = 0; $i < count($map[self::COLUMNS]); ++$i) {
                            $col = $this->_db->foldCase($map[self::COLUMNS][$i]);
                            $refCol = $this->_db->foldCase($map[self::REF_COLUMNS][$i]);
                            if (array_key_exists($refCol, $newPrimaryKey)) {
                                $newRefs[$col] = $newPrimaryKey[$refCol];
                            }
                            $type = $this->_metadata[$col]['DATA_TYPE'];
                            $where[] = $this->_db->quoteInto(
                                $this->_db->quoteIdentifier($col, true) . ' = ?',
                                $oldPrimaryKey[$refCol], $type);
                        }
                        $rowsAffected += $this->update($newRefs, $where);
                        break;
                    default:
                        // no action
                        break;
                }
            }
        }
        return $rowsAffected;
    }

    /**
     * Deletes existing rows.
     *
     * @param  array|string $where SQL WHERE clause(s).
     * @return int          The number of rows deleted.
     */
    public function delete($where)
    {
        $tableSpec = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
        return $this->_db->delete($tableSpec, $where);
    }

    /**
     * Called by parent table's class during delete() method.
     *
     * @param  string $parentTableClassname
     * @param  array  $primaryKey
     * @return int    Number of affected rows
     */
    public function _cascadeDelete($parentTableClassname, array $primaryKey)
    {
        $this->_setupMetadata();
        $rowsAffected = 0;
        foreach ($this->_getReferenceMapNormalized() as $map) {
            if ($map[self::REF_TABLE_CLASS] == $parentTableClassname && isset($map[self::ON_DELETE])) {
                switch ($map[self::ON_DELETE]) {
                    case self::CASCADE:
                        $where = array();
                        for ($i = 0; $i < count($map[self::COLUMNS]); ++$i) {
                            $col = $this->_db->foldCase($map[self::COLUMNS][$i]);
                            $refCol = $this->_db->foldCase($map[self::REF_COLUMNS][$i]);
                            $type = $this->_metadata[$col]['DATA_TYPE'];
                            $where[] = $this->_db->quoteInto(
                                $this->_db->quoteIdentifier($col, true) . ' = ?',
                                $primaryKey[$refCol], $type);
                        }
                        $rowsAffected += $this->delete($where);
                        break;
                    default:
                        // no action
                        break;
                }
            }
        }
        return $rowsAffected;
    }

    /**
     * Fetches rows by primary key.  The argument specifies one or more primary
     * key value(s).  To find multiple rows by primary key, the argument must
     * be an array.
     *
     * This method accepts a variable number of arguments.  If the table has a
     * multi-column primary key, the number of arguments must be the same as
     * the number of columns in the primary key.  To find multiple rows in a
     * table with a multi-column primary key, each argument must be an array
     * with the same number of elements.
     *
     * The find() method always returns a Rowset object, even if only one row
     * was found.
     *
     * @param  mixed $key The value(s) of the primary keys.
     * @return Zend_Db_Table_Rowset_Abstract Row(s) matching the criteria.
     * @throws Zend_Db_Table_Exception
     */
    public function find()
    {
        $this->_setupPrimaryKey();
        $args = func_get_args();
        $keyNames = array_values((array) $this->_primary);

        if (count($args) < count($keyNames)) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Too few columns for the primary key");
        }

        if (count($args) > count($keyNames)) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Too many columns for the primary key");
        }

        $whereList = array();
        $numberTerms = 0;
        foreach ($args as $keyPosition => $keyValues) {
            $keyValuesCount = count($keyValues);
            // Coerce the values to an array.
            // Don't simply typecast to array, because the values
            // might be Zend_Db_Expr objects.
            if (!is_array($keyValues)) {
                $keyValues = array($keyValues);
            }
            if ($numberTerms == 0) {
                $numberTerms = $keyValuesCount;
            } else if ($keyValuesCount != $numberTerms) {
                require_once 'Zend/Db/Table/Exception.php';
                throw new Zend_Db_Table_Exception("Missing value(s) for the primary key");
            }
            $keyValues = array_values($keyValues);
            for ($i = 0; $i < $keyValuesCount; ++$i) {
                if (!isset($whereList[$i])) {
                    $whereList[$i] = array();
                }
                $whereList[$i][$keyPosition] = $keyValues[$i];
            }
        }

        $whereClause = null;
        if (count($whereList)) {
            $whereOrTerms = array();
            $tableName = $this->_db->quoteTableAs($this->_name, null, true);
            foreach ($whereList as $keyValueSets) {
                $whereAndTerms = array();
                foreach ($keyValueSets as $keyPosition => $keyValue) {
                    $type = $this->_metadata[$keyNames[$keyPosition]]['DATA_TYPE'];
                    $columnName = $this->_db->quoteIdentifier($keyNames[$keyPosition], true);
                    $whereAndTerms[] = $this->_db->quoteInto(
                        $tableName . '.' . $columnName . ' = ?',
                        $keyValue, $type);
                }
                $whereOrTerms[] = '(' . implode(' AND ', $whereAndTerms) . ')';
            }
            $whereClause = '(' . implode(' OR ', $whereOrTerms) . ')';
        }

        // issue ZF-5775 (empty where clause should return empty rowset)
        if ($whereClause == null) {
            $rowsetClass = $this->getRowsetClass();
            if (!class_exists($rowsetClass)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($rowsetClass);
            }
            return new $rowsetClass(array('table' => $this, 'rowClass' => $this->getRowClass(), 'stored' => true));
        }

        return $this->fetchAll($whereClause);
    }

    /**
     * Fetches all rows.
     *
     * Honors the Zend_Db_Adapter fetch mode.
     *
     * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $count  OPTIONAL An SQL LIMIT count.
     * @param int                               $offset OPTIONAL An SQL LIMIT offset.
     * @return Zend_Db_Table_Rowset_Abstract The row results per the Zend_Db_Adapter fetch mode.
     */
    public function fetchAll($where = null, $order = null, $count = null, $offset = null)
    {
        if (!($where instanceof Zend_Db_Table_Select)) {
            $select = $this->select();

            if ($where !== null) {
                $this->_where($select, $where);
            }

            if ($order !== null) {
                $this->_order($select, $order);
            }

            if ($count !== null || $offset !== null) {
                $select->limit($count, $offset);
            }

        } else {
            $select = $where;
        }

        $rows = $this->_fetch($select);

        $data  = array(
            'table'    => $this,
            'data'     => $rows,
            'readOnly' => $select->isReadOnly(),
            'rowClass' => $this->getRowClass(),
            'stored'   => true
        );

        $rowsetClass = $this->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($rowsetClass);
        }
        return new $rowsetClass($data);
    }

    /**
     * Fetches one row in an object of type Zend_Db_Table_Row_Abstract,
     * or returns null if no row matches the specified criteria.
     *
     * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $offset OPTIONAL An SQL OFFSET value.
     * @return Zend_Db_Table_Row_Abstract|null The row results per the
     *     Zend_Db_Adapter fetch mode, or null if no row found.
     */
    public function fetchRow($where = null, $order = null, $offset = null)
    {
        if (!($where instanceof Zend_Db_Table_Select)) {
            $select = $this->select();

            if ($where !== null) {
                $this->_where($select, $where);
            }

            if ($order !== null) {
                $this->_order($select, $order);
            }

            $select->limit(1, ((is_numeric($offset)) ? (int) $offset : null));

        } else {
            $select = $where->limit(1, $where->getPart(Zend_Db_Select::LIMIT_OFFSET));
        }

        $rows = $this->_fetch($select);

        if (count($rows) == 0) {
            return null;
        }

        $data = array(
            'table'   => $this,
            'data'     => $rows[0],
            'readOnly' => $select->isReadOnly(),
            'stored'  => true
        );

        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
    }

    /**
     * Fetches a new blank row (not from the database).
     *
     * @return Zend_Db_Table_Row_Abstract
     * @deprecated since 0.9.3 - use createRow() instead.
     */
    public function fetchNew()
    {
        return $this->createRow();
    }

    /**
     * Fetches a new blank row (not from the database).
     *
     * @param  array $data OPTIONAL data to populate in the new row.
     * @param  string $defaultSource OPTIONAL flag to force default values into new row
     * @return Zend_Db_Table_Row_Abstract
     */
    public function createRow(array $data = array(), $defaultSource = null)
    {
        $cols     = $this->_getCols();
        $defaults = array_combine($cols, array_fill(0, count($cols), null));

        // nothing provided at call-time, take the class value
        if ($defaultSource == null) {
            $defaultSource = $this->_defaultSource;
        }

        if (!in_array($defaultSource, array(self::DEFAULT_CLASS, self::DEFAULT_DB, self::DEFAULT_NONE))) {
            $defaultSource = self::DEFAULT_NONE;
        }

        if ($defaultSource == self::DEFAULT_DB) {
            foreach ($this->_metadata as $metadataName => $metadata) {
                if (($metadata['DEFAULT'] != null) &&
                    ($metadata['NULLABLE'] !== true || ($metadata['NULLABLE'] === true && isset($this->_defaultValues[$metadataName]) && $this->_defaultValues[$metadataName] === true)) &&
                    (!(isset($this->_defaultValues[$metadataName]) && $this->_defaultValues[$metadataName] === false))) {
                    $defaults[$metadataName] = $metadata['DEFAULT'];
                }
            }
        } elseif ($defaultSource == self::DEFAULT_CLASS && $this->_defaultValues) {
            foreach ($this->_defaultValues as $defaultName => $defaultValue) {
                if (array_key_exists($defaultName, $defaults)) {
                    $defaults[$defaultName] = $defaultValue;
                }
            }
        }

        $config = array(
            'table'    => $this,
            'data'     => $defaults,
            'readOnly' => false,
            'stored'   => false
        );

        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($rowClass);
        }
        $row = new $rowClass($config);
        $row->setFromArray($data);
        return $row;
    }

    /**
     * Generate WHERE clause from user-supplied string or array
     *
     * @param  string|array $where  OPTIONAL An SQL WHERE clause.
     * @return Zend_Db_Table_Select
     */
    protected function _where(Zend_Db_Table_Select $select, $where)
    {
        $where = (array) $where;

        foreach ($where as $key => $val) {
            // is $key an int?
            if (is_int($key)) {
                // $val is the full condition
                $select->where($val);
            } else {
                // $key is the condition with placeholder,
                // and $val is quoted into the condition
                $select->where($key, $val);
            }
        }

        return $select;
    }

    /**
     * Generate ORDER clause from user-supplied string or array
     *
     * @param  string|array $order  OPTIONAL An SQL ORDER clause.
     * @return Zend_Db_Table_Select
     */
    protected function _order(Zend_Db_Table_Select $select, $order)
    {
        if (!is_array($order)) {
            $order = array($order);
        }

        foreach ($order as $val) {
            $select->order($val);
        }

        return $select;
    }

    /**
     * Support method for fetching rows.
     *
     * @param  Zend_Db_Table_Select $select  query options.
     * @return array An array containing the row results in FETCH_ASSOC mode.
     */
    protected function _fetch(Zend_Db_Table_Select $select)
    {
        $stmt = $this->_db->query($select);
        $data = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        return $data;
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Definition.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Class for SQL table interface.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Table_Definition
{

    /**
     * @var array
     */
    protected $_tableConfigs = array();

    /**
     * __construct()
     *
     * @param array|Zend_Config $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * setConfig()
     *
     * @param Zend_Config $config
     * @return Zend_Db_Table_Definition
     */
    public function setConfig(Zend_Config $config)
    {
        $this->setOptions($config->toArray());
        return $this;
    }

    /**
     * setOptions()
     *
     * @param array $options
     * @return Zend_Db_Table_Definition
     */
    public function setOptions(Array $options)
    {
        foreach ($options as $optionName => $optionValue) {
            $this->setTableConfig($optionName, $optionValue);
        }
        return $this;
    }

    /**
     * @param string $tableName
     * @param array  $tableConfig
     * @return Zend_Db_Table_Definition
     */
    public function setTableConfig($tableName, array $tableConfig)
    {
        // @todo logic here
        $tableConfig[Zend_Db_Table::DEFINITION_CONFIG_NAME] = $tableName;
        $tableConfig[Zend_Db_Table::DEFINITION] = $this;

        if (!isset($tableConfig[Zend_Db_Table::NAME])) {
            $tableConfig[Zend_Db_Table::NAME] = $tableName;
        }

        $this->_tableConfigs[$tableName] = $tableConfig;
        return $this;
    }

    /**
     * getTableConfig()
     *
     * @param string $tableName
     * @return array
     */
    public function getTableConfig($tableName)
    {
        return $this->_tableConfigs[$tableName];
    }

    /**
     * removeTableConfig()
     *
     * @param string $tableName
     */
    public function removeTableConfig($tableName)
    {
        unset($this->_tableConfigs[$tableName]);
    }

    /**
     * hasTableConfig()
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTableConfig($tableName)
    {
        return (isset($this->_tableConfigs[$tableName]));
    }

}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Rewrite.php 24182 2011-07-03 13:43:05Z adamlundrigan $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Router_Abstract */
require_once 'Zend/Controller/Router/Abstract.php';

/** Zend_Controller_Router_Route */
require_once 'Zend/Controller/Router/Route.php';

/**
 * Ruby routing based Router.
 *
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Zend_Controller_Router_Rewrite extends Zend_Controller_Router_Abstract
{

    /**
     * Whether or not to use default routes
     *
     * @var boolean
     */
    protected $_useDefaultRoutes = true;

    /**
     * Array of routes to match against
     *
     * @var array
     */
    protected $_routes = array();

    /**
     * Currently matched route
     *
     * @var Zend_Controller_Router_Route_Interface
     */
    protected $_currentRoute = null;

    /**
     * Global parameters given to all routes
     *
     * @var array
     */
    protected $_globalParams = array();

    /**
     * Separator to use with chain names
     *
     * @var string
     */
    protected $_chainNameSeparator = '-';

    /**
     * Determines if request parameters should be used as global parameters
     * inside this router.
     *
     * @var boolean
     */
    protected $_useCurrentParamsAsGlobal = false;

    /**
     * Add default routes which are used to mimic basic router behaviour
     *
     * @return Zend_Controller_Router_Rewrite
     */
    public function addDefaultRoutes()
    {
        if (!$this->hasRoute('default')) {
            $dispatcher = $this->getFrontController()->getDispatcher();
            $request = $this->getFrontController()->getRequest();

            require_once 'Zend/Controller/Router/Route/Module.php';
            $compat = new Zend_Controller_Router_Route_Module(array(), $dispatcher, $request);

            $this->_routes = array('default' => $compat) + $this->_routes;
        }

        return $this;
    }

    /**
     * Add route to the route chain
     *
     * If route contains method setRequest(), it is initialized with a request object
     *
     * @param  string                                 $name       Name of the route
     * @param  Zend_Controller_Router_Route_Interface $route      Instance of the route
     * @return Zend_Controller_Router_Rewrite
     */
    public function addRoute($name, Zend_Controller_Router_Route_Interface $route)
    {
        if (method_exists($route, 'setRequest')) {
            $route->setRequest($this->getFrontController()->getRequest());
        }

        $this->_routes[$name] = $route;

        return $this;
    }

    /**
     * Add routes to the route chain
     *
     * @param  array $routes Array of routes with names as keys and routes as values
     * @return Zend_Controller_Router_Rewrite
     */
    public function addRoutes($routes) {
        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }

        return $this;
    }

    /**
     * Create routes out of Zend_Config configuration
     *
     * Example INI:
     * routes.archive.route = "archive/:year/*"
     * routes.archive.defaults.controller = archive
     * routes.archive.defaults.action = show
     * routes.archive.defaults.year = 2000
     * routes.archive.reqs.year = "\d+"
     *
     * routes.news.type = "Zend_Controller_Router_Route_Static"
     * routes.news.route = "news"
     * routes.news.defaults.controller = "news"
     * routes.news.defaults.action = "list"
     *
     * And finally after you have created a Zend_Config with above ini:
     * $router = new Zend_Controller_Router_Rewrite();
     * $router->addConfig($config, 'routes');
     *
     * @param  Zend_Config $config  Configuration object
     * @param  string      $section Name of the config section containing route's definitions
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Router_Rewrite
     */
    public function addConfig(Zend_Config $config, $section = null)
    {
        if ($section !== null) {
            if ($config->{$section} === null) {
                require_once 'Zend/Controller/Router/Exception.php';
                throw new Zend_Controller_Router_Exception("No route configuration in section '{$section}'");
            }

            $config = $config->{$section};
        }

        foreach ($config as $name => $info) {
            $route = $this->_getRouteFromConfig($info);

            if ($route instanceof Zend_Controller_Router_Route_Chain) {
                if (!isset($info->chain)) {
                    require_once 'Zend/Controller/Router/Exception.php';
                    throw new Zend_Controller_Router_Exception("No chain defined");
                }

                if ($info->chain instanceof Zend_Config) {
                    $childRouteNames = $info->chain;
                } else {
                    $childRouteNames = explode(',', $info->chain);
                }

                foreach ($childRouteNames as $childRouteName) {
                    $childRoute = $this->getRoute(trim($childRouteName));
                    $route->chain($childRoute);
                }

                $this->addRoute($name, $route);
            } elseif (isset($info->chains) && $info->chains instanceof Zend_Config) {
                $this->_addChainRoutesFromConfig($name, $route, $info->chains);
            } else {
                $this->addRoute($name, $route);
            }
        }

        return $this;
    }

    /**
     * Get a route frm a config instance
     *
     * @param  Zend_Config $info
     * @return Zend_Controller_Router_Route_Interface
     */
    protected function _getRouteFromConfig(Zend_Config $info)
    {
        $class = (isset($info->type)) ? $info->type : 'Zend_Controller_Router_Route';
        if (!class_exists($class)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($class);
        }

        $route = call_user_func(array($class, 'getInstance'), $info);

        if (isset($info->abstract) && $info->abstract && method_exists($route, 'isAbstract')) {
            $route->isAbstract(true);
        }

        return $route;
    }

    /**
     * Add chain routes from a config route
     *
     * @param  string                                 $name
     * @param  Zend_Controller_Router_Route_Interface $route
     * @param  Zend_Config                            $childRoutesInfo
     * @return void
     */
    protected function _addChainRoutesFromConfig($name,
                                                 Zend_Controller_Router_Route_Interface $route,
                                                 Zend_Config $childRoutesInfo)
    {
        foreach ($childRoutesInfo as $childRouteName => $childRouteInfo) {
            if (is_string($childRouteInfo)) {
                $childRouteName = $childRouteInfo;
                $childRoute     = $this->getRoute($childRouteName);
            } else {
                $childRoute = $this->_getRouteFromConfig($childRouteInfo);
            }

            if ($route instanceof Zend_Controller_Router_Route_Chain) {
                $chainRoute = clone $route;
                $chainRoute->chain($childRoute);
            } else {
                $chainRoute = $route->chain($childRoute);
            }

            $chainName = $name . $this->_chainNameSeparator . $childRouteName;

            if (isset($childRouteInfo->chains)) {
                $this->_addChainRoutesFromConfig($chainName, $chainRoute, $childRouteInfo->chains);
            } else {
                $this->addRoute($chainName, $chainRoute);
            }
        }
    }

    /**
     * Remove a route from the route chain
     *
     * @param  string $name Name of the route
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Router_Rewrite
     */
    public function removeRoute($name)
    {
        if (!isset($this->_routes[$name])) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception("Route $name is not defined");
        }

        unset($this->_routes[$name]);

        return $this;
    }

    /**
     * Remove all standard default routes
     *
     * @param  Zend_Controller_Router_Route_Interface Route
     * @return Zend_Controller_Router_Rewrite
     */
    public function removeDefaultRoutes()
    {
        $this->_useDefaultRoutes = false;

        return $this;
    }

    /**
     * Check if named route exists
     *
     * @param  string $name Name of the route
     * @return boolean
     */
    public function hasRoute($name)
    {
        return isset($this->_routes[$name]);
    }

    /**
     * Retrieve a named route
     *
     * @param string $name Name of the route
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Router_Route_Interface Route object
     */
    public function getRoute($name)
    {
        if (!isset($this->_routes[$name])) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception("Route $name is not defined");
        }

        return $this->_routes[$name];
    }

    /**
     * Retrieve a currently matched route
     *
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Router_Route_Interface Route object
     */
    public function getCurrentRoute()
    {
        if (!isset($this->_currentRoute)) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception("Current route is not defined");
        }
        return $this->getRoute($this->_currentRoute);
    }

    /**
     * Retrieve a name of currently matched route
     *
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Router_Route_Interface Route object
     */
    public function getCurrentRouteName()
    {
        if (!isset($this->_currentRoute)) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception("Current route is not defined");
        }
        return $this->_currentRoute;
    }

    /**
     * Retrieve an array of routes added to the route chain
     *
     * @return array All of the defined routes
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Find a matching route to the current PATH_INFO and inject
     * returning values to the Request object.
     *
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Request_Abstract Request object
     */
    public function route(Zend_Controller_Request_Abstract $request)
    {
        if (!$request instanceof Zend_Controller_Request_Http) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception('Zend_Controller_Router_Rewrite requires a Zend_Controller_Request_Http-based request object');
        }

        if ($this->_useDefaultRoutes) {
            $this->addDefaultRoutes();
        }

        // Find the matching route
        $routeMatched = false;

        foreach (array_reverse($this->_routes, true) as $name => $route) {
            // TODO: Should be an interface method. Hack for 1.0 BC
            if (method_exists($route, 'isAbstract') && $route->isAbstract()) {
                continue;
            }

            // TODO: Should be an interface method. Hack for 1.0 BC
            if (!method_exists($route, 'getVersion') || $route->getVersion() == 1) {
                $match = $request->getPathInfo();
            } else {
                $match = $request;
            }

            if ($params = $route->match($match)) {
                $this->_setRequestParams($request, $params);
                $this->_currentRoute = $name;
                $routeMatched        = true;
                break;
            }
        }

         if (!$routeMatched) {
             require_once 'Zend/Controller/Router/Exception.php';
             throw new Zend_Controller_Router_Exception('No route matched the request', 404);
         }

        if($this->_useCurrentParamsAsGlobal) {
            $params = $request->getParams();
            foreach($params as $param => $value) {
                $this->setGlobalParam($param, $value);
            }
        }

        return $request;

    }

    protected function _setRequestParams($request, $params)
    {
        foreach ($params as $param => $value) {

            $request->setParam($param, $value);

            if ($param === $request->getModuleKey()) {
                $request->setModuleName($value);
            }
            if ($param === $request->getControllerKey()) {
                $request->setControllerName($value);
            }
            if ($param === $request->getActionKey()) {
                $request->setActionName($value);
            }

        }
    }

    /**
     * Generates a URL path that can be used in URL creation, redirection, etc.
     *
     * @param  array $userParams Options passed by a user used to override parameters
     * @param  mixed $name The name of a Route to use
     * @param  bool $reset Whether to reset to the route defaults ignoring URL params
     * @param  bool $encode Tells to encode URL parts on output
     * @throws Zend_Controller_Router_Exception
     * @return string Resulting absolute URL path
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        if (!is_array($userParams)) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception('userParams must be an array');
        }
        
        if ($name == null) {
            try {
                $name = $this->getCurrentRouteName();
            } catch (Zend_Controller_Router_Exception $e) {
                $name = 'default';
            }
        }

        // Use UNION (+) in order to preserve numeric keys
        $params = $userParams + $this->_globalParams;

        $route = $this->getRoute($name);
        $url   = $route->assemble($params, $reset, $encode);

        if (!preg_match('|^[a-z]+://|', $url)) {
            $url = rtrim($this->getFrontController()->getBaseUrl(), self::URI_DELIMITER) . self::URI_DELIMITER . $url;
        }

        return $url;
    }

    /**
     * Set a global parameter
     *
     * @param  string $name
     * @param  mixed $value
     * @return Zend_Controller_Router_Rewrite
     */
    public function setGlobalParam($name, $value)
    {
        $this->_globalParams[$name] = $value;

        return $this;
    }

    /**
     * Set the separator to use with chain names
     *
     * @param string $separator The separator to use
     * @return Zend_Controller_Router_Rewrite
     */
    public function setChainNameSeparator($separator) {
        $this->_chainNameSeparator = $separator;

        return $this;
    }

    /**
     * Get the separator to use for chain names
     *
     * @return string
     */
    public function getChainNameSeparator() {
        return $this->_chainNameSeparator;
    }

    /**
     * Determines/returns whether to use the request parameters as global parameters.
     *
     * @param boolean|null $use
     *           Null/unset when you want to retrieve the current state.
     *           True when request parameters should be global, false otherwise
     * @return boolean|Zend_Controller_Router_Rewrite
     *              Returns a boolean if first param isn't set, returns an
     *              instance of Zend_Controller_Router_Rewrite otherwise.
     *
     */
    public function useRequestParametersAsGlobal($use = null) {
        if($use === null) {
            return $this->_useCurrentParamsAsGlobal;
        }

        $this->_useCurrentParamsAsGlobal = (bool) $use;

        return $this;
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 24182 2011-07-03 13:43:05Z adamlundrigan $
 */


/** Zend_Controller_Router_Interface */
require_once 'Zend/Controller/Router/Interface.php';

/**
 * Simple first implementation of a router, to be replaced
 * with rules-based URI processor.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Router_Abstract implements Zend_Controller_Router_Interface
{
    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';
    
    /**
     * Front controller instance
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * Array of invocation parameters to use when instantiating action
     * controllers
     * @var array
     */
    protected $_invokeParams = array();

    /**
     * Constructor
     *
     * @param array $params
     * @return void
     */
    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    /**
     * Add or modify a parameter to use when instantiating an action controller
     *
     * @param string $name
     * @param mixed $value
     * @return Zend_Controller_Router
     */
    public function setParam($name, $value)
    {
        $name = (string) $name;
        $this->_invokeParams[$name] = $value;
        return $this;
    }

    /**
     * Set parameters to pass to action controller constructors
     *
     * @param array $params
     * @return Zend_Controller_Router
     */
    public function setParams(array $params)
    {
        $this->_invokeParams = array_merge($this->_invokeParams, $params);
        return $this;
    }

    /**
     * Retrieve a single parameter from the controller parameter stack
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        if(isset($this->_invokeParams[$name])) {
            return $this->_invokeParams[$name];
        }

        return null;
    }

    /**
     * Retrieve action controller instantiation parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_invokeParams;
    }

    /**
     * Clear the controller parameter stack
     *
     * By default, clears all parameters. If a parameter name is given, clears
     * only that parameter; if an array of parameter names is provided, clears
     * each.
     *
     * @param null|string|array single key or array of keys for params to clear
     * @return Zend_Controller_Router
     */
    public function clearParams($name = null)
    {
        if (null === $name) {
            $this->_invokeParams = array();
        } elseif (is_string($name) && isset($this->_invokeParams[$name])) {
            unset($this->_invokeParams[$name]);
        } elseif (is_array($name)) {
            foreach ($name as $key) {
                if (is_string($key) && isset($this->_invokeParams[$key])) {
                    unset($this->_invokeParams[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve Front Controller
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        // Used cache version if found
        if (null !== $this->_frontController) {
            return $this->_frontController;
        }

        require_once 'Zend/Controller/Front.php';
        $this->_frontController = Zend_Controller_Front::getInstance();
        return $this->_frontController;
    }

    /**
     * Set Front Controller
     *
     * @param Zend_Controller_Front $controller
     * @return Zend_Controller_Router_Interface
     */
    public function setFrontController(Zend_Controller_Front $controller)
    {
        $this->_frontController = $controller;
        return $this;
    }

}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Controller_Router_Interface
{
    /**
     * Processes a request and sets its controller and action.  If
     * no route was possible, an exception is thrown.
     *
     * @param  Zend_Controller_Request_Abstract
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Request_Abstract|boolean
     */
    public function route(Zend_Controller_Request_Abstract $dispatcher);

    /**
     * Generates a URL path that can be used in URL creation, redirection, etc.
     *
     * May be passed user params to override ones from URI, Request or even defaults.
     * If passed parameter has a value of null, it's URL variable will be reset to
     * default.
     *
     * If null is passed as a route name assemble will use the current Route or 'default'
     * if current is not yet set.
     *
     * Reset is used to signal that all parameters should be reset to it's defaults.
     * Ignoring all URL specified values. User specified params still get precedence.
     *
     * Encode tells to url encode resulting path parts.
     *
     * @param  array $userParams Options passed by a user used to override parameters
     * @param  mixed $name The name of a Route to use
     * @param  bool $reset Whether to reset to the route defaults ignoring URL params
     * @param  bool $encode Tells to encode URL parts on output
     * @throws Zend_Controller_Router_Exception
     * @return string Resulting URL path
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true);

    /**
     * Retrieve Front Controller
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController();

    /**
     * Set Front Controller
     *
     * @param Zend_Controller_Front $controller
     * @return Zend_Controller_Router_Interface
     */
    public function setFrontController(Zend_Controller_Front $controller);

    /**
     * Add or modify a parameter with which to instantiate any helper objects
     *
     * @param string $name
     * @param mixed $param
     * @return Zend_Controller_Router_Interface
     */
    public function setParam($name, $value);

    /**
     * Set an array of a parameters to pass to helper object constructors
     *
     * @param array $params
     * @return Zend_Controller_Router_Interface
     */
    public function setParams(array $params);

    /**
     * Retrieve a single parameter from the controller parameter stack
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name);

    /**
     * Retrieve the parameters to pass to helper object constructors
     *
     * @return array
     */
    public function getParams();

    /**
     * Clear the controller parameter stack
     *
     * By default, clears all parameters. If a parameter name is given, clears
     * only that parameter; if an array of parameter names is provided, clears
     * each.
     *
     * @param null|string|array single key or array of keys for params to clear
     * @return Zend_Controller_Router_Interface
     */
    public function clearParams($name = null);

}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Route.php 24183 2011-07-04 16:08:16Z guilhermeblanco $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Router_Route_Abstract */
require_once 'Zend/Controller/Router/Route/Abstract.php';

/**
 * Route
 *
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Zend_Controller_Router_Route extends Zend_Controller_Router_Route_Abstract
{
    /**
     * Default translator
     *
     * @var Zend_Translate
     */
    protected static $_defaultTranslator;

    /**
     * Translator
     *
     * @var Zend_Translate
     */
    protected $_translator;

    /**
     * Default locale
     *
     * @var mixed
     */
    protected static $_defaultLocale;

    /**
     * Locale
     *
     * @var mixed
     */
    protected $_locale;

    /**
     * Wether this is a translated route or not
     *
     * @var boolean
     */
    protected $_isTranslated = false;

    /**
     * Translatable variables
     *
     * @var array
     */
    protected $_translatable = array();

    protected $_urlVariable = ':';
    protected $_urlDelimiter = self::URI_DELIMITER;
    protected $_regexDelimiter = '#';
    protected $_defaultRegex = null;

    /**
     * Holds names of all route's pattern variable names. Array index holds a position in URL.
     * @var array
     */
    protected $_variables = array();

    /**
     * Holds Route patterns for all URL parts. In case of a variable it stores it's regex
     * requirement or null. In case of a static part, it holds only it's direct value.
     * In case of a wildcard, it stores an asterisk (*)
     * @var array
     */
    protected $_parts = array();

    /**
     * Holds user submitted default values for route's variables. Name and value pairs.
     * @var array
     */
    protected $_defaults = array();

    /**
     * Holds user submitted regular expression patterns for route's variables' values.
     * Name and value pairs.
     * @var array
     */
    protected $_requirements = array();

    /**
     * Associative array filled on match() that holds matched path values
     * for given variable names.
     * @var array
     */
    protected $_values = array();

    /**
     * Associative array filled on match() that holds wildcard variable
     * names and values.
     * @var array
     */
    protected $_wildcardData = array();

    /**
     * Helper var that holds a count of route pattern's static parts
     * for validation
     * @var int
     */
    protected $_staticCount = 0;

    public function getVersion() {
        return 1;
    }

    /**
     * Instantiates route based on passed Zend_Config structure
     *
     * @param Zend_Config $config Configuration object
     */
    public static function getInstance(Zend_Config $config)
    {
        $reqs = ($config->reqs instanceof Zend_Config) ? $config->reqs->toArray() : array();
        $defs = ($config->defaults instanceof Zend_Config) ? $config->defaults->toArray() : array();
        return new self($config->route, $defs, $reqs);
    }

    /**
     * Prepares the route for mapping by splitting (exploding) it
     * to a corresponding atomic parts. These parts are assigned
     * a position which is later used for matching and preparing values.
     *
     * @param string $route Map used to match with later submitted URL path
     * @param array $defaults Defaults for map variables with keys as variable names
     * @param array $reqs Regular expression requirements for variables (keys as variable names)
     * @param Zend_Translate $translator Translator to use for this instance
     */
    public function __construct($route, $defaults = array(), $reqs = array(), Zend_Translate $translator = null, $locale = null)
    {
        $route               = trim($route, $this->_urlDelimiter);
        $this->_defaults     = (array) $defaults;
        $this->_requirements = (array) $reqs;
        $this->_translator   = $translator;
        $this->_locale       = $locale;

        if ($route !== '') {
            foreach (explode($this->_urlDelimiter, $route) as $pos => $part) {
                if (substr($part, 0, 1) == $this->_urlVariable && substr($part, 1, 1) != $this->_urlVariable) {
                    $name = substr($part, 1);

                    if (substr($name, 0, 1) === '@' && substr($name, 1, 1) !== '@') {
                        $name                  = substr($name, 1);
                        $this->_translatable[] = $name;
                        $this->_isTranslated   = true;
                    }

                    $this->_parts[$pos]     = (isset($reqs[$name]) ? $reqs[$name] : $this->_defaultRegex);
                    $this->_variables[$pos] = $name;
                } else {
                    if (substr($part, 0, 1) == $this->_urlVariable) {
                        $part = substr($part, 1);
                    }

                    if (substr($part, 0, 1) === '@' && substr($part, 1, 1) !== '@') {
                        $this->_isTranslated = true;
                    }

                    $this->_parts[$pos] = $part;

                    if ($part !== '*') {
                        $this->_staticCount++;
                    }
                }
            }
        }
    }

    /**
     * Matches a user submitted path with parts defined by a map. Assigns and
     * returns an array of variables on a successful match.
     *
     * @param string $path Path used to match against this routing map
     * @return array|false An array of assigned values or a false on a mismatch
     */
    public function match($path, $partial = false)
    {
        if ($this->_isTranslated) {
            $translateMessages = $this->getTranslator()->getMessages();
        }

        $pathStaticCount = 0;
        $values          = array();
        $matchedPath     = '';

        if (!$partial) {
            $path = trim($path, $this->_urlDelimiter);
        }

        if ($path !== '') {
            $path = explode($this->_urlDelimiter, $path);

            foreach ($path as $pos => $pathPart) {
                // Path is longer than a route, it's not a match
                if (!array_key_exists($pos, $this->_parts)) {
                    if ($partial) {
                        break;
                    } else {
                        return false;
                    }
                }

                $matchedPath .= $pathPart . $this->_urlDelimiter;

                // If it's a wildcard, get the rest of URL as wildcard data and stop matching
                if ($this->_parts[$pos] == '*') {
                    $count = count($path);
                    for($i = $pos; $i < $count; $i+=2) {
                        $var = urldecode($path[$i]);
                        if (!isset($this->_wildcardData[$var]) && !isset($this->_defaults[$var]) && !isset($values[$var])) {
                            $this->_wildcardData[$var] = (isset($path[$i+1])) ? urldecode($path[$i+1]) : null;
                        }
                    }

                    $matchedPath = implode($this->_urlDelimiter, $path);
                    break;
                }

                $name     = isset($this->_variables[$pos]) ? $this->_variables[$pos] : null;
                $pathPart = urldecode($pathPart);

                // Translate value if required
                $part = $this->_parts[$pos];
                if ($this->_isTranslated && (substr($part, 0, 1) === '@' && substr($part, 1, 1) !== '@' && $name === null) || $name !== null && in_array($name, $this->_translatable)) {
                    if (substr($part, 0, 1) === '@') {
                        $part = substr($part, 1);
                    }

                    if (($originalPathPart = array_search($pathPart, $translateMessages)) !== false) {
                        $pathPart = $originalPathPart;
                    }
                }

                if (substr($part, 0, 2) === '@@') {
                    $part = substr($part, 1);
                }

                // If it's a static part, match directly
                if ($name === null && $part != $pathPart) {
                    return false;
                }

                // If it's a variable with requirement, match a regex. If not - everything matches
                if ($part !== null && !preg_match($this->_regexDelimiter . '^' . $part . '$' . $this->_regexDelimiter . 'iu', $pathPart)) {
                    return false;
                }

                // If it's a variable store it's value for later
                if ($name !== null) {
                    $values[$name] = $pathPart;
                } else {
                    $pathStaticCount++;
                }
            }
        }

        // Check if all static mappings have been matched
        if ($this->_staticCount != $pathStaticCount) {
            return false;
        }

        $return = $values + $this->_wildcardData + $this->_defaults;

        // Check if all map variables have been initialized
        foreach ($this->_variables as $var) {
            if (!array_key_exists($var, $return)) {
                return false;
            } elseif ($return[$var] == '' || $return[$var] === null) {
                // Empty variable? Replace with the default value.
                $return[$var] = $this->_defaults[$var];
            }
        }

        $this->setMatchedPath(rtrim($matchedPath, $this->_urlDelimiter));

        $this->_values = $values;

        return $return;

    }

    /**
     * Assembles user submitted parameters forming a URL path defined by this route
     *
     * @param  array $data An array of variable and value pairs used as parameters
     * @param  boolean $reset Whether or not to set route defaults with those provided in $data
     * @return string Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = false, $partial = false)
    {
        if ($this->_isTranslated) {
            $translator = $this->getTranslator();

            if (isset($data['@locale'])) {
                $locale = $data['@locale'];
                unset($data['@locale']);
            } else {
                $locale = $this->getLocale();
            }
        }

        $url  = array();
        $flag = false;

        foreach ($this->_parts as $key => $part) {
            $name = isset($this->_variables[$key]) ? $this->_variables[$key] : null;

            $useDefault = false;
            if (isset($name) && array_key_exists($name, $data) && $data[$name] === null) {
                $useDefault = true;
            }

            if (isset($name)) {
                if (isset($data[$name]) && !$useDefault) {
                    $value = $data[$name];
                    unset($data[$name]);
                } elseif (!$reset && !$useDefault && isset($this->_values[$name])) {
                    $value = $this->_values[$name];
                } elseif (!$reset && !$useDefault && isset($this->_wildcardData[$name])) {
                    $value = $this->_wildcardData[$name];
                } elseif (array_key_exists($name, $this->_defaults)) {
                    $value = $this->_defaults[$name];
                } else {
                    require_once 'Zend/Controller/Router/Exception.php';
                    throw new Zend_Controller_Router_Exception($name . ' is not specified');
                }

                if ($this->_isTranslated && in_array($name, $this->_translatable)) {
                    $url[$key] = $translator->translate($value, $locale);
                } else {
                    $url[$key] = $value;
                }
            } elseif ($part != '*') {
                if ($this->_isTranslated && substr($part, 0, 1) === '@') {
                    if (substr($part, 1, 1) !== '@') {
                        $url[$key] = $translator->translate(substr($part, 1), $locale);
                    } else {
                        $url[$key] = substr($part, 1);
                    }
                } else {
                    if (substr($part, 0, 2) === '@@') {
                        $part = substr($part, 1);
                    }

                    $url[$key] = $part;
                }
            } else {
                if (!$reset) $data += $this->_wildcardData;
                $defaults = $this->getDefaults();
                foreach ($data as $var => $value) {
                    if ($value !== null && (!isset($defaults[$var]) || $value != $defaults[$var])) {
                        $url[$key++] = $var;
                        $url[$key++] = $value;
                        $flag = true;
                    }
                }
            }
        }

        $return = '';

        foreach (array_reverse($url, true) as $key => $value) {
            $defaultValue = null;

            if (isset($this->_variables[$key])) {
                $defaultValue = $this->getDefault($this->_variables[$key]);

                if ($this->_isTranslated && $defaultValue !== null && isset($this->_translatable[$this->_variables[$key]])) {
                    $defaultValue = $translator->translate($defaultValue, $locale);
                }
            }

            if ($flag || $value !== $defaultValue || $partial) {
                if ($encode) $value = urlencode($value);
                $return = $this->_urlDelimiter . $value . $return;
                $flag = true;
            }
        }

        return trim($return, $this->_urlDelimiter);

    }

    /**
     * Return a single parameter of route's defaults
     *
     * @param string $name Array key of the parameter
     * @return string Previously set default
     */
    public function getDefault($name) {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
        return null;
    }

    /**
     * Return an array of defaults
     *
     * @return array Route defaults
     */
    public function getDefaults() {
        return $this->_defaults;
    }

    /**
     * Get all variables which are used by the route
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->_variables;
    }

    /**
     * Set a default translator
     *
     * @param  Zend_Translate $translator
     * @return void
     */
    public static function setDefaultTranslator(Zend_Translate $translator = null)
    {
        self::$_defaultTranslator = $translator;
    }

    /**
     * Get the default translator
     *
     * @return Zend_Translate
     */
    public static function getDefaultTranslator()
    {
        return self::$_defaultTranslator;
    }

    /**
     * Set a translator
     *
     * @param  Zend_Translate $translator
     * @return void
     */
    public function setTranslator(Zend_Translate $translator)
    {
        $this->_translator = $translator;
    }

    /**
     * Get the translator
     *
     * @throws Zend_Controller_Router_Exception When no translator can be found
     * @return Zend_Translate
     */
    public function getTranslator()
    {
        if ($this->_translator !== null) {
            return $this->_translator;
        } else if (($translator = self::getDefaultTranslator()) !== null) {
            return $translator;
        } else {
            try {
                $translator = Zend_Registry::get('Zend_Translate');
            } catch (Zend_Exception $e) {
                $translator = null;
            }

            if ($translator instanceof Zend_Translate) {
                return $translator;
            }
        }

        require_once 'Zend/Controller/Router/Exception.php';
        throw new Zend_Controller_Router_Exception('Could not find a translator');
    }

    /**
     * Set a default locale
     *
     * @param  mixed $locale
     * @return void
     */
    public static function setDefaultLocale($locale = null)
    {
        self::$_defaultLocale = $locale;
    }

    /**
     * Get the default locale
     *
     * @return mixed
     */
    public static function getDefaultLocale()
    {
        return self::$_defaultLocale;
    }

    /**
     * Set a locale
     *
     * @param  mixed $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->_locale = $locale;
    }

    /**
     * Get the locale
     *
     * @return mixed
     */
    public function getLocale()
    {
        if ($this->_locale !== null) {
            return $this->_locale;
        } else if (($locale = self::getDefaultLocale()) !== null) {
            return $locale;
        } else {
            try {
                $locale = Zend_Registry::get('Zend_Locale');
            } catch (Zend_Exception $e) {
                $locale = null;
            }

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Abstract.php 24182 2011-07-03 13:43:05Z adamlundrigan $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Controller_Router_Route_Interface
 */
require_once 'Zend/Controller/Router/Route/Interface.php';

/**
 * Abstract Route
 *
 * Implements interface and provides convenience methods
 *
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Controller_Router_Route_Abstract implements Zend_Controller_Router_Route_Interface
{
    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';
    
    /**
     * Wether this route is abstract or not
     *
     * @var boolean
     */
    protected $_isAbstract = false;

    /**
     * Path matched by this route
     *
     * @var string
     */
    protected $_matchedPath = null;

    /**
     * Get the version of the route
     *
     * @return integer
     */
    public function getVersion()
    {
        return 2;
    }

    /**
     * Set partially matched path
     *
     * @param  string $path
     * @return void
     */
    public function setMatchedPath($path)
    {
        $this->_matchedPath = $path;
    }

    /**
     * Get partially matched path
     *
     * @return string
     */
    public function getMatchedPath()
    {
        return $this->_matchedPath;
    }

    /**
     * Check or set wether this is an abstract route or not
     *
     * @param  boolean $flag
     * @return boolean
     */
    public function isAbstract($flag = null)
    {
        if ($flag !== null) {
            $this->_isAbstract = $flag;
        }

        return $this->_isAbstract;
    }

    /**
     * Create a new chain
     *
     * @param  Zend_Controller_Router_Route_Abstract $route
     * @param  string                                $separator
     * @return Zend_Controller_Router_Route_Chain
     */
    public function chain(Zend_Controller_Router_Route_Abstract $route, $separator = '/')
    {
        require_once 'Zend/Controller/Router/Route/Chain.php';

        $chain = new Zend_Controller_Router_Route_Chain();
        $chain->chain($this)->chain($route, $separator);

        return $chain;
    }

}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Config */
require_once 'Zend/Config.php';

/**
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Controller_Router_Route_Interface {
    public function match($path);
    public function assemble($data = array(), $reset = false, $encode = false);
    public static function getInstance(Zend_Config $config);
}


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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Plugin_Abstract */
require_once 'Zend/Controller/Plugin/Abstract.php';

/**
 * Handle exceptions that bubble up based on missing controllers, actions, or
 * application errors, and forward to an error handler.
 *
 * @uses       Zend_Controller_Plugin_Abstract
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: ErrorHandler.php 24241 2011-07-14 08:09:41Z bate $
 */
class Zend_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_Abstract
{
    /**
     * Const - No controller exception; controller does not exist
     */
    const EXCEPTION_NO_CONTROLLER = 'EXCEPTION_NO_CONTROLLER';

    /**
     * Const - No action exception; controller exists, but action does not
     */
    const EXCEPTION_NO_ACTION = 'EXCEPTION_NO_ACTION';

    /**
     * Const - No route exception; no routing was possible
     */
    const EXCEPTION_NO_ROUTE = 'EXCEPTION_NO_ROUTE';

    /**
     * Const - Other Exception; exceptions thrown by application controllers
     */
    const EXCEPTION_OTHER = 'EXCEPTION_OTHER';

    /**
     * Module to use for errors; defaults to default module in dispatcher
     * @var string
     */
    protected $_errorModule;

    /**
     * Controller to use for errors; defaults to 'error'
     * @var string
     */
    protected $_errorController = 'error';

    /**
     * Action to use for errors; defaults to 'error'
     * @var string
     */
    protected $_errorAction = 'error';

    /**
     * Flag; are we already inside the error handler loop?
     * @var bool
     */
    protected $_isInsideErrorHandlerLoop = false;

    /**
     * Exception count logged at first invocation of plugin
     * @var int
     */
    protected $_exceptionCountAtFirstEncounter = 0;

    /**
     * Constructor
     *
     * Options may include:
     * - module
     * - controller
     * - action
     *
     * @param  Array $options
     * @return void
     */
    public function __construct(Array $options = array())
    {
        $this->setErrorHandler($options);
    }

    /**
     * setErrorHandler() - setup the error handling options
     *
     * @param  array $options
     * @return Zend_Controller_Plugin_ErrorHandler
     */
    public function setErrorHandler(Array $options = array())
    {
        if (isset($options['module'])) {
            $this->setErrorHandlerModule($options['module']);
        }
        if (isset($options['controller'])) {
            $this->setErrorHandlerController($options['controller']);
        }
        if (isset($options['action'])) {
            $this->setErrorHandlerAction($options['action']);
        }
        return $this;
    }

    /**
     * Set the module name for the error handler
     *
     * @param  string $module
     * @return Zend_Controller_Plugin_ErrorHandler
     */
    public function setErrorHandlerModule($module)
    {
        $this->_errorModule = (string) $module;
        return $this;
    }

    /**
     * Retrieve the current error handler module
     *
     * @return string
     */
    public function getErrorHandlerModule()
    {
        if (null === $this->_errorModule) {
            $this->_errorModule = Zend_Controller_Front::getInstance()->getDispatcher()->getDefaultModule();
        }
        return $this->_errorModule;
    }

    /**
     * Set the controller name for the error handler
     *
     * @param  string $controller
     * @return Zend_Controller_Plugin_ErrorHandler
     */
    public function setErrorHandlerController($controller)
    {
        $this->_errorController = (string) $controller;
        return $this;
    }

    /**
     * Retrieve the current error handler controller
     *
     * @return string
     */
    public function getErrorHandlerController()
    {
        return $this->_errorController;
    }

    /**
     * Set the action name for the error handler
     *
     * @param  string $action
     * @return Zend_Controller_Plugin_ErrorHandler
     */
    public function setErrorHandlerAction($action)
    {
        $this->_errorAction = (string) $action;
        return $this;
    }

    /**
     * Retrieve the current error handler action
     *
     * @return string
     */
    public function getErrorHandlerAction()
    {
        return $this->_errorAction;
    }

    /**
     * Route shutdown hook -- Ccheck for router exceptions
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError($request);
    }

    /**
     * Pre dispatch hook -- check for exceptions and dispatch error handler if
     * necessary
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError($request);
    }
	
    /**
     * Post dispatch hook -- check for exceptions and dispatch error handler if
     * necessary
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError($request);
    }

    /**
     * Handle errors and exceptions
     *
     * If the 'noErrorHandler' front controller flag has been set,
     * returns early.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    protected function _handleError(Zend_Controller_Request_Abstract $request)
    {
        $frontController = Zend_Controller_Front::getInstance();
        if ($frontController->getParam('noErrorHandler')) {
            return;
        }

        $response = $this->getResponse();

        if ($this->_isInsideErrorHandlerLoop) {
            $exceptions = $response->getException();
            if (count($exceptions) > $this->_exceptionCountAtFirstEncounter) {
                // Exception thrown by error handler; tell the front controller to throw it
                $frontController->throwExceptions(true);
                throw array_pop($exceptions);
            }
        }

        // check for an exception AND allow the error handler controller the option to forward
        if (($response->isException()) && (!$this->_isInsideErrorHandlerLoop)) {
            $this->_isInsideErrorHandlerLoop = true;

            // Get exception information
            $error            = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
            $exceptions       = $response->getException();
            $exception        = $exceptions[0];
            $exceptionType    = get_class($exception);
            $error->exception = $exception;
            switch ($exceptionType) {
                case 'Zend_Controller_Router_Exception':
                    if (404 == $exception->getCode()) {
                        $error->type = self::EXCEPTION_NO_ROUTE;
                    } else {
                        $error->type = self::EXCEPTION_OTHER;
                    }
                    break;
                case 'Zend_Controller_Dispatcher_Exception':
                    $error->type = self::EXCEPTION_NO_CONTROLLER;
                    break;
                case 'Zend_Controller_Action_Exception':
                    if (404 == $exception->getCode()) {
                        $error->type = self::EXCEPTION_NO_ACTION;
                    } else {
                        $error->type = self::EXCEPTION_OTHER;
                    }
                    break;
                default:
                    $error->type = self::EXCEPTION_OTHER;
                    break;
            }

            // Keep a copy of the original request
            $error->request = clone $request;

            // get a count of the number of exceptions encountered
            $this->_exceptionCountAtFirstEncounter = count($exceptions);

            // Forward to the error handler
            $request->setParam('error_handler', $error)
                    ->setModuleName($this->getErrorHandlerModule())
                    ->setControllerName($this->getErrorHandlerController())
                    ->setActionName($this->getErrorHandlerAction())
                    ->setDispatched(false);
        }
    }
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Http.php 24008 2011-05-04 18:11:15Z ralph $
 */

/** @see Zend_Controller_Request_Abstract */
require_once 'Zend/Controller/Request/Abstract.php';

/** @see Zend_Uri */
require_once 'Zend/Uri.php';

/**
 * Zend_Controller_Request_Http
 *
 * HTTP request object for use with Zend_Controller family.
 *
 * @uses Zend_Controller_Request_Abstract
 * @package Zend_Controller
 * @subpackage Request
 */
class Zend_Controller_Request_Http extends Zend_Controller_Request_Abstract
{
    /**
     * Scheme for http
     *
     */
    const SCHEME_HTTP  = 'http';

    /**
     * Scheme for https
     *
     */
    const SCHEME_HTTPS = 'https';

    /**
     * Allowed parameter sources
     * @var array
     */
    protected $_paramSources = array('_GET', '_POST');

    /**
     * REQUEST_URI
     * @var string;
     */
    protected $_requestUri;

    /**
     * Base URL of request
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Base path of request
     * @var string
     */
    protected $_basePath = null;

    /**
     * PATH_INFO
     * @var string
     */
    protected $_pathInfo = '';

    /**
     * Instance parameters
     * @var array
     */
    protected $_params = array();

    /**
     * Raw request body
     * @var string|false
     */
    protected $_rawBody;

    /**
     * Alias keys for request parameters
     * @var array
     */
    protected $_aliases = array();

    /**
     * Constructor
     *
     * If a $uri is passed, the object will attempt to populate itself using
     * that information.
     *
     * @param string|Zend_Uri $uri
     * @return void
     * @throws Zend_Controller_Request_Exception when invalid URI passed
     */
    public function __construct($uri = null)
    {
        if (null !== $uri) {
            if (!$uri instanceof Zend_Uri) {
                $uri = Zend_Uri::factory($uri);
            }
            if ($uri->valid()) {
                $path  = $uri->getPath();
                $query = $uri->getQuery();
                if (!empty($query)) {
                    $path .= '?' . $query;
                }

                $this->setRequestUri($path);
            } else {
                require_once 'Zend/Controller/Request/Exception.php';
                throw new Zend_Controller_Request_Exception('Invalid URI provided to constructor');
            }
        } else {
            $this->setRequestUri();
        }
    }

    /**
     * Access values contained in the superglobals as public members
     * Order of precedence: 1. GET, 2. POST, 3. COOKIE, 4. SERVER, 5. ENV
     *
     * @see http://msdn.microsoft.com/en-us/library/system.web.httprequest.item.aspx
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        switch (true) {
            case isset($this->_params[$key]):
                return $this->_params[$key];
            case isset($_GET[$key]):
                return $_GET[$key];
            case isset($_POST[$key]):
                return $_POST[$key];
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case ($key == 'REQUEST_URI'):
                return $this->getRequestUri();
            case ($key == 'PATH_INFO'):
                return $this->getPathInfo();
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            default:
                return null;
        }
    }

    /**
     * Alias to __get
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->__get($key);
    }

    /**
     * Set values
     *
     * In order to follow {@link __get()}, which operates on a number of
     * superglobals, setting values through overloading is not allowed and will
     * raise an exception. Use setParam() instead.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Zend_Controller_Request_Exception
     */
    public function __set($key, $value)
    {
        require_once 'Zend/Controller/Request/Exception.php';
        throw new Zend_Controller_Request_Exception('Setting values in superglobals not allowed; please use setParam()');
    }

    /**
     * Alias to __set()
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        return $this->__set($key, $value);
    }

    /**
     * Check to see if a property is set
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        switch (true) {
            case isset($this->_params[$key]):
                return true;
            case isset($_GET[$key]):
                return true;
            case isset($_POST[$key]):
                return true;
            case isset($_COOKIE[$key]):
                return true;
            case isset($_SERVER[$key]):
                return true;
            case isset($_ENV[$key]):
                return true;
            default:
                return false;
        }
    }

    /**
     * Alias to __isset()
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->__isset($key);
    }

    /**
     * Set GET values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return Zend_Controller_Request_Http
     */
    public function setQuery($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Invalid value passed to setQuery(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setQuery($key, $value);
            }
            return $this;
        }
        $_GET[(string) $spec] = $value;
        return $this;
    }

    /**
     * Retrieve a member of the $_GET superglobal
     *
     * If no $key is passed, returns the entire $_GET array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getQuery($key = null, $default = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }

    /**
     * Set POST values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return Zend_Controller_Request_Http
     */
    public function setPost($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            require_once 'Zend/Controller/Exception.php';
            throw new Zend_Controller_Exception('Invalid value passed to setPost(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setPost($key, $value);
            }
            return $this;
        }
        $_POST[(string) $spec] = $value;
        return $this;
    }

    /**
     * Retrieve a member of the $_POST superglobal
     *
     * If no $key is passed, returns the entire $_POST array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getPost($key = null, $default = null)
    {
        if (null === $key) {
            return $_POST;
        }

        return (isset($_POST[$key])) ? $_POST[$key] : $default;
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Retrieve a member of the $_ENV superglobal
     *
     * If no $key is passed, returns the entire $_ENV array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getEnv($key = null, $default = null)
    {
        if (null === $key) {
            return $_ENV;
        }

        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }

    /**
     * Set the REQUEST_URI on which the instance operates
     *
     * If no request URI is passed, uses the value in $_SERVER['REQUEST_URI'],
     * $_SERVER['HTTP_X_REWRITE_URL'], or $_SERVER['ORIG_PATH_INFO'] + $_SERVER['QUERY_STRING'].
     *
     * @param string $requestUri
     * @return Zend_Controller_Request_Http
     */
    public function setRequestUri($requestUri = null)
    {
        if ($requestUri === null) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
                $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            } elseif (
                // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
                isset($_SERVER['IIS_WasUrlRewritten'])
                && $_SERVER['IIS_WasUrlRewritten'] == '1'
                && isset($_SERVER['UNENCODED_URL'])
                && $_SERVER['UNENCODED_URL'] != ''
                ) {
                $requestUri = $_SERVER['UNENCODED_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
                // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
                $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
                if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                    $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
                }
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
                $requestUri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $requestUri .= '?' . $_SERVER['QUERY_STRING'];
                }
            } else {
                return $this;
            }
        } elseif (!is_string($requestUri)) {
            return $this;
        } else {
            // Set GET items, if available
            if (false !== ($pos = strpos($requestUri, '?'))) {
                // Get key => value pairs and set $_GET
                $query = substr($requestUri, $pos + 1);
                parse_str($query, $vars);
                $this->setQuery($vars);
            }
        }

        $this->_requestUri = $requestUri;
        return $this;
    }

    /**
     * Returns the REQUEST_URI taking into account
     * platform differences between Apache and IIS
     *
     * @return string
     */
    public function getRequestUri()
    {
        if (empty($this->_requestUri)) {
            $this->setRequestUri();
        }

        return $this->_requestUri;
    }

    /**
     * Set the base URL of the request; i.e., the segment leading to the script name
     *
     * E.g.:
     * - /admin
     * - /myapp
     * - /subdir/index.php
     *
     * Do not use the full URI when providing the base. The following are
     * examples of what not to use:
     * - http://example.com/admin (should be just /admin)
     * - http://example.com/subdir/index.php (should be just /subdir/index.php)
     *
     * If no $baseUrl is provided, attempts to determine the base URL from the
     * environment, using SCRIPT_FILENAME, SCRIPT_NAME, PHP_SELF, and
     * ORIG_SCRIPT_NAME in its determination.
     *
     * @param mixed $baseUrl
     * @return Zend_Controller_Request_Http
     */
    public function setBaseUrl($baseUrl = null)
    {
        if ((null !== $baseUrl) && !is_string($baseUrl)) {
            return $this;
        }

        if ($baseUrl === null) {
            $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
                $baseUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
            } else {
                // Backtrack up the script_filename to find the portion matching
                // php_self
                $path    = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
                $file    = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
                $segs    = explode('/', trim($file, '/'));
                $segs    = array_reverse($segs);
                $index   = 0;
                $last    = count($segs);
                $baseUrl = '';
                do {
                    $seg     = $segs[$index];
                    $baseUrl = '/' . $seg . $baseUrl;
                    ++$index;
                } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
            }

            // Does the baseUrl have anything in common with the request_uri?
            $requestUri = $this->getRequestUri();

            if (0 === strpos($requestUri, $baseUrl)) {
                // full $baseUrl matches
                $this->_baseUrl = $baseUrl;
                return $this;
            }

            if (0 === strpos($requestUri, dirname($baseUrl))) {
                // directory portion of $baseUrl matches
                $this->_baseUrl = rtrim(dirname($baseUrl), '/');
                return $this;
            }

            $truncatedRequestUri = $requestUri;
            if (($pos = strpos($requestUri, '?')) !== false) {
                $truncatedRequestUri = substr($requestUri, 0, $pos);
            }

            $basename = basename($baseUrl);
            if (empty($basename) || !strpos($truncatedRequestUri, $basename)) {
                // no match whatsoever; set it blank
                $this->_baseUrl = '';
                return $this;
            }

            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            if ((strlen($requestUri) >= strlen($baseUrl))
                && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
            {
                $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
            }
        }

        $this->_baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Everything in REQUEST_URI before PATH_INFO
     * <form action="<?=$baseUrl?>/news/submit" method="POST"/>
     *
     * @return string
     */
    public function getBaseUrl($raw = false)
    {
        if (null === $this->_baseUrl) {
            $this->setBaseUrl();
        }

        return (($raw == false) ? urldecode($this->_baseUrl) : $this->_baseUrl);
    }

    /**
     * Set the base path for the URL
     *
     * @param string|null $basePath
     * @return Zend_Controller_Request_Http
     */
    public function setBasePath($basePath = null)
    {
        if ($basePath === null) {
            $filename = (isset($_SERVER['SCRIPT_FILENAME']))
                      ? basename($_SERVER['SCRIPT_FILENAME'])
                      : '';

            $baseUrl = $this->getBaseUrl();
            if (empty($baseUrl)) {
                $this->_basePath = '';
                return $this;
            }

            if (basename($baseUrl) === $filename) {
                $basePath = dirname($baseUrl);
            } else {
                $basePath = $baseUrl;
            }
        }

        if (substr(PHP_OS, 0, 3) === 'WIN') {
            $basePath = str_replace('\\', '/', $basePath);
        }

        $this->_basePath = rtrim($basePath, '/');
        return $this;
    }

    /**
     * Everything in REQUEST_URI before PATH_INFO not including the filename
     * <img src="<?=$basePath?>/images/zend.png"/>
     *
     * @return string
     */
    public function getBasePath()
    {
        if (null === $this->_basePath) {
            $this->setBasePath();
        }

        return $this->_basePath;
    }

    /**
     * Set the PATH_INFO string
     *
     * @param string|null $pathInfo
     * @return Zend_Controller_Request_Http
     */
    public function setPathInfo($pathInfo = null)
    {
        if ($pathInfo === null) {
            $baseUrl = $this->getBaseUrl(); // this actually calls setBaseUrl() & setRequestUri()
            $baseUrlRaw = $this->getBaseUrl(false);
            $baseUrlEncoded = urlencode($baseUrlRaw);
        
            if (null === ($requestUri = $this->getRequestUri())) {
                return $this;
            }
        
            // Remove the query string from REQUEST_URI
            if ($pos = strpos($requestUri, '?')) {
                $requestUri = substr($requestUri, 0, $pos);
            }
            
            if (!empty($baseUrl) || !empty($baseUrlRaw)) {
                if (strpos($requestUri, $baseUrl) === 0) {
                    $pathInfo = substr($requestUri, strlen($baseUrl));
                } elseif (strpos($requestUri, $baseUrlRaw) === 0) {
                    $pathInfo = substr($requestUri, strlen($baseUrlRaw));
                } elseif (strpos($requestUri, $baseUrlEncoded) === 0) {
                    $pathInfo = substr($requestUri, strlen($baseUrlEncoded));
                } else {
                    $pathInfo = $requestUri;
                }
            } else {
                $pathInfo = $requestUri;
            }
        
        }

        $this->_pathInfo = (string) $pathInfo;
        return $this;
    }

    /**
     * Returns everything between the BaseUrl and QueryString.
     * This value is calculated instead of reading PATH_INFO
     * directly from $_SERVER due to cross-platform differences.
     *
     * @return string
     */
    public function getPathInfo()
    {
        if (empty($this->_pathInfo)) {
            $this->setPathInfo();
        }

        return $this->_pathInfo;
    }

    /**
     * Set allowed parameter sources
     *
     * Can be empty array, or contain one or more of '_GET' or '_POST'.
     *
     * @param  array $paramSoures
     * @return Zend_Controller_Request_Http
     */
    public function setParamSources(array $paramSources = array())
    {
        $this->_paramSources = $paramSources;
        return $this;
    }

    /**
     * Get list of allowed parameter sources
     *
     * @return array
     */
    public function getParamSources()
    {
        return $this->_paramSources;
    }

    /**
     * Set a userland parameter
     *
     * Uses $key to set a userland parameter. If $key is an alias, the actual
     * key will be retrieved and used to set the parameter.
     *
     * @param mixed $key
     * @param mixed $value
     * @return Zend_Controller_Request_Http
     */
    public function setParam($key, $value)
    {
        $key = (null !== ($alias = $this->getAlias($key))) ? $alias : $key;
        parent::setParam($key, $value);
        return $this;
    }

    /**
     * Retrieve a parameter
     *
     * Retrieves a parameter from the instance. Priority is in the order of
     * userland parameters (see {@link setParam()}), $_GET, $_POST. If a
     * parameter matching the $key is not found, null is returned.
     *
     * If the $key is an alias, the actual key aliased will be used.
     *
     * @param mixed $key
     * @param mixed $default Default value to use if key not found
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $keyName = (null !== ($alias = $this->getAlias($key))) ? $alias : $key;

        $paramSources = $this->getParamSources();
        if (isset($this->_params[$keyName])) {
            return $this->_params[$keyName];
        } elseif (in_array('_GET', $paramSources) && (isset($_GET[$keyName]))) {
            return $_GET[$keyName];
        } elseif (in_array('_POST', $paramSources) && (isset($_POST[$keyName]))) {
            return $_POST[$keyName];
        }

        return $default;
    }

    /**
     * Retrieve an array of parameters
     *
     * Retrieves a merged array of parameters, with precedence of userland
     * params (see {@link setParam()}), $_GET, $_POST (i.e., values in the
     * userland params will take precedence over all others).
     *
     * @return array
     */
    public function getParams()
    {
        $return       = $this->_params;
        $paramSources = $this->getParamSources();
        if (in_array('_GET', $paramSources)
            && isset($_GET)
            && is_array($_GET)
        ) {
            $return += $_GET;
        }
        if (in_array('_POST', $paramSources)
            && isset($_POST)
            && is_array($_POST)
        ) {
            $return += $_POST;
        }
        return $return;
    }

    /**
     * Set parameters
     *
     * Set one or more parameters. Parameters are set as userland parameters,
     * using the keys specified in the array.
     *
     * @param array $params
     * @return Zend_Controller_Request_Http
     */
    public function setParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
        return $this;
    }

    /**
     * Set a key alias
     *
     * Set an alias used for key lookups. $name specifies the alias, $target
     * specifies the actual key to use.
     *
     * @param string $name
     * @param string $target
     * @return Zend_Controller_Request_Http
     */
    public function setAlias($name, $target)
    {
        $this->_aliases[$name] = $target;
        return $this;
    }

    /**
     * Retrieve an alias
     *
     * Retrieve the actual key represented by the alias $name.
     *
     * @param string $name
     * @return string|null Returns null when no alias exists
     */
    public function getAlias($name)
    {
        if (isset($this->_aliases[$name])) {
            return $this->_aliases[$name];
        }

        return null;
    }

    /**
     * Retrieve the list of all aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->_aliases;
    }

    /**
     * Return the method by which the request was made
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getServer('REQUEST_METHOD');
    }

    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost()
    {
        if ('POST' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by GET?
     *
     * @return boolean
     */
    public function isGet()
    {
        if ('GET' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut()
    {
        if ('PUT' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by DELETE?
     *
     * @return boolean
     */
    public function isDelete()
    {
        if ('DELETE' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by HEAD?
     *
     * @return boolean
     */
    public function isHead()
    {
        if ('HEAD' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by OPTIONS?
     *
     * @return boolean
     */
    public function isOptions()
    {
        if ('OPTIONS' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return boolean
     */
    public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /**
     * Is this a Flash request?
     *
     * @return boolean
     */
    public function isFlashRequest()
    {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }

    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure()
    {
        return ($this->getScheme() === self::SCHEME_HTTPS);
    }

    /**
     * Return the raw body of the request, if present
     *
     * @return string|false Raw body, or false if not present
     */
    public function getRawBody()
    {
        if (null === $this->_rawBody) {
            $body = file_get_contents('php://input');

            if (strlen(trim($body)) > 0) {
                $this->_rawBody = $body;
            } else {
                $this->_rawBody = false;
            }
        }
        return $this->_rawBody;
    }

    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws Zend_Controller_Request_Exception
     */
    public function getHeader($header)
    {
        if (empty($header)) {
            require_once 'Zend/Controller/Request/Exception.php';
            throw new Zend_Controller_Request_Exception('An HTTP header name is required');
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (isset($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers[$header])) {
                return $headers[$header];
            }
            $header = strtolower($header);
            foreach ($headers as $key => $value) {
                if (strtolower($key) == $header) {
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * Get the request URI scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    /**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        if (!empty($host)) {
            return $host;
        }

        $scheme = $this->getScheme();
        $name   = $this->getServer('SERVER_NAME');
        $port   = $this->getServer('SERVER_PORT');

        if(null === $name) {
            return '';
        }
        elseif (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        } else {
            return $name . ':' . $port;
        }
    }

    /**
     * Get the client's IP addres
     *
     * @param  boolean $checkProxy
     * @return string
     */
    public function getClientIp($checkProxy = true)
    {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        } else if ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        return $ip;
    }
}

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
 * @category  Zend
 * @package   Zend_Uri
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 * @version   $Id: Uri.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Abstract class for all Zend_Uri handlers
 *
 * @category  Zend
 * @package   Zend_Uri
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Uri
{
    /**
     * Scheme of this URI (http, ftp, etc.)
     *
     * @var string
     */
    protected $_scheme = '';

    /**
     * Global configuration array
     *
     * @var array
     */
    static protected $_config = array(
        'allow_unwise' => false
    );

    /**
     * Return a string representation of this URI.
     *
     * @see    getUri()
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->getUri();
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

    /**
     * Convenience function, checks that a $uri string is well-formed
     * by validating it but not returning an object.  Returns TRUE if
     * $uri is a well-formed URI, or FALSE otherwise.
     *
     * @param  string $uri The URI to check
     * @return boolean
     */
    public static function check($uri)
    {
        try {
            $uri = self::factory($uri);
        } catch (Exception $e) {
            return false;
        }

        return $uri->valid();
    }

    /**
     * Create a new Zend_Uri object for a URI.  If building a new URI, then $uri should contain
     * only the scheme (http, ftp, etc).  Otherwise, supply $uri with the complete URI.
     *
     * @param  string $uri       The URI form which a Zend_Uri instance is created
     * @param  string $className The name of the class to use in order to manipulate URI
     * @throws Zend_Uri_Exception When an empty string was supplied for the scheme
     * @throws Zend_Uri_Exception When an illegal scheme is supplied
     * @throws Zend_Uri_Exception When the scheme is not supported
     * @throws Zend_Uri_Exception When $className doesn't exist or doesn't implements Zend_Uri
     * @return Zend_Uri
     * @link   http://www.faqs.org/rfcs/rfc2396.html
     */
    public static function factory($uri = 'http', $className = null)
    {
        // Separate the scheme from the scheme-specific parts
        $uri            = explode(':', $uri, 2);
        $scheme         = strtolower($uri[0]);
        $schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';

        if (strlen($scheme) === 0) {
            require_once 'Zend/Uri/Exception.php';
            throw new Zend_Uri_Exception('An empty string was supplied for the scheme');
        }

        // Security check: $scheme is used to load a class file, so only alphanumerics are allowed.
        if (ctype_alnum($scheme) === false) {
            require_once 'Zend/Uri/Exception.php';
            throw new Zend_Uri_Exception('Illegal scheme supplied, only alphanumeric characters are permitted');
        }

        if ($className === null) {
            /**
             * Create a new Zend_Uri object for the $uri. If a subclass of Zend_Uri exists for the
             * scheme, return an instance of that class. Otherwise, a Zend_Uri_Exception is thrown.
             */
            switch ($scheme) {
                case 'http':
                    // Break intentionally omitted
                case 'https':
                    $className = 'Zend_Uri_Http';
                    break;

                case 'mailto':
                    // TODO
                default:
                    require_once 'Zend/Uri/Exception.php';
                    throw new Zend_Uri_Exception("Scheme \"$scheme\" is not supported");
                    break;
            }
        }

        require_once 'Zend/Loader.php';
        try {
            Zend_Loader::loadClass($className);
        } catch (Exception $e) {
            require_once 'Zend/Uri/Exception.php';
            throw new Zend_Uri_Exception("\"$className\" not found");
        }

        $schemeHandler = new $className($scheme, $schemeSpecific);

        if (! $schemeHandler instanceof Zend_Uri) {
            require_once 'Zend/Uri/Exception.php';
            throw new Zend_Uri_Exception("\"$className\" is not an instance of Zend_Uri");
        }

        return $schemeHandler;
    }

    /**
     * Get the URI's scheme
     *
     * @return string|false Scheme or false if no scheme is set.
     */
    public function getScheme()
    {
        if (empty($this->_scheme) === false) {
            return $this->_scheme;
        } else {
            return false;
        }
    }

    /**
     * Set global configuration options
     *
     * @param Zend_Config|array $config
     */
    static public function setConfig($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new Zend_Uri_Exception("Config must be an array or an instance of Zend_Config.");
        }

        foreach ($config as $k => $v) {
            self::$_config[$k] = $v;
        }
    }

    /**
     * Zend_Uri and its subclasses cannot be instantiated directly.
     * Use Zend_Uri::factory() to return a new Zend_Uri object.
     *
     * @param string $scheme         The scheme of the URI
     * @param string $schemeSpecific The scheme-specific part of the URI
     */
    abstract protected function __construct($scheme, $schemeSpecific = '');

    /**
     * Return a string representation of this URI.
     *
     * @return string
     */
    abstract public function getUri();

    /**
     * Returns TRUE if this URI is valid, or FALSE otherwise.
     *
     * @return boolean
     */
    abstract public function valid();
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Http.php 23775 2011-03-01 17:25:24Z ralph $
 */


/** Zend_Controller_Response_Abstract */
require_once 'Zend/Controller/Response/Abstract.php';


/**
 * Zend_Controller_Response_Http
 *
 * HTTP response for controllers
 *
 * @uses Zend_Controller_Response_Abstract
 * @package Zend_Controller
 * @subpackage Response
 */
class Zend_Controller_Response_Http extends Zend_Controller_Response_Abstract
{
}

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
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Module.php 24182 2011-07-03 13:43:05Z adamlundrigan $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Router_Route_Abstract */
require_once 'Zend/Controller/Router/Route/Abstract.php';

/**
 * Module Route
 *
 * Default route for module functionality
 *
 * @package    Zend_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Zend_Controller_Router_Route_Module extends Zend_Controller_Router_Route_Abstract
{
    /**
     * Default values for the route (ie. module, controller, action, params)
     * @var array
     */
    protected $_defaults;

    protected $_values      = array();
    protected $_moduleValid = false;
    protected $_keysSet     = false;

    /**#@+
     * Array keys to use for module, controller, and action. Should be taken out of request.
     * @var string
     */
    protected $_moduleKey     = 'module';
    protected $_controllerKey = 'controller';
    protected $_actionKey     = 'action';
    /**#@-*/

    /**
     * @var Zend_Controller_Dispatcher_Interface
     */
    protected $_dispatcher;

    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    public function getVersion() {
        return 1;
    }

    /**
     * Instantiates route based on passed Zend_Config structure
     */
    public static function getInstance(Zend_Config $config)
    {
        $frontController = Zend_Controller_Front::getInstance();

        $defs       = ($config->defaults instanceof Zend_Config) ? $config->defaults->toArray() : array();
        $dispatcher = $frontController->getDispatcher();
        $request    = $frontController->getRequest();

        return new self($defs, $dispatcher, $request);
    }

    /**
     * Constructor
     *
     * @param array $defaults Defaults for map variables with keys as variable names
     * @param Zend_Controller_Dispatcher_Interface $dispatcher Dispatcher object
     * @param Zend_Controller_Request_Abstract $request Request object
     */
    public function __construct(array $defaults = array(),
                Zend_Controller_Dispatcher_Interface $dispatcher = null,
                Zend_Controller_Request_Abstract $request = null)
    {
        $this->_defaults = $defaults;

        if (isset($request)) {
            $this->_request = $request;
        }

        if (isset($dispatcher)) {
            $this->_dispatcher = $dispatcher;
        }
    }

    /**
     * Set request keys based on values in request object
     *
     * @return void
     */
    protected function _setRequestKeys()
    {
        if (null !== $this->_request) {
            $this->_moduleKey     = $this->_request->getModuleKey();
            $this->_controllerKey = $this->_request->getControllerKey();
            $this->_actionKey     = $this->_request->getActionKey();
        }

        if (null !== $this->_dispatcher) {
            $this->_defaults += array(
                $this->_controllerKey => $this->_dispatcher->getDefaultControllerName(),
                $this->_actionKey     => $this->_dispatcher->getDefaultAction(),
                $this->_moduleKey     => $this->_dispatcher->getDefaultModule()
            );
        }

        $this->_keysSet = true;
    }

    /**
     * Matches a user submitted path. Assigns and returns an array of variables
     * on a successful match.
     *
     * If a request object is registered, it uses its setModuleName(),
     * setControllerName(), and setActionName() accessors to set those values.
     * Always returns the values as an array.
     *
     * @param string $path Path used to match against this routing map
     * @return array An array of assigned values or a false on a mismatch
     */
    public function match($path, $partial = false)
    {
        $this->_setRequestKeys();

        $values = array();
        $params = array();

        if (!$partial) {
            $path = trim($path, self::URI_DELIMITER);
        } else {
            $matchedPath = $path;
        }

        if ($path != '') {
            $path = explode(self::URI_DELIMITER, $path);

            if ($this->_dispatcher && $this->_dispatcher->isValidModule($path[0])) {
                $values[$this->_moduleKey] = array_shift($path);
                $this->_moduleValid = true;
            }

            if (count($path) && !empty($path[0])) {
                $values[$this->_controllerKey] = array_shift($path);
            }

            if (count($path) && !empty($path[0])) {
                $values[$this->_actionKey] = array_shift($path);
            }

            if ($numSegs = count($path)) {
                for ($i = 0; $i < $numSegs; $i = $i + 2) {
                    $key = urldecode($path[$i]);
                    $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                    $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
                }
            }
        }

        if ($partial) {
            $this->setMatchedPath($matchedPath);
        }

        $this->_values = $values + $params;

        return $this->_values + $this->_defaults;
    }

    /**
     * Assembles user submitted parameters forming a URL path defined by this route
     *
     * @param array $data An array of variable and value pairs used as parameters
     * @param bool $reset Weither to reset the current params
     * @return string Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = true, $partial = false)
    {
        if (!$this->_keysSet) {
            $this->_setRequestKeys();
        }

        $params = (!$reset) ? $this->_values : array();

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            } elseif (isset($params[$key])) {
                unset($params[$key]);
            }
        }

        $params += $this->_defaults;

        $url = '';

        if ($this->_moduleValid || array_key_exists($this->_moduleKey, $data)) {
            if ($params[$this->_moduleKey] != $this->_defaults[$this->_moduleKey]) {
                $module = $params[$this->_moduleKey];
            }
        }
        unset($params[$this->_moduleKey]);

        $controller = $params[$this->_controllerKey];
        unset($params[$this->_controllerKey]);

        $action = $params[$this->_actionKey];
        unset($params[$this->_actionKey]);

        foreach ($params as $key => $value) {
            $key = ($encode) ? urlencode($key) : $key;
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $arrayValue = ($encode) ? urlencode($arrayValue) : $arrayValue;
                    $url .= self::URI_DELIMITER . $key;
                    $url .= self::URI_DELIMITER . $arrayValue;
                }
            } else {
                if ($encode) $value = urlencode($value);
                $url .= self::URI_DELIMITER . $key;
                $url .= self::URI_DELIMITER . $value;
            }
        }

        if (!empty($url) || $action !== $this->_defaults[$this->_actionKey]) {
            if ($encode) $action = urlencode($action);
            $url = self::URI_DELIMITER . $action . $url;
        }

        if (!empty($url) || $controller !== $this->_defaults[$this->_controllerKey]) {
            if ($encode) $controller = urlencode($controller);
            $url = self::URI_DELIMITER . $controller . $url;
        }

        if (isset($module)) {
            if ($encode) $module = urlencode($module);
            $url = self::URI_DELIMITER . $module . $url;
        }

        return ltrim($url, self::URI_DELIMITER);
    }

    /**
     * Return a single parameter of route's defaults
     *
     * @param string $name Array key of the parameter
     * @return string Previously set default
     */
    public function getDefault($name) {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
    }

    /**
     * Return an array of defaults
     *
     * @return array Route defaults
     */
    public function getDefaults() {
        return $this->_defaults;
    }

}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Cache {
	
	protected static $_instance = NULL;
	protected static $_backend=NULL;
	
	
	protected function __construct()
	{
		$config = Zend_Registry::get('config')->site;
		if (extension_loaded('memcache'))
		{
			$oBackend = new Zend_Cache_Backend_Memcached(
				array(
					'servers' => array( array(
					'host' => '127.0.0.1',
					'port' => '11211'
				)),
				'compression' => false
			));
		}
		else
		{
			$oBackend = new Zend_Cache_Backend_File(
				array(
					'cache_dir'		=>	APPLICATION_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR,
			));
		}
		self::$_backend = $oBackend;

		$oFrontend = new Zend_Cache_Core(array(
			'caching'			=> $config->get('cache_on',false),
			'lifetime'			=> $config->get('cache_life_time',60),	
			'cache_id_prefix'	=> trim(str_replace(array(DIRECTORY_SEPARATOR,'.','-',':','/','\\'),'_',SITE_PATH),'_').'_',
//			'logging'			=> false,
//			'logger'			=> Z_Log::getInstance(),
			'write_control'		=> true,
			'automatic_serialization' => true,
			'ignore_user_abort'	=> true
    	));
		
    	$oCache = Zend_Cache::factory( $oFrontend, $oBackend );
    	
		self::$_instance = $oCache;
	}
	
	/**
	 * @return Zend_Cache_Core
	 */
	public static function getInstance()
	{
		if (NULL === self::$_instance)
		{
			$cacher = new self();
		}
		return self::$_instance;
	}
	
	public static function getbackend()
	{
		self::getInstance();
		return self::$_backend;
	}
	
}


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
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: File.php 24030 2011-05-09 22:10:00Z mabe $
 */

/**
 * @see Zend_Cache_Backend_Interface
 */
require_once 'Zend/Cache/Backend/ExtendedInterface.php';

/**
 * @see Zend_Cache_Backend
 */
require_once 'Zend/Cache/Backend.php';


/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Cache_Backend_File extends Zend_Cache_Backend implements Zend_Cache_Backend_ExtendedInterface
{
    /**
     * Available options
     *
     * =====> (string) cache_dir :
     * - Directory where to put the cache files
     *
     * =====> (boolean) file_locking :
     * - Enable / disable file_locking
     * - Can avoid cache corruption under bad circumstances but it doesn't work on multithread
     * webservers and on NFS filesystems for example
     *
     * =====> (boolean) read_control :
     * - Enable / disable read control
     * - If enabled, a control key is embeded in cache file and this key is compared with the one
     * calculated after the reading.
     *
     * =====> (string) read_control_type :
     * - Type of read control (only if read control is enabled). Available values are :
     *   'md5' for a md5 hash control (best but slowest)
     *   'crc32' for a crc32 hash control (lightly less safe but faster, better choice)
     *   'adler32' for an adler32 hash control (excellent choice too, faster than crc32)
     *   'strlen' for a length only test (fastest)
     *
     * =====> (int) hashed_directory_level :
     * - Hashed directory level
     * - Set the hashed directory structure level. 0 means "no hashed directory
     * structure", 1 means "one level of directory", 2 means "two levels"...
     * This option can speed up the cache only when you have many thousands of
     * cache file. Only specific benchs can help you to choose the perfect value
     * for you. Maybe, 1 or 2 is a good start.
     *
     * =====> (int) hashed_directory_umask :
     * - Umask for hashed directory structure
     *
     * =====> (string) file_name_prefix :
     * - prefix for cache files
     * - be really carefull with this option because a too generic value in a system cache dir
     *   (like /tmp) can cause disasters when cleaning the cache
     *
     * =====> (int) cache_file_umask :
     * - Umask for cache files
     *
     * =====> (int) metatadatas_array_max_size :
     * - max size for the metadatas array (don't change this value unless you
     *   know what you are doing)
     *
     * @var array available options
     */
    protected $_options = array(
        'cache_dir' => null,
        'file_locking' => true,
        'read_control' => true,
        'read_control_type' => 'crc32',
        'hashed_directory_level' => 0,
        'hashed_directory_umask' => 0700,
        'file_name_prefix' => 'zend_cache',
        'cache_file_umask' => 0600,
        'metadatas_array_max_size' => 100
    );

    /**
     * Array of metadatas (each item is an associative array)
     *
     * @var array
     */
    protected $_metadatasArray = array();


    /**
     * Constructor
     *
     * @param  array $options associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if ($this->_options['cache_dir'] !== null) { // particular case for this option
            $this->setCacheDir($this->_options['cache_dir']);
        } else {
            $this->setCacheDir(self::getTmpDir() . DIRECTORY_SEPARATOR, false);
        }
        if (isset($this->_options['file_name_prefix'])) { // particular case for this option
            if (!preg_match('~^[a-zA-Z0-9_]+$~D', $this->_options['file_name_prefix'])) {
                Zend_Cache::throwException('Invalid file_name_prefix : must use only [a-zA-Z0-9_]');
            }
        }
        if ($this->_options['metadatas_array_max_size'] < 10) {
            Zend_Cache::throwException('Invalid metadatas_array_max_size, must be > 10');
        }
        if (isset($options['hashed_directory_umask']) && is_string($options['hashed_directory_umask'])) {
            // See #ZF-4422
            $this->_options['hashed_directory_umask'] = octdec($this->_options['hashed_directory_umask']);
        }
        if (isset($options['cache_file_umask']) && is_string($options['cache_file_umask'])) {
            // See #ZF-4422
            $this->_options['cache_file_umask'] = octdec($this->_options['cache_file_umask']);
        }
    }

    /**
     * Set the cache_dir (particular case of setOption() method)
     *
     * @param  string  $value
     * @param  boolean $trailingSeparator If true, add a trailing separator is necessary
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setCacheDir($value, $trailingSeparator = true)
    {
        if (!is_dir($value)) {
            Zend_Cache::throwException('cache_dir must be a directory');
        }
        if (!is_writable($value)) {
            Zend_Cache::throwException('cache_dir is not writable');
        }
        if ($trailingSeparator) {
            // add a trailing DIRECTORY_SEPARATOR if necessary
            $value = rtrim(realpath($value), '\\/') . DIRECTORY_SEPARATOR;
        }
        $this->_options['cache_dir'] = $value;
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param string $id cache id
     * @param boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        if (!($this->_test($id, $doNotTestCacheValidity))) {
            // The cache is not hit !
            return false;
        }
        $metadatas = $this->_getMetadatas($id);
        $file = $this->_file($id);
        $data = $this->_fileGetContents($file);
        if ($this->_options['read_control']) {
            $hashData = $this->_hash($data, $this->_options['read_control_type']);
            $hashControl = $metadatas['hash'];
            if ($hashData != $hashControl) {
                // Problem detected by the read control !
                $this->_log('Zend_Cache_Backend_File::load() / read_control : stored hash and computed hash do not match');
                $this->remove($id);
                return false;
            }
        }
        return $data;
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param string $id cache id
     * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        clearstatcache();
        return $this->_test($id, false);
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        clearstatcache();
        $file = $this->_file($id);
        $path = $this->_path($id);
        if ($this->_options['hashed_directory_level'] > 0) {
            if (!is_writable($path)) {
                // maybe, we just have to build the directory structure
                $this->_recursiveMkdirAndChmod($id);
            }
            if (!is_writable($path)) {
                return false;
            }
        }
        if ($this->_options['read_control']) {
            $hash = $this->_hash($data, $this->_options['read_control_type']);
        } else {
            $hash = '';
        }
        $metadatas = array(
            'hash' => $hash,
            'mtime' => time(),
            'expire' => $this->_expireTime($this->getLifetime($specificLifetime)),
            'tags' => $tags
        );
        $res = $this->_setMetadatas($id, $metadatas);
        if (!$res) {
            $this->_log('Zend_Cache_Backend_File::save() / error on saving metadata');
            return false;
        }
        $res = $this->_filePutContents($file, $data);
        return $res;
    }

    /**
     * Remove a cache record
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    public function remove($id)
    {
        $file = $this->_file($id);
        $boolRemove   = $this->_remove($file);
        $boolMetadata = $this->_delMetadatas($id);
        return $boolMetadata && $boolRemove;
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     *
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param string $mode clean mode
     * @param tags array $tags array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        // We use this protected method to hide the recursive stuff
        clearstatcache();
        return $this->_clean($this->_options['cache_dir'], $mode, $tags);
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds()
    {
        return $this->_get($this->_options['cache_dir'], 'ids', array());
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
        return $this->_get($this->_options['cache_dir'], 'tags', array());
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array())
    {
        return $this->_get($this->_options['cache_dir'], 'matching', $tags);
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        return $this->_get($this->_options['cache_dir'], 'notMatching', $tags);
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        return $this->_get($this->_options['cache_dir'], 'matchingAny', $tags);
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @throws Zend_Cache_Exception
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage()
    {
        $free = disk_free_space($this->_options['cache_dir']);
        $total = disk_total_space($this->_options['cache_dir']);
        if ($total == 0) {
            Zend_Cache::throwException('can\'t get disk_total_space');
        } else {
            if ($free >= $total) {
                return 100;
            }
            return ((int) (100. * ($total - $free) / $total));
        }
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
        $metadatas = $this->_getMetadatas($id);
        if (!$metadatas) {
            return false;
        }
        if (time() > $metadatas['expire']) {
            return false;
        }
        return array(
            'expire' => $metadatas['expire'],
            'tags' => $metadatas['tags'],
            'mtime' => $metadatas['mtime']
        );
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime)
    {
        $metadatas = $this->_getMetadatas($id);
        if (!$metadatas) {
            return false;
        }
        if (time() > $metadatas['expire']) {
            return false;
        }
        $newMetadatas = array(
            'hash' => $metadatas['hash'],
            'mtime' => time(),
            'expire' => $metadatas['expire'] + $extraLifetime,
            'tags' => $metadatas['tags']
        );
        $res = $this->_setMetadatas($id, $newMetadatas);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
        return array(
            'automatic_cleaning' => true,
            'tags' => true,
            'expired_read' => true,
            'priority' => false,
            'infinite_lifetime' => true,
            'get_list' => true
        );
    }

    /**
     * PUBLIC METHOD FOR UNIT TESTING ONLY !
     *
     * Force a cache record to expire
     *
     * @param string $id cache id
     */
    public function ___expire($id)
    {
        $metadatas = $this->_getMetadatas($id);
        if ($metadatas) {
            $metadatas['expire'] = 1;
            $this->_setMetadatas($id, $metadatas);
        }
    }

    /**
     * Get a metadatas record
     *
     * @param  string $id  Cache id
     * @return array|false Associative array of metadatas
     */
    protected function _getMetadatas($id)
    {
        if (isset($this->_metadatasArray[$id])) {
            return $this->_metadatasArray[$id];
        } else {
            $metadatas = $this->_loadMetadatas($id);
            if (!$metadatas) {
                return false;
            }
            $this->_setMetadatas($id, $metadatas, false);
            return $metadatas;
        }
    }

    /**
     * Set a metadatas record
     *
     * @param  string $id        Cache id
     * @param  array  $metadatas Associative array of metadatas
     * @param  boolean $save     optional pass false to disable saving to file
     * @return boolean True if no problem
     */
    protected function _setMetadatas($id, $metadatas, $save = true)
    {
        if (count($this->_metadatasArray) >= $this->_options['metadatas_array_max_size']) {
            $n = (int) ($this->_options['metadatas_array_max_size'] / 10);
            $this->_metadatasArray = array_slice($this->_metadatasArray, $n);
        }
        if ($save) {
            $result = $this->_saveMetadatas($id, $metadatas);
            if (!$result) {
                return false;
            }
        }
        $this->_metadatasArray[$id] = $metadatas;
        return true;
    }

    /**
     * Drop a metadata record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    protected function _delMetadatas($id)
    {
        if (isset($this->_metadatasArray[$id])) {
            unset($this->_metadatasArray[$id]);
        }
        $file = $this->_metadatasFile($id);
        return $this->_remove($file);
    }

    /**
     * Clear the metadatas array
     *
     * @return void
     */
    protected function _cleanMetadatas()
    {
        $this->_metadatasArray = array();
    }

    /**
     * Load metadatas from disk
     *
     * @param  string $id Cache id
     * @return array|false Metadatas associative array
     */
    protected function _loadMetadatas($id)
    {
        $file = $this->_metadatasFile($id);
        $result = $this->_fileGetContents($file);
        if (!$result) {
            return false;
        }
        $tmp = @unserialize($result);
        return $tmp;
    }

    /**
     * Save metadatas to disk
     *
     * @param  string $id        Cache id
     * @param  array  $metadatas Associative array
     * @return boolean True if no problem
     */
    protected function _saveMetadatas($id, $metadatas)
    {
        $file = $this->_metadatasFile($id);
        $result = $this->_filePutContents($file, serialize($metadatas));
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * Make and return a file name (with path) for metadatas
     *
     * @param  string $id Cache id
     * @return string Metadatas file name (with path)
     */
    protected function _metadatasFile($id)
    {
        $path = $this->_path($id);
        $fileName = $this->_idToFileName('internal-metadatas---' . $id);
        return $path . $fileName;
    }

    /**
     * Check if the given filename is a metadatas one
     *
     * @param  string $fileName File name
     * @return boolean True if it's a metadatas one
     */
    protected function _isMetadatasFile($fileName)
    {
        $id = $this->_fileNameToId($fileName);
        if (substr($id, 0, 21) == 'internal-metadatas---') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove a file
     *
     * If we can't remove the file (because of locks or any problem), we will touch
     * the file to invalidate it
     *
     * @param  string $file Complete file path
     * @return boolean True if ok
     */
    protected function _remove($file)
    {
        if (!is_file($file)) {
            return false;
        }
        if (!@unlink($file)) {
            # we can't remove the file (because of locks or any problem)
            $this->_log("Zend_Cache_Backend_File::_remove() : we can't remove $file");
            return false;
        }
        return true;
    }

    /**
     * Clean some cache records (protected method used for recursive stuff)
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $dir  Directory to clean
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @throws Zend_Cache_Exception
     * @return boolean True if no problem
     */
    protected function _clean($dir, $mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        if (!is_dir($dir)) {
            return false;
        }
        $result = true;
        $prefix = $this->_options['file_name_prefix'];
        $glob = @glob($dir . $prefix . '--*');
        if ($glob === false) {
            // On some systems it is impossible to distinguish between empty match and an error.
            return true;
        }
        foreach ($glob as $file)  {
            if (is_file($file)) {
                $fileName = basename($file);
                if ($this->_isMetadatasFile($fileName)) {
                    // in CLEANING_MODE_ALL, we drop anything, even remainings old metadatas files
                    if ($mode != Zend_Cache::CLEANING_MODE_ALL) {
                        continue;
                    }
                }
                $id = $this->_fileNameToId($fileName);
                $metadatas = $this->_getMetadatas($id);
                if ($metadatas === FALSE) {
                    $metadatas = array('expire' => 1, 'tags' => array());
                }
                switch ($mode) {
                    case Zend_Cache::CLEANING_MODE_ALL:
                        $res = $this->remove($id);
                        if (!$res) {
                            // in this case only, we accept a problem with the metadatas file drop
                            $res = $this->_remove($file);
                        }
                        $result = $result && $res;
                        break;
                    case Zend_Cache::CLEANING_MODE_OLD:
                        if (time() > $metadatas['expire']) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
                        $matching = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $metadatas['tags'])) {
                                $matching = false;
                                break;
                            }
                        }
                        if ($matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if (!$matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if ($matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    default:
                        Zend_Cache::throwException('Invalid mode for clean() method');
                        break;
                }
            }
            if ((is_dir($file)) and ($this->_options['hashed_directory_level']>0)) {
                // Recursive call
                $result = $this->_clean($file . DIRECTORY_SEPARATOR, $mode, $tags) && $result;
                if ($mode == Zend_Cache::CLEANING_MODE_ALL) {
                    // we try to drop the structure too
                    @rmdir($file);
                }
            }
        }
        return $result;
    }

    protected function _get($dir, $mode, $tags = array())
    {
        if (!is_dir($dir)) {
            return false;
        }
        $result = array();
        $prefix = $this->_options['file_name_prefix'];
        $glob = @glob($dir . $prefix . '--*');
        if ($glob === false) {
            // On some systems it is impossible to distinguish between empty match and an error.
            return array();
        }
        foreach ($glob as $file)  {
            if (is_file($file)) {
                $fileName = basename($file);
                $id = $this->_fileNameToId($fileName);
                $metadatas = $this->_getMetadatas($id);
                if ($metadatas === FALSE) {
                    continue;
                }
                if (time() > $metadatas['expire']) {
                    continue;
                }
                switch ($mode) {
                    case 'ids':
                        $result[] = $id;
                        break;
                    case 'tags':
                        $result = array_unique(array_merge($result, $metadatas['tags']));
                        break;
                    case 'matching':
                        $matching = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $metadatas['tags'])) {
                                $matching = false;
                                break;
                            }
                        }
                        if ($matching) {
                            $result[] = $id;
                        }
                        break;
                    case 'notMatching':
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if (!$matching) {
                            $result[] = $id;
                        }
                        break;
                    case 'matchingAny':
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if ($matching) {
                            $result[] = $id;
                        }
                        break;
                    default:
                        Zend_Cache::throwException('Invalid mode for _get() method');
                        break;
                }
            }
            if ((is_dir($file)) and ($this->_options['hashed_directory_level']>0)) {
                // Recursive call
                $recursiveRs =  $this->_get($file . DIRECTORY_SEPARATOR, $mode, $tags);
                if ($recursiveRs === false) {
                    $this->_log('Zend_Cache_Backend_File::_get() / recursive call : can\'t list entries of "'.$file.'"');
                } else {
                    $result = array_unique(array_merge($result, $recursiveRs));
                }
            }
        }
        return array_unique($result);
    }

    /**
     * Compute & return the expire time
     *
     * @return int expire time (unix timestamp)
     */
    protected function _expireTime($lifetime)
    {
        if ($lifetime === null) {
            return 9999999999;
        }
        return time() + $lifetime;
    }

    /**
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data
     * @param  string $controlType Type of control 'md5', 'crc32' or 'strlen'
     * @throws Zend_Cache_Exception
     * @return string Control key
     */
    protected function _hash($data, $controlType)
    {
        switch ($controlType) {
        case 'md5':
            return md5($data);
        case 'crc32':
            return crc32($data);
        case 'strlen':
            return strlen($data);
        case 'adler32':
            return hash('adler32', $data);
        default:
            Zend_Cache::throwException("Incorrect hash function : $controlType");
        }
    }

    /**
     * Transform a cache id into a file name and return it
     *
     * @param  string $id Cache id
     * @return string File name
     */
    protected function _idToFileName($id)
    {
        $prefix = $this->_options['file_name_prefix'];
        $result = $prefix . '---' . $id;
        return $result;
    }

    /**
     * Make and return a file name (with path)
     *
     * @param  string $id Cache id
     * @return string File name (with path)
     */
    protected function _file($id)
    {
        $path = $this->_path($id);
        $fileName = $this->_idToFileName($id);
        return $path . $fileName;
    }

    /**
     * Return the complete directory path of a filename (including hashedDirectoryStructure)
     *
     * @param  string $id Cache id
     * @param  boolean $parts if true, returns array of directory parts instead of single string
     * @return string Complete directory path
     */
    protected function _path($id, $parts = false)
    {
        $partsArray = array();
        $root = $this->_options['cache_dir'];
        $prefix = $this->_options['file_name_prefix'];
        if ($this->_options['hashed_directory_level']>0) {
            $hash = hash('adler32', $id);
            for ($i=0 ; $i < $this->_options['hashed_directory_level'] ; $i++) {
                $root = $root . $prefix . '--' . substr($hash, 0, $i + 1) . DIRECTORY_SEPARATOR;
                $partsArray[] = $root;
            }
        }
        if ($parts) {
            return $partsArray;
        } else {
            return $root;
        }
    }

    /**
     * Make the directory strucuture for the given id
     *
     * @param string $id cache id
     * @return boolean true
     */
    protected function _recursiveMkdirAndChmod($id)
    {
        if ($this->_options['hashed_directory_level'] <=0) {
            return true;
        }
        $partsArray = $this->_path($id, true);
        foreach ($partsArray as $part) {
            if (!is_dir($part)) {
                @mkdir($part, $this->_options['hashed_directory_umask']);
                @chmod($part, $this->_options['hashed_directory_umask']); // see #ZF-320 (this line is required in some configurations)
            }
        }
        return true;
    }

    /**
     * Test if the given cache id is available (and still valid as a cache record)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return boolean|mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    protected function _test($id, $doNotTestCacheValidity)
    {
        $metadatas = $this->_getMetadatas($id);
        if (!$metadatas) {
            return false;
        }
        if ($doNotTestCacheValidity || (time() <= $metadatas['expire'])) {
            return $metadatas['mtime'];
        }
        return false;
    }

    /**
     * Return the file content of the given file
     *
     * @param  string $file File complete path
     * @return string File content (or false if problem)
     */
    protected function _fileGetContents($file)
    {
        $result = false;
        if (!is_file($file)) {
            return false;
        }
        $f = @fopen($file, 'rb');
        if ($f) {
            if ($this->_options['file_locking']) @flock($f, LOCK_SH);
            $result = stream_get_contents($f);
            if ($this->_options['file_locking']) @flock($f, LOCK_UN);
            @fclose($f);
        }
        return $result;
    }

    /**
     * Put the given string into the given file
     *
     * @param  string $file   File complete path
     * @param  string $string String to put in file
     * @return boolean true if no problem
     */
    protected function _filePutContents($file, $string)
    {
        $result = false;
        $f = @fopen($file, 'ab+');
        if ($f) {
            if ($this->_options['file_locking']) @flock($f, LOCK_EX);
            fseek($f, 0);
            ftruncate($f, 0);
            $tmp = @fwrite($f, $string);
            if (!($tmp === FALSE)) {
                $result = true;
            }
            @fclose($f);
        }
        @chmod($file, $this->_options['cache_file_umask']);
        return $result;
    }

    /**
     * Transform a file name into cache id and return it
     *
     * @param  string $fileName File name
     * @return string Cache id
     */
    protected function _fileNameToId($fileName)
    {
        $prefix = $this->_options['file_name_prefix'];
        return preg_replace('~^' . $prefix . '---(.*)$~', '$1', $fileName);
    }

}

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
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: ExtendedInterface.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Cache_Backend_Interface
 */
require_once 'Zend/Cache/Backend/Interface.php';

/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Cache_Backend_ExtendedInterface extends Zend_Cache_Backend_Interface
{

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds();

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags();

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array());

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array());

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array());

    /**
     * Return the filling percentage of the backend storage
     *
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage();

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id);

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime);

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities();

}

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
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Cache_Backend_Interface
{
    /**
     * Set the frontend directives
     *
     * @param array $directives assoc of directives
     */
    public function setDirectives($directives);

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * Note : return value is always "string" (unserialization is done by the core not by the backend)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false);

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id);

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data            Datas to cache
     * @param  string $id              Cache id
     * @param  array $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int   $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false);

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id);

    /**
     * Clean some cache records
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array());

}

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
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Backend.php 23800 2011-03-10 20:52:08Z mabe $
 */


/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Cache_Backend
{
    /**
     * Frontend or Core directives
     *
     * =====> (int) lifetime :
     * - Cache lifetime (in seconds)
     * - If null, the cache is valid forever
     *
     * =====> (int) logging :
     * - if set to true, a logging is activated throw Zend_Log
     *
     * @var array directives
     */
    protected $_directives = array(
        'lifetime' => 3600,
        'logging'  => false,
        'logger'   => null
    );

    /**
     * Available options
     *
     * @var array available options
     */
    protected $_options = array();

    /**
     * Constructor
     *
     * @param  array $options Associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        while (list($name, $value) = each($options)) {
            $this->setOption($name, $value);
        }
    }

    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setDirectives($directives)
    {
        if (!is_array($directives)) Zend_Cache::throwException('Directives parameter must be an array');
        while (list($name, $value) = each($directives)) {
            if (!is_string($name)) {
                Zend_Cache::throwException("Incorrect option name : $name");
            }
            $name = strtolower($name);
            if (array_key_exists($name, $this->_directives)) {
                $this->_directives[$name] = $value;
            }

        }

        $this->_loggerSanity();
    }

    /**
     * Set an option
     *
     * @param  string $name
     * @param  mixed  $value
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setOption($name, $value)
    {
        if (!is_string($name)) {
            Zend_Cache::throwException("Incorrect option name : $name");
        }
        $name = strtolower($name);
        if (array_key_exists($name, $this->_options)) {
            $this->_options[$name] = $value;
        }
    }

    /**
     * Get the life time
     *
     * if $specificLifetime is not false, the given specific life time is used
     * else, the global lifetime is used
     *
     * @param  int $specificLifetime
     * @return int Cache life time
     */
    public function getLifetime($specificLifetime)
    {
        if ($specificLifetime === false) {
            return $this->_directives['lifetime'];
        }
        return $specificLifetime;
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * DEPRECATED : use getCapabilities() instead
     *
     * @deprecated
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return true;
    }

    /**
     * Determine system TMP directory and detect if we have read access
     *
     * inspired from Zend_File_Transfer_Adapter_Abstract
     *
     * @return string
     * @throws Zend_Cache_Exception if unable to determine directory
     */
    public function getTmpDir()
    {
        $tmpdir = array();
        foreach (array($_ENV, $_SERVER) as $tab) {
            foreach (array('TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot') as $key) {
                if (isset($tab[$key])) {
                    if (($key == 'windir') or ($key == 'SystemRoot')) {
                        $dir = realpath($tab[$key] . '\\temp');
                    } else {
                        $dir = realpath($tab[$key]);
                    }
                    if ($this->_isGoodTmpDir($dir)) {
                        return $dir;
                    }
                }
            }
        }
        $upload = ini_get('upload_tmp_dir');
        if ($upload) {
            $dir = realpath($upload);
            if ($this->_isGoodTmpDir($dir)) {
                return $dir;
            }
        }
        if (function_exists('sys_get_temp_dir')) {
            $dir = sys_get_temp_dir();
            if ($this->_isGoodTmpDir($dir)) {
                return $dir;
            }
        }
        // Attemp to detect by creating a temporary file
        $tempFile = tempnam(md5(uniqid(rand(), TRUE)), '');
        if ($tempFile) {
            $dir = realpath(dirname($tempFile));
            unlink($tempFile);
            if ($this->_isGoodTmpDir($dir)) {
                return $dir;
            }
        }
        if ($this->_isGoodTmpDir('/tmp')) {
            return '/tmp';
        }
        if ($this->_isGoodTmpDir('\\temp')) {
            return '\\temp';
        }
        Zend_Cache::throwException('Could not determine temp directory, please specify a cache_dir manually');
    }

    /**
     * Verify if the given temporary directory is readable and writable
     *
     * @param string $dir temporary directory
     * @return boolean true if the directory is ok
     */
    protected function _isGoodTmpDir($dir)
    {
        if (is_readable($dir)) {
            if (is_writable($dir)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Make sure if we enable logging that the Zend_Log class
     * is available.
     * Create a default log object if none is set.
     *
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected function _loggerSanity()
    {
        if (!isset($this->_directives['logging']) || !$this->_directives['logging']) {
            return;
        }

        if (isset($this->_directives['logger'])) {
            if ($this->_directives['logger'] instanceof Zend_Log) {
                return;
            }
            Zend_Cache::throwException('Logger object is not an instance of Zend_Log class.');
        }

        // Create a default logger to the standard output stream
        require_once 'Zend/Log.php';
        require_once 'Zend/Log/Writer/Stream.php';
        require_once 'Zend/Log/Filter/Priority.php';
        $logger = new Zend_Log(new Zend_Log_Writer_Stream('php://output'));
        $logger->addFilter(new Zend_Log_Filter_Priority(Zend_Log::WARN, '<='));
        $this->_directives['logger'] = $logger;
    }

    /**
     * Log a message at the WARN (4) priority.
     *
     * @param  string $message
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected function _log($message, $priority = 4)
    {
        if (!$this->_directives['logging']) {
            return;
        }

        if (!isset($this->_directives['logger'])) {
            Zend_Cache::throwException('Logging is enabled but logger is not set.');
        }
        $logger = $this->_directives['logger'];
        if (!$logger instanceof Zend_Log) {
            Zend_Cache::throwException('Logger object is not an instance of Zend_Log class.');
        }
        $logger->log($message, $priority);
    }
}

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
 * @category   Zend
 * @package    Zend_Cache
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Core.php 23800 2011-03-10 20:52:08Z mabe $
 */


/**
 * @package    Zend_Cache
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Cache_Core
{
    /**
     * Messages
     */
    const BACKEND_NOT_SUPPORTS_TAG = 'tags are not supported by the current backend';
    const BACKEND_NOT_IMPLEMENTS_EXTENDED_IF = 'Current backend doesn\'t implement the Zend_Cache_Backend_ExtendedInterface, so this method is not available';

    /**
     * Backend Object
     *
     * @var Zend_Cache_Backend_Interface $_backend
     */
    protected $_backend = null;

    /**
     * Available options
     *
     * ====> (boolean) write_control :
     * - Enable / disable write control (the cache is read just after writing to detect corrupt entries)
     * - Enable write control will lightly slow the cache writing but not the cache reading
     * Write control can detect some corrupt cache files but maybe it's not a perfect control
     *
     * ====> (boolean) caching :
     * - Enable / disable caching
     * (can be very useful for the debug of cached scripts)
     *
     * =====> (string) cache_id_prefix :
     * - prefix for cache ids (namespace)
     *
     * ====> (boolean) automatic_serialization :
     * - Enable / disable automatic serialization
     * - It can be used to save directly datas which aren't strings (but it's slower)
     *
     * ====> (int) automatic_cleaning_factor :
     * - Disable / Tune the automatic cleaning process
     * - The automatic cleaning process destroy too old (for the given life time)
     *   cache files when a new cache file is written :
     *     0               => no automatic cache cleaning
     *     1               => systematic cache cleaning
     *     x (integer) > 1 => automatic cleaning randomly 1 times on x cache write
     *
     * ====> (int) lifetime :
     * - Cache lifetime (in seconds)
     * - If null, the cache is valid forever.
     *
     * ====> (boolean) logging :
     * - If set to true, logging is activated (but the system is slower)
     *
     * ====> (boolean) ignore_user_abort
     * - If set to true, the core will set the ignore_user_abort PHP flag inside the
     *   save() method to avoid cache corruptions in some cases (default false)
     *
     * @var array $_options available options
     */
    protected $_options = array(
        'write_control'             => true,
        'caching'                   => true,
        'cache_id_prefix'           => null,
        'automatic_serialization'   => false,
        'automatic_cleaning_factor' => 10,
        'lifetime'                  => 3600,
        'logging'                   => false,
        'logger'                    => null,
        'ignore_user_abort'         => false
    );

    /**
     * Array of options which have to be transfered to backend
     *
     * @var array $_directivesList
     */
    protected static $_directivesList = array('lifetime', 'logging', 'logger');

    /**
     * Not used for the core, just a sort a hint to get a common setOption() method (for the core and for frontends)
     *
     * @var array $_specificOptions
     */
    protected $_specificOptions = array();

    /**
     * Last used cache id
     *
     * @var string $_lastId
     */
    private $_lastId = null;

    /**
     * True if the backend implements Zend_Cache_Backend_ExtendedInterface
     *
     * @var boolean $_extendedBackend
     */
    protected $_extendedBackend = false;

    /**
     * Array of capabilities of the backend (only if it implements Zend_Cache_Backend_ExtendedInterface)
     *
     * @var array
     */
    protected $_backendCapabilities = array();

    /**
     * Constructor
     *
     * @param  array|Zend_Config $options Associative array of options or Zend_Config instance
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            Zend_Cache::throwException("Options passed were not an array"
            . " or Zend_Config instance.");
        }
        while (list($name, $value) = each($options)) {
            $this->setOption($name, $value);
        }
        $this->_loggerSanity();
    }

    /**
     * Set options using an instance of type Zend_Config
     *
     * @param Zend_Config $config
     * @return Zend_Cache_Core
     */
    public function setConfig(Zend_Config $config)
    {
        $options = $config->toArray();
        while (list($name, $value) = each($options)) {
            $this->setOption($name, $value);
        }
        return $this;
    }

    /**
     * Set the backend
     *
     * @param  Zend_Cache_Backend $backendObject
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setBackend(Zend_Cache_Backend $backendObject)
    {
        $this->_backend= $backendObject;
        // some options (listed in $_directivesList) have to be given
        // to the backend too (even if they are not "backend specific")
        $directives = array();
        foreach (Zend_Cache_Core::$_directivesList as $directive) {
            $directives[$directive] = $this->_options[$directive];
        }
        $this->_backend->setDirectives($directives);
        if (in_array('Zend_Cache_Backend_ExtendedInterface', class_implements($this->_backend))) {
            $this->_extendedBackend = true;
            $this->_backendCapabilities = $this->_backend->getCapabilities();
        }

    }

    /**
     * Returns the backend
     *
     * @return Zend_Cache_Backend backend object
     */
    public function getBackend()
    {
        return $this->_backend;
    }

    /**
     * Public frontend to set an option
     *
     * There is an additional validation (relatively to the protected _setOption method)
     *
     * @param  string $name  Name of the option
     * @param  mixed  $value Value of the option
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setOption($name, $value)
    {
        if (!is_string($name)) {
            Zend_Cache::throwException("Incorrect option name : $name");
        }
        $name = strtolower($name);
        if (array_key_exists($name, $this->_options)) {
            // This is a Core option
            $this->_setOption($name, $value);
            return;
        }
        if (array_key_exists($name, $this->_specificOptions)) {
            // This a specic option of this frontend
            $this->_specificOptions[$name] = $value;
            return;
        }
    }

    /**
     * Public frontend to get an option value
     *
     * @param  string $name  Name of the option
     * @throws Zend_Cache_Exception
     * @return mixed option value
     */
    public function getOption($name)
    {
        if (is_string($name)) {
            $name = strtolower($name);
            if (array_key_exists($name, $this->_options)) {
                // This is a Core option
                return $this->_options[$name];
            }
            if (array_key_exists($name, $this->_specificOptions)) {
                // This a specic option of this frontend
                return $this->_specificOptions[$name];
            }
        }
        Zend_Cache::throwException("Incorrect option name : $name");
    }

    /**
     * Set an option
     *
     * @param  string $name  Name of the option
     * @param  mixed  $value Value of the option
     * @throws Zend_Cache_Exception
     * @return void
     */
    private function _setOption($name, $value)
    {
        if (!is_string($name) || !array_key_exists($name, $this->_options)) {
            Zend_Cache::throwException("Incorrect option name : $name");
        }
        if ($name == 'lifetime' && empty($value)) {
            $value = null;
        }
        $this->_options[$name] = $value;
    }

    /**
     * Force a new lifetime
     *
     * The new value is set for the core/frontend but for the backend too (directive)
     *
     * @param  int $newLifetime New lifetime (in seconds)
     * @return void
     */
    public function setLifetime($newLifetime)
    {
        $this->_options['lifetime'] = $newLifetime;
        $this->_backend->setDirectives(array(
            'lifetime' => $newLifetime
        ));
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @param  boolean $doNotUnserialize       Do not serialize (even if automatic_serialization is true) => for internal use
     * @return mixed|false Cached datas
     */
    public function load($id, $doNotTestCacheValidity = false, $doNotUnserialize = false)
    {
        if (!$this->_options['caching']) {
            return false;
        }
        $id = $this->_id($id); // cache id may need prefix
        $this->_lastId = $id;
        self::_validateIdOrTag($id);

        $this->_log("Zend_Cache_Core: load item '{$id}'", 7);
        $data = $this->_backend->load($id, $doNotTestCacheValidity);
        if ($data===false) {
            // no cache available
            return false;
        }
        if ((!$doNotUnserialize) && $this->_options['automatic_serialization']) {
            // we need to unserialize before sending the result
            return unserialize($data);
        }
        return $data;
    }

    /**
     * Test if a cache is available for the given id
     *
     * @param  string $id Cache id
     * @return int|false Last modified time of cache entry if it is available, false otherwise
     */
    public function test($id)
    {
        if (!$this->_options['caching']) {
            return false;
        }
        $id = $this->_id($id); // cache id may need prefix
        self::_validateIdOrTag($id);
        $this->_lastId = $id;

        $this->_log("Zend_Cache_Core: test item '{$id}'", 7);
        return $this->_backend->test($id);
    }

    /**
     * Save some data in a cache
     *
     * @param  mixed $data           Data to put in cache (can be another type than string if automatic_serialization is on)
     * @param  string $id             Cache id (if not set, the last cache id will be used)
     * @param  array $tags           Cache tags
     * @param  int $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @param  int   $priority         integer between 0 (very low priority) and 10 (maximum priority) used by some particular backends
     * @throws Zend_Cache_Exception
     * @return boolean True if no problem
     */
    public function save($data, $id = null, $tags = array(), $specificLifetime = false, $priority = 8)
    {
        if (!$this->_options['caching']) {
            return true;
        }
        if ($id === null) {
            $id = $this->_lastId;
        } else {
            $id = $this->_id($id);
        }
        self::_validateIdOrTag($id);
        self::_validateTagsArray($tags);
        if ($this->_options['automatic_serialization']) {
            // we need to serialize datas before storing them
            $data = serialize($data);
        } else {
            if (!is_string($data)) {
                Zend_Cache::throwException("Datas must be string or set automatic_serialization = true");
            }
        }

        // automatic cleaning
        if ($this->_options['automatic_cleaning_factor'] > 0) {
            $rand = rand(1, $this->_options['automatic_cleaning_factor']);
            if ($rand==1) {
                //  new way                 || deprecated way
                if ($this->_extendedBackend || method_exists($this->_backend, 'isAutomaticCleaningAvailable')) {
                    $this->_log("Zend_Cache_Core::save(): automatic cleaning running", 7);
                    $this->clean(Zend_Cache::CLEANING_MODE_OLD);
                } else {
                    $this->_log("Zend_Cache_Core::save(): automatic cleaning is not available/necessary with current backend", 4);
                }
            }
        }

        $this->_log("Zend_Cache_Core: save item '{$id}'", 7);
        if ($this->_options['ignore_user_abort']) {
            $abort = ignore_user_abort(true);
        }
        if (($this->_extendedBackend) && ($this->_backendCapabilities['priority'])) {
            $result = $this->_backend->save($data, $id, $tags, $specificLifetime, $priority);
        } else {
            $result = $this->_backend->save($data, $id, $tags, $specificLifetime);
        }
        if ($this->_options['ignore_user_abort']) {
            ignore_user_abort($abort);
        }

        if (!$result) {
            // maybe the cache is corrupted, so we remove it !
            $this->_log("Zend_Cache_Core::save(): failed to save item '{$id}' -> removing it", 4);
            $this->_backend->remove($id);
            return false;
        }

        if ($this->_options['write_control']) {
            $data2 = $this->_backend->load($id, true);
            if ($data!=$data2) {
                $this->_log("Zend_Cache_Core::save(): write control of item '{$id}' failed -> removing it", 4);
                $this->_backend->remove($id);
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a cache
     *
     * @param  string $id Cache id to remove
     * @return boolean True if ok
     */
    public function remove($id)
    {
        if (!$this->_options['caching']) {
            return true;
        }
        $id = $this->_id($id); // cache id may need prefix
        self::_validateIdOrTag($id);

        $this->_log("Zend_Cache_Core: remove item '{$id}'", 7);
        return $this->_backend->remove($id);
    }

    /**
     * Clean cache entries
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     * 'matchingAnyTag' => remove cache entries matching any given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string       $mode
     * @param  array|string $tags
     * @throws Zend_Cache_Exception
     * @return boolean True if ok
     */
    public function clean($mode = 'all', $tags = array())
    {
        if (!$this->_options['caching']) {
            return true;
        }
        if (!in_array($mode, array(Zend_Cache::CLEANING_MODE_ALL,
                                   Zend_Cache::CLEANING_MODE_OLD,
                                   Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                                   Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG,
                                   Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG))) {
            Zend_Cache::throwException('Invalid cleaning mode');
        }
        self::_validateTagsArray($tags);

        return $this->_backend->clean($mode, $tags);
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array())
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        if (!($this->_backendCapabilities['tags'])) {
            Zend_Cache::throwException(self::BACKEND_NOT_SUPPORTS_TAG);
        }

        $ids = $this->_backend->getIdsMatchingTags($tags);

        // we need to remove cache_id_prefix from ids (see #ZF-6178, #ZF-7600)
        if (isset($this->_options['cache_id_prefix']) && $this->_options['cache_id_prefix'] !== '') {
            $prefix    = & $this->_options['cache_id_prefix'];
            $prefixLen = strlen($prefix);
            foreach ($ids as &$id) {
                if (strpos($id, $prefix) === 0) {
                    $id = substr($id, $prefixLen);
                }
            }
        }

        return $ids;
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        if (!($this->_backendCapabilities['tags'])) {
            Zend_Cache::throwException(self::BACKEND_NOT_SUPPORTS_TAG);
        }

        $ids = $this->_backend->getIdsNotMatchingTags($tags);

        // we need to remove cache_id_prefix from ids (see #ZF-6178, #ZF-7600)
        if (isset($this->_options['cache_id_prefix']) && $this->_options['cache_id_prefix'] !== '') {
            $prefix    = & $this->_options['cache_id_prefix'];
            $prefixLen = strlen($prefix);
            foreach ($ids as &$id) {
                if (strpos($id, $prefix) === 0) {
                    $id = substr($id, $prefixLen);
                }
            }
        }

        return $ids;
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching any cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        if (!($this->_backendCapabilities['tags'])) {
            Zend_Cache::throwException(self::BACKEND_NOT_SUPPORTS_TAG);
        }

        $ids = $this->_backend->getIdsMatchingAnyTags($tags);

        // we need to remove cache_id_prefix from ids (see #ZF-6178, #ZF-7600)
        if (isset($this->_options['cache_id_prefix']) && $this->_options['cache_id_prefix'] !== '') {
            $prefix    = & $this->_options['cache_id_prefix'];
            $prefixLen = strlen($prefix);
            foreach ($ids as &$id) {
                if (strpos($id, $prefix) === 0) {
                    $id = substr($id, $prefixLen);
                }
            }
        }

        return $ids;
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds()
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }

        $ids = $this->_backend->getIds();

        // we need to remove cache_id_prefix from ids (see #ZF-6178, #ZF-7600)
        if (isset($this->_options['cache_id_prefix']) && $this->_options['cache_id_prefix'] !== '') {
            $prefix    = & $this->_options['cache_id_prefix'];
            $prefixLen = strlen($prefix);
            foreach ($ids as &$id) {
                if (strpos($id, $prefix) === 0) {
                    $id = substr($id, $prefixLen);
                }
            }
        }

        return $ids;
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        if (!($this->_backendCapabilities['tags'])) {
            Zend_Cache::throwException(self::BACKEND_NOT_SUPPORTS_TAG);
        }
        return $this->_backend->getTags();
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage()
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        return $this->_backend->getFillingPercentage();
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array will include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        $id = $this->_id($id); // cache id may need prefix
        return $this->_backend->getMetadatas($id);
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime)
    {
        if (!$this->_extendedBackend) {
            Zend_Cache::throwException(self::BACKEND_NOT_IMPLEMENTS_EXTENDED_IF);
        }
        $id = $this->_id($id); // cache id may need prefix

        $this->_log("Zend_Cache_Core: touch item '{$id}'", 7);
        return $this->_backend->touch($id, $extraLifetime);
    }

    /**
     * Validate a cache id or a tag (security, reliable filenames, reserved prefixes...)
     *
     * Throw an exception if a problem is found
     *
     * @param  string $string Cache id or tag
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected static function _validateIdOrTag($string)
    {
        if (!is_string($string)) {
            Zend_Cache::throwException('Invalid id or tag : must be a string');
        }
        if (substr($string, 0, 9) == 'internal-') {
            Zend_Cache::throwException('"internal-*" ids or tags are reserved');
        }
        if (!preg_match('~^[a-zA-Z0-9_]+$~D', $string)) {
            Zend_Cache::throwException("Invalid id or tag '$string' : must use only [a-zA-Z0-9_]");
        }
    }

    /**
     * Validate a tags array (security, reliable filenames, reserved prefixes...)
     *
     * Throw an exception if a problem is found
     *
     * @param  array $tags Array of tags
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected static function _validateTagsArray($tags)
    {
        if (!is_array($tags)) {
            Zend_Cache::throwException('Invalid tags array : must be an array');
        }
        foreach($tags as $tag) {
            self::_validateIdOrTag($tag);
        }
        reset($tags);
    }

    /**
     * Make sure if we enable logging that the Zend_Log class
     * is available.
     * Create a default log object if none is set.
     *
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected function _loggerSanity()
    {
        if (!isset($this->_options['logging']) || !$this->_options['logging']) {
            return;
        }

        if (isset($this->_options['logger']) && $this->_options['logger'] instanceof Zend_Log) {
            return;
        }

        // Create a default logger to the standard output stream
        require_once 'Zend/Log.php';
        require_once 'Zend/Log/Writer/Stream.php';
        require_once 'Zend/Log/Filter/Priority.php';
        $logger = new Zend_Log(new Zend_Log_Writer_Stream('php://output'));
        $logger->addFilter(new Zend_Log_Filter_Priority(Zend_Log::WARN, '<='));
        $this->_options['logger'] = $logger;
    }

    /**
     * Log a message at the WARN (4) priority.
     *
     * @param string $message
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected function _log($message, $priority = 4)
    {
        if (!$this->_options['logging']) {
            return;
        }
        if (!(isset($this->_options['logger']) || $this->_options['logger'] instanceof Zend_Log)) {
            Zend_Cache::throwException('Logging is enabled but logger is not set');
        }
        $logger = $this->_options['logger'];
        $logger->log($message, $priority);
    }

    /**
     * Make and return a cache id
     *
     * Checks 'cache_id_prefix' and returns new id with prefix or simply the id if null
     *
     * @param  string $id Cache id
     * @return string Cache id (with or without prefix)
     */
    protected function _id($id)
    {
        if (($id !== null) && isset($this->_options['cache_id_prefix'])) {
            return $this->_options['cache_id_prefix'] . $id; // return with prefix
        }
        return $id; // no prefix, just return the $id passed
    }

}

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
 * @category   Zend
 * @package    Zend_Cache
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Cache.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @package    Zend_Cache
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Cache
{

    /**
     * Standard frontends
     *
     * @var array
     */
    public static $standardFrontends = array('Core', 'Output', 'Class', 'File', 'Function', 'Page');

    /**
     * Standard backends
     *
     * @var array
     */
    public static $standardBackends = array('File', 'Sqlite', 'Memcached', 'Libmemcached', 'Apc', 'ZendPlatform',
                                            'Xcache', 'TwoLevels', 'WinCache', 'ZendServer_Disk', 'ZendServer_ShMem');

    /**
     * Standard backends which implement the ExtendedInterface
     *
     * @var array
     */
    public static $standardExtendedBackends = array('File', 'Apc', 'TwoLevels', 'Memcached', 'Libmemcached', 'Sqlite', 'WinCache');

    /**
     * Only for backward compatibility (may be removed in next major release)
     *
     * @var array
     * @deprecated
     */
    public static $availableFrontends = array('Core', 'Output', 'Class', 'File', 'Function', 'Page');

    /**
     * Only for backward compatibility (may be removed in next major release)
     *
     * @var array
     * @deprecated
     */
    public static $availableBackends = array('File', 'Sqlite', 'Memcached', 'Libmemcached', 'Apc', 'ZendPlatform', 'Xcache', 'WinCache', 'TwoLevels');

    /**
     * Consts for clean() method
     */
    const CLEANING_MODE_ALL              = 'all';
    const CLEANING_MODE_OLD              = 'old';
    const CLEANING_MODE_MATCHING_TAG     = 'matchingTag';
    const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';
    const CLEANING_MODE_MATCHING_ANY_TAG = 'matchingAnyTag';

    /**
     * Factory
     *
     * @param mixed  $frontend        frontend name (string) or Zend_Cache_Frontend_ object
     * @param mixed  $backend         backend name (string) or Zend_Cache_Backend_ object
     * @param array  $frontendOptions associative array of options for the corresponding frontend constructor
     * @param array  $backendOptions  associative array of options for the corresponding backend constructor
     * @param boolean $customFrontendNaming if true, the frontend argument is used as a complete class name ; if false, the frontend argument is used as the end of "Zend_Cache_Frontend_[...]" class name
     * @param boolean $customBackendNaming if true, the backend argument is used as a complete class name ; if false, the backend argument is used as the end of "Zend_Cache_Backend_[...]" class name
     * @param boolean $autoload if true, there will no require_once for backend and frontend (useful only for custom backends/frontends)
     * @throws Zend_Cache_Exception
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static function factory($frontend, $backend, $frontendOptions = array(), $backendOptions = array(), $customFrontendNaming = false, $customBackendNaming = false, $autoload = false)
    {
        if (is_string($backend)) {
            $backendObject = self::_makeBackend($backend, $backendOptions, $customBackendNaming, $autoload);
        } else {
            if ((is_object($backend)) && (in_array('Zend_Cache_Backend_Interface', class_implements($backend)))) {
                $backendObject = $backend;
            } else {
                self::throwException('backend must be a backend name (string) or an object which implements Zend_Cache_Backend_Interface');
            }
        }
        if (is_string($frontend)) {
            $frontendObject = self::_makeFrontend($frontend, $frontendOptions, $customFrontendNaming, $autoload);
        } else {
            if (is_object($frontend)) {
                $frontendObject = $frontend;
            } else {
                self::throwException('frontend must be a frontend name (string) or an object');
            }
        }
        $frontendObject->setBackend($backendObject);
        return $frontendObject;
    }

    /**
     * Backend Constructor
     *
     * @param string  $backend
     * @param array   $backendOptions
     * @param boolean $customBackendNaming
     * @param boolean $autoload
     * @return Zend_Cache_Backend
     */
    public static function _makeBackend($backend, $backendOptions, $customBackendNaming = false, $autoload = false)
    {
        if (!$customBackendNaming) {
            $backend  = self::_normalizeName($backend);
        }
        if (in_array($backend, Zend_Cache::$standardBackends)) {
            // we use a standard backend
            $backendClass = 'Zend_Cache_Backend_' . $backend;
            // security controls are explicit
            require_once str_replace('_', DIRECTORY_SEPARATOR, $backendClass) . '.php';
        } else {
            // we use a custom backend
            if (!preg_match('~^[\w]+$~D', $backend)) {
                Zend_Cache::throwException("Invalid backend name [$backend]");
            }
            if (!$customBackendNaming) {
                // we use this boolean to avoid an API break
                $backendClass = 'Zend_Cache_Backend_' . $backend;
            } else {
                $backendClass = $backend;
            }
            if (!$autoload) {
                $file = str_replace('_', DIRECTORY_SEPARATOR, $backendClass) . '.php';
                if (!(self::_isReadable($file))) {
                    self::throwException("file $file not found in include_path");
                }
                require_once $file;
            }
        }
        return new $backendClass($backendOptions);
    }

    /**
     * Frontend Constructor
     *
     * @param string  $frontend
     * @param array   $frontendOptions
     * @param boolean $customFrontendNaming
     * @param boolean $autoload
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static function _makeFrontend($frontend, $frontendOptions = array(), $customFrontendNaming = false, $autoload = false)
    {
        if (!$customFrontendNaming) {
            $frontend = self::_normalizeName($frontend);
        }
        if (in_array($frontend, self::$standardFrontends)) {
            // we use a standard frontend
            // For perfs reasons, with frontend == 'Core', we can interact with the Core itself
            $frontendClass = 'Zend_Cache_' . ($frontend != 'Core' ? 'Frontend_' : '') . $frontend;
            // security controls are explicit
            require_once str_replace('_', DIRECTORY_SEPARATOR, $frontendClass) . '.php';
        } else {
            // we use a custom frontend
            if (!preg_match('~^[\w]+$~D', $frontend)) {
                Zend_Cache::throwException("Invalid frontend name [$frontend]");
            }
            if (!$customFrontendNaming) {
                // we use this boolean to avoid an API break
                $frontendClass = 'Zend_Cache_Frontend_' . $frontend;
            } else {
                $frontendClass = $frontend;
            }
            if (!$autoload) {
                $file = str_replace('_', DIRECTORY_SEPARATOR, $frontendClass) . '.php';
                if (!(self::_isReadable($file))) {
                    self::throwException("file $file not found in include_path");
                }
                require_once $file;
            }
        }
        return new $frontendClass($frontendOptions);
    }

    /**
     * Throw an exception
     *
     * Note : for perf reasons, the "load" of Zend/Cache/Exception is dynamic
     * @param  string $msg  Message for the exception
     * @throws Zend_Cache_Exception
     */
    public static function throwException($msg, Exception $e = null)
    {
        // For perfs reasons, we use this dynamic inclusion
        require_once 'Zend/Cache/Exception.php';
        throw new Zend_Cache_Exception($msg, 0, $e);
    }

    /**
     * Normalize frontend and backend names to allow multiple words TitleCased
     *
     * @param  string $name  Name to normalize
     * @return string
     */
    protected static function _normalizeName($name)
    {
        $name = ucfirst(strtolower($name));
        $name = str_replace(array('-', '_', '.'), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        if (stripos($name, 'ZendServer') === 0) {
            $name = 'ZendServer_' . substr($name, strlen('ZendServer'));
        }

        return $name;
    }

    /**
     * Returns TRUE if the $filename is readable, or FALSE otherwise.
     * This function uses the PHP include_path, where PHP's is_readable()
     * does not.
     *
     * Note : this method comes from Zend_Loader (see #ZF-2891 for details)
     *
     * @param string   $filename
     * @return boolean
     */
    private static function _isReadable($filename)
    {
        if (!$fh = @fopen($filename, 'r', true)) {
            return false;
        }
        @fclose($fh);
        return true;
    }

}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

/**
 * News
 *  
 * @author cramen
 * @version 
 */

require_once 'Z/Db/Table.php';

class Z_Model_Titles extends Z_Db_Table {
	/**
	 * The default table name 
	 */
	protected $_name = 'z_titles';

}


/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

require_once 'Zend/Db/Table/Abstract.php';

class Z_Db_Table extends Zend_Db_Table_Abstract
{

  /**
   * Возвращает ассоцитиативный массив, где ключем является поле, указанное в первом элементе параметра $keys а значение во второом
   * @param <array('id','title')> $keys
   * @param <array> $where
   * @param <array or string> $order
   * @return <array>
   */
  public function fetchPairs($keys = NULL,$where=NULL,$order=NULL)
  {
    if ($keys===NULL) $keys = array('id','title');
    if ($where===NULL) $where = array();
    $select = $this->select()->from($this,$keys);
    if (!empty($where))
    {
      foreach ($where as $key=>$value)
      {
		$select->where($key,$value);
      }
    }
    if (!empty($order)) $select->order($order);
    $ret = $this->getAdapter()->fetchPairs($select);
    return $ret;
  }

  /**
   * Возвращяет элементы с заданными id из входного массива в порядке следования их во входном массиве
   * @param <array> $ids
   * @return Zend_Db_Table_Rowset
   */
  public function fetchByIds($ids = array(),$where=array(),$order=NULL,$count=NULL,$offset=NULL)
  {
    return $this->fetchAll($this->fetchByIdsSelect($ids,$where,$order,$count,$offset));
  }

  /**
   * 
   * @return Zend_Db_Table_Select
   */
  public function fetchByIdsSelect($ids = array(),$where=array(),$order=NULL,$count=NULL,$offset=NULL)
  {
  	if (!$where) $where=array();
    $id_list = implode(',', $ids);
    if (!empty($ids))
    {
	    $where['goods.id IN ('.$id_list.')'] = '';
	    $order = $order?$order:'FIELD('.$this->info('name').'.id, '.$id_list.')';
    }
    else 
    {
    	$where['false'] = '';
    }
    $select = $this->select(true);
    foreach ($where as $key=>$where_item)
    	$select->where($key,$where_item);
    $select->order($order);
    $select->limit($count,$offset);
    return $select;
  }
  
}


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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Select
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Select.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @see Zend_Db_Select
 */
require_once 'Zend/Db/Select.php';


/**
 * @see Zend_Db_Table_Abstract
 */
require_once 'Zend/Db/Table/Abstract.php';


/**
 * Class for SQL SELECT query manipulation for the Zend_Db_Table component.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Table_Select extends Zend_Db_Select
{
    /**
     * Table schema for parent Zend_Db_Table.
     *
     * @var array
     */
    protected $_info;

    /**
     * Table integrity override.
     *
     * @var array
     */
    protected $_integrityCheck = true;

    /**
     * Table instance that created this select object
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_table;

    /**
     * Class constructor
     *
     * @param Zend_Db_Table_Abstract $adapter
     */
    public function __construct(Zend_Db_Table_Abstract $table)
    {
        parent::__construct($table->getAdapter());

        $this->setTable($table);
    }

    /**
     * Return the table that created this select object
     *
     * @return Zend_Db_Table_Abstract
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Sets the primary table name and retrieves the table schema.
     *
     * @param Zend_Db_Table_Abstract $adapter
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function setTable(Zend_Db_Table_Abstract $table)
    {
        $this->_adapter = $table->getAdapter();
        $this->_info    = $table->info();
        $this->_table   = $table;

        return $this;
    }

    /**
     * Sets the integrity check flag.
     *
     * Setting this flag to false skips the checks for table joins, allowing
     * 'hybrid' table rows to be created.
     *
     * @param Zend_Db_Table_Abstract $adapter
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function setIntegrityCheck($flag = true)
    {
        $this->_integrityCheck = $flag;
        return $this;
    }

    /**
     * Tests query to determine if expressions or aliases columns exist.
     *
     * @return boolean
     */
    public function isReadOnly()
    {
        $readOnly = false;
        $fields   = $this->getPart(Zend_Db_Table_Select::COLUMNS);
        $cols     = $this->_info[Zend_Db_Table_Abstract::COLS];

        if (!count($fields)) {
            return $readOnly;
        }

        foreach ($fields as $columnEntry) {
            $column = $columnEntry[1];
            $alias = $columnEntry[2];

            if ($alias !== null) {
                $column = $alias;
            }

            switch (true) {
                case ($column == self::SQL_WILDCARD):
                    break;

                case ($column instanceof Zend_Db_Expr):
                case (!in_array($column, $cols)):
                    $readOnly = true;
                    break 2;
            }
        }

        return $readOnly;
    }

    /**
     * Adds a FROM table and optional columns to the query.
     *
     * The table name can be expressed
     *
     * @param  array|string|Zend_Db_Expr|Zend_Db_Table_Abstract $name The table name or an
                                                                      associative array relating
                                                                      table name to correlation
                                                                      name.
     * @param  array|string|Zend_Db_Expr $cols The columns to select from this table.
     * @param  string $schema The schema name to specify, if any.
     * @return Zend_Db_Table_Select This Zend_Db_Table_Select object.
     */
    public function from($name, $cols = self::SQL_WILDCARD, $schema = null)
    {
        if ($name instanceof Zend_Db_Table_Abstract) {
            $info = $name->info();
            $name = $info[Zend_Db_Table_Abstract::NAME];
            if (isset($info[Zend_Db_Table_Abstract::SCHEMA])) {
                $schema = $info[Zend_Db_Table_Abstract::SCHEMA];
            }
        }

        return $this->joinInner($name, null, $cols, $schema);
    }

    /**
     * Performs a validation on the select query before passing back to the parent class.
     * Ensures that only columns from the primary Zend_Db_Table are returned in the result.
     *
     * @return string|null This object as a SELECT string (or null if a string cannot be produced)
     */
    public function assemble()
    {
        $fields  = $this->getPart(Zend_Db_Table_Select::COLUMNS);
        $primary = $this->_info[Zend_Db_Table_Abstract::NAME];
        $schema  = $this->_info[Zend_Db_Table_Abstract::SCHEMA];


        if (count($this->_parts[self::UNION]) == 0) {

            // If no fields are specified we assume all fields from primary table
            if (!count($fields)) {
                $this->from($primary, self::SQL_WILDCARD, $schema);
                $fields = $this->getPart(Zend_Db_Table_Select::COLUMNS);
            }

            $from = $this->getPart(Zend_Db_Table_Select::FROM);

            if ($this->_integrityCheck !== false) {
                foreach ($fields as $columnEntry) {
                    list($table, $column) = $columnEntry;

                    // Check each column to ensure it only references the primary table
                    if ($column) {
                        if (!isset($from[$table]) || $from[$table]['tableName'] != $primary) {
                            require_once 'Zend/Db/Table/Select/Exception.php';
                            throw new Zend_Db_Table_Select_Exception('Select query cannot join with another table');
                        }
                    }
                }
            }
        }

        return parent::assemble();
    }
}
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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Profiler
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Query.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Profiler
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Profiler_Query
{

    /**
     * SQL query string or user comment, set by $query argument in constructor.
     *
     * @var string
     */
    protected $_query = '';

    /**
     * One of the Zend_Db_Profiler constants for query type, set by $queryType argument in constructor.
     *
     * @var integer
     */
    protected $_queryType = 0;

    /**
     * Unix timestamp with microseconds when instantiated.
     *
     * @var float
     */
    protected $_startedMicrotime = null;

    /**
     * Unix timestamp with microseconds when self::queryEnd() was called.
     *
     * @var integer
     */
    protected $_endedMicrotime = null;

    /**
     * @var array
     */
    protected $_boundParams = array();

    /**
     * @var array
     */

    /**
     * Class constructor.  A query is about to be started, save the query text ($query) and its
     * type (one of the Zend_Db_Profiler::* constants).
     *
     * @param  string  $query
     * @param  integer $queryType
     * @return void
     */
    public function __construct($query, $queryType)
    {
        $this->_query = $query;
        $this->_queryType = $queryType;
        // by default, and for backward-compatibility, start the click ticking
        $this->start();
    }

    /**
     * Clone handler for the query object.
     * @return void
     */
    public function __clone()
    {
        $this->_boundParams = array();
        $this->_endedMicrotime = null;
        $this->start();
    }

    /**
     * Starts the elapsed time click ticking.
     * This can be called subsequent to object creation,
     * to restart the clock.  For instance, this is useful
     * right before executing a prepared query.
     *
     * @return void
     */
    public function start()
    {
        $this->_startedMicrotime = microtime(true);
    }

    /**
     * Ends the query and records the time so that the elapsed time can be determined later.
     *
     * @return void
     */
    public function end()
    {
        $this->_endedMicrotime = microtime(true);
    }

    /**
     * Returns true if and only if the query has ended.
     *
     * @return boolean
     */
    public function hasEnded()
    {
        return $this->_endedMicrotime !== null;
    }

    /**
     * Get the original SQL text of the query.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Get the type of this query (one of the Zend_Db_Profiler::* constants)
     *
     * @return integer
     */
    public function getQueryType()
    {
        return $this->_queryType;
    }

    /**
     * @param string $param
     * @param mixed $variable
     * @return void
     */
    public function bindParam($param, $variable)
    {
        $this->_boundParams[$param] = $variable;
    }

    /**
     * @param array $param
     * @return void
     */
    public function bindParams(array $params)
    {
        if (array_key_exists(0, $params)) {
            array_unshift($params, null);
            unset($params[0]);
        }
        foreach ($params as $param => $value) {
            $this->bindParam($param, $value);
        }
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->_boundParams;
    }

    /**
     * Get the elapsed time (in seconds) that the query ran.
     * If the query has not yet ended, false is returned.
     *
     * @return float|false
     */
    public function getElapsedSecs()
    {
        if (null === $this->_endedMicrotime) {
            return false;
        }

        return $this->_endedMicrotime - $this->_startedMicrotime;
    }

    /**
     * Get the time (in seconds) when the profiler started running.
     *
     * @return bool|float
     */
    public function getStartedMicrotime()
    {
        if(null === $this->_startedMicrotime) {
            return false;
        }

        return $this->_startedMicrotime;
    }
}



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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Rowset.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @see Zend_Db_Table_Rowset_Abstract
 */
require_once 'Zend/Db/Table/Rowset/Abstract.php';


/**
 * Reference concrete class that extends Zend_Db_Table_Rowset_Abstract.
 * Developers may also create their own classes that extend the abstract class.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Table_Rowset extends Zend_Db_Table_Rowset_Abstract
{
}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Table_Rowset_Abstract implements SeekableIterator, Countable, ArrayAccess
{
    /**
     * The original data for each row.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Zend_Db_Table_Abstract object.
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_table;

    /**
     * Connected is true if we have a reference to a live
     * Zend_Db_Table_Abstract object.
     * This is false after the Rowset has been deserialized.
     *
     * @var boolean
     */
    protected $_connected = true;

    /**
     * Zend_Db_Table_Abstract class name.
     *
     * @var string
     */
    protected $_tableClass;

    /**
     * Zend_Db_Table_Row_Abstract class name.
     *
     * @var string
     */
    protected $_rowClass = 'Zend_Db_Table_Row';

    /**
     * Iterator pointer.
     *
     * @var integer
     */
    protected $_pointer = 0;

    /**
     * How many data rows there are.
     *
     * @var integer
     */
    protected $_count;

    /**
     * Collection of instantiated Zend_Db_Table_Row objects.
     *
     * @var array
     */
    protected $_rows = array();

    /**
     * @var boolean
     */
    protected $_stored = false;

    /**
     * @var boolean
     */
    protected $_readOnly = false;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (isset($config['table'])) {
            $this->_table      = $config['table'];
            $this->_tableClass = get_class($this->_table);
        }
        if (isset($config['rowClass'])) {
            $this->_rowClass   = $config['rowClass'];
        }
        if (!class_exists($this->_rowClass)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($this->_rowClass);
        }
        if (isset($config['data'])) {
            $this->_data       = $config['data'];
        }
        if (isset($config['readOnly'])) {
            $this->_readOnly   = $config['readOnly'];
        }
        if (isset($config['stored'])) {
            $this->_stored     = $config['stored'];
        }

        // set the count of rows
        $this->_count = count($this->_data);

        $this->init();
    }

    /**
     * Store data, class names, and state in serialized object
     *
     * @return array
     */
    public function __sleep()
    {
        return array('_data', '_tableClass', '_rowClass', '_pointer', '_count', '_rows', '_stored',
                     '_readOnly');
    }

    /**
     * Setup to do on wakeup.
     * A de-serialized Rowset should not be assumed to have access to a live
     * database connection, so set _connected = false.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->_connected = false;
    }

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Return the connected state of the rowset.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_connected;
    }

    /**
     * Returns the table object, or null if this is disconnected rowset
     *
     * @return Zend_Db_Table_Abstract
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Set the table object, to re-establish a live connection
     * to the database for a Rowset that has been de-serialized.
     *
     * @param Zend_Db_Table_Abstract $table
     * @return boolean
     * @throws Zend_Db_Table_Row_Exception
     */
    public function setTable(Zend_Db_Table_Abstract $table)
    {
        $this->_table = $table;
        $this->_connected = false;
        // @todo This works only if we have iterated through
        // the result set once to instantiate the rows.
        foreach ($this as $row) {
            $connected = $row->setTable($table);
            if ($connected == true) {
                $this->_connected = true;
            }
        }
        return $this->_connected;
    }

    /**
     * Query the class name of the Table object for which this
     * Rowset was created.
     *
     * @return string
     */
    public function getTableClass()
    {
        return $this->_tableClass;
    }

    /**
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return Zend_Db_Table_Rowset_Abstract Fluent interface.
     */
    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }

    /**
     * Return the current element.
     * Similar to the current() function for arrays in PHP
     * Required by interface Iterator.
     *
     * @return Zend_Db_Table_Row_Abstract current element from the collection
     */
    public function current()
    {
        if ($this->valid() === false) {
            return null;
        }

        // return the row object
        return $this->_loadAndReturnRow($this->_pointer);
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->_pointer;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return void
     */
    public function next()
    {
        ++$this->_pointer;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     * Used to check if we've iterated to the end of the collection.
     * Required by interface Iterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->_pointer >= 0 && $this->_pointer < $this->_count;
    }

    /**
     * Returns the number of elements in the collection.
     *
     * Implements Countable::count()
     *
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * Take the Iterator to position $position
     * Required by interface SeekableIterator.
     *
     * @param int $position the position to seek to
     * @return Zend_Db_Table_Rowset_Abstract
     * @throws Zend_Db_Table_Rowset_Exception
     */
    public function seek($position)
    {
        $position = (int) $position;
        if ($position < 0 || $position >= $this->_count) {
            require_once 'Zend/Db/Table/Rowset/Exception.php';
            throw new Zend_Db_Table_Rowset_Exception("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;
    }

    /**
     * Check if an offset exists
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[(int) $offset]);
    }

    /**
     * Get the row for the given offset
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return Zend_Db_Table_Row_Abstract
     */
    public function offsetGet($offset)
    {
        $offset = (int) $offset;
        if ($offset < 0 || $offset >= $this->_count) {
            require_once 'Zend/Db/Table/Rowset/Exception.php';
            throw new Zend_Db_Table_Rowset_Exception("Illegal index $offset");
        }
        $this->_pointer = $offset;

        return $this->current();
    }

    /**
     * Does nothing
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Does nothing
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * Returns a Zend_Db_Table_Row from a known position into the Iterator
     *
     * @param int $position the position of the row expected
     * @param bool $seek wether or not seek the iterator to that position after
     * @return Zend_Db_Table_Row
     * @throws Zend_Db_Table_Rowset_Exception
     */
    public function getRow($position, $seek = false)
    {
        try {
            $row = $this->_loadAndReturnRow($position);
        } catch (Zend_Db_Table_Rowset_Exception $e) {
            require_once 'Zend/Db/Table/Rowset/Exception.php';
            throw new Zend_Db_Table_Rowset_Exception('No row could be found at position ' . (int) $position, 0, $e);
        }

        if ($seek == true) {
            $this->seek($position);
        }

        return $row;
    }

    /**
     * Returns all data as an array.
     *
     * Updates the $_data property with current row object values.
     *
     * @return array
     */
    public function toArray()
    {
        // @todo This works only if we have iterated through
        // the result set once to instantiate the rows.
        foreach ($this->_rows as $i => $row) {
            $this->_data[$i] = $row->toArray();
        }
        return $this->_data;
    }

    protected function _loadAndReturnRow($position)
    {
        if (!isset($this->_data[$position])) {
            require_once 'Zend/Db/Table/Rowset/Exception.php';
            throw new Zend_Db_Table_Rowset_Exception("Data for provided position does not exist");
        }

        // do we already have a row object for this position?
        if (empty($this->_rows[$position])) {
            $this->_rows[$position] = new $this->_rowClass(
                array(
                    'table'    => $this->_table,
                    'data'     => $this->_data[$position],
                    'stored'   => $this->_stored,
                    'readOnly' => $this->_readOnly
                )
            );
        }

        // return the row object
        return $this->_rows[$position];
    }

}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Row.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @see Zend_Db_Table_Row_Abstract
 */
require_once 'Zend/Db/Table/Row/Abstract.php';


/**
 * Reference concrete class that extends Zend_Db_Table_Row_Abstract.
 * Developers may also create their own classes that extend the abstract class.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Db_Table_Row extends Zend_Db_Table_Row_Abstract
{
}

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
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Db
 */
require_once 'Zend/Db.php';

/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Table_Row_Abstract implements ArrayAccess, IteratorAggregate
{

    /**
     * The data for each column in the row (column_name => value).
     * The keys must match the physical names of columns in the
     * table for which this row is defined.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * This is set to a copy of $_data when the data is fetched from
     * a database, specified as a new tuple in the constructor, or
     * when dirty data is posted to the database with save().
     *
     * @var array
     */
    protected $_cleanData = array();

    /**
     * Tracks columns where data has been updated. Allows more specific insert and
     * update operations.
     *
     * @var array
     */
    protected $_modifiedFields = array();

    /**
     * Zend_Db_Table_Abstract parent class or instance.
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_table = null;

    /**
     * Connected is true if we have a reference to a live
     * Zend_Db_Table_Abstract object.
     * This is false after the Rowset has been deserialized.
     *
     * @var boolean
     */
    protected $_connected = true;

    /**
     * A row is marked read only if it contains columns that are not physically represented within
     * the database schema (e.g. evaluated columns/Zend_Db_Expr columns). This can also be passed
     * as a run-time config options as a means of protecting row data.
     *
     * @var boolean
     */
    protected $_readOnly = false;

    /**
     * Name of the class of the Zend_Db_Table_Abstract object.
     *
     * @var string
     */
    protected $_tableClass = null;

    /**
     * Primary row key(s).
     *
     * @var array
     */
    protected $_primary;

    /**
     * Constructor.
     *
     * Supported params for $config are:-
     * - table       = class name or object of type Zend_Db_Table_Abstract
     * - data        = values of columns in this row.
     *
     * @param  array $config OPTIONAL Array of user-specified config options.
     * @return void
     * @throws Zend_Db_Table_Row_Exception
     */
    public function __construct(array $config = array())
    {
        if (isset($config['table']) && $config['table'] instanceof Zend_Db_Table_Abstract) {
            $this->_table = $config['table'];
            $this->_tableClass = get_class($this->_table);
        } elseif ($this->_tableClass !== null) {
            $this->_table = $this->_getTableFromString($this->_tableClass);
        }

        if (isset($config['data'])) {
            if (!is_array($config['data'])) {
                require_once 'Zend/Db/Table/Row/Exception.php';
                throw new Zend_Db_Table_Row_Exception('Data must be an array');
            }
            $this->_data = $config['data'];
        }
        if (isset($config['stored']) && $config['stored'] === true) {
            $this->_cleanData = $this->_data;
        }

        if (isset($config['readOnly']) && $config['readOnly'] === true) {
            $this->setReadOnly(true);
        }

        // Retrieve primary keys from table schema
        if (($table = $this->_getTable())) {
            $info = $table->info();
            $this->_primary = (array) $info['primary'];
        }

        $this->init();
    }

    /**
     * Transform a column name from the user-specified form
     * to the physical form used in the database.
     * You can override this method in a custom Row class
     * to implement column name mappings, for example inflection.
     *
     * @param string $columnName Column name given.
     * @return string The column name after transformation applied (none by default).
     * @throws Zend_Db_Table_Row_Exception if the $columnName is not a string.
     */
    protected function _transformColumn($columnName)
    {
        if (!is_string($columnName)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('Specified column is not a string');
        }
        // Perform no transformation by default
        return $columnName;
    }

    /**
     * Retrieve row field value
     *
     * @param  string $columnName The user-specified column name.
     * @return string             The corresponding column value.
     * @throws Zend_Db_Table_Row_Exception if the $columnName is not a column in the row.
     */
    public function __get($columnName)
    {
        $columnName = $this->_transformColumn($columnName);
        if (!array_key_exists($columnName, $this->_data)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is not in the row");
        }
        return $this->_data[$columnName];
    }

    /**
     * Set row field value
     *
     * @param  string $columnName The column key.
     * @param  mixed  $value      The value for the property.
     * @return void
     * @throws Zend_Db_Table_Row_Exception
     */
    public function __set($columnName, $value)
    {
        $columnName = $this->_transformColumn($columnName);
        if (!array_key_exists($columnName, $this->_data)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is not in the row");
        }
        $this->_data[$columnName] = $value;
        $this->_modifiedFields[$columnName] = true;
    }

    /**
     * Unset row field value
     *
     * @param  string $columnName The column key.
     * @return Zend_Db_Table_Row_Abstract
     * @throws Zend_Db_Table_Row_Exception
     */
    public function __unset($columnName)
    {
        $columnName = $this->_transformColumn($columnName);
        if (!array_key_exists($columnName, $this->_data)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is not in the row");
        }
        if ($this->isConnected() && in_array($columnName, $this->_table->info('primary'))) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is a primary key and should not be unset");
        }
        unset($this->_data[$columnName]);
        return $this;
    }

    /**
     * Test existence of row field
     *
     * @param  string  $columnName   The column key.
     * @return boolean
     */
    public function __isset($columnName)
    {
        $columnName = $this->_transformColumn($columnName);
        return array_key_exists($columnName, $this->_data);
    }

    /**
     * Store table, primary key and data in serialized object
     *
     * @return array
     */
    public function __sleep()
    {
        return array('_tableClass', '_primary', '_data', '_cleanData', '_readOnly' ,'_modifiedFields');
    }

    /**
     * Setup to do on wakeup.
     * A de-serialized Row should not be assumed to have access to a live
     * database connection, so set _connected = false.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->_connected = false;
    }

    /**
     * Proxy to __isset
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Proxy to __get
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return string
     */
     public function offsetGet($offset)
     {
         return $this->__get($offset);
     }

     /**
      * Proxy to __set
      * Required by the ArrayAccess implementation
      *
      * @param string $offset
      * @param mixed $value
      */
     public function offsetSet($offset, $value)
     {
         $this->__set($offset, $value);
     }

     /**
      * Proxy to __unset
      * Required by the ArrayAccess implementation
      *
      * @param string $offset
      */
     public function offsetUnset($offset)
     {
         return $this->__unset($offset);
     }

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Returns the table object, or null if this is disconnected row
     *
     * @return Zend_Db_Table_Abstract|null
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Set the table object, to re-establish a live connection
     * to the database for a Row that has been de-serialized.
     *
     * @param Zend_Db_Table_Abstract $table
     * @return boolean
     * @throws Zend_Db_Table_Row_Exception
     */
    public function setTable(Zend_Db_Table_Abstract $table = null)
    {
        if ($table == null) {
            $this->_table = null;
            $this->_connected = false;
            return false;
        }

        $tableClass = get_class($table);
        if (! $table instanceof $this->_tableClass) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("The specified Table is of class $tableClass, expecting class to be instance of $this->_tableClass");
        }

        $this->_table = $table;
        $this->_tableClass = $tableClass;

        $info = $this->_table->info();

        if ($info['cols'] != array_keys($this->_data)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('The specified Table does not have the same columns as the Row');
        }

        if (! array_intersect((array) $this->_primary, $info['primary']) == (array) $this->_primary) {

            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("The specified Table '$tableClass' does not have the same primary key as the Row");
        }

        $this->_connected = true;
        return true;
    }

    /**
     * Query the class name of the Table object for which this
     * Row was created.
     *
     * @return string
     */
    public function getTableClass()
    {
        return $this->_tableClass;
    }

    /**
     * Test the connected status of the row.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_connected;
    }

    /**
     * Test the read-only status of the row.
     *
     * @return boolean
     */
    public function isReadOnly()
    {
        return $this->_readOnly;
    }

    /**
     * Set the read-only status of the row.
     *
     * @param boolean $flag
     * @return boolean
     */
    public function setReadOnly($flag)
    {
        $this->_readOnly = (bool) $flag;
    }

    /**
     * Returns an instance of the parent table's Zend_Db_Table_Select object.
     *
     * @return Zend_Db_Table_Select
     */
    public function select()
    {
        return $this->getTable()->select();
    }

    /**
     * Saves the properties to the database.
     *
     * This performs an intelligent insert/update, and reloads the
     * properties with fresh data from the table on success.
     *
     * @return mixed The primary key value(s), as an associative array if the
     *     key is compound, or a scalar if the key is single-column.
     */
    public function save()
    {
        /**
         * If the _cleanData array is empty,
         * this is an INSERT of a new row.
         * Otherwise it is an UPDATE.
         */
        if (empty($this->_cleanData)) {
            return $this->_doInsert();
        } else {
            return $this->_doUpdate();
        }
    }

    /**
     * @return mixed The primary key value(s), as an associative array if the
     *     key is compound, or a scalar if the key is single-column.
     */
    protected function _doInsert()
    {
        /**
         * A read-only row cannot be saved.
         */
        if ($this->_readOnly === true) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('This row has been marked read-only');
        }

        /**
         * Run pre-INSERT logic
         */
        $this->_insert();

        /**
         * Execute the INSERT (this may throw an exception)
         */
        $data = array_intersect_key($this->_data, $this->_modifiedFields);
        $primaryKey = $this->_getTable()->insert($data);

        /**
         * Normalize the result to an array indexed by primary key column(s).
         * The table insert() method may return a scalar.
         */
        if (is_array($primaryKey)) {
            $newPrimaryKey = $primaryKey;
        } else {
            //ZF-6167 Use tempPrimaryKey temporary to avoid that zend encoding fails.
            $tempPrimaryKey = (array) $this->_primary;
            $newPrimaryKey = array(current($tempPrimaryKey) => $primaryKey);
        }

        /**
         * Save the new primary key value in _data.  The primary key may have
         * been generated by a sequence or auto-increment mechanism, and this
         * merge should be done before the _postInsert() method is run, so the
         * new values are available for logging, etc.
         */
        $this->_data = array_merge($this->_data, $newPrimaryKey);

        /**
         * Run post-INSERT logic
         */
        $this->_postInsert();

        /**
         * Update the _cleanData to reflect that the data has been inserted.
         */
        $this->_refresh();

        return $primaryKey;
    }

    /**
     * @return mixed The primary key value(s), as an associative array if the
     *     key is compound, or a scalar if the key is single-column.
     */
    protected function _doUpdate()
    {
        /**
         * A read-only row cannot be saved.
         */
        if ($this->_readOnly === true) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('This row has been marked read-only');
        }

        /**
         * Get expressions for a WHERE clause
         * based on the primary key value(s).
         */
        $where = $this->_getWhereQuery(false);

        /**
         * Run pre-UPDATE logic
         */
        $this->_update();

        /**
         * Compare the data to the modified fields array to discover
         * which columns have been changed.
         */
        $diffData = array_intersect_key($this->_data, $this->_modifiedFields);

        /**
         * Were any of the changed columns part of the primary key?
         */
        $pkDiffData = array_intersect_key($diffData, array_flip((array)$this->_primary));

        /**
         * Execute cascading updates against dependent tables.
         * Do this only if primary key value(s) were changed.
         */
        if (count($pkDiffData) > 0) {
            $depTables = $this->_getTable()->getDependentTables();
            if (!empty($depTables)) {
                $pkNew = $this->_getPrimaryKey(true);
                $pkOld = $this->_getPrimaryKey(false);
                foreach ($depTables as $tableClass) {
                    $t = $this->_getTableFromString($tableClass);
                    $t->_cascadeUpdate($this->getTableClass(), $pkOld, $pkNew);
                }
            }
        }

        /**
         * Execute the UPDATE (this may throw an exception)
         * Do this only if data values were changed.
         * Use the $diffData variable, so the UPDATE statement
         * includes SET terms only for data values that changed.
         */
        if (count($diffData) > 0) {
            $this->_getTable()->update($diffData, $where);
        }

        /**
         * Run post-UPDATE logic.  Do this before the _refresh()
         * so the _postUpdate() function can tell the difference
         * between changed data and clean (pre-changed) data.
         */
        $this->_postUpdate();

        /**
         * Refresh the data just in case triggers in the RDBMS changed
         * any columns.  Also this resets the _cleanData.
         */
        $this->_refresh();

        /**
         * Return the primary key value(s) as an array
         * if the key is compound or a scalar if the key
         * is a scalar.
         */
        $primaryKey = $this->_getPrimaryKey(true);
        if (count($primaryKey) == 1) {
            return current($primaryKey);
        }

        return $primaryKey;
    }

    /**
     * Deletes existing rows.
     *
     * @return int The number of rows deleted.
     */
    public function delete()
    {
        /**
         * A read-only row cannot be deleted.
         */
        if ($this->_readOnly === true) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('This row has been marked read-only');
        }

        $where = $this->_getWhereQuery();

        /**
         * Execute pre-DELETE logic
         */
        $this->_delete();

        /**
         * Execute cascading deletes against dependent tables
         */
        $depTables = $this->_getTable()->getDependentTables();
        if (!empty($depTables)) {
            $pk = $this->_getPrimaryKey();
            foreach ($depTables as $tableClass) {
                $t = $this->_getTableFromString($tableClass);
                $t->_cascadeDelete($this->getTableClass(), $pk);
            }
        }

        /**
         * Execute the DELETE (this may throw an exception)
         */
        $result = $this->_getTable()->delete($where);

        /**
         * Execute post-DELETE logic
         */
        $this->_postDelete();

        /**
         * Reset all fields to null to indicate that the row is not there
         */
        $this->_data = array_combine(
            array_keys($this->_data),
            array_fill(0, count($this->_data), null)
        );

        return $result;
    }

    public function getIterator()
    {
        return new ArrayIterator((array) $this->_data);
    }

    /**
     * Returns the column/value data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return (array)$this->_data;
    }

    /**
     * Sets all data in the row from an array.
     *
     * @param  array $data
     * @return Zend_Db_Table_Row_Abstract Provides a fluent interface
     */
    public function setFromArray(array $data)
    {
        $data = array_intersect_key($data, $this->_data);

        foreach ($data as $columnName => $value) {
            $this->__set($columnName, $value);
        }

        return $this;
    }

    /**
     * Refreshes properties from the database.
     *
     * @return void
     */
    public function refresh()
    {
        return $this->_refresh();
    }

    /**
     * Retrieves an instance of the parent table.
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTable()
    {
        if (!$this->_connected) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('Cannot save a Row unless it is connected');
        }
        return $this->_table;
    }

    /**
     * Retrieves an associative array of primary keys.
     *
     * @param bool $useDirty
     * @return array
     */
    protected function _getPrimaryKey($useDirty = true)
    {
        if (!is_array($this->_primary)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("The primary key must be set as an array");
        }

        $primary = array_flip($this->_primary);
        if ($useDirty) {
            $array = array_intersect_key($this->_data, $primary);
        } else {
            $array = array_intersect_key($this->_cleanData, $primary);
        }
        if (count($primary) != count($array)) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("The specified Table '$this->_tableClass' does not have the same primary key as the Row");
        }
        return $array;
    }

    /**
     * Constructs where statement for retrieving row(s).
     *
     * @param bool $useDirty
     * @return array
     */
    protected function _getWhereQuery($useDirty = true)
    {
        $where = array();
        $db = $this->_getTable()->getAdapter();
        $primaryKey = $this->_getPrimaryKey($useDirty);
        $info = $this->_getTable()->info();
        $metadata = $info[Zend_Db_Table_Abstract::METADATA];

        // retrieve recently updated row using primary keys
        $where = array();
        foreach ($primaryKey as $column => $value) {
            $tableName = $db->quoteIdentifier($info[Zend_Db_Table_Abstract::NAME], true);
            $type = $metadata[$column]['DATA_TYPE'];
            $columnName = $db->quoteIdentifier($column, true);
            $where[] = $db->quoteInto("{$tableName}.{$columnName} = ?", $value, $type);
        }
        return $where;
    }

    /**
     * Refreshes properties from the database.
     *
     * @return void
     */
    protected function _refresh()
    {
        $where = $this->_getWhereQuery();
        $row = $this->_getTable()->fetchRow($where);

        if (null === $row) {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception('Cannot refresh row as parent is missing');
        }

        $this->_data = $row->toArray();
        $this->_cleanData = $this->_data;
        $this->_modifiedFields = array();
    }

    /**
     * Allows pre-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _insert()
    {
    }

    /**
     * Allows post-insert logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postInsert()
    {
    }

    /**
     * Allows pre-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _update()
    {
    }

    /**
     * Allows post-update logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postUpdate()
    {
    }

    /**
     * Allows pre-delete logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _delete()
    {
    }

    /**
     * Allows post-delete logic to be applied to row.
     * Subclasses may override this method.
     *
     * @return void
     */
    protected function _postDelete()
    {
    }

    /**
     * Prepares a table reference for lookup.
     *
     * Ensures all reference keys are set and properly formatted.
     *
     * @param Zend_Db_Table_Abstract $dependentTable
     * @param Zend_Db_Table_Abstract $parentTable
     * @param string                 $ruleKey
     * @return array
     */
    protected function _prepareReference(Zend_Db_Table_Abstract $dependentTable, Zend_Db_Table_Abstract $parentTable, $ruleKey)
    {
        $parentTableName = (get_class($parentTable) === 'Zend_Db_Table') ? $parentTable->getDefinitionConfigName() : get_class($parentTable);
        $map = $dependentTable->getReference($parentTableName, $ruleKey);

        if (!isset($map[Zend_Db_Table_Abstract::REF_COLUMNS])) {
            $parentInfo = $parentTable->info();
            $map[Zend_Db_Table_Abstract::REF_COLUMNS] = array_values((array) $parentInfo['primary']);
        }

        $map[Zend_Db_Table_Abstract::COLUMNS] = (array) $map[Zend_Db_Table_Abstract::COLUMNS];
        $map[Zend_Db_Table_Abstract::REF_COLUMNS] = (array) $map[Zend_Db_Table_Abstract::REF_COLUMNS];

        return $map;
    }

    /**
     * Query a dependent table to retrieve rows matching the current row.
     *
     * @param string|Zend_Db_Table_Abstract  $dependentTable
     * @param string                         OPTIONAL $ruleKey
     * @param Zend_Db_Table_Select           OPTIONAL $select
     * @return Zend_Db_Table_Rowset_Abstract Query result from $dependentTable
     * @throws Zend_Db_Table_Row_Exception If $dependentTable is not a table or is not loadable.
     */
    public function findDependentRowset($dependentTable, $ruleKey = null, Zend_Db_Table_Select $select = null)
    {
        $db = $this->_getTable()->getAdapter();

        if (is_string($dependentTable)) {
            $dependentTable = $this->_getTableFromString($dependentTable);
        }

        if (!$dependentTable instanceof Zend_Db_Table_Abstract) {
            $type = gettype($dependentTable);
            if ($type == 'object') {
                $type = get_class($dependentTable);
            }
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Dependent table must be a Zend_Db_Table_Abstract, but it is $type");
        }

        // even if we are interacting between a table defined in a class and a
        // table via extension, ensure to persist the definition
        if (($tableDefinition = $this->_table->getDefinition()) !== null
            && ($dependentTable->getDefinition() == null)) {
            $dependentTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
        }

        if ($select === null) {
            $select = $dependentTable->select();
        } else {
            $select->setTable($dependentTable);
        }

        $map = $this->_prepareReference($dependentTable, $this->_getTable(), $ruleKey);

        for ($i = 0; $i < count($map[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
            $parentColumnName = $db->foldCase($map[Zend_Db_Table_Abstract::REF_COLUMNS][$i]);
            $value = $this->_data[$parentColumnName];
            // Use adapter from dependent table to ensure correct query construction
            $dependentDb = $dependentTable->getAdapter();
            $dependentColumnName = $dependentDb->foldCase($map[Zend_Db_Table_Abstract::COLUMNS][$i]);
            $dependentColumn = $dependentDb->quoteIdentifier($dependentColumnName, true);
            $dependentInfo = $dependentTable->info();
            $type = $dependentInfo[Zend_Db_Table_Abstract::METADATA][$dependentColumnName]['DATA_TYPE'];
            $select->where("$dependentColumn = ?", $value, $type);
        }

        return $dependentTable->fetchAll($select);
    }

    /**
     * Query a parent table to retrieve the single row matching the current row.
     *
     * @param string|Zend_Db_Table_Abstract $parentTable
     * @param string                        OPTIONAL $ruleKey
     * @param Zend_Db_Table_Select          OPTIONAL $select
     * @return Zend_Db_Table_Row_Abstract   Query result from $parentTable
     * @throws Zend_Db_Table_Row_Exception If $parentTable is not a table or is not loadable.
     */
    public function findParentRow($parentTable, $ruleKey = null, Zend_Db_Table_Select $select = null)
    {
        $db = $this->_getTable()->getAdapter();

        if (is_string($parentTable)) {
            $parentTable = $this->_getTableFromString($parentTable);
        }

        if (!$parentTable instanceof Zend_Db_Table_Abstract) {
            $type = gettype($parentTable);
            if ($type == 'object') {
                $type = get_class($parentTable);
            }
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Parent table must be a Zend_Db_Table_Abstract, but it is $type");
        }

        // even if we are interacting between a table defined in a class and a
        // table via extension, ensure to persist the definition
        if (($tableDefinition = $this->_table->getDefinition()) !== null
            && ($parentTable->getDefinition() == null)) {
            $parentTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
        }

        if ($select === null) {
            $select = $parentTable->select();
        } else {
            $select->setTable($parentTable);
        }

        $map = $this->_prepareReference($this->_getTable(), $parentTable, $ruleKey);

        // iterate the map, creating the proper wheres
        for ($i = 0; $i < count($map[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
            $dependentColumnName = $db->foldCase($map[Zend_Db_Table_Abstract::COLUMNS][$i]);
            $value = $this->_data[$dependentColumnName];
            // Use adapter from parent table to ensure correct query construction
            $parentDb = $parentTable->getAdapter();
            $parentColumnName = $parentDb->foldCase($map[Zend_Db_Table_Abstract::REF_COLUMNS][$i]);
            $parentColumn = $parentDb->quoteIdentifier($parentColumnName, true);
            $parentInfo = $parentTable->info();

            // determine where part
            $type     = $parentInfo[Zend_Db_Table_Abstract::METADATA][$parentColumnName]['DATA_TYPE'];
            $nullable = $parentInfo[Zend_Db_Table_Abstract::METADATA][$parentColumnName]['NULLABLE'];
            if ($value === null && $nullable == true) {
                $select->where("$parentColumn IS NULL");
            } elseif ($value === null && $nullable == false) {
                return null;
            } else {
                $select->where("$parentColumn = ?", $value, $type);
            }

        }

        return $parentTable->fetchRow($select);
    }

    /**
     * @param  string|Zend_Db_Table_Abstract  $matchTable
     * @param  string|Zend_Db_Table_Abstract  $intersectionTable
     * @param  string                         OPTIONAL $callerRefRule
     * @param  string                         OPTIONAL $matchRefRule
     * @param  Zend_Db_Table_Select           OPTIONAL $select
     * @return Zend_Db_Table_Rowset_Abstract Query result from $matchTable
     * @throws Zend_Db_Table_Row_Exception If $matchTable or $intersectionTable is not a table class or is not loadable.
     */
    public function findManyToManyRowset($matchTable, $intersectionTable, $callerRefRule = null,
                                         $matchRefRule = null, Zend_Db_Table_Select $select = null)
    {
        $db = $this->_getTable()->getAdapter();

        if (is_string($intersectionTable)) {
            $intersectionTable = $this->_getTableFromString($intersectionTable);
        }

        if (!$intersectionTable instanceof Zend_Db_Table_Abstract) {
            $type = gettype($intersectionTable);
            if ($type == 'object') {
                $type = get_class($intersectionTable);
            }
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Intersection table must be a Zend_Db_Table_Abstract, but it is $type");
        }

        // even if we are interacting between a table defined in a class and a
        // table via extension, ensure to persist the definition
        if (($tableDefinition = $this->_table->getDefinition()) !== null
            && ($intersectionTable->getDefinition() == null)) {
            $intersectionTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
        }

        if (is_string($matchTable)) {
            $matchTable = $this->_getTableFromString($matchTable);
        }

        if (! $matchTable instanceof Zend_Db_Table_Abstract) {
            $type = gettype($matchTable);
            if ($type == 'object') {
                $type = get_class($matchTable);
            }
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Match table must be a Zend_Db_Table_Abstract, but it is $type");
        }

        // even if we are interacting between a table defined in a class and a
        // table via extension, ensure to persist the definition
        if (($tableDefinition = $this->_table->getDefinition()) !== null
            && ($matchTable->getDefinition() == null)) {
            $matchTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
        }

        if ($select === null) {
            $select = $matchTable->select();
        } else {
            $select->setTable($matchTable);
        }

        // Use adapter from intersection table to ensure correct query construction
        $interInfo = $intersectionTable->info();
        $interDb   = $intersectionTable->getAdapter();
        $interName = $interInfo['name'];
        $interSchema = isset($interInfo['schema']) ? $interInfo['schema'] : null;
        $matchInfo = $matchTable->info();
        $matchName = $matchInfo['name'];
        $matchSchema = isset($matchInfo['schema']) ? $matchInfo['schema'] : null;

        $matchMap = $this->_prepareReference($intersectionTable, $matchTable, $matchRefRule);

        for ($i = 0; $i < count($matchMap[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
            $interCol = $interDb->quoteIdentifier('i' . '.' . $matchMap[Zend_Db_Table_Abstract::COLUMNS][$i], true);
            $matchCol = $interDb->quoteIdentifier('m' . '.' . $matchMap[Zend_Db_Table_Abstract::REF_COLUMNS][$i], true);
            $joinCond[] = "$interCol = $matchCol";
        }
        $joinCond = implode(' AND ', $joinCond);

        $select->from(array('i' => $interName), array(), $interSchema)
               ->joinInner(array('m' => $matchName), $joinCond, Zend_Db_Select::SQL_WILDCARD, $matchSchema)
               ->setIntegrityCheck(false);

        $callerMap = $this->_prepareReference($intersectionTable, $this->_getTable(), $callerRefRule);

        for ($i = 0; $i < count($callerMap[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
            $callerColumnName = $db->foldCase($callerMap[Zend_Db_Table_Abstract::REF_COLUMNS][$i]);
            $value = $this->_data[$callerColumnName];
            $interColumnName = $interDb->foldCase($callerMap[Zend_Db_Table_Abstract::COLUMNS][$i]);
            $interCol = $interDb->quoteIdentifier("i.$interColumnName", true);
            $interInfo = $intersectionTable->info();
            $type = $interInfo[Zend_Db_Table_Abstract::METADATA][$interColumnName]['DATA_TYPE'];
            $select->where($interDb->quoteInto("$interCol = ?", $value, $type));
        }

        $stmt = $select->query();

        $config = array(
            'table'    => $matchTable,
            'data'     => $stmt->fetchAll(Zend_Db::FETCH_ASSOC),
            'rowClass' => $matchTable->getRowClass(),
            'readOnly' => false,
            'stored'   => true
        );

        $rowsetClass = $matchTable->getRowsetClass();
        if (!class_exists($rowsetClass)) {
            try {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($rowsetClass);
            } catch (Zend_Exception $e) {
                require_once 'Zend/Db/Table/Row/Exception.php';
                throw new Zend_Db_Table_Row_Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
        $rowset = new $rowsetClass($config);
        return $rowset;
    }

    /**
     * Turn magic function calls into non-magic function calls
     * to the above methods.
     *
     * @param string $method
     * @param array $args OPTIONAL Zend_Db_Table_Select query modifier
     * @return Zend_Db_Table_Row_Abstract|Zend_Db_Table_Rowset_Abstract
     * @throws Zend_Db_Table_Row_Exception If an invalid method is called.
     */
    public function __call($method, array $args)
    {
        $matches = array();

        if (count($args) && $args[0] instanceof Zend_Db_Table_Select) {
            $select = $args[0];
        } else {
            $select = null;
        }

        /**
         * Recognize methods for Has-Many cases:
         * findParent<Class>()
         * findParent<Class>By<Rule>()
         * Use the non-greedy pattern repeat modifier e.g. \w+?
         */
        if (preg_match('/^findParent(\w+?)(?:By(\w+))?$/', $method, $matches)) {
            $class    = $matches[1];
            $ruleKey1 = isset($matches[2]) ? $matches[2] : null;
            return $this->findParentRow($class, $ruleKey1, $select);
        }

        /**
         * Recognize methods for Many-to-Many cases:
         * find<Class1>Via<Class2>()
         * find<Class1>Via<Class2>By<Rule>()
         * find<Class1>Via<Class2>By<Rule1>And<Rule2>()
         * Use the non-greedy pattern repeat modifier e.g. \w+?
         */
        if (preg_match('/^find(\w+?)Via(\w+?)(?:By(\w+?)(?:And(\w+))?)?$/', $method, $matches)) {
            $class    = $matches[1];
            $viaClass = $matches[2];
            $ruleKey1 = isset($matches[3]) ? $matches[3] : null;
            $ruleKey2 = isset($matches[4]) ? $matches[4] : null;
            return $this->findManyToManyRowset($class, $viaClass, $ruleKey1, $ruleKey2, $select);
        }

        /**
         * Recognize methods for Belongs-To cases:
         * find<Class>()
         * find<Class>By<Rule>()
         * Use the non-greedy pattern repeat modifier e.g. \w+?
         */
        if (preg_match('/^find(\w+?)(?:By(\w+))?$/', $method, $matches)) {
            $class    = $matches[1];
            $ruleKey1 = isset($matches[2]) ? $matches[2] : null;
            return $this->findDependentRowset($class, $ruleKey1, $select);
        }

        require_once 'Zend/Db/Table/Row/Exception.php';
        throw new Zend_Db_Table_Row_Exception("Unrecognized method '$method()'");
    }


    /**
     * _getTableFromString
     *
     * @param string $tableName
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTableFromString($tableName)
    {

        if ($this->_table instanceof Zend_Db_Table_Abstract) {
            $tableDefinition = $this->_table->getDefinition();

            if ($tableDefinition !== null && $tableDefinition->hasTableConfig($tableName)) {
                return new Zend_Db_Table($tableName, $tableDefinition);
            }
        }

        // assume the tableName is the class name
        if (!class_exists($tableName)) {
            try {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($tableName);
            } catch (Zend_Exception $e) {
                require_once 'Zend/Db/Table/Row/Exception.php';
                throw new Zend_Db_Table_Row_Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        $options = array();

        if (($table = $this->_getTable())) {
            $options['db'] = $table->getAdapter();
        }

        if (isset($tableDefinition) && $tableDefinition !== null) {
            $options[Zend_Db_Table_Abstract::DEFINITION] = $tableDefinition;
        }

        return new $tableName($options);
    }

}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Seo
{
	protected static $title = array();
	protected static $description = array();
	protected static $keywords =array();
	protected static $order = NULL;
	protected static $separator = NULL;
	
	protected function __construct()
	{
		
	}
	
	public static function setTitle($title)
	{
		if (!is_string($title) || !$title) return ;
		self::$title = array($title);
	}

	public static function setDescription($desc)
	{
		if (!is_string($desc) && !$desc) return ;
		self::$description = array($desc);
	}
	
	public static function setKeywords($Keywords)
	{
		if (!is_string($Keywords) && !$Keywords) return ;
		self::$keywords = array($Keywords);
	}
	
	public static function addTitle($title)
	{
		if (!is_string($title) && !$title) return ;
		self::$title[] = $title;
	}

	public static function addDescription($desc)
	{
		if (!is_string($desc) && !$desc) return ;
		self::$description[] = $desc;
	}
	
	public static function addKeywords($Keywords)
	{
		if (!is_string($Keywords) && !$Keywords) return ;
		self::$keywords[] = $Keywords;
	}
	
	public static function getTitle()
	{
		$res_array = self::$title;
		if (self::getOrder() == 'prepend')
		{
			$res_array = array_reverse($res_array);
		}
		return implode(self::getSeparator(), $res_array);
	}

	public static function getDescription()
	{
		$res_array = self::$description;
		if (self::getOrder() == 'prepend')
		{
			$res_array = array_reverse($res_array);
		}
		return implode(', ', $res_array);
	}
	
	public static function getKeywords()
	{
		$res_array = self::$keywords;
		if (self::getOrder() == 'prepend')
		{
			$res_array = array_reverse($res_array);
		}
		return implode(', ', $res_array);
	}
	
	
	
	protected static function getOrder()
	{
		if (NULL === self::$order)
		{
			$default = 'prepend';
			$config = Zend_Registry::getInstance()->get('config')->site;
			if (!$config) self::$order = $default;
			else self::$order = $config->title->get('order',$default);
			unset($config);
		}
		return self::$order;
	}
	
	protected static function getSeparator()
	{
		if (NULL === self::$separator)
		{
			$default = ' — ';
			$config = Zend_Registry::getInstance()->get('config')->site;
			if (!$config) self::$sepsrator = $default;
			else self::$separator = $config->title->get('separator',$default);
			unset($config);
		}
		return self::$separator;
	}
	
}
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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Inflector.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter.php';

/**
 * @see Zend_Loader_PluginLoader
 */
require_once 'Zend/Loader/PluginLoader.php';

/**
 * Filter chain for string inflection
 *
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_Inflector implements Zend_Filter_Interface
{
    /**
     * @var Zend_Loader_PluginLoader_Interface
     */
    protected $_pluginLoader = null;

    /**
     * @var string
     */
    protected $_target = null;

    /**
     * @var bool
     */
    protected $_throwTargetExceptionsOn = true;

    /**
     * @var string
     */
    protected $_targetReplacementIdentifier = ':';

    /**
     * @var array
     */
    protected $_rules = array();

    /**
     * Constructor
     *
     * @param string|array $options Options to set
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp    = array();

            if (!empty($options)) {
                $temp['target'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['rules'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['throwTargetExceptionsOn'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['targetReplacementIdentifier'] = array_shift($options);
            }

            $options = $temp;
        }

        $this->setOptions($options);
    }

    /**
     * Retreive PluginLoader
     *
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader()
    {
        if (!$this->_pluginLoader instanceof Zend_Loader_PluginLoader_Interface) {
            $this->_pluginLoader = new Zend_Loader_PluginLoader(array('Zend_Filter_' => 'Zend/Filter/'), __CLASS__);
        }

        return $this->_pluginLoader;
    }

    /**
     * Set PluginLoader
     *
     * @param Zend_Loader_PluginLoader_Interface $pluginLoader
     * @return Zend_Filter_Inflector
     */
    public function setPluginLoader(Zend_Loader_PluginLoader_Interface $pluginLoader)
    {
        $this->_pluginLoader = $pluginLoader;
        return $this;
    }

    /**
     * Use Zend_Config object to set object state
     *
     * @deprecated Use setOptions() instead
     * @param  Zend_Config $config
     * @return Zend_Filter_Inflector
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config);
    }

    /**
     * Set options
     *
     * @param  array $options
     * @return Zend_Filter_Inflector
     */
    public function setOptions($options) {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        // Set Präfix Path
        if (array_key_exists('filterPrefixPath', $options)) {
            if (!is_scalar($options['filterPrefixPath'])) {
                foreach ($options['filterPrefixPath'] as $prefix => $path) {
                    $this->addFilterPrefixPath($prefix, $path);
                }
            }
        }

        if (array_key_exists('throwTargetExceptionsOn', $options)) {
            $this->setThrowTargetExceptionsOn($options['throwTargetExceptionsOn']);
        }

        if (array_key_exists('targetReplacementIdentifier', $options)) {
            $this->setTargetReplacementIdentifier($options['targetReplacementIdentifier']);
        }

        if (array_key_exists('target', $options)) {
            $this->setTarget($options['target']);
        }

        if (array_key_exists('rules', $options)) {
            $this->addRules($options['rules']);
        }

        return $this;
    }

    /**
     * Convienence method to add prefix and path to PluginLoader
     *
     * @param string $prefix
     * @param string $path
     * @return Zend_Filter_Inflector
     */
    public function addFilterPrefixPath($prefix, $path)
    {
        $this->getPluginLoader()->addPrefixPath($prefix, $path);
        return $this;
    }

    /**
     * Set Whether or not the inflector should throw an exception when a replacement
     * identifier is still found within an inflected target.
     *
     * @param bool $throwTargetExceptions
     * @return Zend_Filter_Inflector
     */
    public function setThrowTargetExceptionsOn($throwTargetExceptionsOn)
    {
        $this->_throwTargetExceptionsOn = ($throwTargetExceptionsOn == true) ? true : false;
        return $this;
    }

    /**
     * Will exceptions be thrown?
     *
     * @return bool
     */
    public function isThrowTargetExceptionsOn()
    {
        return $this->_throwTargetExceptionsOn;
    }

    /**
     * Set the Target Replacement Identifier, by default ':'
     *
     * @param string $targetReplacementIdentifier
     * @return Zend_Filter_Inflector
     */
    public function setTargetReplacementIdentifier($targetReplacementIdentifier)
    {
        if ($targetReplacementIdentifier) {
            $this->_targetReplacementIdentifier = (string) $targetReplacementIdentifier;
        }

        return $this;
    }

    /**
     * Get Target Replacement Identifier
     *
     * @return string
     */
    public function getTargetReplacementIdentifier()
    {
        return $this->_targetReplacementIdentifier;
    }

    /**
     * Set a Target
     * ex: 'scripts/:controller/:action.:suffix'
     *
     * @param string
     * @return Zend_Filter_Inflector
     */
    public function setTarget($target)
    {
        $this->_target = (string) $target;
        return $this;
    }

    /**
     * Retrieve target
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->_target;
    }

    /**
     * Set Target Reference
     *
     * @param reference $target
     * @return Zend_Filter_Inflector
     */
    public function setTargetReference(&$target)
    {
        $this->_target =& $target;
        return $this;
    }

    /**
     * SetRules() is the same as calling addRules() with the exception that it
     * clears the rules before adding them.
     *
     * @param array $rules
     * @return Zend_Filter_Inflector
     */
    public function setRules(Array $rules)
    {
        $this->clearRules();
        $this->addRules($rules);
        return $this;
    }

    /**
     * AddRules(): multi-call to setting filter rules.
     *
     * If prefixed with a ":" (colon), a filter rule will be added.  If not
     * prefixed, a static replacement will be added.
     *
     * ex:
     * array(
     *     ':controller' => array('CamelCaseToUnderscore','StringToLower'),
     *     ':action'     => array('CamelCaseToUnderscore','StringToLower'),
     *     'suffix'      => 'phtml'
     *     );
     *
     * @param array
     * @return Zend_Filter_Inflector
     */
    public function addRules(Array $rules)
    {
        $keys = array_keys($rules);
        foreach ($keys as $spec) {
            if ($spec[0] == ':') {
                $this->addFilterRule($spec, $rules[$spec]);
            } else {
                $this->setStaticRule($spec, $rules[$spec]);
            }
        }

        return $this;
    }

    /**
     * Get rules
     *
     * By default, returns all rules. If a $spec is provided, will return those
     * rules if found, false otherwise.
     *
     * @param  string $spec
     * @return array|false
     */
    public function getRules($spec = null)
    {
        if (null !== $spec) {
            $spec = $this->_normalizeSpec($spec);
            if (isset($this->_rules[$spec])) {
                return $this->_rules[$spec];
            }
            return false;
        }

        return $this->_rules;
    }

    /**
     * getRule() returns a rule set by setFilterRule(), a numeric index must be provided
     *
     * @param string $spec
     * @param int $index
     * @return Zend_Filter_Interface|false
     */
    public function getRule($spec, $index)
    {
        $spec = $this->_normalizeSpec($spec);
        if (isset($this->_rules[$spec]) && is_array($this->_rules[$spec])) {
            if (isset($this->_rules[$spec][$index])) {
                return $this->_rules[$spec][$index];
            }
        }
        return false;
    }

    /**
     * ClearRules() clears the rules currently in the inflector
     *
     * @return Zend_Filter_Inflector
     */
    public function clearRules()
    {
        $this->_rules = array();
        return $this;
    }

    /**
     * Set a filtering rule for a spec.  $ruleSet can be a string, Filter object
     * or an array of strings or filter objects.
     *
     * @param string $spec
     * @param array|string|Zend_Filter_Interface $ruleSet
     * @return Zend_Filter_Inflector
     */
    public function setFilterRule($spec, $ruleSet)
    {
        $spec = $this->_normalizeSpec($spec);
        $this->_rules[$spec] = array();
        return $this->addFilterRule($spec, $ruleSet);
    }

    /**
     * Add a filter rule for a spec
     *
     * @param mixed $spec
     * @param mixed $ruleSet
     * @return void
     */
    public function addFilterRule($spec, $ruleSet)
    {
        $spec = $this->_normalizeSpec($spec);
        if (!isset($this->_rules[$spec])) {
            $this->_rules[$spec] = array();
        }

        if (!is_array($ruleSet)) {
            $ruleSet = array($ruleSet);
        }

        if (is_string($this->_rules[$spec])) {
            $temp = $this->_rules[$spec];
            $this->_rules[$spec] = array();
            $this->_rules[$spec][] = $temp;
        }

        foreach ($ruleSet as $rule) {
            $this->_rules[$spec][] = $this->_getRule($rule);
        }

        return $this;
    }

    /**
     * Set a static rule for a spec.  This is a single string value
     *
     * @param string $name
     * @param string $value
     * @return Zend_Filter_Inflector
     */
    public function setStaticRule($name, $value)
    {
        $name = $this->_normalizeSpec($name);
        $this->_rules[$name] = (string) $value;
        return $this;
    }

    /**
     * Set Static Rule Reference.
     *
     * This allows a consuming class to pass a property or variable
     * in to be referenced when its time to build the output string from the
     * target.
     *
     * @param string $name
     * @param mixed $reference
     * @return Zend_Filter_Inflector
     */
    public function setStaticRuleReference($name, &$reference)
    {
        $name = $this->_normalizeSpec($name);
        $this->_rules[$name] =& $reference;
        return $this;
    }

    /**
     * Inflect
     *
     * @param  string|array $source
     * @return string
     */
    public function filter($source)
    {
        // clean source
        foreach ( (array) $source as $sourceName => $sourceValue) {
            $source[ltrim($sourceName, ':')] = $sourceValue;
        }

        $pregQuotedTargetReplacementIdentifier = preg_quote($this->_targetReplacementIdentifier, '#');
        $processedParts = array();

        foreach ($this->_rules as $ruleName => $ruleValue) {
            if (isset($source[$ruleName])) {
                if (is_string($ruleValue)) {
                    // overriding the set rule
                    $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $source[$ruleName]);
                } elseif (is_array($ruleValue)) {
                    $processedPart = $source[$ruleName];
                    foreach ($ruleValue as $ruleFilter) {
                        $processedPart = $ruleFilter->filter($processedPart);
                    }
                    $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $processedPart);
                }
            } elseif (is_string($ruleValue)) {
                $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $ruleValue);
            }
        }

        // all of the values of processedParts would have been str_replace('\\', '\\\\', ..)'d to disable preg_replace backreferences
        $inflectedTarget = preg_replace(array_keys($processedParts), array_values($processedParts), $this->_target);

        if ($this->_throwTargetExceptionsOn && (preg_match('#(?='.$pregQuotedTargetReplacementIdentifier.'[A-Za-z]{1})#', $inflectedTarget) == true)) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception('A replacement identifier ' . $this->_targetReplacementIdentifier . ' was found inside the inflected target, perhaps a rule was not satisfied with a target source?  Unsatisfied inflected target: ' . $inflectedTarget);
        }

        return $inflectedTarget;
    }

    /**
     * Normalize spec string
     *
     * @param  string $spec
     * @return string
     */
    protected function _normalizeSpec($spec)
    {
        return ltrim((string) $spec, ':&');
    }

    /**
     * Resolve named filters and convert them to filter objects.
     *
     * @param  string $rule
     * @return Zend_Filter_Interface
     */
    protected function _getRule($rule)
    {
        if ($rule instanceof Zend_Filter_Interface) {
            return $rule;
        }

        $rule = (string) $rule;

        $className  = $this->getPluginLoader()->load($rule);
        $ruleObject = new $className();
        if (!$ruleObject instanceof Zend_Filter_Interface) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception('No class named ' . $rule . ' implementing Zend_Filter_Interface could be found');
        }

        return $ruleObject;
    }
}

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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Filter.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter implements Zend_Filter_Interface
{

    const CHAIN_APPEND  = 'append';
    const CHAIN_PREPEND = 'prepend';

    /**
     * Filter chain
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * Default Namespaces
     *
     * @var array
     */
    protected static $_defaultNamespaces = array();

    /**
     * Adds a filter to the chain
     *
     * @param  Zend_Filter_Interface $filter
     * @param  string $placement
     * @return Zend_Filter Provides a fluent interface
     */
    public function addFilter(Zend_Filter_Interface $filter, $placement = self::CHAIN_APPEND)
    {
        if ($placement == self::CHAIN_PREPEND) {
            array_unshift($this->_filters, $filter);
        } else {
            $this->_filters[] = $filter;
        }
        return $this;
    }

    /**
     * Add a filter to the end of the chain
     *
     * @param  Zend_Filter_Interface $filter
     * @return Zend_Filter Provides a fluent interface
     */
    public function appendFilter(Zend_Filter_Interface $filter)
    {
        return $this->addFilter($filter, self::CHAIN_APPEND);
    }

    /**
     * Add a filter to the start of the chain
     *
     * @param  Zend_Filter_Interface $filter
     * @return Zend_Filter Provides a fluent interface
     */
    public function prependFilter(Zend_Filter_Interface $filter)
    {
        return $this->addFilter($filter, self::CHAIN_PREPEND);
    }

    /**
     * Get all the filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * Returns $value filtered through each filter in the chain
     *
     * Filters are run in the order in which they were added to the chain (FIFO)
     *
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        $valueFiltered = $value;
        foreach ($this->_filters as $filter) {
            $valueFiltered = $filter->filter($valueFiltered);
        }
        return $valueFiltered;
    }

    /**
     * Returns the set default namespaces
     *
     * @return array
     */
    public static function getDefaultNamespaces()
    {
        return self::$_defaultNamespaces;
    }

    /**
     * Sets new default namespaces
     *
     * @param array|string $namespace
     * @return null
     */
    public static function setDefaultNamespaces($namespace)
    {
        if (!is_array($namespace)) {
            $namespace = array((string) $namespace);
        }

        self::$_defaultNamespaces = $namespace;
    }

    /**
     * Adds a new default namespace
     *
     * @param array|string $namespace
     * @return null
     */
    public static function addDefaultNamespaces($namespace)
    {
        if (!is_array($namespace)) {
            $namespace = array((string) $namespace);
        }

        self::$_defaultNamespaces = array_unique(array_merge(self::$_defaultNamespaces, $namespace));
    }

    /**
     * Returns true when defaultNamespaces are set
     *
     * @return boolean
     */
    public static function hasDefaultNamespaces()
    {
        return (!empty(self::$_defaultNamespaces));
    }

    /**
     * @deprecated
     * @see Zend_Filter::filterStatic()
     *
     * @param  mixed        $value
     * @param  string       $classBaseName
     * @param  array        $args          OPTIONAL
     * @param  array|string $namespaces    OPTIONAL
     * @return mixed
     * @throws Zend_Filter_Exception
     */
    public static function get($value, $classBaseName, array $args = array(), $namespaces = array())
    {
        trigger_error(
            'Zend_Filter::get() is deprecated as of 1.9.0; please update your code to utilize Zend_Filter::filterStatic()',
            E_USER_NOTICE
        );

        return self::filterStatic($value, $classBaseName, $args, $namespaces);
    }

    /**
     * Returns a value filtered through a specified filter class, without requiring separate
     * instantiation of the filter object.
     *
     * The first argument of this method is a data input value, that you would have filtered.
     * The second argument is a string, which corresponds to the basename of the filter class,
     * relative to the Zend_Filter namespace. This method automatically loads the class,
     * creates an instance, and applies the filter() method to the data input. You can also pass
     * an array of constructor arguments, if they are needed for the filter class.
     *
     * @param  mixed        $value
     * @param  string       $classBaseName
     * @param  array        $args          OPTIONAL
     * @param  array|string $namespaces    OPTIONAL
     * @return mixed
     * @throws Zend_Filter_Exception
     */
    public static function filterStatic($value, $classBaseName, array $args = array(), $namespaces = array())
    {
        require_once 'Zend/Loader.php';
        $namespaces = array_merge((array) $namespaces, self::$_defaultNamespaces, array('Zend_Filter'));
        foreach ($namespaces as $namespace) {
            $className = $namespace . '_' . ucfirst($classBaseName);
            if (!class_exists($className, false)) {
                try {
                    $file = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
                    if (Zend_Loader::isReadable($file)) {
                        Zend_Loader::loadClass($className);
                    } else {
                        continue;
                    }
                } catch (Zend_Exception $ze) {
                    continue;
                }
            }

            $class = new ReflectionClass($className);
            if ($class->implementsInterface('Zend_Filter_Interface')) {
                if ($class->hasMethod('__construct')) {
                    $object = $class->newInstanceArgs($args);
                } else {
                    $object = $class->newInstance();
                }
                return $object->filter($value);
            }
        }
        require_once 'Zend/Filter/Exception.php';
        throw new Zend_Filter_Exception("Filter class not found from basename '$classBaseName'");
    }
}


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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Interface.php 23775 2011-03-01 17:25:24Z ralph $
 */


/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Filter_Interface
{
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value);
}

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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: PregReplace.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_PregReplace implements Zend_Filter_Interface
{
    /**
     * Pattern to match
     * @var mixed
     */
    protected $_matchPattern = null;

    /**
     * Replacement pattern
     * @var mixed
     */
    protected $_replacement = '';

    /**
     * Is unicode enabled?
     *
     * @var bool
     */
    static protected $_unicodeSupportEnabled = null;

    /**
     * Is Unicode Support Enabled Utility function
     *
     * @return bool
     */
    static public function isUnicodeSupportEnabled()
    {
        if (self::$_unicodeSupportEnabled === null) {
            self::_determineUnicodeSupport();
        }

        return self::$_unicodeSupportEnabled;
    }

    /**
     * Method to cache the regex needed to determine if unicode support is available
     *
     * @return bool
     */
    static protected function _determineUnicodeSupport()
    {
        self::$_unicodeSupportEnabled = (@preg_match('/\pL/u', 'a')) ? true : false;
    }

    /**
     * Constructor
     * Supported options are
     *     'match'   => matching pattern
     *     'replace' => replace with this
     *
     * @param  string|array $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp    = array();
            if (!empty($options)) {
                $temp['match'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['replace'] = array_shift($options);
            }

            $options = $temp;
        }

        if (array_key_exists('match', $options)) {
            $this->setMatchPattern($options['match']);
        }

        if (array_key_exists('replace', $options)) {
            $this->setReplacement($options['replace']);
        }
    }

    /**
     * Set the match pattern for the regex being called within filter()
     *
     * @param mixed $match - same as the first argument of preg_replace
     * @return Zend_Filter_PregReplace
     */
    public function setMatchPattern($match)
    {
        $this->_matchPattern = $match;
        return $this;
    }

    /**
     * Get currently set match pattern
     *
     * @return string
     */
    public function getMatchPattern()
    {
        return $this->_matchPattern;
    }

    /**
     * Set the Replacement pattern/string for the preg_replace called in filter
     *
     * @param mixed $replacement - same as the second argument of preg_replace
     * @return Zend_Filter_PregReplace
     */
    public function setReplacement($replacement)
    {
        $this->_replacement = $replacement;
        return $this;
    }

    /**
     * Get currently set replacement value
     *
     * @return string
     */
    public function getReplacement()
    {
        return $this->_replacement;
    }

    /**
     * Perform regexp replacement as filter
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        if ($this->_matchPattern == null) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception(get_class($this) . ' does not have a valid MatchPattern set.');
        }

        return preg_replace($this->_matchPattern, $this->_replacement, $value);
    }

}

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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: UnderscoreToSeparator.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_PregReplace
 */
require_once 'Zend/Filter/Word/SeparatorToSeparator.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_Word_UnderscoreToSeparator extends Zend_Filter_Word_SeparatorToSeparator
{
    /**
     * Constructor
     *
     * @param  string $separator Space by default
     * @return void
     */
    public function __construct($replacementSeparator = ' ')
    {
        parent::__construct('_', $replacementSeparator);
    }
}

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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: SeparatorToSeparator.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_PregReplace
 */
require_once 'Zend/Filter/PregReplace.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_Word_SeparatorToSeparator extends Zend_Filter_PregReplace
{

    protected $_searchSeparator = null;
    protected $_replacementSeparator = null;

    /**
     * Constructor
     *
     * @param  string  $searchSeparator      Seperator to search for
     * @param  string  $replacementSeperator Seperator to replace with
     * @return void
     */
    public function __construct($searchSeparator = ' ', $replacementSeparator = '-')
    {
        $this->setSearchSeparator($searchSeparator);
        $this->setReplacementSeparator($replacementSeparator);
    }

    /**
     * Sets a new seperator to search for
     *
     * @param  string  $separator  Seperator to search for
     * @return $this
     */
    public function setSearchSeparator($separator)
    {
        $this->_searchSeparator = $separator;
        return $this;
    }

    /**
     * Returns the actual set seperator to search for
     *
     * @return  string
     */
    public function getSearchSeparator()
    {
        return $this->_searchSeparator;
    }

    /**
     * Sets a new seperator which replaces the searched one
     *
     * @param  string  $separator  Seperator which replaces the searched one
     * @return $this
     */
    public function setReplacementSeparator($separator)
    {
        $this->_replacementSeparator = $separator;
        return $this;
    }

    /**
     * Returns the actual set seperator which replaces the searched one
     *
     * @return  string
     */
    public function getReplacementSeparator()
    {
        return $this->_replacementSeparator;
    }

    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns the string $value, replacing the searched seperators with the defined ones
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        return $this->_separatorToSeparatorFilter($value);
    }

    /**
     * Do the real work, replaces the seperator to search for with the replacement seperator
     *
     * Returns the replaced string
     *
     * @param  string $value
     * @return string
     */
    protected function _separatorToSeparatorFilter($value)
    {
        if ($this->_searchSeparator == null) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception('You must provide a search separator for this filter to work.');
        }

        $this->setMatchPattern('#' . preg_quote($this->_searchSeparator, '#') . '#');
        $this->setReplacement($this->_replacementSeparator);
        return parent::filter($value);
    }

}
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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: CamelCaseToDash.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Word/CamelCaseToSeparator.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_Word_CamelCaseToDash extends Zend_Filter_Word_CamelCaseToSeparator
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('-');
    }
}

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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: CamelCaseToSeparator.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_PregReplace
 */
require_once 'Zend/Filter/Word/Separator/Abstract.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_Word_CamelCaseToSeparator extends Zend_Filter_Word_Separator_Abstract
{

    public function filter($value)
    {
        if (self::isUnicodeSupportEnabled()) {
            parent::setMatchPattern(array('#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#','#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'));
            parent::setReplacement(array($this->_separator . '\1', $this->_separator . '\1'));
        } else {
            parent::setMatchPattern(array('#(?<=(?:[A-Z]))([A-Z]+)([A-Z][A-z])#', '#(?<=(?:[a-z0-9]))([A-Z])#'));
            parent::setReplacement(array('\1' . $this->_separator . '\2', $this->_separator . '\1'));
        }

        return parent::filter($value);
    }

}

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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_PregReplace
 */
require_once 'Zend/Filter/PregReplace.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @uses       Zend_Filter_PregReplace
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Filter_Word_Separator_Abstract extends Zend_Filter_PregReplace
{

    protected $_separator = null;

    /**
     * Constructor
     *
     * @param  string $separator Space by default
     * @return void
     */
    public function __construct($separator = ' ')
    {
        $this->setSeparator($separator);
    }

    /**
     * Sets a new seperator
     *
     * @param  string  $separator  Seperator
     * @return $this
     */
    public function setSeparator($separator)
    {
        if ($separator == null) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception('"' . $separator . '" is not a valid separator.');
        }
        $this->_separator = $separator;
        return $this;
    }

    /**
     * Returns the actual set seperator
     *
     * @return  string
     */
    public function getSeparator()
    {
        return $this->_separator;
    }

}
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
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: StringToLower.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_StringToLower implements Zend_Filter_Interface
{
    /**
     * Encoding for the input string
     *
     * @var string
     */
    protected $_encoding = null;

    /**
     * Constructor
     *
     * @param string|array|Zend_Config $options OPTIONAL
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp    = array();
            if (!empty($options)) {
                $temp['encoding'] = array_shift($options);
            }
            $options = $temp;
        }

        if (!array_key_exists('encoding', $options) && function_exists('mb_internal_encoding')) {
            $options['encoding'] = mb_internal_encoding();
        }

        if (array_key_exists('encoding', $options)) {
            $this->setEncoding($options['encoding']);
        }
    }

    /**
     * Returns the set encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Set the input encoding for the given string
     *
     * @param  string $encoding
     * @return Zend_Filter_StringToLower Provides a fluent interface
     * @throws Zend_Filter_Exception
     */
    public function setEncoding($encoding = null)
    {
        if ($encoding !== null) {
            if (!function_exists('mb_strtolower')) {
                require_once 'Zend/Filter/Exception.php';
                throw new Zend_Filter_Exception('mbstring is required for this feature');
            }

            $encoding = (string) $encoding;
            if (!in_array(strtolower($encoding), array_map('strtolower', mb_list_encodings()))) {
                require_once 'Zend/Filter/Exception.php';
                throw new Zend_Filter_Exception("The given encoding '$encoding' is not supported by mbstring");
            }
        }

        $this->_encoding = $encoding;
        return $this;
    }

    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns the string $value, converting characters to lowercase as necessary
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        if ($this->_encoding !== null) {
            return mb_strtolower((string) $value, $this->_encoding);
        }

        return strtolower((string) $value);
    }
}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_View_Helper_Z_Statpage extends Zend_View_Helper_Abstract
{
	public function z_statpage($sid,$part='text')
	{
		$sp = new Z_Statpage($sid);
		if ($part=='title')
		{
			return $sp->getTitle();
		}
		else
		{
			return $sp;
		}
	}
}


/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Statpage
{

  /**
   *
   * @var Z_Db_Table
   */
  protected static $_model = NULL;
//  protected static $_rows = NULL;
  protected $_isError = false;

  /**
   * принимает первый аргумент - идентификатор статьи или ее id в таблице
   * @var Zend_Db_Table_Row
   */
  protected $_row = NULL;

  public function __construct($sid)
  {
    self::$_model = new Z_Model_Statpage();
    $cache = Z_Cache::getInstance();
    $cache_id = 'z_spatpage_'.str_replace(array(DIRECTORY_SEPARATOR,'.','-',':','/','\\'),'_',$sid);
    if (!$this->_row = $cache->load($cache_id))
    {
      $this->_row = $this->_getRow($sid);
      $cache->save($this->_row,$cache_id);
    }
  }

  /**
   * @return string
   */
  public function getText()
  {
    return $this->_row->text;
  }

  /**
   * @return string
   */
  public function getTitle()
  {
    return $this->_row->title;
  }

  /**
   * @return string
   */
  public function get($field,$default=NULL)
  {
    $data = $this->_row->toArray();
    if (array_key_exists($field,$data))
      return $data[$field];
    else
      return $default;
  }

  /**
   * @return Zend_Db_Table_Row
   */
  protected function _getRow($sid)
  {
    $row = self::$_model->fetchRow(array('sid=?'=>$sid));
    if (!$row && is_numeric($sid))
    {
      $row = self::$_model->fetchRow(array('id=?'=>$sid));
    }
    if (!$row)
    {
      $this->_isError = true;
      $row = $this->_getErrorRow();
    }
    return $row;
  }


  /**
   * @return Zend_Db_Table_Row
   */
  protected function _getErrorRow()
  {
    $row = self::$_model->fetchRow(array('sid=?'=>'error'));
    if (!$row)
    {
      $configError = new Z_Config('error_text');
      $errtext = $configError->getValue();
      $row = self::$_model->createRow(array(
	      'sid'		=>	'error',
	      'title'		=>	'Ошибка',
	      'text'		=>	$errtext?$errtext:'Страница не найдена'
      ));
    }
    return $row;
  }

  public function isError()
  {
    return $this->_isError;
  }

  public function __toString()
  {
    return $this->getText();
  }

  public static function create($sid,$title,$content,$options = array())
  {
    $model = new Z_Model_Statpage();
    if ($model->fetchRow(array('sid=?'=>$sid))) throw new Exception('Страница с идентификатором "'.$sid.'" уже существует.');
    $model->createRow(array_merge($options,array(
      'sid'   =>  $sid,
      'title' =>  $title,
      'text'  =>  $content
    )));
  }

}


/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

/**
 * News
 *  
 * @author cramen
 * @version 
 */

require_once 'Z/Db/Table.php';

class Z_Model_Statpage extends Z_Db_Table {
	/**
	 * The default table name 
	 */
	protected $_name = 'z_statpages';

	
	public function ZGetLinks($count=0)
	{
		$select = $this->select()
			->from($this,array('CONCAT("/",sid)','title'))
			->order('title');
		if ($count) $select->limit($count);
		$result = $this->getAdapter()->fetchPairs($select);

        if (array_key_exists('/index',$result))
        {
			$result = array_reverse($result);
			$result['/'] = $result['/index'];
			unset($result['/index']);
			$result = array_reverse($result);
		}
						
		return $result;
	}
}


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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Action.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * Helper for rendering output of a controller action
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Action extends Zend_View_Helper_Abstract
{
    /**
     * @var string
     */
    public $defaultModule;

    /**
     * @var Zend_Controller_Dispatcher_Interface
     */
    public $dispatcher;

    /**
     * @var Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     * @var Zend_Controller_Response_Abstract
     */
    public $response;

    /**
     * Constructor
     *
     * Grab local copies of various MVC objects
     *
     * @return void
     */
    public function __construct()
    {
        $front   = Zend_Controller_Front::getInstance();
        $modules = $front->getControllerDirectory();
        if (empty($modules)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Action helper depends on valid front controller instance');
            $e->setView($this->view);
            throw $e;
        }

        $request  = $front->getRequest();
        $response = $front->getResponse();

        if (empty($request) || empty($response)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Action view helper requires both a registered request and response object in the front controller instance');
            $e->setView($this->view);
            throw $e;
        }

        $this->request       = clone $request;
        $this->response      = clone $response;
        $this->dispatcher    = clone $front->getDispatcher();
        $this->defaultModule = $front->getDefaultModule();
    }

    /**
     * Reset object states
     *
     * @return void
     */
    public function resetObjects()
    {
        $params = $this->request->getUserParams();
        foreach (array_keys($params) as $key) {
            $this->request->setParam($key, null);
        }

        $this->response->clearBody();
        $this->response->clearHeaders()
                       ->clearRawHeaders();
    }

    /**
     * Retrieve rendered contents of a controller action
     *
     * If the action results in a forward or redirect, returns empty string.
     *
     * @param  string $action
     * @param  string $controller
     * @param  string $module Defaults to default module
     * @param  array $params
     * @return string
     */
    public function action($action, $controller, $module = null, array $params = array())
    {
        $this->resetObjects();
        if (null === $module) {
            $module = $this->defaultModule;
        }

        // clone the view object to prevent over-writing of view variables
        $viewRendererObj = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        Zend_Controller_Action_HelperBroker::addHelper(clone $viewRendererObj);

        $this->request->setParams($params)
                      ->setModuleName($module)
                      ->setControllerName($controller)
                      ->setActionName($action)
                      ->setDispatched(true);

        $this->dispatcher->dispatch($this->request, $this->response);

        // reset the viewRenderer object to it's original state
        Zend_Controller_Action_HelperBroker::addHelper($viewRendererObj);


        if (!$this->request->isDispatched()
            || $this->response->isRedirect())
        {
            // forwards and redirects render nothing
            return '';
        }

        $return = $this->response->getBody();
        $this->resetObjects();
        return $return;
    }

    /**
     * Clone the current View
     *
     * @return Zend_View_Interface
     */
    public function cloneView()
    {
        $view = clone $this->view;
        $view->clearVars();
        return $view;
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Placeholder.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Registry */
require_once 'Zend/View/Helper/Placeholder/Registry.php';

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * Helper for passing data between otherwise segregated Views. It's called
 * Placeholder to make its typical usage obvious, but can be used just as easily
 * for non-Placeholder things. That said, the support for this is only
 * guaranteed to effect subsequently rendered templates, and of course Layouts.
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Placeholder extends Zend_View_Helper_Abstract
{
    /**
     * Placeholder items
     * @var array
     */
    protected $_items = array();

    /**
     * @var Zend_View_Helper_Placeholder_Registry
     */
    protected $_registry;

    /**
     * Constructor
     *
     * Retrieve container registry from Zend_Registry, or create new one and register it.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_registry = Zend_View_Helper_Placeholder_Registry::getRegistry();
    }


    /**
     * Placeholder helper
     *
     * @param  string $name
     * @return Zend_View_Helper_Placeholder_Container_Abstract
     */
    public function placeholder($name)
    {
        $name = (string) $name;
        return $this->_registry->getContainer($name);
    }

    /**
     * Retrieve the registry
     *
     * @return Zend_View_Helper_Placeholder_Registry
     */
    public function getRegistry()
    {
        return $this->_registry;
    }
}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Filter_Template implements Zend_Filter_Interface
{
	/**
	 * 
	 * @var Zend_View
	 */
	protected static $view = NULL;
	
    public function filter($value)
    {
		if (NULL === self::$view)    	
    		self::$view = Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer')->view;
    	
        return $this->parse($value);
    }

	protected function parse($template)
	{
		if (Zend_Controller_Front::getInstance()->getRequest()->getModuleName()=='admin') return $template;
		
		preg_match_all('/\{\{(.*?)\}\}/si', $template, $res);
		if (@$res[1]) {
			foreach ($res[1] as $el)
			{
				if (strpos($el, 'action:')===0)
					$template = $this->parseAction($template, $el);
				
				if (strpos($el, 'config:')===0)
					$template = $this->parseConfig($template, $el);
					
			}
		}
		return $template;
	}
    
	
	protected function parseAction($template,$actionstring)
	{
		$actionArray = explode(':', str_replace('action:', '', $actionstring));
		$params = array();
		isset($actionArray[1])?parse_str($actionArray[1],$params):NULL;
		$acmArray = explode('.', $actionArray[0]);
		try {
			$result = self::$view->action(isset($acmArray[0])?$acmArray[0]:NULL,isset($acmArray[1])?$acmArray[1]:NULL,isset($acmArray[2])?$acmArray[2]:NULL,$params);
		}
		catch (Exception $e)
		{
			if (APPLICATION_ENV == 'development')
				$result = $e->getMessage();
			else
				$result = $actionstring;
		}
		return str_ireplace('{{'.$actionstring.'}}', $result, $template);
	}

	protected function parseConfig($template,$actionstring)
	{
		$id = str_replace('config:', '', $actionstring);
		$result = new Z_Config($id);
		return str_ireplace('{{'.$actionstring.'}}', $result, $template);
	}
	
	
}
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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: HeadTitle.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Helper for setting and retrieving title element for HTML head
 *
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_HeadTitle extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'Zend_View_Helper_HeadTitle';

    /**
     * Whether or not auto-translation is enabled
     * @var boolean
     */
    protected $_translate = false;

    /**
     * Translation object
     *
     * @var Zend_Translate_Adapter
     */
    protected $_translator;

    /**
     * Default title rendering order (i.e. order in which each title attached)
     *
     * @var string
     */
    protected $_defaultAttachOrder = null;

    /**
     * Retrieve placeholder for title element and optionally set state
     *
     * @param  string $title
     * @param  string $setType
     * @return Zend_View_Helper_HeadTitle
     */
    public function headTitle($title = null, $setType = null)
    {
        if (null === $setType) {
            $setType = (null === $this->getDefaultAttachOrder())
                     ? Zend_View_Helper_Placeholder_Container_Abstract::APPEND
                     : $this->getDefaultAttachOrder();
        }
        $title = (string) $title;
        if ($title !== '') {
            if ($setType == Zend_View_Helper_Placeholder_Container_Abstract::SET) {
                $this->set($title);
            } elseif ($setType == Zend_View_Helper_Placeholder_Container_Abstract::PREPEND) {
                $this->prepend($title);
            } else {
                $this->append($title);
            }
        }

        return $this;
    }

    /**
     * Set a default order to add titles
     *
     * @param string $setType
     */
    public function setDefaultAttachOrder($setType)
    {
        if (!in_array($setType, array(
            Zend_View_Helper_Placeholder_Container_Abstract::APPEND,
            Zend_View_Helper_Placeholder_Container_Abstract::SET,
            Zend_View_Helper_Placeholder_Container_Abstract::PREPEND
        ))) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception("You must use a valid attach order: 'PREPEND', 'APPEND' or 'SET'");
        }

        $this->_defaultAttachOrder = $setType;
        return $this;
    }

    /**
     * Get the default attach order, if any.
     *
     * @return mixed
     */
    public function getDefaultAttachOrder()
    {
        return $this->_defaultAttachOrder;
    }

    /**
     * Sets a translation Adapter for translation
     *
     * @param  Zend_Translate|Zend_Translate_Adapter $translate
     * @return Zend_View_Helper_HeadTitle
     */
    public function setTranslator($translate)
    {
        if ($translate instanceof Zend_Translate_Adapter) {
            $this->_translator = $translate;
        } elseif ($translate instanceof Zend_Translate) {
            $this->_translator = $translate->getAdapter();
        } else {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception("You must set an instance of Zend_Translate or Zend_Translate_Adapter");
            $e->setView($this->view);
            throw $e;
        }
        return $this;
    }

    /**
     * Retrieve translation object
     *
     * If none is currently registered, attempts to pull it from the registry
     * using the key 'Zend_Translate'.
     *
     * @return Zend_Translate_Adapter|null
     */
    public function getTranslator()
    {
        if (null === $this->_translator) {
            require_once 'Zend/Registry.php';
            if (Zend_Registry::isRegistered('Zend_Translate')) {
                $this->setTranslator(Zend_Registry::get('Zend_Translate'));
            }
        }
        return $this->_translator;
    }

    /**
     * Enables translation
     *
     * @return Zend_View_Helper_HeadTitle
     */
    public function enableTranslation()
    {
        $this->_translate = true;
        return $this;
    }

    /**
     * Disables translation
     *
     * @return Zend_View_Helper_HeadTitle
     */
    public function disableTranslation()
    {
        $this->_translate = false;
        return $this;
    }

    /**
     * Turn helper into string
     *
     * @param  string|null $indent
     * @param  string|null $locale
     * @return string
     */
    public function toString($indent = null, $locale = null)
    {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        $items = array();

        if($this->_translate && $translator = $this->getTranslator()) {
            foreach ($this as $item) {
                $items[] = $translator->translate($item, $locale);
            }
        } else {
            foreach ($this as $item) {
                $items[] = $item;
            }
        }

        $separator = $this->getSeparator();
        $output = '';
        if(($prefix = $this->getPrefix())) {
            $output  .= $prefix;
        }
        $output .= implode($separator, $items);
        if(($postfix = $this->getPostfix())) {
            $output .= $postfix;
        }

        $output = ($this->_autoEscape) ? $this->_escape($output) : $output;

        return $indent . '<title>' . $output . '</title>';
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: HeadLink.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Zend_Layout_View_Helper_HeadLink
 *
 * @see        http://www.w3.org/TR/xhtml1/dtds.html
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_HeadLink extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * $_validAttributes
     *
     * @var array
     */
    protected $_itemKeys = array('charset', 'href', 'hreflang', 'id', 'media', 'rel', 'rev', 'type', 'title', 'extras');

    /**
     * @var string registry key
     */
    protected $_regKey = 'Zend_View_Helper_HeadLink';

    /**
     * Constructor
     *
     * Use PHP_EOL as separator
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSeparator(PHP_EOL);
    }

    /**
     * headLink() - View Helper Method
     *
     * Returns current object instance. Optionally, allows passing array of
     * values to build link.
     *
     * @return Zend_View_Helper_HeadLink
     */
    public function headLink(array $attributes = null, $placement = Zend_View_Helper_Placeholder_Container_Abstract::APPEND)
    {
        if (null !== $attributes) {
            $item = $this->createData($attributes);
            switch ($placement) {
                case Zend_View_Helper_Placeholder_Container_Abstract::SET:
                    $this->set($item);
                    break;
                case Zend_View_Helper_Placeholder_Container_Abstract::PREPEND:
                    $this->prepend($item);
                    break;
                case Zend_View_Helper_Placeholder_Container_Abstract::APPEND:
                default:
                    $this->append($item);
                    break;
            }
        }
        return $this;
    }

    /**
     * Overload method access
     *
     * Creates the following virtual methods:
     * - appendStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - offsetSetStylesheet($index, $href, $media, $conditionalStylesheet, $extras)
     * - prependStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - setStylesheet($href, $media, $conditionalStylesheet, $extras)
     * - appendAlternate($href, $type, $title, $extras)
     * - offsetSetAlternate($index, $href, $type, $title, $extras)
     * - prependAlternate($href, $type, $title, $extras)
     * - setAlternate($href, $type, $title, $extras)
     *
     * Items that may be added in the future:
     * - Navigation?  need to find docs on this
     *   - public function appendStart()
     *   - public function appendContents()
     *   - public function appendPrev()
     *   - public function appendNext()
     *   - public function appendIndex()
     *   - public function appendEnd()
     *   - public function appendGlossary()
     *   - public function appendAppendix()
     *   - public function appendHelp()
     *   - public function appendBookmark()
     * - Other?
     *   - public function appendCopyright()
     *   - public function appendChapter()
     *   - public function appendSection()
     *   - public function appendSubsection()
     *
     * @param mixed $method
     * @param mixed $args
     * @return void
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(?P<type>Stylesheet|Alternate)$/', $method, $matches)) {
            $argc   = count($args);
            $action = $matches['action'];
            $type   = $matches['type'];
            $index  = null;

            if ('offsetSet' == $action) {
                if (0 < $argc) {
                    $index = array_shift($args);
                    --$argc;
                }
            }

            if (1 > $argc) {
                require_once 'Zend/View/Exception.php';
                $e =  new Zend_View_Exception(sprintf('%s requires at least one argument', $method));
                $e->setView($this->view);
                throw $e;
            }

            if (is_array($args[0])) {
                $item = $this->createData($args[0]);
            } else {
                $dataMethod = 'createData' . $type;
                $item       = $this->$dataMethod($args);
            }

            if ($item) {
                if ('offsetSet' == $action) {
                    $this->offsetSet($index, $item);
                } else {
                    $this->$action($item);
                }
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Check if value is valid
     *
     * @param  mixed $value
     * @return boolean
     */
    protected function _isValid($value)
    {
        if (!$value instanceof stdClass) {
            return false;
        }

        $vars         = get_object_vars($value);
        $keys         = array_keys($vars);
        $intersection = array_intersect($this->_itemKeys, $keys);
        if (empty($intersection)) {
            return false;
        }

        return true;
    }

    /**
     * append()
     *
     * @param  array $value
     * @return void
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('append() expects a data token; please use one of the custom append*() methods');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->append($value);
    }

    /**
     * offsetSet()
     *
     * @param  string|int $index
     * @param  array $value
     * @return void
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('offsetSet() expects a data token; please use one of the custom offsetSet*() methods');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->offsetSet($index, $value);
    }

    /**
     * prepend()
     *
     * @param  array $value
     * @return Zend_Layout_ViewHelper_HeadLink
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('prepend() expects a data token; please use one of the custom prepend*() methods');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->prepend($value);
    }

    /**
     * set()
     *
     * @param  array $value
     * @return Zend_Layout_ViewHelper_HeadLink
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('set() expects a data token; please use one of the custom set*() methods');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->set($value);
    }


    /**
     * Create HTML link element from data item
     *
     * @param  stdClass $item
     * @return string
     */
    public function itemToString(stdClass $item)
    {
        $attributes = (array) $item;
        $link       = '<link ';

        foreach ($this->_itemKeys as $itemKey) {
            if (isset($attributes[$itemKey])) {
                if(is_array($attributes[$itemKey])) {
                    foreach($attributes[$itemKey] as $key => $value) {
                        $link .= sprintf('%s="%s" ', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
                    }
                } else {
                    $link .= sprintf('%s="%s" ', $itemKey, ($this->_autoEscape) ? $this->_escape($attributes[$itemKey]) : $attributes[$itemKey]);
                }
            }
        }

        if ($this->view instanceof Zend_View_Abstract) {
            $link .= ($this->view->doctype()->isXhtml()) ? '/>' : '>';
        } else {
            $link .= '/>';
        }

        if (($link == '<link />') || ($link == '<link >')) {
            return '';
        }

        if (isset($attributes['conditionalStylesheet'])
            && !empty($attributes['conditionalStylesheet'])
            && is_string($attributes['conditionalStylesheet']))
        {
            $link = '<!--[if ' . $attributes['conditionalStylesheet'] . ']> ' . $link . '<![endif]-->';
        }

        return $link;
    }

    /**
     * Render link elements as string
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        $items = array();
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            $items[] = $this->itemToString($item);
        }

        return $indent . implode($this->_escape($this->getSeparator()) . $indent, $items);
    }

    /**
     * Create data item for stack
     *
     * @param  array $attributes
     * @return stdClass
     */
    public function createData(array $attributes)
    {
        $data = (object) $attributes;
        return $data;
    }

    /**
     * Create item for stylesheet link item
     *
     * @param  array $args
     * @return stdClass|false Returns fals if stylesheet is a duplicate
     */
    public function createDataStylesheet(array $args)
    {
        $rel                   = 'stylesheet';
        $type                  = 'text/css';
        $media                 = 'screen';
        $conditionalStylesheet = false;
        $href                  = array_shift($args);

        if ($this->_isDuplicateStylesheet($href)) {
            return false;
        }

        if (0 < count($args)) {
            $media = array_shift($args);
            if(is_array($media)) {
                $media = implode(',', $media);
            } else {
                $media = (string) $media;
            }
        }
        if (0 < count($args)) {
            $conditionalStylesheet = array_shift($args);
            if(!empty($conditionalStylesheet) && is_string($conditionalStylesheet)) {
                $conditionalStylesheet = (string) $conditionalStylesheet;
            } else {
                $conditionalStylesheet = null;
            }
        }

        if(0 < count($args) && is_array($args[0])) {
            $extras = array_shift($args);
            $extras = (array) $extras;
        }

        $attributes = compact('rel', 'type', 'href', 'media', 'conditionalStylesheet', 'extras');
        return $this->createData($attributes);
    }

    /**
     * Is the linked stylesheet a duplicate?
     *
     * @param  string $uri
     * @return bool
     */
    protected function _isDuplicateStylesheet($uri)
    {
        foreach ($this->getContainer() as $item) {
            if (($item->rel == 'stylesheet') && ($item->href == $uri)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create item for alternate link item
     *
     * @param  array $args
     * @return stdClass
     */
    public function createDataAlternate(array $args)
    {
        if (3 > count($args)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception(sprintf('Alternate tags require 3 arguments; %s provided', count($args)));
            $e->setView($this->view);
            throw $e;
        }

        $rel   = 'alternate';
        $href  = array_shift($args);
        $type  = array_shift($args);
        $title = array_shift($args);

        if(0 < count($args) && is_array($args[0])) {
            $extras = array_shift($args);
            $extras = (array) $extras;

            if(isset($extras['media']) && is_array($extras['media'])) {
                $extras['media'] = implode(',', $extras['media']);
            }
        }

        $href  = (string) $href;
        $type  = (string) $type;
        $title = (string) $title;

        $attributes = compact('rel', 'href', 'type', 'title', 'extras');
        return $this->createData($attributes);
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: HeadScript.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Helper for setting and retrieving script elements for HTML head section
 *
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_HeadScript extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**#@+
     * Script type contants
     * @const string
     */
    const FILE   = 'FILE';
    const SCRIPT = 'SCRIPT';
    /**#@-*/

    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'Zend_View_Helper_HeadScript';

    /**
     * Are arbitrary attributes allowed?
     * @var bool
     */
    protected $_arbitraryAttributes = false;

    /**#@+
     * Capture type and/or attributes (used for hinting during capture)
     * @var string
     */
    protected $_captureLock;
    protected $_captureScriptType  = null;
    protected $_captureScriptAttrs = null;
    protected $_captureType;
    /**#@-*/

    /**
     * Optional allowed attributes for script tag
     * @var array
     */
    protected $_optionalAttributes = array(
        'charset', 'defer', 'language', 'src'
    );

    /**
     * Required attributes for script tag
     * @var string
     */
    protected $_requiredAttributes = array('type');

    /**
     * Whether or not to format scripts using CDATA; used only if doctype
     * helper is not accessible
     * @var bool
     */
    public $useCdata = false;

    /**
     * Constructor
     *
     * Set separator to PHP_EOL.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSeparator(PHP_EOL);
    }

    /**
     * Return headScript object
     *
     * Returns headScript helper object; optionally, allows specifying a script
     * or script file to include.
     *
     * @param  string $mode Script or file
     * @param  string $spec Script/url
     * @param  string $placement Append, prepend, or set
     * @param  array $attrs Array of script attributes
     * @param  string $type Script type and/or array of script attributes
     * @return Zend_View_Helper_HeadScript
     */
    public function headScript($mode = Zend_View_Helper_HeadScript::FILE, $spec = null, $placement = 'APPEND', array $attrs = array(), $type = 'text/javascript')
    {
        if ((null !== $spec) && is_string($spec)) {
            $action    = ucfirst(strtolower($mode));
            $placement = strtolower($placement);
            switch ($placement) {
                case 'set':
                case 'prepend':
                case 'append':
                    $action = $placement . $action;
                    break;
                default:
                    $action = 'append' . $action;
                    break;
            }
            $this->$action($spec, $type, $attrs);
        }

        return $this;
    }

    /**
     * Start capture action
     *
     * @param  mixed $captureType
     * @param  string $typeOrAttrs
     * @return void
     */
    public function captureStart($captureType = Zend_View_Helper_Placeholder_Container_Abstract::APPEND, $type = 'text/javascript', $attrs = array())
    {
        if ($this->_captureLock) {
            require_once 'Zend/View/Helper/Placeholder/Container/Exception.php';
            $e = new Zend_View_Helper_Placeholder_Container_Exception('Cannot nest headScript captures');
            $e->setView($this->view);
            throw $e;
        }

        $this->_captureLock        = true;
        $this->_captureType        = $captureType;
        $this->_captureScriptType  = $type;
        $this->_captureScriptAttrs = $attrs;
        ob_start();
    }

    /**
     * End capture action and store
     *
     * @return void
     */
    public function captureEnd()
    {
        $content                   = ob_get_clean();
        $type                      = $this->_captureScriptType;
        $attrs                     = $this->_captureScriptAttrs;
        $this->_captureScriptType  = null;
        $this->_captureScriptAttrs = null;
        $this->_captureLock        = false;

        switch ($this->_captureType) {
            case Zend_View_Helper_Placeholder_Container_Abstract::SET:
            case Zend_View_Helper_Placeholder_Container_Abstract::PREPEND:
            case Zend_View_Helper_Placeholder_Container_Abstract::APPEND:
                $action = strtolower($this->_captureType) . 'Script';
                break;
            default:
                $action = 'appendScript';
                break;
        }
        $this->$action($content, $type, $attrs);
    }

    /**
     * Overload method access
     *
     * Allows the following method calls:
     * - appendFile($src, $type = 'text/javascript', $attrs = array())
     * - offsetSetFile($index, $src, $type = 'text/javascript', $attrs = array())
     * - prependFile($src, $type = 'text/javascript', $attrs = array())
     * - setFile($src, $type = 'text/javascript', $attrs = array())
     * - appendScript($script, $type = 'text/javascript', $attrs = array())
     * - offsetSetScript($index, $src, $type = 'text/javascript', $attrs = array())
     * - prependScript($script, $type = 'text/javascript', $attrs = array())
     * - setScript($script, $type = 'text/javascript', $attrs = array())
     *
     * @param  string $method
     * @param  array $args
     * @return Zend_View_Helper_HeadScript
     * @throws Zend_View_Exception if too few arguments or invalid method
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(?P<mode>File|Script)$/', $method, $matches)) {
            if (1 > count($args)) {
                require_once 'Zend/View/Exception.php';
                $e = new Zend_View_Exception(sprintf('Method "%s" requires at least one argument', $method));
                $e->setView($this->view);
                throw $e;
            }

            $action  = $matches['action'];
            $mode    = strtolower($matches['mode']);
            $type    = 'text/javascript';
            $attrs   = array();

            if ('offsetSet' == $action) {
                $index = array_shift($args);
                if (1 > count($args)) {
                    require_once 'Zend/View/Exception.php';
                    $e = new Zend_View_Exception(sprintf('Method "%s" requires at least two arguments, an index and source', $method));
                    $e->setView($this->view);
                    throw $e;
                }
            }

            $content = $args[0];

            if (isset($args[1])) {
                $type = (string) $args[1];
            }
            if (isset($args[2])) {
                $attrs = (array) $args[2];
            }

            switch ($mode) {
                case 'script':
                    $item = $this->createData($type, $attrs, $content);
                    if ('offsetSet' == $action) {
                        $this->offsetSet($index, $item);
                    } else {
                        $this->$action($item);
                    }
                    break;
                case 'file':
                default:
                    if (!$this->_isDuplicate($content)) {
                        $attrs['src'] = $content;
                        $item = $this->createData($type, $attrs);
                        if ('offsetSet' == $action) {
                            $this->offsetSet($index, $item);
                        } else {
                            $this->$action($item);
                        }
                    }
                    break;
            }

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Is the file specified a duplicate?
     *
     * @param  string $file
     * @return bool
     */
    protected function _isDuplicate($file)
    {
        foreach ($this->getContainer() as $item) {
            if (($item->source === null)
                && array_key_exists('src', $item->attributes)
                && ($file == $item->attributes['src']))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Is the script provided valid?
     *
     * @param  mixed $value
     * @param  string $method
     * @return bool
     */
    protected function _isValid($value)
    {
        if ((!$value instanceof stdClass)
            || !isset($value->type)
            || (!isset($value->source) && !isset($value->attributes)))
        {
            return false;
        }

        return true;
    }

    /**
     * Override append
     *
     * @param  string $value
     * @return void
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid argument passed to append(); please use one of the helper methods, appendScript() or appendFile()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->append($value);
    }

    /**
     * Override prepend
     *
     * @param  string $value
     * @return void
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid argument passed to prepend(); please use one of the helper methods, prependScript() or prependFile()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->prepend($value);
    }

    /**
     * Override set
     *
     * @param  string $value
     * @return void
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid argument passed to set(); please use one of the helper methods, setScript() or setFile()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->set($value);
    }

    /**
     * Override offsetSet
     *
     * @param  string|int $index
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception('Invalid argument passed to offsetSet(); please use one of the helper methods, offsetSetScript() or offsetSetFile()');
            $e->setView($this->view);
            throw $e;
        }

        return $this->getContainer()->offsetSet($index, $value);
    }

    /**
     * Set flag indicating if arbitrary attributes are allowed
     *
     * @param  bool $flag
     * @return Zend_View_Helper_HeadScript
     */
    public function setAllowArbitraryAttributes($flag)
    {
        $this->_arbitraryAttributes = (bool) $flag;
        return $this;
    }

    /**
     * Are arbitrary attributes allowed?
     *
     * @return bool
     */
    public function arbitraryAttributesAllowed()
    {
        return $this->_arbitraryAttributes;
    }

    /**
     * Create script HTML
     *
     * @param  string $type
     * @param  array $attributes
     * @param  string $content
     * @param  string|int $indent
     * @return string
     */
    public function itemToString($item, $indent, $escapeStart, $escapeEnd)
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if (!$this->arbitraryAttributesAllowed()
                    && !in_array($key, $this->_optionalAttributes))
                {
                    continue;
                }
                if ('defer' == $key) {
                    $value = 'defer';
                }
                $attrString .= sprintf(' %s="%s"', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
            }
        }

        $type = ($this->_autoEscape) ? $this->_escape($item->type) : $item->type;
        $html  = '<script type="' . $type . '"' . $attrString . '>';
        if (!empty($item->source)) {
              $html .= PHP_EOL . $indent . '    ' . $escapeStart . PHP_EOL . $item->source . $indent . '    ' . $escapeEnd . PHP_EOL . $indent;
        }
        $html .= '</script>';

        if (isset($item->attributes['conditional'])
            && !empty($item->attributes['conditional'])
            && is_string($item->attributes['conditional']))
        {
            $html = $indent . '<!--[if ' . $item->attributes['conditional'] . ']> ' . $html . '<![endif]-->';
        } else {
            $html = $indent . $html;
        }

        return $html;
    }

    /**
     * Retrieve string representation
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        if ($this->view) {
            $useCdata = $this->view->doctype()->isXhtml() ? true : false;
        } else {
            $useCdata = $this->useCdata ? true : false;
        }
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>'       : '//-->';

        $items = array();
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            $items[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
        }

        $return = implode($this->getSeparator(), $items);
        return $return;
    }

    /**
     * Create data item containing all necessary components of script
     *
     * @param  string $type
     * @param  array $attributes
     * @param  string $content
     * @return stdClass
     */
    public function createData($type, array $attributes, $content = null)
    {
        $data             = new stdClass();
        $data->type       = $type;
        $data->attributes = $attributes;
        $data->source     = $content;
        return $data;
    }
}

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
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Layout.php 23775 2011-03-01 17:25:24Z ralph $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Abstract.php */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * View helper for retrieving layout object
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Layout extends Zend_View_Helper_Abstract
{
    /** @var Zend_Layout */
    protected $_layout;

    /**
     * Get layout object
     *
     * @return Zend_Layout
     */
    public function getLayout()
    {
        if (null === $this->_layout) {
            require_once 'Zend/Layout.php';
            $this->_layout = Zend_Layout::getMvcInstance();
            if (null === $this->_layout) {
                // Implicitly creates layout object
                $this->_layout = new Zend_Layout();
            }
        }

        return $this->_layout;
    }

    /**
     * Set layout object
     *
     * @param  Zend_Layout $layout
     * @return Zend_Layout_Controller_Action_Helper_Layout
     */
    public function setLayout(Zend_Layout $layout)
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * Return layout object
     *
     * Usage: $this->layout()->setLayout('alternate');
     *
     * @return Zend_Layout
     */
    public function layout()
    {
        return $this->getLayout();
    }
}

/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_View_Helper_Z_Config extends Zend_View_Helper_Abstract
{
	public function z_config($sid)
	{
		return new Z_Config($sid);
	}
}


/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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

class Z_Config {

	/**
	 * 
	 * @var Z_Db_Table
	 */
	protected static $_model = NULL;
	
	/**
	 * 
	 * @var Zend_Db_Table_Row
	 */
	protected $_row = NULL;
	
	public function __construct($sid)
	{
		$this->_row = $this->_getRow($sid);
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		if (is_object($this->_row))
			return $this->_row->value;
		else
			return '';
	}
	
	/**
	 * @return Zend_Db_Table_Row
	 */
	protected function _getRow($sid)
	{
		if (NULL === self::$_model)
			self::$_model = new Z_Model_Config_Tree();
		$row = self::$_model->fetchRow(array('sid=?'=>$sid));
		if (!$row)
		{
			return false;
		}
		return $row;
	}
	
	public function __toString()
	{
		return $this->getValue();
	}
	
}


/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Лицензия
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
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
class Z_Model_Config_Tree extends Z_Db_Table
{

    protected $_name = 'z_config_tree';

}
