<?php
/**
 * Classe de gestion de la requête en cours
 */
class Request extends StaticClass {
	/**
	 * Objets d'analyse de la requête
	 * @var array
	 */
	protected static $_parsers = array();
	/**
	 * Routes de requête
	 * @var array
	 */
	protected static $_routes = array();
	/**
	 * Gestionnaires de requête
	 * @var array
	 */
	protected static $_handlers = array();
	/**
	 * Objet final de gestion de la requête
	 * @var iRequestHandler
	 */
	protected static $_handler;
	/**
	 * Objet de gestion original de la requête (en cas de redirection interne)
	 * @var iRequestHandler
	 */
	protected static $_originalHandler;
	/**
	 * Gestionnaire de réponse
	 * @var iResponse
	 */
	protected static $_response = NULL;
	/**
	 * Mode de la requête courante
	 * @var int
	 */
	protected static $_mode;
	/**
	 * Méthode de la requête courante
	 * @var string
	 */
	protected static $_method;
	/**
	 * Headers de la requête courante
	 * @var array
	 */
	protected static $_headers;
	/**
	 * Nom des headers à récupérer en plus de ceux commençant par http-
	 * @var array
	 */
	protected static $_additionalHeaders = array('content-type', 'x-requested-with', 'server-protocol', 'request-method');
	/**
	 * Le protocole HTTP en cours
	 * @var string
	 */
	protected static $_protocol;
	/**
	 * Type de contenu attendu
	 * @var string
	 */
	protected static $_contentType;
	/**
	 * Corps de la requête courante (si défini)
	 * @var string
	 */
	protected static $_body;
	/**
	 * Données de requête PUT
	 * @var array
	 */
	protected static $_put;
	/**
	 * Indique si la requête a été redirigée
	 * @var boolean
	 */
	protected static $_internalRedirected = false;
	
	/**
	 * Mode de requête CLI
	 * @var int
	 */
	const MODE_CLI = 1;
	/**
	 * Mode de requête Web
	 * @var int
	 */
	const MODE_WEB = 2;
	/**
	 * Mode de requête AJAX
	 * @var int
	 */
	const MODE_AJAX = 3;
	/**
	 * Méthode de requête HEAD
	 * @var string
	 */
	const METHOD_HEAD = 'HEAD';
	/**
	 * Méthode de requête GET
	 * @var string
	 */
	const METHOD_GET = 'GET';
	/**
	 * Méthode de requête POST
	 * @var string
	 */
	const METHOD_POST = 'POST';
	/**
	 * Méthode de requête PUT
	 * @var string
	 */
	const METHOD_PUT = 'PUT';
	/**
	 * Méthode de requête DELETE
	 * @var string
	 */
	const METHOD_DELETE = 'DELETE';
	
	/**
	 * Initialise la classe
	 *
	 * @return void
	 */
	public static function initClass()
	{
		// Méthode
		self::$_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
		
		// Headers et corps
		self::$_body = @file_get_contents('php://input');
		self::_getPut();
	}
	
	/**
	 * Renvoie les headers de la requête
	 *
	 * @return array les headers
	 */
	public static function getHeaders()
	{
		if (!isset(self::$_headers))
		{
			self::$_headers = array();
			
			// Parcours
			foreach ($_SERVER as $name => $value)
			{
				// Détection des headers souhaités
				$name = self::_convertHeaderName($name);
				if (strpos($name, 'http-') === 0 || in_array($name, self::$_additionalHeaders))
				{
					self::$_headers[str_replace('http-', '', $name)] = $value;
				}
			}
		}
		
		return self::$_headers;
	}
	
	/**
	 * Renvoie la valeur d'un header de la requête
	 *
	 * @param string $name le nom du header
	 * @param mixed $default la valeur par défaut à renvoyer si le header n'est pas défini (facultatif, défaut : NULL)
	 * @param boolean $ignoreEmtpy indique s'il faut considérer un header vide comme non défini (facultatif, défaut : true)
	 * @return string la valeur trouvée, ou NULL si non définie
	 */
	public static function getHeader($name, $default = NULL, $ignoreEmtpy = true)
	{
		$headers = self::getHeaders();
		$name = self::_convertHeaderName($name);
		
		return (isset($headers[$name]) and (!empty($headers[$name]) or !$ignoreEmtpy)) ? $headers[$name] : $default;
	}

