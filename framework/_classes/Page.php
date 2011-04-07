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
	 * Liste des marqueurs de paramètres de l'url
	 * @var array
	 */
	protected $_paramNames;
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
				$this->_parent = self::getById($this->id_parent);
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
	 * @param array $params les identifiants à passer dans l'url aux emplacements définis, sous la forme clé => valeur (facultatif, défaut : array())
	 * @return string l'url de la page
	 * @throws SCException si des paramètres sont attendus mais pas fournis
	 */
	public function getUrl($params = array())
	{
		$url = $this->get('url');
		$paramNames = $this->getParamNames();
		
		// Si aucun paramètre
		if (count($params) === 0)
		{
			if (count($paramNames) > 0)
			{
				throw new SCException('Paramètres manquants pour l\'url (attendus : '.implode(', ', $paramNames).')');
			}
			return $url;
		}
		
		// Remplacement
		$parts = explode('/', $url);
		foreach ($parts as $index => $part)
		{
			if (substr($part, 0, 1) === ':')
			{
				$param = substr($part, 1);
				if (!isset($params[$param]))
				{
					throw new SCException('Paramètre manquant pour l\'url : '.$param);
				}
				$parts[$index] = $params[$param];
			}
		}
		
		return implode('/', $parts);
	}
	
	/**
	 * Extrait les paramètres nommés de l'url de la page
	 *
	 * @return array la liste des noms de paramètres
	 */
	public function getParamNames()
	{
		if (!isset($this->_paramNames))
		{
			// Ajout du slash initial pour faciliter la détection
			preg_match_all('/\/:([[:alnum:]]+)/', '/'.$this->get('url'), $matches);
			$this->_paramNames = $matches[1];
		}
		
		return $this->_paramNames;
	}
	
	/**
	 * Indique si la page correspond à une url
	 *
	 * @param string $url l'url à tester
	 * @return boolean une confirmation
	 */
	public function matchesUrl($url)
	{
		return self::urlMatchesPattern($url, $this->get('url'));
	}
	
	/**
	 * Extrait les paramètres passés dans l'url fournie en fonction des marqueurs de l'url interne
	 *
	 * @param string $url l'url à analyser
	 * @return array un tableau associatif clé => valeur
	 */
	public function extractUrlParams($url)
	{
		$paramNames = $this->getParamNames();
		
		// Si aucun paramètre
		if (count($paramNames) === 0)
		{
			return array();
		}
		
		// Test
		if (preg_match('/^'.str_replace('/', '\\/', preg_replace('/\/:[[:alnum:]]+/', '/([^/]+)', '/'.$this->get('url'))).'/', '/'.$url, $matches))
		{
			// Retrait de la chaîne globale des résultats
			array_shift($matches);
			
			// Assemblage
			return array_combine($paramNames, $matches);
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Lien complet vers la page
	 *
	 * @param string $content le contenu du lien, ou NULL pour utiliser le titre de la page (facultatif, défaut : NULL)
	 * @param string $title le contenu de l'attribut title, ou NULL pour utiliser le titre de la page (facultatif, défaut : NULL)
	 * @param array $params les identifiants à passer dans l'url aux emplacements définis, sous la forme clé => valeur (facultatif, défaut : array())
	 * @return string le lien complet
	 */
	public function getLink($content = NULL, $title = NULL, $params = array())
	{
		if (is_null($content))
		{
			$content = $this->get('title', '(sans titre)');
		}
		if (is_null($title))
		{
			$title = $this->get('title', '(sans titre)');
		}
		
		return '<a href="/'.$this->getUrl($params).'" title="'.utf8entities($title).'">'.$content.'</a>';
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
	 * Obtention d'une page par son id
	 *
	 * @param int $id l'id de la page
	 * @return Page|false l'objet page, ou false si inexistant
	 * @static
	 */
	public static function getById($id)
	{
		// Détection si en cache
		$cached = Factory::getInstance('Page', $id);
		if (!is_null($cached))
		{
			return $cached;
		}
		
		// Sélection
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` A LEFT JOIN `'.self::$table.'_access` B ON A.`id_page`=B.`page` AND (B.`statut` IS NULL OR B.`statut`=?) WHERE id_page=?;', array(User::getCurrent()->statut, $id));
		
		// Si trouvé
		if ($result->count() > 0)
		{
			// Composition
			return Factory::getInstance('Page', $result[0]);
		}
		else
		{
			return false;
		}
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
		$server = Database::get(self::$server);
		
		// Retrait des paramètres
		$index = strpos($url, '?');
		if ($index !== false)
		{
			$url = substr($url, 0, $index);
		}
		
		// Préparation de l'expression régulière
		$url = removeSlashes($url);
		$parts = explode('/', $url);
		foreach ($parts as $index => $part)
		{
			$parts[$index] = '('.$part.'|:[[:alnum:]]+)';
		}
		$regexp = implode('/', $parts);
		
		// Activation des urls uniquement constituées de paramètres
		$requiredLength = Env::getConfig('sys')->get('allParamRewrite') ? '`url` REGEXP ?' : '(LENGTH(`url`) > 0 AND `url` REGEXP ?)';
		
		// Requête
		$result = $server->query('SELECT * FROM `'.self::$table.'` A LEFT JOIN `'.self::$table.'_access` B ON A.`id_page`=B.`page` AND (B.`statut` IS NULL OR B.`statut`=?) WHERE '.$requiredLength.' OR `url`=? OR `file`=? ORDER BY LENGTH(`url`) DESC, `id_parent` DESC;', array(User::getCurrent()->statut, $regexp, $url, $url));
		
		// Si trouvé
		$nbPages = $result->count();
		if ($nbPages > 0)
		{
			// Init
			$params = false;
			
			// Si plusieurs pages, on recherche s'il y en a une spécifique au statut d'utilisateur
			if ($nbPages > 1)
			{
				$page = false;
				$exact = false;
				foreach ($result as $row)
				{
					// Si url exacte ou correspondance des paramètres
					$test = self::urlMatchesPattern($url, $row['url']);
					if ($test !== false)
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
						
						$params = $test;
						$exact = true;
					}
					elseif (!$exact and $page)
					{
						// Si url la plus longue
						if (strlen($row['url']) > strlen($page['url']))
						{
							$page = $row;
							$params = false;
						}
						// Si taille identique mais statut particulier
						elseif (strlen($row['url']) == strlen($page['url']) and !is_null($row['statut']) and is_null($page['statut']))
						{
							$page = $row;
							$params = false;
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
				$params = self::urlMatchesPattern($url, $page['url']);
			}
			
			return new PageUrl(Factory::getInstance('Page', $page), $url, ($params === false) ? array() : $params);
		}
		
		// Renvoi par défaut
		return false;
	}
	
	/**
	 * Indique si l'url correspond au pattern fourni
	 *
	 * @param string $url l'url à tester
	 * @param string $pattern un modèle d'url avec d'éventuels paramètres commençant par ':'
	 * @return array|boolean le tableau des paramètres trouvés, ou false si ne correspond pas
	 */
	public static function urlMatchesPattern($url, $pattern)
	{
		// Ajout du slash initial pour faciliter la détection
		$pattern = '/'.removeSlashes($pattern);
		$url = '/'.removeSlashes($url);
		
		// Si aucun paramètre
		if (strpos($pattern, '/:') === false)
		{
			return ($url === $pattern) ? array() : false;
		}
		
		// Analyse
		if (preg_match('/^'.str_replace('/', '\\/', preg_replace('/\/:[[:alnum:]]+/', '/([^/]+)', $pattern)).'/', $url, $matches))
		{
			array_shift($matches);
			return $matches;
		}
		
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
	
	/**
	 * Renvoie le lien vers une page à partir de son id
	 *
	 * @param int $id l'identifiant de la page
	 * @param array $params les identifiants à passer dans l'url aux emplacements définis, sous la forme clé => valeur (facultatif, défaut : array())
	 * @return string le lien, ou # si la page n'existe pas
	 */
	public static function getIdUrl($id, $params = array())
	{
		if ($page = self::getById($id))
		{
			return $page->getUrl($params);
		}
		
		return '#';
	}
	
	/**
	 * Lien complet vers une page à partir de son id
	 *
	 * @param string $content le contenu du lien, ou NULL pour utiliser le titre de la page (facultatif, défaut : NULL)
	 * @param string $title le contenu de l'attribut title, ou NULL pour utiliser le titre de la page (facultatif, défaut : NULL)
	 * @param array $params les identifiants à passer dans l'url aux emplacements définis, sous la forme clé => valeur (facultatif, défaut : array())
	 * @return string le lien complet, ou '' si la page n'existe pas
	 */
	public static function getIdLink($content = NULL, $title = NULL, $params = array())
	{
		if ($page = self::getById($id))
		{
			return $page->getLink($content, $title, $params);
		}
		
		return '';
	}
}