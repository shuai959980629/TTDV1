<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Spl_autoloader {

	private $_include_paths = array();

	public function register(array $paths = array())
	{
		$this->_include_paths = $paths;

		spl_autoload_register(array($this, 'autoloader'));
	}

	public function autoloader($class)
	{
		foreach($this->_include_paths as $path)
		{
            $filepath = $path . ucfirst(strtolower($class)) . '.php';

			if( ! class_exists($class, FALSE) AND is_file($filepath))
			{
			    require_once($filepath);
				break;
			}
		}
	}
}