	/**
	 * Formatte un nom de header
	 *
	 * @param string $name le nom du header
	 * @return string le nom formatté
	 */
	protected static function _convertHeaderName($name)
	{
		return str_replace('_', '-', strtolower($name));
	}

	/**
	 * Renvoie le protocole en cours
	 *
	 * @return string le protocole
	 */
	public static function getProtocol()
	{
		if (!isset(self::$_protocol))
		{
			self::$_protocol = self::getHeader('Server-Protocol', 'HTTP/1.1');
		}

		return self::$_protocol;
	}

	/**
	 * Renvoie le type de contenu attendu
	 *
	 * @return string le type (par défaut : application/x-www-form-urlencoded)
	 */
	public static function getContentType()
	{
		if (!isset(self::$_contentType))
		{
			self::$_contentType = self::getHeader('Content-Type', 'application/x-www-form-urlencoded');
		}
		
		return self::$_contentType;
	}
	
	/**
	 * Renvoie le corps de la requête
	 *
	 * @return string le corps de la requête, ou une chaîne vide si non défini
	 */
	public static function getBody()
	{
		if (!isset(self::$_body))
		{
			self::$_body = @file_get_contents('php://input');
		}
		
		return self::$_body;
	}
	
	/**
	 * Définit le parseur de requête
	 *
	 * @param string $parser le nom d'une classe implémentant l'interface iRequestParser
	 */
	public static function addParser($parser)
	{
		self::$_parsers[] = $parser;
	}
	
	/**
	 * Renvoie le parser de la requête
	 *
	 * @param iRequestParser le parseur, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getParser()
	{
		return isset(self::$_handler) ? self::$_handler->getRoute()->getParser() : NULL;
	}
	
	/**
	 * Renvoie le parser original de la requête
	 *
	 * @param iRequestParser le parseur, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getOriginalParser()
	{
		return isset(self::$_originalHandler) ? self::$_originalHandler->getRoute()->getParser() : NULL;
	}
	
	/**
	 * Ajout d'une route de requête
	 *
	 * @param string $route le nom d'une classe implémentant l'interface iRequestRoute
	 */
	public static function addRoute($route)
	{
		self::$_routes[] = $route;
	}

	/**
	 * Renvoie la route de la requête
	 *
	 * @return iRequestRoute la route si définie, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getRoute()
	{
		return isset(self::$_handler) ? self::$_handler->getRoute() : NULL;
	}

	/**
	 * Renvoie la route originale de la requête
	 *
	 * @return iRequestRoute la route si définie, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getOriginalRoute()
	{
		return isset(self::$_originalHandler) ? self::$_originalHandler->getRoute() : NULL;
	}
	
	/**
	 * Ajout d'un handler de requête
	 *
	 * @param string $handler le nom d'une classe implémentant l'interface iRequestHandler
	 */
	public static function addHandler($handler)
	{
		self::$_handlers[] = $handler;
	}

	/**
	 * Renvoie le handler final de la requête
	 *
	 * @return iRequestHandler le handler si défini, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getHandler()
	{
		return isset(self::$_handler) ? self::$_handler : NULL;
	}

	/**
	 * Renvoie le handler original de la requête
	 *
	 * @return iRequestHandler le handler original si défini, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getOriginalHandler()
	{
		return isset(self::$_originalHandler) ? self::$_originalHandler : NULL;
	}
	
	/**
	 * Définit le gestionnaire de réponse
	 *
	 * @param iResponse $response un objet implémentant l'interface iResponse
	 */
	public static function setResponse($response)
	{
		self::$_response = $response;
	}
	
	/**
	 * Crée le gestionnaire de réponse si non défini et le renvoie
	 *
	 * @param iResponse le gestionnaire de réponse
	 */
	public static function getResponse()
	{
		// Par défaut
		if (is_null(self::$_response))
		{
			self::$_response = self::isCLI() ? new CliResponse() : new HttpResponse();
		}
		
		return self::$_response;
	}
	
