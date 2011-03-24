<?php
/**
 * Classe d'accès aux données de requête
 */
class Request extends StaticClass {
	/**
	 * Chaine originale de la requête
	 * @var string
	 */
	protected static $_query = '';
	/**
	 * Page chargée par la requête
	 * @var Page
	 */
	protected static $_page = false;
	/**
	 * Controleur de la page chargée par la requête
	 * @var DefaultControler
	 */
	protected static $_controler;
	/**
	 * Chaine originale de la requête, sans paramètres GET
	 * @var string
	 */
	protected static $_baseQuery = '';
	/**
	 * Partie de la requête correspondant à l'url réelle de la page
	 * @var string
	 */
	protected static $_pageQuery = '';
	/**
	 * Partie de la requête correspondant aux paramètres additionnels
	 * @var string
	 */
	protected static $_paramQuery = '';
	/**
	 * Identifiants contenus dans la requête
	 * @var array
	 */
	protected static $_queryParams = array();
	/**
	 * Mode de la requête courante
	 * @var int
	 */
	protected static $_mode;
	/**
	 * Le protocole HTTP en cours
	 * @var string
	 */
	protected static $_protocol;
	/**
	 * Données d'actions pré-requête
	 * @var array
	 */
	protected static $_action = false;
	/**
	 * Données de la requête AJAX
	 * @var array
	 */
	protected static $_ajax = false;
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
	 * Initialise la requête en cours
	 * @return void
	 */
	public static function initClass()
	{
		// Actions
		if (self::issetParam('__action'))
		{
			// Récupération
			self::$_action = self::getParam('__action');
			
			// Formattage et sécurisation
			if (!is_array(self::$_action))
			{
				// Si valide
				if (strlen(trim(self::$_action)) > 0)
				{
					self::$_action = array(self::$_action);
				}
				else
				{
					self::$_action = false;
				}
			}
			elseif (count(self::$_action) == 0 or strlen(trim(self::$_action[0])) == 0)
			{
				self::$_action = false;
			}
		}
		
		// Ajax
		if (self::issetParam('__ajax'))
		{
			// Récupération
			self::$_ajax = self::getParam('__ajax');
			
			// Formattage et sécurisation
			if (!is_array(self::$_ajax))
			{
				// Si valide
				if (strlen(trim(self::$_ajax)) > 0)
				{
					self::$_ajax = array(self::$_ajax);
				}
				else
				{
					self::$_ajax = false;
				}
			}
			elseif (count(self::$_ajax) == 0 or strlen(trim(self::$_ajax[0])) == 0)
			{
				self::$_ajax = false;
			}
		}
		
		// Nettoyage
		self::clearGet('rewriteLink');
		self::clearGet('__action');
		self::clearGet('__ajax');
		self::clearGet('logout');
		
		// Requête en cours
		$request = isset($_GET['rewriteLink']) ? trim($_GET['rewriteLink']) : '';
		
		// Nettoyage
		$request = removeTrailingSlash($request);
		if (strlen(URL_FOLDER) > 0 and strpos($request, URL_FOLDER) === 0)
		{
			$request = substr($request, strlen(URL_FOLDER));
		}
		$queryParts = explode('?', $request);
		self::$_query = '/'.array_shift($queryParts);
		self::$_baseQuery = self::$_query;
		self::$_query .= self::getQueryString();
		
		// Chargement de la page
		self::$_page = Page::getByUrl($request);
		if (!self::$_page)
		{
			Request::header404();
		}
		
		// Parties de la requête
		self::$_pageQuery = '/'.self::$_page->getUrl();
		self::$_paramQuery = removeInitialSlash(substr(self::$_baseQuery, strlen(self::$_pageQuery)));
		
		// Identifiants contenus dans la requête
		self::$_queryParams = array();
		$queryParts = array_reverse(explode('/', self::$_baseQuery));
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
					self::$_queryParams[$part] = $queryId;
				}
			}
		}
		
		// Si retour en arrière
		if (isset($_GET['__back']) and $_GET['__back'] == 1)
		{
			// Nettoyage de l'historique
			History::trim();
			self::clearGet('__back');
		}
	}
	
	/**
	 * Renvoie la requête complète en cours
	 * @param array $params un tableau associatif des paramètres à ajouter/modifier par rapport à la requête originale (facultatif)
	 * @return string la requête
	 */
	public static function getQuery($params = array())
	{
		if (!is_array($params) or count($params) === 0)
		{
			return self::$_query;
		}
		else
		{
			return self::$_baseQuery.self::getQueryString($params);
		}
	}
	
	/**
	 * Renvoie la page en cours
	 * @return Page|boolean l'objet de la apge, ou false si inexistant
	 */
	public static function getPage()
	{
		return self::$_page;
	}
	
	/**
	 * Renvoie le controleur de la page en cours
	 * @return DefaultControler le controleur
	 */
	public static function getControler()
	{
		if (!isset(self::$_controler))
		{
			// Test si controleur existant
			if (self::$_page->hasControler())
			{
				// Chargement
				require(self::$_page->getControlerPath());
				self::$_controler = new PageControler(self::$_page);
			}
			else
			{
				self::$_controler = new AppControler(self::$_page);
			}
		}
		
		return self::$_controler;
	}
	
	/**
	 * Renvoie la requête sans les paramètres
	 * @return string la requête
	 */
	public static function getBaseQuery()
	{
		return self::$_baseQuery;
	}
	
	/**
	 * Renvoie la partie de la requête correspondant à l'url réelle de la page
	 * @return string la requête
	 */
	public static function getPageQuery()
	{
		return self::$_pageQuery;
	}
	
	/**
	 * Renvoie la partie de la requête correspondant aux paramètres additionnels
	 * @return string la requête
	 */
	public static function getParamQuery()
	{
		return self::$_paramQuery;
	}
	
	/**
	 * Renvoie les paramètres contenus dans le chemin de la requête elle-même
	 * par exemple :
	 * 		- la page /edit chargée avec la requête /edit/user/1 va renvoyer array( 'user' => 1 )
	 * 		- la page /list chargée avec la requête /list/group/1/page/2 va renvoyer array( 'group' => 1, 'page' => 2 )
	 * 		- la page /edit chargée avec la requête /edit/user/settings/1 va renvoyer array( 'user' => 1, 'settings' => 1 )
	 * @return array les paramètres trouvés
	 */
	public static function getQueryParams()
	{
		return self::$_queryParams;
	}
	
	/**
	 * Renvoie la valeur d'un identifiant contenu dans l'url si existant
	 * @param string $name le nom de l'identifiant (partie précédente de l'url)
	 * @param mixed $default la valeur par défaut
	 * @return mixed la valeur si existant, sinon $default
	 */
	public static function getQueryParam($name, $default = NULL)
	{
		return isset(self::$_queryParams[$name]) ? self::$_queryParams[$name] : $default;
	}
	
	/**
	 * Obtient la liste des actions pré-requêtes
	 * @return boolean|array la liste des action, ou false si aucune
	 */
	public static function getActionRequest()
	{
		return self::$_action;
	}
	
	/**
	 * Obtient les appels de la requête AJAX
	 * @return boolean|array la liste des requêtes AJAX, ou false si aucune
	 */
	public static function getAjaxRequest()
	{
		return self::$_ajax;
	}
	
	/**
	 * Redirige en interne vers une autre page, sans modifier les chemins de la requête orginale
	 * @param string $request la requête à utiliser
	 * @return void
	 */
	public static function internalRedirect($request)
	{
		self::$_page = Page::getByUrl($request);
		if (!self::$_page)
		{
			Request::header404();
		}
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
			elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' or self::getAjaxRequest())
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
	 * Indique si une requête est de type AJAX
	 * 
	 * Détermine si le mode de requête détecté est le mode AJAX
	 * @return boolean une confirmation si le mode est AJAX ou non
	 */
	public static function isAjax()
	{
		// Test
		return (self::getMode() == self::MODE_AJAX);
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
	 * Renvoie le protocole en cours
	 * @return string le protocole
	 */
	public static function getProtocol()
	{
		if (!isset(self::$_protocol))
		{
			if (isset($_SERVER['SERVER_PROTOCOL']))
			{
				self::$_protocol = $_SERVER['SERVER_PROTOCOL'];
				if (self::$_protocol != 'HTTP/1.1' and self::$_protocol != 'HTTP/1.0')
				{
					self::$_protocol = 'HTTP/1.0';
				}
			}
			else
			{
				self::$_protocol = 'HTTP/1.0';
			}
		}

		return self::$_protocol;
	}
	
	/**
	 * Exécute la requête
	 * @return string|array le ou les résultats de la requête
	 */
	public static function exec()
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
		
		// Si ajax
		if ($ajax = self::getAjaxRequest())
		{
			$content = array();
			foreach ($ajax as $call)
			{
				if (method_exists($controler, $call))
				{
					$content[] = call_user_func(array($controler, $call));
				}
			}
			
			// Si requête unique
			if (count($content) === 1)
			{
				$content = $content[0];
			}
			
			// Ajout à l'historique
			History::addParams();
		}
		else
		{
			// Construction de la page
			$content = $controler->build();
			
			// Ajout à l'historique
			History::add();
		}
		
		return $content;
	}
	
	/**
	 * Envoie un header location de redirection
	 * @param string $target la page à charger
	 * @return void
	 * @throws SCException
	 */
	public static function redirect($target)
	{
		// Sécurisation
		if (substr($target, 0, 1) === '#')
		{
			throw new SCException('Url de redirection non valide', 8, 'Url : '.$target);
		}

		// Envoi
		header('location:'.$target);
		exit();
	}
	
	/**
	 * Cherche si une page de redirection est définie ($redirect en GET ou POST), sinon retourne à la page précédente, 
	 * ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default l'url par défaut si aucune page précédente n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public static function redirectOrGoBack($default = '', $append = '')
	{
		if (self::issetParam('redirect'))
		{
			self::redirect(trim(self::getParam('redirect').$append));
		}
		else
		{
			History::goBack($default);
		}
	}
	
	/**
	 * Cherche si une page de redirection est définie ($redirect en GET ou POST), sinon va à la page par défaut
	 * @param string $default l'url par défaut si aucune page de redirection n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public static function redirectOrGo($default = '', $append = '')
	{
		if (self::issetParam('redirect'))
		{
			self::redirect(trim(self::getParam('redirect').$append));
		}
		else
		{
			self::redirect($default);
		}
	}
	
	/**
	 * Envoie un header 403 - accès refusé
	 * @return void
	 * @todo ajouter le support des pages peronnalisées
	 */
	public static function header403()
	{
		header(self::getProtocol().' 403 Forbidden', true, 403);
		exit();
	}
	
	/**
	 * Envoie un header 404 - non trouvé
	 * @return void
	 * @todo ajouter le support des pages peronnalisées
	 */
	public static function header404()
	{
		header(self::getProtocol().' 404 Not Found', true, 404);
		exit();
	}
	
	/**
	 * Envoie un header 500 - erreur interne
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public static function header500()
	{
		header(self::getProtocol().' 500 Internal Server Error', true, 500);
		exit();
	}
}