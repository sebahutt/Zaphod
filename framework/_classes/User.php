<?php
/**
 * Fichier de définition de la classe de gestion des utilisateurs - fonctions génériques
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

/**
 * Classe de gestion des utilisateurs - fonctions génériques
 */
class User extends BaseClass
{
	/**
	 * Indique si l'utilisateur est un utilisateur par défaut
	 * @var boolean
	 */
	protected $_default;
	/**
	 * Administrateur de l'utilisateur
	 * @var User|boolean
	 */
	protected $_admin;
	/**
	 * Statut de l'utilisateur
	 * @var Statut
	 */
	protected $_statut;
	/**
	 * Cache des options de l'utilisateur
	 * @var array
	 */
	protected $_options;
	/**
	 * Table de référence
	 * @var string
	 */
	public static $table = 'users';
	/**
	 * Objet user courant
	 * @var User|boolean
	 * @static
	 */
	protected static $_current;
	
	/**
	 * Initialise la classe
	 */
	public static function initClass()
	{
		// Effacement de l'utilisateur
		if (Request::getGET('logout', 0) == 1)
		{
			self::logOut();
			
			// Log
			Log::info('Utilisateur courant déconnecté');
		}
		
		// Nettoyage
		Request::clearGet('logout');
	}
	
	/**
	 * Indique si l'utilisateur est un utilisateur par défaut
	 *
	 * @return boolean une confirmation
	 */
	public function isDefault()
	{
		return (isset($this->_default) and $this->_default === true);
	}
	
	/**
	 * Indique si l'utilisateur est un administrateur
	 *
	 * @return boolean une confirmation
	 */
	public function isAdmin()
	{
		return ($this->statut == 1);
	}
	
	/**
	 * Renvoie l'administrateur de l'utilisateur, si existant
	 *
	 * @return User|boolean l'objet utilisateur si existant, false sinon
	 */
	public function getAdmin()
	{
		// Si pas encore chargé
		if (!isset($this->_admin))
		{
			$this->_admin = is_null($this->admin) ? false : self::getUser($this->admin);
		}
		
		return $this->_admin;
	}
	
	/**
	 * Renvoie le nom complet de l'utilisateur
	 *
	 * @param string $sep la chaîne de séparation entre les parties du nom (facultatif, défaut : ' ')
	 * @param string $default lenom par défaut si aucune information n'est disponible (facultatif, défaut : '(sans nom)')
	 * @return string le nom complet
	 */
	public function getFullName($sep = ' ', $default = '(sans nom)')
	{
		$name = array();
		$firstname = trim($this->get('first_name', ''));
		$lastname = trim($this->get('last_name', ''));
		
		if (strlen($firstname) > 0)
		{
			$name[] = $firstname;
		}
		if (strlen($lastname) > 0)
		{
			$name[] = $lastname;
		}
		
		return (count($name) > 0) ? implode($sep, $name) : $default;
	}
	
	/**
	 * Renvoie l'objet statut de l'utilisateur
	 *
	 * @return Statut l'objet statut
	 */
	public function getStatut()
	{
		// Si pas encore chargé
		if (!isset($this->_statut))
		{
			$this->_statut = Statut::getStatut($this->statut);
		}
		
		return $this->_statut;
	}
	
