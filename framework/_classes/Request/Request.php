<?php
/**
 * Classe d'accès aux données de requête (modèle temporaire en attendant de passer à un router)
 */
class Request extends StaticClass {
	/**
	 * Handler de la requête en cours
	 * @var PageRequest|CliRequest
	 */
	protected static $_handler;
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
	 * Indique si la requête est en cours d'exécution
	 * @var boolean
	 */
	protected static $_running = false;
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
		// Effacement de l'utilisateur
		if (isset($_GET['logout']) and $_GET['logout'] == 1)
		{
			User::logOut();
		}
		
		// Nettoyage
		self::clearGet('logout');
		
		// Handler en cours
		$handler = self::getHandler();
		$handler->init();
		
		// Vérification des droits
		if ($handler->isAccessible())
		{
			$handler->allowAccess();
		}
		else
		{
			$handler->denyAccess();
		}
	}

	/**
	 * Renvoie le handler de la requête en cours
	 * @return PageRequest|CliRequest le handler
	 */
	public static function getHandler()
	{
		if (!isset(self::$_handler))
		{
			if (self::isCLI())
			{
				self::$_handler = new CliRequest();
			}
			elseif (self::issetGET('__ajax'))
			{
				self::$_handler = new AjaxPageRequest();
			}
			else
			{
				self::$_handler = new StandardPageRequest();
			}
		}

		return self::$_handler;
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
	 * Indique si la requête utilise la réécriture d'URL
	 * @return boolean une confirmation
	 */
	public static function isRewriten()
	{
		return self::$_rewritten;
	}
	
	/**
	 * Indique si la requête est en cours d'éxécution
	 * @return boolean une confirmation
	 */
	public static function isRunning()
	{
		return self::$_running;
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
	
	/**
	 * Exécute la requête
	 * @return string|array le ou les résultats de la requête
	 */
	public static function exec()
	{
		self::$_running = true;
		$handler = self::getHandler();
		
		// Exécution
		$handler->start();
		$content = $handler->exec();
		$handler->end();
		
		self::$_running = false;
		return $content;
	}
}