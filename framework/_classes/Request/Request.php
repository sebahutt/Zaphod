<?php
/**
 * Classe de gestion de la requête en cours
 */
class Request extends StaticClass {
	/**
	 * Objet d'analyse de la requête
	 * @var RequestParser
	 */
	protected static $_parser = NULL;
	/**
	 * Routes de requête
	 * @var array
	 */
	protected static $_routes = array();
	/**
	 * Route finale de la requête
	 * @var iRoute
	 */
	protected static $_route = NULL;
	/**
	 * Gestionnaires de requête
	 * @var array
	 */
	protected static $_handlers = array();
	/**
	 * Gestionnaire final de la requête
	 * @var array
	 */
	protected static $_handler = NULL;
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
	 * Définit le parseur de requête
	 * @param iRequestParser $parser un objet implémentant l'interface iRequestParser
	 */
	public static function setParser($parser)
	{
		self::$_parser = $parser;
	}
	
	/**
	 * Crée le parseur de requête si non défini et le renvoie
	 * @param iRequestParser le parseur
	 */
	public static function getParser()
	{
		// Par défaut
		if (is_null(self::$_parser))
		{
			self::$_parser = self::isCLI() ? new CliRequestParser() : new HttpRequestParser();
		}
		
		return self::$_parser;
	}
	
	/**
	 * Ajout d'une route de requête
	 * @param iRoute $route un objet implémentant l'interface iRoute
	 */
	public static function addRoute($route)
	{
		self::$_routes[] = $route;
	}

	/**
	 * Indique si la route finale de la requête est définie
	 * @return boolean une confirmation
	 */
	public static function hasActiveRoute()
	{
		return !is_null(self::$_route);
	}

	/**
	 * Renvoie la route finale de la requête
	 * @return iRoute la route si définie, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getRoute()
	{
		return self::$_route;
	}
	
	/**
	 * Ajout d'un handler de requête
	 * @param iHandler $handler un objet implémentant l'interface iHandler
	 */
	public static function addHandler($handler)
	{
		self::$_handlers[] = $handler;
	}

	/**
	 * Indique si le handler final de la requête est défini
	 * @return boolean une confirmation
	 */
	public static function hasActiveHandler()
	{
		return !is_null(self::$_handler);
	}

	/**
	 * Renvoie le handler final de la requête
	 * @return iHandler le handler si défini, ou NULL si aucun ou pas encore déterminé
	 */
	public static function getHandler()
	{
		return self::$_handler;
	}
	
	/**
	 * Définit le gestionnaire de réponse
	 * @param iResponse $response un objet implémentant l'interface iResponse
	 */
	public static function setResponse($response)
	{
		self::$_response = $response;
	}
	
	/**
	 * Crée le gestionnaire de réponse si non défini et le renvoie
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
	 * Exécute la requête
	 * @return void
	 */
	public static function run()
	{
		// Préparation
		$parser = self::getParser();
		$response = self::getResponse();
		
		// Analyse de la requête
		if (!$parser->run())
		{
			self::abort(400);	// Bad Request
		}
		
		// Parcours des routes disponibles
		self::$_route = self::getParserRoute($parser);
		
		// Si aucune route trouvée
		if (is_null(self::$_route))
		{
			self::abort(404);	// Not Found
		}
		
		// Parcours des handlers disponibles
		self::$_handler = self::getRouteHandler(self::$_route);
		
		// Si aucun handler trouvé
		if (is_null(self::$_handler))
		{
			self::abort(501);	// Not Implemented
		}
		
		// Préparation
		$inited = self::$_route->init();
		if ($inited !== true)
		{
			self::abort($inited);
		}
		
		// Exécution
		$response->addContent(self::$_handler->exec(self::$_route));
		
		// Cloture
		self::$_route->close();
		
		// Sortie
		self::getResponse()->send();
	}
	
	/**
	 * Récupère la première route compatible avec le parser parmis celles disponibles
	 * @param iRequestParser $parser le parser
	 * @return iRoute la route trouvée, ou NULL si aucune
	 */
	public static function getParserRoute($parser)
	{
		foreach (self::$_routes as $route)
		{
			if ($route->match($parser))
			{
				return $route;
			}
		}
		
		return false;
	}
	
	/**
	 * Récupère le première handler compatible avec la route parmis ceux disponibles
	 * @param iRoute $route la route
	 * @return iHandler le handler trouvé, ou NULL si aucun
	 */
	public static function getRouteHandler($route)
	{
		foreach (self::$_handlers as $handler)
		{
			if ($handler->handles(self::$_route))
			{
				return $handler;
			}
		}
		
		return false;
	}
	
	/**
	 * Interrompt la requête en cours et envoie l'erreur correspondante
	 * @param int $error le code de l'erreur à l'origine de l'interruption (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return void
	 */
	public static function abort($error = 0, $message = '', $data = NULL)
	{
		// Fin du script
		self::getResponse()->error($error, $message, $data);
		exit();
	}
	
