<?php
/**
 * Gestionnaire de requête de page web
 */
abstract class PageRequest extends WebRequest implements iRequestHandler {
	/**
	 * Données d'actions pré-requête
	 * @var array
	 */
	protected $_action;
	/**
	 * Page chargée par la requête
	 * @var Page
	 */
	protected $_page;
	/**
	 * Controleur de la page chargée par la requête
	 * @var DefaultControler
	 */
	protected $_controler;
	/**
	 * Partie de la requête correspondant à l'url réelle de la page
	 * @var string
	 */
	protected $_pageQuery = '';
	/**
	 * Partie de la requête correspondant aux paramètres additionnels
	 * @var string
	 */
	protected $_paramQuery = '';
	/**
	 * Identifiants contenus dans la requête
	 * @var array
	 */
	protected $_queryParams;
	/**
	 * Chaîne de réécriture si existante, false sinon
	 * @var string|boolean
	 */
	protected $_rewriteString;
	
	/**
	 * Initialise la requête en cours, et prépare les données
	 * @return boolean true si la requête est correctement initialisée, false sinon
	 */
	public function init()
	{
		parent::init();
		
		// Actions
		if (Request::issetParam('__action'))
		{
			// Récupération
			$this->_action = Request::getParam('__action');
			
			// Formattage et sécurisation
			if (!is_array($this->_action))
			{
				// Si valide
				if (strlen(trim($this->_action)) > 0)
				{
					$this->_action = array($this->_action);
				}
				else
				{
					$this->_action = false;
				}
			}
			elseif (count($this->_action) == 0 or strlen(trim($this->_action[0])) == 0)
			{
				$this->_action = false;
			}
		}
		
		// Nettoyage
		Request::clearGet('__action');
		
		// Chargement de la page
		$this->_page = Page::getByUrl($this->getRequest());
		if (!$this->_page)
		{
			$this->header404();
		}
		
		// Parties de la requête
		$this->_pageQuery = '/'.$this->_page->getUrl();
		$this->_paramQuery = removeInitialSlash(substr($this->_baseQuery, strlen($this->_pageQuery)));
		
		// Identifiants contenus dans la requête
		$this->_queryParams = array();
		$queryParts = array_reverse(explode('/', $this->_baseQuery));
		$queryId = false;
		foreach ($queryParts as $part)
		{
			if (strlen($part) > 0)
			{
				if (preg_match('/[0-9]+/', $part))
				{
					$queryId = intval($part);
				}
				elseif ($queryId !== false)
				{
					$this->_queryParams[$part] = $queryId;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Vérifie le droit d'accès
	 * @return boolean une confirmation que la ressource demandée est accessible
	 */
	public function isAccessible()
	{
		return $this->_page->isAccessible();
	}
	
	/**
	 * Renvoie la chaîne de la requête en cours, avec ses paramètres GET éventuels
	 * @return string la requête
	 */
	public function getRequest()
	{
		if (!isset($this->_request))
		{
			$rewriteString = $this->getRewriteString();
			$this->_request = ($rewriteString !== false) ? $rewriteString : parent::getRequest();
		}
		
		return $this->_request;
	}
	
	/**
	 * Renvoie la chaîne de réécriture, si définie
	 * @return string la chaîne de réécriture, ou false si non définie
	 */
	public function getRewriteString()
	{
		if (!isset($this->_rewriteString))
		{
			$this->_rewriteString = Request::getGET('__rewrite', false, false);
			Request::clearGet('__rewrite');
		}
		
		return $this->_rewriteString;
	}
	
	/**
	 * Autorise l'accès à la ressource
	 * @return void
	 */
	public function allowAccess()
	{
		// Aucune action particulière requise
	}
	
	/**
	 * Refuse l'accès à la ressource
	 * @return void
	 */
	public function denyAccess()
	{
		$this->header403();
	}
	
	/**
	 * Renvoie la partie de la requête correspondant à l'url réelle de la page
	 * @return string la requête
	 */
	public function getPageQuery()
	{
		return $this->_pageQuery;
	}
	
	/**
	 * Renvoie la partie de la requête correspondant aux paramètres additionnels
	 * @return string la requête
	 */
	public function getParamQuery()
	{
		return $this->_paramQuery;
	}
	
	/**
	 * Renvoie les paramètres contenus dans le chemin de la requête elle-même
	 * par exemple :
	 * 		- la page /edit chargée avec la requête /edit/user/1 va renvoyer array( 'user' => 1 )
	 * 		- la page /list chargée avec la requête /list/group/1/page/2 va renvoyer array( 'group' => 1, 'page' => 2 )
	 * 		- la page /edit chargée avec la requête /edit/user/settings/1 va renvoyer array( 'user' => 1, 'settings' => 1 )
	 * @return array les paramètres trouvés
	 */
	public function getQueryParams()
	{
		return $this->_queryParams;
	}
	
	/**
	 * Renvoie la valeur d'un identifiant contenu dans l'url si existant
	 * @param string $name le nom de l'identifiant (partie précédente de l'url)
	 * @param mixed $default la valeur par défaut
	 * @return mixed la valeur si existant, sinon $default
	 */
	public function getQueryParam($name, $default = NULL)
	{
		return isset($this->_queryParams[$name]) ? $this->_queryParams[$name] : $default;
	}
	
	/**
	 * Obtient la liste des actions pré-requêtes
	 * @return boolean|array la liste des action, ou false si aucune
	 */
	public function getActionRequest()
	{
		return $this->_action;
	}
	
	/**
	 * Renvoie la page en cours
	 * @return Page|boolean l'objet de la page, ou false si inexistant
	 */
	public function getPage()
	{
		return $this->_page;
	}
	
	/**
	 * Renvoie le controleur de la page en cours
	 * @return DefaultControler le controleur
	 */
	public function getControler()
	{
		if (!isset($this->_controler))
		{
			// Test si controleur existant
			if ($this->_page->hasControler())
			{
				// Chargement
				require($this->_page->getControlerPath());
				$this->_controler = new PageControler($this->_page);
			}
			else
			{
				$this->_controler = new AppControler($this->_page);
			}
		}
		
		return $this->_controler;
	}
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * @return void
	 */
	public function start()
	{
		// Si retour en arrière
		if (Request::getGet('__back', 0) == 1)
		{
			// Nettoyage de l'historique
			History::trim();
			Request::clearGet('__back');
		}
	}
	
	/**
	 * Execute la requête
	 * @return string le contenu à afficher
	 */
	public function exec()
	{
		$controler = self::getControler();
		
		// Actions
		if ($actions = self::getActionRequest())
		{
			foreach ($actions as $action)
			{
				if (method_exists($controler, $action))
				{
					call_user_func(array($controler, $action));
				}
			}
		}
		
		return '';
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @return void
	 */
	public function end()
	{
		
	}
	
	/**
	 * Redirige en interne vers une autre page, sans modifier les chemins de la requête orginale
	 * @param string $request la requête à utiliser
	 * @return void
	 * @throws SCException
	 */
	public function internalRedirect($request)
	{
		// Si requête déjà en cours
		if (Request::isRunning())
		{
			throw new SCException('Impossible de rediriger en interne, la requête est déjà en cours d\'exécution', 999, 'Redirection demandée : '.$request);
		}
		
		$this->_page = Page::getByUrl($request);
		if (!$this->_page)
		{
			$this->header404();
		}
	}
}