<?php
/**
 * Objet d'analyse de requête HTTP
 */
class HttpRequestParser implements iRequestParser {
	/**
	 * La chaîne de la requête en cours, avec ses paramètres GET éventuels
	 * @var string
	 */
	protected $_request;
	/**
	 * Chaine nettoyée de la requête
	 * @var string
	 */
	protected $_query;
	/**
	 * Chaine nettoyée de la requête, sans paramètres GET
	 * @var string
	 */
	protected $_baseQuery;
	/**
	 * Chaine finale nettoyée pour le routage de la requête
	 * @var string
	 */
	protected $_routeQuery;
	/**
	 * Chaîne de réécriture si existante, false sinon
	 * @var string|boolean
	 */
	protected $_rewriteString;
	
	/**
	 * Constructeur
	 * @param string $request la requête à analyser
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 */
	public function __construct($request, $internalRedirect = false)
	{
		// Mémorisation
		$this->_request = $request;
		
		// Traitement
		$this->_request = removeInitialSlash($this->_request);
		if (strlen(URL_FOLDER) > 0 and strpos($this->_request, URL_FOLDER) === 0)
		{
			$this->_request = substr($this->_request, strlen(URL_FOLDER));
		}
		
		// Recomposition de la requête sans les paramètres système
		$queryParts = explode('?', $this->_request, 2);
		$this->_baseQuery = array_shift($queryParts);
		
		// Route finale
		$this->_rewriteString = Request::getGET('__rewrite', false, false);
		Request::clearGET('__rewrite');
		$this->_routeQuery = ($this->_rewriteString !== false) ? $this->_rewriteString : $this->_baseQuery;
		$this->_query = $this->_baseQuery.Request::getQueryString();
		
		// Log
		Log::info('Chemin de routage : \''.$this->_routeQuery.'\'');
		
		// Ecouteur de changement de paramètres GET
		Env::addAction('request.changeget', array($this, 'updateQueryString'));
	}
	
	/**
	 * Vérifie si la classe courante est en mesure d'analyser la requête
	 *
	 * @param string $request la requête à analyser, ou NULL pour utiliser l'environnement
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestParser|boolean une instance de la classe si la requête peut être gérée, false sinon
	 */
	public static function match($request = NULL, $internalRedirect = false)
	{
		// Vérification
		if (is_null($request))
		{
			if (!isset($_SERVER['REQUEST_URI']))
			{
				return false;
			}
			$request = $_SERVER['REQUEST_URI'];
		}
		
		return new HttpRequestParser($request, $internalRedirect);
	}
	
	/**
	 * Met à jour la chaîne de requête GET
	 * @return void
	 */
	public function updateQueryString()
	{
		$this->_query = $this->_baseQuery.Request::getQueryString();
	}
	
	/**
	 * Renvoie la chaîne de la requête en cours, avec ses paramètres GET éventuels
	 *
	 * @return string la requête
	 */
	public function getRequest()
	{
		return $this->_request;
	}
	
	/**
	 * Renvoie la chaîne de réécriture, si définie
	 *
	 * @return string la chaîne de réécriture, ou false si non définie
	 */
	public function getRewriteString()
	{
		return $this->_rewriteString;
	}
	
	/**
	 * Renvoie la requête complète en cours
	 *
	 * @param array $params un tableau associatif des paramètres à ajouter/modifier par rapport à la requête originale (facultatif)
	 * @return string la requête
	 */
	public function getQuery($params = array())
	{
		if (!is_array($params) or count($params) === 0)
		{
			return $this->_query;
		}
		else
		{
			return $this->_baseQuery.Request::getQueryString($params);
		}
	}
	
	/**
	 * Renvoie la requête complète, formattée pour le champ action d'un formulaire
	 *
	 * @param array $params un tableau associatif des paramètres à ajouter/modifier par rapport à la requête originale (facultatif)
	 * @return string la requête adaptée
	 */
	public function getQueryForForm($params = array())
	{
		$query = $this->getQuery($params);
		return strlen($query) ? '/'.$query : './';
	}
	
	/**
	 * Renvoie la requête de base, sans paramètres, qui sert de référence
	 *
	 * @return string la requête
	 */
	public function getBaseQuery()
	{
		return $this->_baseQuery;
	}
	
	/**
	 * Renvoie la requête de référence pour le routage
	 *
	 * @return string la requête
	 */
	public function getRouteQuery()
	{
		return $this->_routeQuery;
	}
}