	/**
	 * Récupère le premier objet d'analyse de requête compatible avec la requête en cours
	 *
	 * @param string $request la requête à analyser, ou NULL pour utiliser l'environnement
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestParser|boolean l'objet d'analyse trouvé, ou false si aucun
	 */
	public static function getRequestParser($request = NULL, $internalRedirect = false)
	{
		foreach (self::$_parsers as $class)
		{
			if ($parser = call_user_func(array($class, 'match'), $request, $internalRedirect))
			{
				return $parser;
			}
		}
		
		return false;
	}
	
	/**
	 * Récupère la première route compatible avec le parser parmis celles disponibles
	 *
	 * @param iRequestParser $parser le parser
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestRoute|boolean la route trouvée, ou false si aucune
	 */
	public static function getParserRoute($parser, $internalRedirect = false)
	{
		foreach (self::$_routes as $class)
		{
			if ($route = call_user_func(array($class, 'match'), $parser, $internalRedirect))
			{
				return $route;
			}
		}
		
		return false;
	}
	
	/**
	 * Récupère le première handler compatible avec la route parmis ceux disponibles
	 *
	 * @param iRequestRoute $route la route
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestHandler|boolean le handler trouvé, ou false si aucun
	 */
	public static function getRouteHandler($route, $internalRedirect = false)
	{
		foreach (self::$_handlers as $class)
		{
			if ($handler = call_user_func(array($class, 'handles'), $route, $internalRedirect))
			{
				return $handler;
			}
		}
		
		return false;
	}
	
	/**
	 * Exécute la requête
	 *
	 * @return void
	 */
	public static function run()
	{
		// Actions
		Log::info('Démarrage de la requête');
		Env::callActions('request.start');
		
		// Préparation
		$response = self::getResponse();
		
		// Analyse de la requête
		$parser = self::getRequestParser();
		
		// Si aucun parser compatible trouvé
		if (!$parser)
		{
			self::abort(400);	// Bad Request
		}
		
		// Parcours des routes disponibles
		$route = self::getParserRoute($parser);
		
		// Si aucune route trouvée
		if (!$route)
		{
			self::abort(404);	// Not Found
		}
		
		// Parcours des handlers disponibles
		self::$_handler = self::getRouteHandler($route);
		
		// Si aucun handler trouvé
		if (!self::$_handler)
		{
			self::abort(501);	// Not Implemented
		}
		self::$_originalHandler = self::$_handler;
		
		// Préparation
		$inited = $route->init();
		if ($inited !== true)
		{
			self::abort($inited);
		}
		
		// Exécution
		self::$_handler->begin();
		$response->setContent(self::$_handler->exec());
		self::$_handler->end();
		
		// Cloture
		$route->close();
		
		// Actions
		Env::callActions('request.end');
		Log::info('Fin de la requête');
		
		// Terminaison
		self::stop();
	}
	
	/**
	 * Interrompt la requête en cours et envoie l'erreur correspondante. Si la requête est redirigée en interne (par exemple pour afficher
	 * une page d'erreur personnalisée), il est possible d'annuler l'interruption de requête depuis la ressource redirigée
	 * en émettant une HandledErrorException.
	 *
	 * @param int $error le code de l'erreur à l'origine de l'interruption (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return void
	 */
	public static function abort($error = 0, $message = '', $data = NULL)
	{
		// Log
		Log::info('Interruption de requête, erreur '.$error.((strlen($message) > 0) ? ' - '.$message : ''));
		
		// Fin du script
		try
		{
			self::getResponse()->error($error, $message, $data);
		}
		catch (HandledErrorException $ex)
		{
			// L'erreur a été gérée, on retourne à la requête principale
			if (self::isInternalRedirected())
			{
				self::$_handler = self::$_originalHandler;
				self::$_internalRedirected = false;
			}
			
			// Retour au processus normal
			return;
		}
		catch (SCException $ex) { }
		
		// Terminaison
		self::stop();
	}
	
	/**
	 * Termine la requête
	 *
	 * @return void
	 */
	public static function stop()
	{
		// Sortie
		self::getResponse()->end();
		
		// Actions
		Env::callActions('request.stop');
		Log::info('Terminaison du script');
		
		exit();
	}
	
