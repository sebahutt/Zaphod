<?php
/**
 * Server is a library of all files managers available
 */
abstract class FileServer {
	/**
	 * List of files managers
	 * @var array
	 */
	protected static $_managers = array();
	
	/**
	 * Get a file manager
	 * @param string $name the name of the file manager
	 * @param Config|array $config the configuration of the file manager (not required for 'local', 'images', 'upload' and 'web')
	 * @return BaseManager the file manager
	 */
	public static function get($name = 'local', $config = array())
	{
		// Create if not exists
		if (!isset(self::$_managers[$name]))
		{
			// Generic configs
			if ($name == 'local')
			{
				$config = new Config(array(
					'root' => PATH_BASE,
					'web' => URL_BASE
				));
			}
			elseif ($name == 'images')
			{
				$config = new Config(array(
					'root' => PATH_IMG,
					'web' => URL_IMG
				));
			}
			elseif ($name == 'upload')
			{
				$config = new Config(array(
					'root' => ''
				));
			}
			elseif ($name == 'web')
			{
				$config = new Config(array(
					'web' => ''
				));
			}
			else
			{
				// Convert if needed
				$config = is_array($config) ? new Config($config) : $config;
			}
			
			// Type
			if ($config->get('ftp', false))
			{
				self::$_managers[$name] = new FTPManager($config);
			}
			elseif ($config->get('root', false) === false and $config->get('web', false) !== false)
			{
				self::$_managers[$name] = new WebManager($config);
			}
			else
			{
				self::$_managers[$name] = new LocalManager($config);
			}
		}
		
		return self::$_managers[$name];
	}
}