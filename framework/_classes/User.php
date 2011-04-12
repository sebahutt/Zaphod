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
	 * @var Status
	 */
	protected $_status;
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
		return ($this->status == 1);
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
			$this->_admin = is_null($this->admin) ? false : self::getById($this->admin);
		}
		
		return $this->_admin;
	}
	
	/**
	 * Change le mot de passe de l'utilisateur
	 *
	 * @param string $pass le mot de passe non encodé
	 * @return boolean une confirmation que le mot de passe est valide
	 */
	public function changePassword($pass)
	{
		$this->set('pass', md5($pass));
		
		return true;
	}
	
	/**
	 * Renvoi l'id unique de l'utilisateur
	 *
	 * @return string l'id unique de 32 caractères
	 * @throws SCException si l'utilisateur n'est pas enregistré dans la base
	 */
	public function getUniqid()
	{
		$uniqid = $this->get('uniqid');
		return is_null($uniqid) ? $this->resetUniqid() : $uniqid;
	}
	
	/**
	 * Réinitialise l'id unique de l'utilisateur
	 *
	 * @return string le nouvel id unique de 32 caractères
	 * @throws SCException si l'utilisateur n'est pas enregistré dans la base
	 */
	public function resetUniqid()
	{
		// Vérification
		if ($this->isNew())
		{
			throw new SCException('L\'utilisateur doit être enregistré dans la base pour bénéficier d\'un uniqid');
		}
		
		$uniqid = md5($this->get('id_user').uniqid());
		$this->set('uniqid', $uniqid);
		$this->save('uniqid');
		
		return $uniqid;
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
	 * @return Status l'objet statut
	 */
	public function getStatus()
	{
		// Si pas encore chargé
		if (!isset($this->_status))
		{
			$this->_status = Status::getStatus($this->status);
		}
		
		return $this->_status;
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
		
		// Si utilisateur non enregistré
		if ($this->isNew())
		{
			return isset($this->_options[$name]) ? $this->_options[$name] : $default;
		}
		else
		{
			return isset($this->_options[$name]) ? $this->_options[$name]->getValue($default) : $default;
		}
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
		
		// Si utilisateur non enregistré
		if ($this->isNew())
		{
			$this->_options[$name] = $value;
		}
		else
		{
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
	}
	
	/**
	 * Mise à jour des données de l'objet et enregistrement : si l'objet est nouveau (pas d'id), il est créé dans la base, sinon il est mis à jour.
	 *
	 * @param string|array|NULL $fields le ou les champs à mettre à jour, ou NULL pour tous (facultatif, défaut : NULL)
	 * @return int l'id de l'élément, ou false en cas d'erreur (ex : aucun champ défini)
	 */
	public function save($fields = NULL)
	{
		$isNew = $this->isNew();
		$result = parent::save($fields);
		
		// Si valide, conversion des options
		if ($result !== false and $isNew)
		{
			$options = $this->_options;
			$this->_options = array();
			foreach ($options as $name => $value)
			{
				$this->setOption($name, $value);
			}
		}
		
		return $result;
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
			$id_user = Session::getCache('User', 'current', false);
			if ($id_user !== false and $user = self::getById($id_user))
			{
				self::$_current = $user;
			}
			elseif (Cookie::exists('user') and $user = self::getByUniqid(Cookie::get('user')->getValue()))
			{
				self::$_current = $user;
				
				// Mémorisation
				Session::setCache('User', 'current', self::$_current->id_user);
				
				// Prolongation du cookie
				Cookie::get('user')->extend();
			}
			else
			{
				// Utilisateur par défaut
				self::$_current = Factory::getInstance('User', array(
					'nom' => __('non identifié'),
					'prenom' => __('Utilisateur'),
					'login' => '',
					'pass' => '',
					'status' => NULL
				));
				self::$_current->_default = true;
				
				// Désactivation de l'enregistrement
				self::$_current->disableSave(true);
				
				// Nettoyage des cookies
				if (Cookie::exists('user'))
				{
					Cookie::get('user')->delete();
				}
			}
		}
		
		return self::$_current;
	}
	
	/**
	 * Obtention d'un utilisateur par son id
	 *
	 * @param int $id_user l'identifiant de l'utilisateur
	 * @return User|boolean l'utilisateur désiré, ou false si inexistant
	 */
	public static function getById($id_user)
	{
		// Requête
		$result = Database::get(self::$server)->query('SELECT * FROM `users` A LEFT JOIN `status` B ON A.`status`=B.`id_status` WHERE A.`id_user`=?', $id_user);
	
		// Si trouvé
		if ($result->count() > 0)
		{
			return Factory::getInstance('User', $result[0]);
		}
		
		return false;
	}
	
	/**
	 * Obtention d'un utilisateur par son uniqid
	 *
	 * @param int $uniqid l'identifiant unique de l'utilisateur
	 * @return User|boolean l'utilisateur désiré, ou false si inexistant
	 */
	public static function getByUniqid($uniqid)
	{
		// Sécurisation
		if (strlen($uniqid) !== 32)
		{
			return false;
		}
		
		// Requête
		$result = Database::get(self::$server)->query('SELECT * FROM `users` A LEFT JOIN `status` B ON A.`status`=B.`id_status` WHERE A.`uniqid`=?', $uniqid);
	
		// Si trouvé
		if ($result->count() > 0)
		{
			return Factory::getInstance('User', $result[0]);
		}
		
		return false;
	}
	
	/**
	 * Liste des users enregistrés
	 *
	 * @param array $options les options de chargement (facultatif, défaut : array())
	 * 					- actif : état actif des utilisateurs (0 ou 1) - défaut : NULL
	 * 					- status : les ou les statuts des utilisateurs - défaut : false
	 *
	 * @return array la liste des users
	 */
	public static function getList($options = array())
	{
		// Options
		$options['actif'] = 	isset($options['actif']) ? 		$options['actif'] : 				NULL;
		$options['status'] = 	isset($options['status']) ? 	$options['status'] : 				false;
		$options['orderby'] = 	isset($options['orderby']) ? 	$options['orderby'] : 				'A.`first_name`, A.`last_name`';
		
		// Init
		$request = 'SELECT * FROM `users` A INNER JOIN `status` B ON A.`status`=B.`id_status`';
		$order = ' ORDER BY '.$options['orderby'];
		$params = array();
		$where = array();
		
		// Options
		if (is_array($options['status']) or is_numeric($options['status']))
		{
			// Type
			if (is_array($options['status']))
			{
				$where[] = 'A.`status` IN ('.implode(',', array_fill(0, count($options['status']), '?')).')';
				$params = array_merge($params, $options['status']);
			}
			else
			{
				$where[] = 'A.`status`=?';
				$params[] = intval($options['status']);
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
	 * @param boolean $remind indique s'il faut mémoriser l'utilisateur par un cookie (facultatif, défaut : false)
	 * @return User|boolean l'user désiré, ou false si identification non valide
	 */
	public static function logUser($login, $pass, $remind = false)
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
				Session::setCache('User', 'current', self::$_current->id_user);
				
				// Date de connection
				Database::get(self::$server)->exec('UPDATE `users` SET `last_connect`=NOW() WHERE `id_user`=?', array(self::$_current->id_user));
				
				// Log
				Log::info('Utilisateur courant identifié : '.self::$_current->first_name.' '.self::$_current->last_name);
				
				// Mémorisation
				if ($remind)
				{
					Cookie::set('user', self::$_current->getUniqid());
				}
				
				return self::$_current;
			}
		}
		
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
		Session::clearCache('User', 'current');
		
		// Nettoyage des cookies
		if (Cookie::exists('user'))
		{
			Cookie::get('user')->delete();
		}
	}
	
	/**
	 * Vérifie si un login existe déjà
	 *
	 * @param string $login le login à tester
	 * @param int|NULL $ignore un id d'utilisateur à ignorer, ou NULL (facultatif, défaut : NULL)
	 * @param boolean une confirmation
	 */
	public static function loginExists($login, $ignore = NULL)
	{
		// Init
		$params = array($login);
		$ignored = '';
		
		if (!is_null($ignore))
		{
			$ignored = ' AND `id_user`<>?';
			$params[] = intval($ignore);
		}
		
		return (Database::get(self::$server)->value('SELECT COUNT(*) FROM `users` WHERE `login`=?'.$ignored, $params) > 0);
	}
}