	/**
	 * Tente d'effectuer une redirection interne de la requête et de l'exécuter, si géré par l'environnement de routage
	 *
	 * @param string $request l'url de la ressource de redirection
	 * @return boolean une confirmation que la redirection a pu être effectuée
	 */
	public static function internalRedirect($request)
	{
		// Log
		Log::info('Tentative de redirection interne vers \''.$request.'\'');
		
		// Test de la validité de l'environnement
		if ($parser = self::getRequestParser($request, true) and $route = self::getParserRoute($parser, true) and $handler = self::getRouteHandler($route, true) and $route->init())
		{
			// Log
			Log::info('Redirection interne valide');
			
			// Indicateur
			self::$_internalRedirected = true;
			
			self::$_handler = $handler;
			self::$_handler->begin();
			self::getResponse()->setContent(self::$_handler->exec());
			self::$_handler->end();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Indique si la requête a été redirigée en interne
	 * @return boolean une confirmation
	 */
	public static function isInternalRedirected()
	{
		return self::$_internalRedirected;
	}
	
	/**
	 * Vérification de la présence d'une variable dans les données GET/POST
	 *
	 * @param string $var Le nom de la variable à chercher
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return boolean Confirmation ou non de la présence de la variable
	 */
	public static function issetParam($var, $ignoreEmpty = true)
	{
		// Si défini
		if (isset($_REQUEST[$var]) and (!$ignoreEmpty or is_array($_REQUEST[$var]) or strlen(trim($_REQUEST[$var])) > 0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Vérification de la présence d'une variable dans les données GET
	 *
	 * @param string $var Le nom de la variable à chercher
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return boolean Confirmation ou non de la présence de la variable
	 */
	public static function issetGET($var, $ignoreEmpty = true)
	{
		// Si défini
		if (isset($_GET[$var]) and (!$ignoreEmpty or is_array($_GET[$var]) or strlen(trim($_GET[$var])) > 0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Vérification de la présence d'une variable dans les données POST
	 *
	 * @param string $var Le nom de la variable à chercher
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return boolean Confirmation ou non de la présence de la variable
	 */
	public static function issetPOST($var, $ignoreEmpty = true)
	{
		// Si défini
		if (isset($_POST[$var]) and (!$ignoreEmpty or is_array($_POST[$var]) or strlen(trim($_POST[$var])) > 0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Vérification de la présence d'une variable dans les données PUT
	 *
	 * @param string $var Le nom de la variable à chercher
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return boolean Confirmation ou non de la présence de la variable
	 */
	public static function issetPUT($var, $ignoreEmpty = true)
	{
		$put = self::_getPut();
		
		// Si défini
		if (isset($put[$var]) and (!$ignoreEmpty or is_array($put[$var]) or strlen(trim($put[$var])) > 0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Recherche dans les données PUT, POST et GET une variable et renvoie sa valeur si elle existe, sinon renvoie
	 * $defaut. Il est possible d'ignorer les variables vides (chaînes vides).
	 *
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de GET et POST (optionnel - défaut : false)
	 *
	 * @param mixed $default La valeur par défaut à renvoyer (optionnel - défaut : NULL)
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return mixed Renvoi la valeur de la variable si définie, ou $default
	 */
	public static function getParam($var = false, $default = NULL, $ignoreEmpty = true)
	{
		// Si pas de variable
		if (!$var)
		{
			return $_REQUEST;
		}
		else
		{
			// Recherche
			if (isset($_REQUEST[$var]) and (!$ignoreEmpty or is_array($_REQUEST[$var]) or strlen(trim($_REQUEST[$var])) > 0))
			{
				return $_REQUEST[$var];
			}
			else
			{
				return $default;
			}
		}
	}
	
	/**
	 * Recherche dans les données GET une variable et renvoie sa valeur si elle existe, sinon renvoie
	 * $defaut. Il est possible d'ignorer les variables vides (chaînes vides).
	 *
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de GET (optionnel - défaut : false)
	 *
	 * @param mixed $default La valeur par défaut à renvoyer (optionnel - défaut : NULL)
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return mixed Renvoi la valeur de la variable si définie, ou $default
	 */
	public static function getGET($var = false, $default = NULL, $ignoreEmpty = true)
	{
		// Si pas de variable
		if (!$var)
		{
			return $_GET;
		}
		else
		{
			// Recherche
			if (isset($_GET[$var]) and (!$ignoreEmpty or is_array($_GET[$var]) or strlen(trim($_GET[$var])) > 0))
			{
				return $_GET[$var];
			}
			else
			{
				return $default;
			}
		}
	}
	
	/**
	 * Recherche dans les données POST une variable et renvoie sa valeur si elle existe, sinon renvoie
	 * $defaut. Il est possible d'ignorer les variables vides (chaînes vides).
	 *
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de POST (optionnel - défaut : false)
	 *
	 * @param mixed $default La valeur par défaut à renvoyer (optionnel - défaut : NULL)
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return mixed Renvoi la valeur de la variable si définie, ou $default
	 */
	public static function getPOST($var = false, $default = NULL, $ignoreEmpty = true)
	{
		// Si pas de variable
		if (!$var)
		{
			return $_POST;
		}
		else
		{
			// Recherche
			if (isset($_POST[$var]) and (!$ignoreEmpty or is_array($_POST[$var]) or strlen(trim($_POST[$var])) > 0))
			{
				return $_POST[$var];
			}
			else
			{
				return $default;
			}
		}
	}
	
	/**
	 * Recherche dans les données PUT une variable et renvoie sa valeur si elle existe, sinon renvoie
	 * $defaut. Il est possible d'ignorer les variables vides (chaînes vides).
	 *
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de PUT (optionnel - défaut : false)
	 *
	 * @param mixed $default La valeur par défaut à renvoyer (optionnel - défaut : NULL)
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return mixed Renvoi la valeur de la variable si définie, ou $default
	 */
	public static function getPUT($var = false, $default = NULL, $ignoreEmpty = true)
	{
		$put = self::_getPut();
		
		// Si pas de variable
		if (!$var)
		{
			return $put;
		}
		else
		{
			// Recherche
			if (isset($put[$var]) and (!$ignoreEmpty or is_array($put[$var]) or strlen(trim($put[$var])) > 0))
			{
				return $put[$var];
			}
			else
			{
				return $default;
			}
		}
	}
	
	/**
	 * Définit une variable en POST
	 *
	 * @param string|boolean $var le nom de la variable à modifier, ou false pour définir $_POST en entier
	 * @param mixed $value la valeur à affecter (doit être un tableau si $var vaut false)
	 * @return void
	 */
	public static function setPOST($var, $value)
	{
		// Mode
		if (is_bool($var))
		{
			// Ecrasement
			$_POST = (array)$value;
		}
		else
		{
			$_POST[$var] = $value;
		}
		
		// Reconstruction
		$_REQUEST = array_merge($_GET, $_POST, self::_getPut());
		
		// Actions
		Env::callActions('request.changepost');
	}
	
	/**
	 * Définit une variable en GET
	 *
	 * @param string|boolean $var le nom de la variable à modifier, ou false pour définir $_GET en entier
	 * @param mixed $value la valeur à affecter (doit être un tableau si $var vaut false)
	 * @return void
	 */
	public static function setGET($var, $value)
	{
		// Mode
		if (is_bool($var))
		{
			// Ecrasement
			$_GET = (array)$value;
		}
		else
		{
			$_GET[$var] = $value;
		}
		
		// Reconstruction
		$_REQUEST = array_merge($_GET, $_POST, self::_getPut());
		
		// Actions
		Env::callActions('request.changeget');
	}
	
	/**
	 * Définit une variable en PUT
	 *
	 * @param string|boolean $var le nom de la variable à modifier, ou false pour définir $_PUT en entier
	 * @param mixed $value la valeur à affecter (doit être un tableau si $var vaut false)
	 * @return void
	 */
	public static function setPUT($var, $value)
	{
		// Mode
		if (is_bool($var))
		{
			// Ecrasement
			self::$_put = (array)$value;
		}
		else
		{
			self::$_put[$var] = $value;
		}
		
		// Reconstruction
		$_REQUEST = array_merge($_GET, $_POST, self::$_put);
		
		// Actions
		Env::callActions('request.changeput');
	}
	
	/**
	 * Efface une variable en PUT, POST et GET
	 *
	 * @param string|boolean $var le nom de la variable à effacer, ou false pour vider PUT, POST et GET
	 * @return void
	 */
	public static function clearParam($var = false)
	{
		// Mode
		if (is_bool($var))
		{
			// Effacement
			$_GET = array();
			$_POST = array();
			self::$_put = array();
			$_REQUEST = array();
		}
		else
		{
			// Effacement
			if (isset($_GET[$var]))
			{
				unset($_GET[$var]);
			}
			if (isset($_POST[$var]))
			{
				unset($_POST[$var]);
			}
			if (isset(self::$_put[$var]))
			{
				unset(self::$_put[$var]);
			}
			if (isset($_REQUEST[$var]))
			{
				unset($_REQUEST[$var]);
			}
		}
		
		// Actions
		Env::callActions('request.changeparam');
	}
	
	/**
	 * Efface une variable en GET
	 *
	 * @param string $var le nom de la variable à effacer, ou false pour vider GET
	 * @return void
	 */
	public static function clearGET($var)
	{
		// Mode
		if (is_bool($var))
		{
			// Effacement
			$_GET = array();
			$_REQUEST = array_merge($_POST, self::_getPut());
		}
		else
		{
			// Effacement
			if (isset($_GET[$var]))
			{
				unset($_GET[$var]);
			}
			
			// Paramètres globaux
			$put = self::_getPut();
			if (isset($put[$var]))
			{
				$_REQUEST[$var] = $put[$var];
			}
			elseif (isset($_POST[$var]))
			{
				$_REQUEST[$var] = $_POST[$var];
			}
			elseif (isset($_REQUEST[$var]))
			{
				// Effacement total
				unset($_REQUEST[$var]);
			}
		}
		
		// Actions
		Env::callActions('request.changeget');
	}
	
	/**
	 * Efface une variable en POST
	 *
	 * @param string $var le nom de la variable à effacer, ou false pour vider POST
	 * @return void
	 */
	public static function clearPOST($var)
	{
		// Mode
		if (is_bool($var))
		{
			// Effacement
			$_POST = array();
			$_REQUEST = array_merge($_GET, self::_getPut());
		}
		else
		{
			// Effacement
			if (isset($_POST[$var]))
			{
				unset($_POST[$var]);
			}
			
			// Paramètres globaux
			$put = self::_getPut();
			if (isset($put[$var]))
			{
				$_REQUEST[$var] = $put[$var];
			}
			elseif (isset($_GET[$var]))
			{
				$_REQUEST[$var] = $_GET[$var];
			}
			elseif (isset($_REQUEST[$var]))
			{
				// Effacement total
				unset($_REQUEST[$var]);
			}
		}
		
		// Actions
		Env::callActions('request.changepost');
	}
	
	/**
	 * Efface une variable en PUT
	 *
	 * @param string $var le nom de la variable à effacer, ou false pour vider PUT
	 * @return void
	 */
	public static function clearPUT($var)
	{
		// Mode
		if (is_bool($var))
		{
			// Effacement
			self::$_put = array();
			$_REQUEST = array_merge($_GET, $_POST);
		}
		else
		{
			// Effacement
			if (isset(self::$_put[$var]))
			{
				unset(self::$_put[$var]);
			}
			
			// Paramètres globaux
			if (isset($_POST[$var]))
			{
				$_REQUEST[$var] = $_POST[$var];
			}
			elseif (isset($_GET[$var]))
			{
				$_REQUEST[$var] = $_GET[$var];
			}
			elseif (isset($_REQUEST[$var]))
			{
				// Effacement total
				unset($_REQUEST[$var]);
			}
		}
		
		// Actions
		Env::callActions('request.changeput');
	}
	
	/**
	 * Récupère les données de requête PUT
	 *
	 * @return array les données
	 */
	protected static function _getPut()
	{
		if (!isset(self::$_put))
		{
			self::$_put = array();
			
			// Si mode correspondant
			if (self::getContentType() === 'application/x-www-form-urlencoded')
			{
				// Analyse du corps du message
				$body = self::getBody();
				if (strlen($body) > 0)
				{
					// Décodage
					if (function_exists('mb_parse_str'))
					{
						mb_parse_str($body, self::$_put);
					}
					else
					{
						parse_str($body, self::$_put);
					}
					
					// Configuration
					if (function_exists('stripslashes_deep'))
					{
						self::$_put = array_map('stripslashes_deep', self::$_put);
					}
					
					// Mise à jour de $_REQUEST
					$_REQUEST = array_merge($_REQUEST, self::$_put);
				}
			}
		}
		
		return self::$_put;
	}
	
	/**
	 * Renvoie la chaîne de paramètres GET assemblée
	 *
	 * @param array|string $params une chaîne de paramètres ou un tableau de paramètres additionnels
	 * (sous la forme clé => valeur) pour compléter ou modifier ceux existant (facultatif, défaut : array())
	 *
	 * @return string la chaîne de paramètres, avec le ? initial si nécessaire
	 */
	public static function getQueryString($params = array())
	{
		// Type des paramètres
		if (is_string($params))
		{
			// Découpe
			parse_str($params, $params);
		}
		
		// Asssemblage
		$string = http_build_query(array_merge(self::getGET(), $params));
		return (strlen($string) > 0) ? '?'.$string : $string;
	}
	
	/**
	 * Détermine et renvoie le mode de requête en cours selon les paramètres d'environnement
	 *
	 * @return int la constante correspondant au mode en cours :
	 * 	 - Request::MODE_CLI : mode autonome de php
	 * 	 - Request::MODE_WEB : mode http standard (module Apache)
	 * 	 - Request::MODE_AJAX : mode AJAX
	 */
	public static function getMode()
	{
		// Si pas encore défini
		if (!isset(self::$_mode))
		{
			// Si mode CLI
			if (!isset($_SERVER))
			{
				self::$_mode = self::MODE_CLI;
			}
			elseif (strtolower(self::getHeader('x-requested-with')) == 'xmlhttprequest')
			{
				self::$_mode = self::MODE_AJAX;
			}
			else
			{
				self::$_mode = self::MODE_WEB;
			}
		}
		
		return self::$_mode;
	}
	
	/**
	 * Indique si une requête est de type ligne de commande
	 *
	 * @return boolean une confirmation
	 */
	public static function isCLI()
	{
		// Test
		return (self::getMode() == self::MODE_CLI);
	}
	
	/**
	 * Indique si une requête est de type AJAX
	 *
	 * @return boolean une confirmation
	 */
	public static function isAjax()
	{
		// Test
		return (self::getMode() == self::MODE_AJAX);
	}
	
	/**
	 * Indique si une requête est de type Web (standard)
	 *
	 * @return boolean une confirmation
	 */
	public static function isWeb()
	{
		// Test
		return (self::getMode() == self::MODE_WEB);
	}
	
	/**
	 * Renvoie la méthode de requête courante
	 *
	 * @return string|boolean false si non défini, ou la constante correspondant à la méthode en cours :
	 * 	 - Request::METHOD_HEAD
	 * 	 - Request::METHOD_GET
	 * 	 - Request::METHOD_POST
	 * 	 - Request::METHOD_PUT
	 * 	 - Request::METHOD_DELETE
	 */
	public static function getMethod()
	{
		if (!isset(self::$_mode))
		{
			self::$_method = self::getHeader('request-method', false);
		}
		
		return self::$_method;
	}
	
	/**
	 * Indique si une requête utilise la méthode GET
	 *
	 * @return boolean une confirmation
	 */
	public static function isGet()
	{
		return (self::getMethod() === self::METHOD_GET);
	}

	/**
	 * Indique si une requête utilise la méthode POST
	 *
	 * @return boolean une confirmation
	 */
	public static function isPost()
	{
		return (self::getMethod() === self::METHOD_POST);
	}

	/**
	 * Indique si une requête utilise la méthode PUT
	 *
	 * @return boolean une confirmation
	 */
	public static function isPut()
	{
		return (self::getMethod() === self::METHOD_PUT);
	}

	/**
	 * Indique si une requête utilise la méthode DELETE
	 *
	 * @return boolean une confirmation
	 */
	public static function isDelete()
	{
		return (self::getMethod() === self::METHOD_DELETE);
	}

	/**
	 * Indique si une requête utilise la méthode HEAD
	 *
	 * @return boolean une confirmation
	 */
	public static function isHead()
	{
		return (self::getMethod() === self::METHOD_HEAD);
	}
}

/**
 * Classe d'exception pour indiquer un affichage d'erreur qui n'est plus nécessaire
 */
class HandledErrorException extends SCException {}