<?php
/**
 * Classe de librairie des serveurs de fichiers disponibles
 */
abstract class FileServer {
	/**
	 * Configurations disponibles
	 * @var array
	 */
	protected static $_configs = array();
	/**
	 * Liste des gestionnaires de fichiers
	 * @var array
	 */
	protected static $_managers = array();
	
	/**
	 * Initialise la classe
	 */
	public static function initClass()
	{
		// Ajout des configurations génériques
		self::addConfig('local', array(
			'root' => PATH_BASE,
			'web' => URL_BASE
		));
		self::addConfig('images', array(
			'root' => PATH_IMG,
			'web' => URL_IMG
		));
		self::addConfig('upload', array(
			'root' => ''
		));
		self::addConfig('web', array(
			'web' => ''
		));
	}
	
	/**
	 * Ajoute une configuration nommée
	 * 
	 * @param string $name le nom de la configuration (correspond au nom du serveur à venir)
	 * @param array $config la configuration à stocker
	 */
	public static function addConfig($name, $config)
	{
		self::$_configs[$name] = $config;
	}
	
	/**
	 * Récupération d'un gestionnaire de fichiers
	 * 
	 * @param string $name le nom du gestionnaire
	 * @param Config|array $config la configuration du gestionnaire (non requise pour 'local', 'images', 'upload', 'web' 
	 * ou tout autre serveur dont la configuration a été pré-renseignée par addConfig)
	 * 
	 * @return BaseManager the file manager
	 */
	public static function get($name = 'local', $config = array())
	{
		if (!isset(self::$_managers[$name]))
		{
			// Si configuration générique
			if (isset(self::$_configs[$name]))
			{
				$config = self::$_configs[$name];
				
				// Nettoyage (optimisation)
				unset(self::$_configs[$name]);
			}
			
			// Format
			$config = is_array($config) ? new Config($config) : $config;
			
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
	
	/**
	 * Analyse un chemin pour tenter de déterminer le serveur correspondant
	 * 
	 * @param string $path le chemin
	 * @return FileServer|boolean le serveur correspondant, ou false si introuvable
	 */
	public static function getPathServer($path)
	{
		// Analyse des configurations existantes
		foreach (self::$_configs as $name => $config)
		{
			if (isset($config['root']) and strlen($config['root']) > 0 and strpos($path, $config['root']) === 0)
			{
				return self::get($name);
			}
		}
		
		// Analyse des gestionnaires disponibles
		foreach (self::$_managers as $manager)
		{
			if ($manager->containsPath($path))
			{
				return $manager;
			}
		}
		
		return false;
	}
	
	/**
	 * Renvoie un fichier en analysant son chemin complet pour déterminer le gestionnaire adéquat
	 * 
	 * @param string $path le chemin fichier
	 * @return File|boolean l'objet fichier si valide, ou false si introuvable
	 */
	public static function getFile($path)
	{
		if ($manager = self::getPathServer($path))
		{
			return $manager->getFile(substr($path, strlen($manager->getLocalPath())));
		}
		
		return false;
	}
	
	/**
	 * Renvoie un fichier en analysant son chemin complet pour déterminer le gestionnaire adéquat
	 * 
	 * @param string $path le chemin fichier
	 * @return File|boolean l'objet fichier si valide, ou false si introuvable
	 */
	public static function getFolder($path)
	{
		if ($manager = self::getPathServer($path))
		{
			return $manager->getFolder(substr($path, strlen($manager->getLocalPath())));
		}
		
		return false;
	}
}