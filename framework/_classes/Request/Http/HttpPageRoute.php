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
	 * Indique s'il s'agit d'une redirection interne
	 * @var boolean
	 */
	protected $_internalRedirect;
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
	 * Partie additionnelle de la requête par rapport à l'url de la page
	 * @var string
	 */
	protected $_extraQuery = '';
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
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 */
	public function __construct($parser, $page, $internalRedirect = false)
	{
		// Mémorisation
		$this->_parser = $parser;
		$this->_page = $page;
		$this->_internalRedirect = $internalRedirect;
		
		// Log
		Log::info('Route active : HttpPageRoute ('.$this->_page->get('id_page').')');
	}
	
	/**
	 * Tente de router la requête courante
	 *
	 * @param iRequestParser $parser le parseur de requête
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestRoute|boolean une instance de la classe si la requête a pu être mappée, false sinon
	 */
	public static function match($parser, $internalRedirect = false)
	{
		// Chargement de la page
		$page = Page::getByUrl($parser->getRouteQuery());
		if (!$page)
		{
			return false;
		}
		
		return new HttpPageRoute($parser, $page, $internalRedirect);
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
		// Identifiants contenus dans la requête
		$routeQuery = $this->_parser->getRouteQuery();
		$this->_queryParams = $this->_page->extractUrlParams();
		
		// Parties de la requête
		$baseQuery = Request::getParser()->getBaseQuery();
		$this->_pageQuery = $this->_page->getUrl($this->_queryParams);
		$this->_extraQuery = removeInitialSlash(substr($baseQuery, strlen($this->_pageQuery)));
		
		/*
		 * Détection de slash terminal absent (élimine les risques de duplicate content)
		 * Ne s'applique pas :
		 * - en cas de redirection interne (n'est pas visible dans l'url)
		 * - si le chemin est vide (page d'accueil)
		 * - si la page utilise une requête étendue
		 * - si la réponse en cours ne supporte pas les redirections
		 */
		if (!$this->_internalRedirect and strlen($routeQuery) > 0 and strlen($this->_extraQuery) === 0 and substr($routeQuery, -1) !== '/' and method_exists(Request::getResponse(), 'redirect'))
		{
			// Log
			Log::info('Redirection pour éviter le duplicate content');
			
			Request::getResponse()->redirect(addTrailingSlash($routeQuery).Request::getQueryString(), 301);
		}
		
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
	 * Renvoie la partie additionnelle de la requête par rapport à l'url de la page
	 *
	 * @return string la partie additionnelle
	 */
	public function getExtraQuery()
	{
		return $this->_extraQuery;
	}
	
	/**
	 * Renvoie les paramètres contenus dans le chemin de la requête elle-même
	 * par exemple, la page /edit/user/:id chargée avec la requête /edit/user/1 va renvoyer array( 'id' => 1 )
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