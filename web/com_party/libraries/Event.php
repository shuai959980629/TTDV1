<?php
/**
 * event/plugin system class
 *
 */
class Event
{
	/**
	 * @var 包含所有事件回调配置，key => 事件名，value => 回调代码
	 */
    private static $events = array();

    public function __construct()
    {

    }

	/**
	 * 触发事件，执行回调函数.
	 *
	 * @param string 事件名称.
	 * @param mixed 回调参数
	 * @return true
	 */
	public static function trigger($event, $data = null)
	{
		if (!is_array($data)) {
			$data = array($data);
		}
		
		if (empty(self::$events[$event])) {
			return true;
        }
        foreach (self::$events[$event] as $callback) {
            if (is_callable($callback)) {
			    call_user_func_array($callback, $data);
            }
            elseif (is_string($callback)) {
                if(!class_exists($callback)) {
                    log_message('error', "Could not find handler class {$callback}.");
                }
                elseif(!method_exists($callback, 'work')) {
                    log_message('error', "Handler class {$callback} does not contain a work method.");
                }
                else {
                    $_instance = new $callback;
                    $_instance->work($data);
                }
            }
            else {
                log_message('error', "Invalid handler fun {$callback}.");
            }
		}

		return true;
	}

	/**
	 * 注册事件
	 *
	 * @param string 事件名称
	 * @param mixed 回调函数
	 * @return true
	 */
	public static function listen($event, $callback)
	{
		if (!isset(self::$events[$event])) {
			self::$events[$event] = array();
		}

		self::$events[$event][] = $callback;
		return true;
	}

	/**
	 * 移除指定的事件回调
	 *
	 * @param string 事件名称
	 * @param mixed 回调函数
	 * @return true
	 */
	public static function stopListening($event, $callback)
	{
		if (!isset(self::$events[$event])) {
			return true;
		}

		$key = array_search($callback, self::$events[$event]);
		if ($key !== false) {
			unset(self::$events[$event][$key]);
		}

		return true;
	}

	/**
	 * 清除所有事件回调
	 */
	public static function clearListeners()
	{
		self::$events = array();
    }

	/**
	 * 加载事件监听配置文件
	 */
	public static function loadCfg()
	{
        $CI = & get_instance();
        $_cfg = $CI->config->item('events');
        if (empty($_cfg)) {
            return;
        }

        foreach ($_cfg as $event => $callbacks) {
            foreach ($callbacks as $callback) {
                self::listen($event, $callback);
            }
        }
    }
}
