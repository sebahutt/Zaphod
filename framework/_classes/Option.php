<?php
/**
 * Fichier de définition de la classe de gestion des options
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

/**
 * Classe de gestion des options
 */
class Option extends BaseClass
{
	/**
	 * Utilisateur de rattachement
	 * @var User|boolean
	 */
	protected $_user;
	/**
	 * Table de référence
	 * @var string
	 */
	public static $table = 'options';
	/**
	 * Cache des options générales
	 * @var array
	 */
	protected static $_cache;
	
	/**
	 * Renvoie l'objet utilisateur de rattachement
	 * 
	 * @return User|boolean l'objet utilisateur ou false si aucun
	 */
	public function getUser()
	{
		if (!isset($this->_user))
		{
			$this->_user = is_null($this->get('user')) ? false : User::getById($this->get('user'));
		}
		
		return $this->_user;
	}
	
	/**
	 * Renvoie la valeur de l'option
	 * 
	 * @param mixed $default la valeur par défaut si l'option n'existe pas (facultatif, défaut : NULL)
	 * @return mixed la valeur trouvée, ou $default
	 */
	public function getValue($default = NULL)
	{
		return $this->get('value', $default);
	}
	
	/**
	 * Mise à jour de la valeur de l'option
	 * 
	 * @param mixed $value la valeur à affecter
	 * @return void
	 */
	public function setValue($value)
	{
		$this->set('value', $value);
		$this->save();
	}
	
	/**
	 * Obtention d'une option par son id
	 * 
	 * @param int $id l'identifiant du fichier
	 * @return Option|boolean l'objet option si trouvé, ou false si inexistant
	 */
	public static function getById($id)
	{
		// Requête
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` WHERE `id_option`=?', array($id));
		
		// Si trouvé
		if ($result->count() > 0)
		{
			return Factory::getInstance('Option', $result[0]);
		}
		
		// Renvoi par défaut
		return false;
	}
	
	/**
	 * Obtention d'option par son nom
	 * 
	 * @param string $name le nom de l'option
	 * @param int $id_user un identifiant d'utilisateur pour obtenir l'option liée à cet utilisateur, 
	 * false pour une option générale ou NULL pour ne pas filtrer (facultatif, défaut : NULL)
	 * 
	 * @return array la liste des options correspondantes
	 */
	public static function getByName($name, $id_user = NULL)
	{
		// Init
		$params = array($name);
		
		// Champ utilisateur
		if (is_bool($id_user))
		{
			$user = ' AND `user` IS NULL';
		}
		elseif (!is_null($id_user))
		{
			$user = ' AND `user`=?';
			$params[] = $id_user;
		}
		
		// Requête
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` WHERE `name`=?'.$user, $params);
		return $result->castAs('Option');
	}
	
	/**
	 * Charge le cache des options globales
	 * 
	 * @return void
	 */
	protected static function _loadCache()
	{
		// Vérification du cache
		if (!isset(self::$_cache))
		{
			self::$_cache = array();
			
			// Requête
			$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` WHERE `user` IS NULL');
			
			// Si trouvé
			foreach ($result as $row)
			{
				self::$_cache[$row['name']] = Factory::getInstance('Option', $row);
			}
		}
	}
	
	/**
	 * Obtention de la valeur d'une option par son nom (renvoie la première valeur trouvée)
	 * 
	 * @param string $name le nom de l'option
	 * @param mixed $default la valeur par défaut si l'option n'existe pas (facultatif, défaut : NULL)
	 * @return mixed la valeur trouvée, ou $default
	 */
	public static function getGlobal($name, $default = NULL)
	{
		// Vérification du cache
		self::_loadCache();
		
		return isset(self::$_cache[$name]) ? self::$_cache[$name]->getValue($default) : $default;
	}
	
	/**
	 * Affectation de la valeur d'une option par son nom (renvoie la première valeur trouvée)
	 * 
	 * @param string $name le nom de l'option
	 * @param mixed $value la valeur à affecter
	 * @return void
	 */
	public static function setGlobal($name, $value)
	{
		// Vérification du cache
		self::_loadCache();
		
		if (!isset(self::$_cache[$name]))
		{
			// Création
			self::$_cache[$name] = Factory::getInstance('Option', array(
				'user' => NULL,
				'name' => $name
			));
		}
		
		// Affectation
		self::$_cache[$name]->setValue($value);
	}
	
	/**
	 * Obtention des options d'un utilisateur
	 * 
	 * @param int $id_user l'id de l'utilisateur
	 * @return array la liste des options trouvées
	 */
	public static function getUserOptions($id_user)
	{
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` WHERE `user`=?', array($id_user));
		return $result->castAs('Option');
	}
}