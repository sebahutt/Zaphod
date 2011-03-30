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
	 * Tente d'analyser la requête
	 * @return boolean la confirmation que la requête a pu être traitée
	 */
	public function run()
	{
		// Vérification
		if (!isset($_SERVER['REQUEST_URI']))
		{
			return false;
		}
		
		// Mémorisation
		$this->_request = $_SERVER['REQUEST_URI'];
		
		// Traitement
		$this->_request = removeInitialSlash($this->_request);
		if (strlen(URL_FOLDER) > 0 and strpos($this->_request, URL_FOLDER) === 0)
		{
			$this->_request = substr($this->_request, strlen(URL_FOLDER));
		}
		$this->_request = removeTrailingSlash($this->_request);
		
		// Recomposition de la requête sans les paramètres système
		$queryParts = explode('?', $this->_request, 2);
		$this->_baseQuery = array_shift($queryParts);
		$this->_query = $this->_baseQuery.Request::getQueryString();
		
		// Route finale
		$this->_rewriteString = Request::getGET('__rewrite', false, false);
		$this->_routeQuery = ($this->_routeQuery !== false) ? $this->_routeQuery : $this->_baseQuery;
		
		return true;
	}
	
	/**
	 * Tente d'effectuer la redirection interne de la requête. Si la redirection échoue, la configuration initiale est conservée.
	 * Les références à la requête initiale sont conservées (url, etc...), seules les fonctions relatives au routage
	 * doivent être impactées (getBaseQuery, par exemple)
	 * @param string $request la nouvelle requête à prendre en compte
	 * @return boolean la confirmation que la requête a pu être traitée
	 */
	public function redirect($request)
	{
		// Remplacement
		$request = removeInitialSlash(removeTrailingSlash($request));
		if (strlen(URL_FOLDER) > 0 and strpos($request, URL_FOLDER) === 0)
		{
			$request = substr($request, strlen(URL_FOLDER));
		}
		
		$queryParts = explode('?', $request, 2);
		$this->_routeQuery = array_shift($queryParts);
		
		return true;
	}
	
	/**
	 * Renvoie la chaîne de la requête en cours, avec ses paramètres GET éventuels
	 * @return string la requête
	 */
	public function getRequest()
	{
		return $this->_request;
	}
	
	/**
	 * Renvoie la chaîne de réécriture, si définie
	 * @return string la chaîne de réécriture, ou false si non définie
	 */
	public function getRewriteString()
	{
		return $this->_rewriteString;
	}
	
	/**
	 * Renvoie la requête complète en cours
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
	 * @param array $params un tableau associatif des paramètres à ajouter/modifier par rapport à la requête originale (facultatif)
	 * @return string la requête adaptée
	 */
	public function getQueryForForm($params = array())
	{
		$query = $this->getQuery($params);
		return strlen($query) ? $query : './';
	}
	
	/**
	 * Renvoie la requête de base, sans paramètres, qui sert de référence
	 * @return string la requête
	 */
	public function getBaseQuery()
	{
		return $this->_baseQuery;
	}
	
	/**
	 * Renvoie la requête de référence pour le routage
	 * @return string la requête
	 */
	public function getRouteQuery()
	{
		return $this->_routeQuery;
	}
}