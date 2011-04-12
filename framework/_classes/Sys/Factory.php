<?php
/**
 * Classe de fabrique d'instances avec système de cache pour limiter les doublons
 */
class Factory extends StaticClass {
	/**
	 * Cache des instances
	 * @var array
	 */
	protected static $_cache = array();
	
	/**
	 * Obtention d'une instance
	 *
	 * @param string $class le nom de la classe de l'instance
	 * @param array|int $data les données de l'instance (ignorées si l'instance existe déjà) ou son id (facultatif, défaut : array())
	 * @param string $primary le nom du champ primaire, ou NULL pour détecter automatiquement (facultatif, défaut : NULL)
	 * @return mixed l'instance créée, ou NULL si un id est fourni et qu'il n'existe pas
	 */
	public static function getInstance($class, $data = array(), $primary = NULL)
	{
		// Si identifiant
		if (is_numeric($data))
		{
			return isset(self::$_cache[$class][$data]) ? self::$_cache[$class][$data] : NULL;
		}
		
		// Champ primaire
		if (is_null($primary) or !is_string($primary))
		{
			// Données de la classe
			$class_vars = get_class_vars($class);
			$primary = Database::get($class_vars['server'])->getTable($class_vars['table'])->getPrimary();
		}
		
		// Si défini
		$id = isset($data[$primary]) ? intval($data[$primary]) : 0;
		if ($id > 0)
		{
			if (!isset(self::$_cache[$class][$id]))
			{
				self::$_cache[$class][$id] = new $class($data);
			}
			
			return self::$_cache[$class][$id];
		}
		
		// Sans index, nouvelle entrée
		return new $class($data);
	}
	
	/**
	 * Met à jour ou ajoute une instance dans le cache
	 *
	 * @param string $class le nom de la classe de l'instance
	 * @param BaseClass $instance les données de l'instance (ignorées si l'instance existe déjà) (facultatif, défaut : array())
	 * @param int $id l'identifiant de l'instance
	 */
	public static function updateInstanceCache($class, $instance, $id)
	{
		self::$_cache[$class][$id] = $instance;
	}
	
	/**
	 * Nettoie le cache d'une instance
	 *
	 * @param string $class le nom de la classe de l'instance
	 * @param int $id l'identifiant de l'instance
	 */
	public static function clearInstanceCache($class, $id)
	{
		if (isset(self::$_cache[$class][$id]))
		{
			unset(self::$_cache[$class][$id]);
		}
	}
}