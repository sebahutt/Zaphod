<?php
/**
 * Gestionnaire de requête de page web
 */
abstract class WebRequest {
	/**
	 * La chaîne de la requête en cours, avec ses paramètres GET éventuels
	 * @var string
	 */
	protected $_request;
	/**
	 * Chaine originale de la requête
	 * @var string
	 */
	protected $_query;
	/**
	 * Chaine originale de la requête, sans paramètres GET
	 * @var string
	 */
	protected $_baseQuery;
	
	/**
	 * Initialise la requête en cours, et prépare les données
	 * @return boolean true si la requête est correctement initialisée, false sinon
	 */
	public function init()
	{
		// Requête en cours
		$request = $this->getRequest();
		
		// Traitement
		$request = removeInitialSlash(removeTrailingSlash($request));
		if (strlen(URL_FOLDER) > 0 and strpos($request, URL_FOLDER) === 0)
		{
			$request = substr($request, strlen(URL_FOLDER));
		}
		$queryParts = explode('?', $request, 2);
		$this->_query = array_shift($queryParts);
		$this->_baseQuery = $this->_query;
		$this->_query .= Request::getQueryString();
	}
	
	/**
	 * Renvoie la chaîne de la requête en cours, avec ses paramètres GET éventuels
	 * @return string la requête
	 */
	public function getRequest()
	{
		if (!isset($this->_request))
		{
			$this->_request = substr($_SERVER['REQUEST_URI'], strlen(URL_FOLDER));
		}
		
		return $this->_request;
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
	public function getQueryAction($params = array())
	{
		$query = $this->getQuery($params);
		return strlen($query) ? $query : './';
	}
	
	/**
	 * Renvoie la requête sans les paramètres
	 * @return string la requête
	 */
	public function getBaseQuery()
	{
		return $this->_baseQuery;
	}	
	
	/**
	 * Envoie un header location de redirection
	 * @param string $target la page à charger
	 * @return void
	 */
	public function redirect($target)
	{
		// Mode
		if (preg_match('/^[0-9a-z]+:\//i', $target))
		{
			header('Location: '.$target);
		}
		else
		{
			header('Location: '.URL_BASE.$target);
		}
		exit();
	}
	
	/**
	 * Cherche si une page de redirection est définie ('redirect' en GET ou POST), sinon retourne à la page précédente, 
	 * ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default l'url par défaut si aucune page précédente n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public function redirectOrGoBack($default = '', $append = '')
	{
		if (Request::issetParam('redirect'))
		{
			$this->redirect(trim(Request::getParam('redirect').$append));
		}
		else
		{
			History::goBack($default);
		}
	}
	
	/**
	 * Cherche si une page de redirection est définie ('redirect' en GET ou POST), sinon va à la page par défaut
	 * @param string $default l'url par défaut si aucune page de redirection n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public function redirectOrGo($default = '', $append = '')
	{
		if (Request::issetParam('redirect'))
		{
			$this->redirect(trim(Request::getParam('redirect').$append));
		}
		else
		{
			$this->redirect($default);
		}
	}
	
	/**
	 * Envoie un header 403 - accès refusé
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public function header403()
	{
		header(Request::getProtocol().' 403 Forbidden', true, 403);
		exit();
	}
	
	/**
	 * Envoie un header 404 - non trouvé
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public function header404()
	{
		header(Request::getProtocol().' 404 Not Found', true, 404);
		exit();
	}
	
	/**
	 * Envoie un header 500 - erreur interne
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public function header500()
	{
		header(Request::getProtocol().' 500 Internal Server Error', true, 500);
		exit();
	}
}