	/**
	 * Tente d'effectuer une redirection interne de la requête, si gérée par l'environnement de routage
	 * @param string $request la nouvelle requête à prendre en compte (facultatif, défaut : NULL)
	 * @return boolean une confirmation que la redirection a pu être effectuée
	 */
	public static function internalRedirect($request = NULL)
	{
		// Test de validité
		$parser = self::getParser();
		if (!$parser->redirect($request))
		{
			return false;
		}
		
		// Test de la validité de l'environnement
		if ($route = self::getParserRoute($parser) and $handler = self::getRouteHandler($route) and $route->redirect($parser))
		{
			self::$_route = $route;
			self::$_handler = $handler;
			self::getResponse()->addContent($handler->exec($route));
			return true;
		}
		
		return false;
	}
	
	/**
	 * Vérification de la présence d'une variable dans les données GET/POST
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
	 * Recherche dans les données GET et POST une variable et renvoie sa valeur si elle existe, sinon renvoie
	 * $defaut. Il est possible d'ignorer les variables vides (chaînes vides).
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de GET et POST (optionnel - défaut : false)
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
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de GET (optionnel - défaut : false)
	 * @param mixed $default La valeur par défaut à renvoyer (optionnel - défaut : NULL)
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return mixed Renvoi la valeur de la variable si définie, ou $default
	 */
	public static function getGet($var = false, $default = NULL, $ignoreEmpty = true)
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
	 * @param string|boolean $var Le nom de la variable à chercher, ou false pour récupérer un tableau
	 * contenant toutes les variables de POST (optionnel - défaut : false)
	 * @param mixed $default La valeur par défaut à renvoyer (optionnel - défaut : NULL)
	 * @param boolean $ignoreEmpty Indique si il faut ou non ignorer les variables vides (optionnel - défaut : true)
	 * @return mixed Renvoi la valeur de la variable si définie, ou $default
	 */
	public static function getPost($var = false, $default = NULL, $ignoreEmpty = true)
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
	 * Renvoie la chaîne de paramètres GET assemblée
	 * @param array|string $params une chaîne de paramètres ou un tableau de paramètres additionnels 
	 * (sous la forme clé => valeur) pour compléter ou modifier ceux existant (facultatif, défaut : array())
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
		$string = http_build_query(array_merge(self::getGet(), $params));
		return (strlen($string) > 0) ? '?'.$string : $string;
	}
	
	/**
	 * Efface une variable en GET et POST
	 * @param string|boolean $var le nom de la variable à effacer, ou false pour vider GET ET POST
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
			if (isset($_REQUEST[$var]))
			{
				unset($_REQUEST[$var]);
			}
		}
	}
	
	/**
	 * Efface une variable en GET
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
			$_REQUEST = $_POST;
		}
		else
		{
			// Effacement
			if (isset($_GET[$var]))
			{
				unset($_GET[$var]);
			}
			
			// Paramètres globaux
			if (isset($_POST[$var]))
			{
				$_REQUEST[$var] = $_POST[$var];
			}
			elseif (isset($_REQUEST[$var]))
			{
				// Effacement total
				unset($_REQUEST[$var]);
			}
		}
	}
	
	/**
	 * Efface une variable en POST
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
			$_REQUEST = $_GET;
		}
		else
		{
			// Effacement
			if (isset($_POST[$var]))
			{
				unset($_POST[$var]);
			}
			
			// Paramètres globaux
			if (isset($_GET[$var]))
			{
				$_REQUEST[$var] = $_GET[$var];
			}
			elseif (isset($_REQUEST[$var]))
			{
				// Effacement total
				unset($_REQUEST[$var]);
			}
		}
	}
	
	/**
	 * Renvoi le mode de la requête courante
	 * 
	 * Détermine et renvoie le mode de requête en cours selon les paramètres d'environnement
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
			elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			{
				self::$_mode = self::MODE_AJAX;
			}
			else
			{
				self::$_mode = self::MODE_WEB;
			}
		}
		
		// Renvoi
		return self::$_mode;
	}
	
	/**
	 * Indique si une requête est de type ligne de commande
	 * @return boolean une confirmation si le mode détecté est la ligne de commande
	 */
	public static function isCLI()
	{
		// Test
		return (self::getMode() == self::MODE_CLI);
	}
	
	/**
	 * Indique si une requête est de type AJAX
	 * @return boolean une confirmation si le mode détecté est AJAX
	 */
	public static function isAjax()
	{
		// Test
		return (self::getMode() == self::MODE_AJAX);
	}
	
	/**
	 * Indique si une requête est de type Web (standard)
	 * @return boolean une confirmation si le mode détecté est une requête web
	 */
	public static function isWeb()
	{
		// Test
		return (self::getMode() == self::MODE_WEB);
	}
}