	/**
	 * Charge le cache des options globales
	 *
	 * @return void
	 */
	protected function _loadOptionsCache()
	{
		// Vérification du cache
		if (!isset($this->_options))
		{
			$this->_options = array();
			if (!$this->isNew())
			{
				$options = Option::getUserOptions($this->id_user);
				foreach ($options as $option)
				{
					$this->_options[$option->name] = $option;
				}
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
	public function getOption($name, $default = NULL)
	{
		// Vérification du cache
		$this->_loadOptionsCache();
		
		return isset($this->_options[$name]) ? $this->_options[$name]->getValue($default) : $default;
	}
	
	/**
	 * Affectation de la valeur d'une option par son nom (renvoie la première valeur trouvée)
	 *
	 * @param string $name le nom de l'option
	 * @param mixed $value la valeur à affecter
	 * @return void
	 */
	public function setOption($name, $value)
	{
		// Vérification du cache
		$this->_loadOptionsCache();
		
		if (!isset($this->_options[$name]))
		{
			// Création
			$this->_options[$name] = Factory::getInstance('Option', array(
				'user' => $this->get('id_user'),
				'name' => $name
			));
			
			// Mode d'enregistrement
			if (!$this->isSavable())
			{
				$this->_options[$name]->disableSave();
			}
		}
		
		// Affectation
		$this->_options[$name]->setValue($value);
	}
	
	/**
	 * Obtention du user courant
	 *
	 * @return User l'utilisateur courant
	 * @static
	 */
	public static function getCurrent()
	{
		// Si pas encore détecté
		if (!isset(self::$_current))
		{
			// Si déjà en session
			if (isset($_SESSION['id_user']) and $_SESSION['id_user'] !== false)
			{
				// Chargement
				self::$_current = self::getUser($_SESSION['id_user']);
			}
			else
			{
				// Utilisateur par défaut
				self::$_current = Factory::getInstance('User', array(
					'nom' => __('non identifié'),
					'prenom' => __('Utilisateur'),
					'login' => '',
					'pass' => '',
					'statut' => NULL
				));
				self::$_current->_default = true;
				
				// Désactivation de l'enregistrement
				self::$_current->disableSave(true);
			}
		}
		
		return self::$_current;
	}
	
	/**
	 * Obtention d'un user
	 *
	 * @param int $id_user l'identifiant de l'utilisateur
	 * @return User|boolean l'utilisateur désiré, ou false si inexistant
	 */
	public static function getUser($id_user)
	{
		// Requête
		$result = Database::get(self::$server)->query('SELECT * FROM `users` A LEFT JOIN `statuts` B ON A.`statut`=B.`id_statut` WHERE A.`id_user`='.intval($id_user).';');
	
		// Si trouvé
		if ($result->count() > 0)
		{
			return Factory::getInstance('User', $result[0]);
		}
		
		// Renvoi par défaut
		return false;
	}
	
	/**
	 * Liste des users enregistrés
	 *
	 * @param array $options les options de chargement (facultatif, défaut : array())
	 * 					- actif : état actif des utilisateurs (0 ou 1) - défaut : NULL
	 * 					- statut : les ou les statuts des utilisateurs - défaut : false
	 *
	 * @return array la liste des users
	 */
	public static function getList($options = array())
	{
		// Options
		$options['actif'] = 	isset($options['actif']) ? 		$options['actif'] : 				NULL;
		$options['statut'] = 	isset($options['statut']) ? 	$options['statut'] : 				false;
		$options['orderby'] = 	isset($options['orderby']) ? 	$options['orderby'] : 				'A.`prenom`, A.`nom`';
		
		// Init
		$request = 'SELECT * FROM `users` A INNER JOIN `statuts` B ON A.`statut`=B.`id_statut`';
		$order = ' ORDER BY '.$options['orderby'];
		$params = array();
		$where = array();
		
		// Options
		if (is_array($options['statut']) or is_numeric($options['statut']))
		{
			// Type
			if (is_array($options['statut']))
			{
				$where[] = 'A.`statut` IN ('.implode(',', array_fill(0, count($options['statut']), '?')).')';
				$params = array_merge($params, $options['statut']);
			}
			else
			{
				$where[] = 'A.`statut`=?';
				$params[] = intval($options['statut']);
			}
		}
		if (!is_null($options['actif']))
		{
			$where[] = 'A.`actif`=?';
			$params[] = intval($options['actif']);
		}
		
		// Finalisation
		$finalWhere = (count($where) > 0) ? ' WHERE '.implode(' AND ', $where) : '';
		
		// Récupération
		$result = Database::get(self::$server)->query($request.$finalWhere.$order, $params);
		return $result->castAs('User');
	}
	
	/**
	 * Identification de l'utilisateur courant
	 *
	 * @param string $login le nom d'utilisateur
	 * @param string $pass le mot de passe
	 * @return User|boolean l'user désiré, ou false si identification non valide
	 */
	public static function logUser($login, $pass)
	{
		if (strlen($login) > 0)
		{
			// Requête
			$result = Database::get(self::$server)->query('SELECT * FROM `users` WHERE `login`=? AND `pass`=? AND `actif`=1', array($login, md5($pass)));
			
			// Si trouvé
			if ($result->count() > 0)
			{
				// Stockage
				self::$_current = Factory::getInstance('User', $result[0]);
				
				// Mémorisation
				$_SESSION['id_user'] = self::$_current->id_user;
				
				// Date de connection
				Database::get(self::$server)->exec('UPDATE `users` SET `last_connect`=NOW() WHERE `id_user`=?', array(self::$_current->id_user));
				
				// Log
				Log::info('Utilisateur courant identifié : '.self::$_current->first_name.' '.self::$_current->last_name);
				
				return self::$_current;
			}
		}
		
		// Renvoi par défaut
		return false;
	}
	
	/**
	 * Déconnexion du user actif
	 *
	 * @return void
	 */
	public static function logOut()
	{
		// Effacement
		self::$_current = NULL;
		$_SESSION['id_user'] = NULL;
	}
	
	/**
	 * Vérifie si un login existe déjà
	 *
	 * @param string $login le login à tester
	 * @param boolean une confirmation
	 */
	public static function loginExists($login)
	{
		return (count(Database::get(self::$server)->query('SELECT * FROM `users` WHERE `login`=?;', array($login))) > 0);
	}
}