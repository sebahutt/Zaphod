<?php
/**
 * Fichier de définition de la classe de gestion des pages du site - fonctions génériques
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

/**
 * Classe de gestion des pages du site - fonctions génériques
 */
class Page extends BaseClass
{
	/**
	 * Elément parent
	 * @var Page
	 */
	protected $_parent;
	/**
	 * Sous-éléments
	 * @var array
	 */
	protected $_children;
	/**
	 * Chemin du fichier contrôleur
	 * @var string
	 */
	protected $_controlerPath;
	/**
	 * Indique si le fichier contrôleur existe
	 * @var boolean
	 */
	protected $_hasControler;
	/**
	 * Table de référence
	 * @var string
	 */
	public static $table = 'pages';
	
	/**
	 * Obtention de l'élément parent
	 * 
	 * @return boolean|Page l'élément parent, ou false si non existant (racine)
	 * @throws SCException
	 */
	public function getParent()
	{
		// Si pas déjà chargé
		if (!isset($this->_parent))
		{
			// Init
			$this->_parent = false;
			
			// Si existant
			if (!is_null($this->id_parent))
			{
				// Sélection
				$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` A LEFT JOIN `'.self::$table.'_access` B ON A.`id_page`=B.`page` AND (B.`statut` IS NULL OR B.`statut`=?) WHERE id_page=?;', array(User::getCurrent()->statut, $this->id_parent));
				
				// Si trouvé
				if ($result->count() > 0)
				{
					// Composition
					$this->_parent = Factory::getInstance('Page', $result[0]);
				}
				else
				{
					// Erreur
					throw new SCException('Impossible de charger l\'élément parent de '.$this->id_page);
				}
			}
		}
		
		return $this->_parent;
	}
	
	/**
	 * Obtention de l'élément racine
	 * 
	 * @return Page l'élément parent (qui peut être l'élément courant)
	 */
	public function getRoot()
	{
		$parent = $this->getParent();
		return $parent ? $parent->getRoot() : $this;
	}
	
	/**
	 * URL de la page
	 * 
	 * @return string l'url de la page
	 */
	public function getUrl()
	{
		return $this->get('url');
	}
	
	/**
	 * Contruit le lien de la page
	 * 
	 * @param string|boolean $title le titre (alt) à utiliser, ou false pour utiliser celui de la page
	 * @return string le lien de la page
	 */
	public function getLink($title = false)
	{
		$text = $this->get('title', 'Page');
		if (!$title)
		{
			$title = $text;
		}
		
		return '<a href="/'.$this->getUrl().'" title="'.htmlspecialchars($title).'">'.htmlspecialchars($text).'</a>';
	}
	
	/**
	 * Obtention des sous-éléments
	 * 
	 * @param boolean $strict indique si on doit se limiter aux pages accessibles ou toutes les charger
	 * 												(facultatif, défaut : true)
	 * 
	 * @return array la liste des sous-éléments
	 */
	public function getChildren($strict = true)
	{
		// Si pas déjà chargé
		if (!isset($this->_children))
		{
			// Relai
			$this->_children = self::getIdChildren($this->id_page, $strict);
		}
		
		return $this->_children;
	}
	
	/**
	 * Teste si l'élément est accessible
	 * 
	 * @return boolean la confirmation que l'élément est accessible ou non
	 */
	public function isAccessible()
	{
		return ($this->has('id_access') and (User::getCurrent()->isDefault() ? ($this->get('loggedOnly') == 0) : true));
	}
	
	/**
	 * Renvoie le chemin du contrôleur
	 * 
	 * @return string le chemin du contrôleur
	 */
	public function getControlerPath()
	{
		if (!isset($this->_controlerPath))
		{
			$file = $this->get('file');
			$controlerDir = strrpos($file, '/');
			if (!$controlerDir)
			{
				$controlerDir = '';
			}
			else
			{
				$controlerDir = substr($file, 0, $controlerDir+1);
			}
			
			$this->_controlerPath = PATH_BASE.$controlerDir.pathinfo($file, PATHINFO_FILENAME).'.ctrl.php';
		}
		
		return $this->_controlerPath;
	}
	
	/**
	 * Indique si la page a un contrôleur
	 * 
	 * @return boolean une confirmation
	 */
	public function hasControler()
	{
		if (!isset($this->_hasControler))
		{
			$this->_hasControler = file_exists($this->getControlerPath());
		}
		
		return $this->_hasControler;
	}
	
	/**
	 * Obtention d'une page par son url
	 * 
	 * @param string $url le nom du fichier
	 * @return Page|boolean l'objet page, ou false si inexistant
	 * @static
	 */
	public static function getByUrl($url)
	{
		// Retrait des paramètres
		$index = strpos($url, '?');
		if ($index !== false)
		{
			$url = substr($url, 0, $index);
		}
		
		// Détection d'identifiants
		$url = preg_replace('/\/[0-9]+\//', '/n/', $url);
		
		// Activation des urls uniquement constituées de paramètres
		$requiredLength = Env::getConfig('sys')->get('allParamRewrite') ? 'INSTR(?, `url`)=1' : '(LENGTH(`url`) > 0 AND INSTR(?, `url`)=1)';
		
		// Requête
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` A LEFT JOIN `'.self::$table.'_access` B ON A.`id_page`=B.`page` AND (B.`statut` IS NULL OR B.`statut`=?) WHERE '.$requiredLength.' OR `url`=? OR `file`=? ORDER BY LENGTH(`url`) DESC, `id_parent` DESC;', array(User::getCurrent()->statut, $url, $url, $url));
		
		// Si trouvé
		$nbPages = $result->count();
		if ($nbPages > 0)
		{
			// Si plusieurs pages, on recherche s'il y en a une spécifique au statut d'utilisateur
			if ($nbPages > 1)
			{
				$page = false;
				$exact = false;
				foreach ($result as $row)
				{
					// Si url exacte
					if ($row['url'] == $url)
					{
						// Si statut particulier
						if (!is_null($row['statut']))
						{
							$page = $row;
							break;
						}
						// Si première page exacte
						elseif (!$page or !$exact)
						{
							$page = $row;
						}
						
						$exact = true;
					}
					elseif (!$exact and $page)
					{
						// Si url la plus longue
						if (strlen($row['url']) > strlen($page['url']))
						{
							$page = $row;
						}
						// Si taille identique mais statut particulier
						elseif (strlen($row['url']) == strlen($page['url']) and !is_null($row['statut']) and is_null($page['statut']))
						{
							$page = $row;
						}
					}
					elseif (!$page)
					{
						$page = $row;
					}
				}
			}
			else
			{
				// Par défaut, première page
				$page = $result[0];
				
			}
			
			return Factory::getInstance('Page', $page);
		}
		
		// Renvoi par défaut
		return false;
	}
	
	/**
	 * Obtention des sous-éléments d'un id
	 * 
	 * @param int $id_parent l'id du parent
	 * @param boolean $strict indique si on doit se limiter aux pages accessibles ou toutes les charger (facultatif, défaut : true)
	 * @return array la liste des sous-éléments trouvés
	 * @static
	 */
	public static function getIdChildren($id_parent, $strict = true)
	{
		// Init
		$param = array();
		$user = User::getCurrent();
		
		// Statut
		if (is_null($user->statut))
		{
			$statut = 'B.`statut` IS NULL';
		}
		else
		{
			$param[] = $user->statut;
			$statut = '(B.`statut` IS NULL OR B.`statut`=?)';
		}
		
		// Parent
		if (is_null($id_parent))
		{
			$parent = ' IS NULL';
		}
		else
		{
			$param[] = $id_parent;
			$parent = '=?';
		}
		
		// Mode
		$logged = $user->isDefault() ? ' OR A.`loggedOnly`=0' : '';
		$force = $strict ? ' AND (B.`id_access` IS NOT NULL'.$logged.')' : '';
		
		// Récupération des pages
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` A INNER JOIN `'.self::$table.'_access` B ON A.`id_page`=B.`page` AND '.$statut.' WHERE `id_parent`'.$parent.' AND `nav`=1'.$force.' ORDER BY `order`', $param);
		return $result->castAs('Page');
	}
	
	/**
	 * Renvoie la navigation principale
	 * 
	 * @param boolean $strict indique si on doit se limiter aux pages accessibles ou toutes les charger (facultatif, défaut : true)
	 * @return array la liste des rubriques principales
	 * @static
	 */
	public static function getRootNav($strict = true)
	{
		return self::getIdChildren(NULL, $strict);
	}
}