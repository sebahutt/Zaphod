<?php
/**
 * Objet de route liée à une page dans la base
 */
class HttpPageRoute implements iRequestRoute {
	/**
	 * Objet d'analyse de la requête
	 * @var iRequestParser
	 */
	protected $_parser;
	/**
	 * Page chargée par la requête
	 * @var Page
	 */
	protected $_page;
	/**
	 * Données d'actions pré-requête
	 * @var array
	 */
	protected $_action;
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
	 * Constructeur
	 *
	 * @param iRequestParser $parser l'objet d'analyse de la requête
	 * @param Page $page l'objet de la page routée
	 */
	public function __construct($parser, $page)
	{
		// Mémorisation
		$this->_parser = $parser;
		$this->_page = $page;
		
		// Log
		Log::info('Route active : HttpPageRoute ('.$this->_page->get('id_page').')');
	}
	
	/**
	 * Tente de router la requête courante
	 *
	 * @param iRequestParser $parser le parseur de requête
	 * @return iRequestRoute|boolean une instance de la classe si la requête a pu être mappée, false sinon
	 */
	public static function match($parser)
	{
		// Chargement de la page
		$page = Page::getByUrl($parser->getRouteQuery());
		if (!$page)
		{
			return false;
		}
		
		return new HttpPageRoute($parser, $page);
	}
	
	/**
	 * Renvoie l'objet parser de rattachement
	 *
	 * @return iRequestParser l'objet parser
	 */
	public function getParser()
	{
		return $this->_parser;
	}
	
	/**
	 * Vérifie la présence de la ressource cible, sa capacité à s'exécuter et les droits d'accès si nécessaire
	 *
	 * @return boolean|int true si la ressource demandée est accessible, ou un code d'erreur
	 */
	public function init()
	{
		// Droits d'accès
		if (!$this->_page->isAccessible())
		{
			return User::getCurrent()->isDefault() ? 401 : 403;
		}
		
		// Actions
		$this->_action = false;
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
		
		// Parties de la requête
		$baseQuery = Request::getParser()->getBaseQuery();
		$this->_pageQuery = $this->_page->getUrl();
		$this->_paramQuery = removeInitialSlash(substr($baseQuery, strlen($this->_pageQuery)));
		
		// Identifiants contenus dans la requête
		$this->_queryParams = array();
		$queryParts = array_reverse(explode('/', $this->_paramQuery));
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
	 * Effectue toutes les actions nécessaires à la cloture de la requête
	 *
	 * @return void
	 */
	public function close()
	{
		
	}
	
	/**
	 * Renvoie la partie de la requête correspondant à l'url réelle de la page
	 *
	 * @return string la requête
	 */
	public function getPageQuery()
	{
		return $this->_pageQuery;
	}
	
	/**
	 * Renvoie la partie de la requête correspondant aux paramètres additionnels
	 *
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
	 * 		- la page /edit chargée avec la requête /edit/user/1/settings va renvoyer array( 'user' => 1 )
	 *
	 * @return array les paramètres trouvés
	 */
	public function getQueryParams()
	{
		return $this->_queryParams;
	}
	
	/**
	 * Renvoie la valeur d'un identifiant contenu dans l'url si existant
	 *
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
	 *
	 * @return boolean|array la liste des action, ou false si aucune
	 */
	public function getActionRequest()
	{
		return $this->_action;
	}
	
	/**
	 * Renvoie la page en cours
	 *
	 * @return Page|boolean l'objet de la page, ou false si inexistant
	 */
	public function getPage()
	{
		return $this->_page;
	}
	
	/**
	 * Renvoie le controleur de la page en cours
	 *
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
}