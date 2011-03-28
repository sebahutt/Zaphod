<?php

/**
 * Classe générale d'environnement
 * 
 * La classe d'environnement centralise toutes les ressources et sert de base au fonctionnement de toutes les autres
 * classes. Sa présence est obligatoire pour le fonctionnement du système. Design Pattern : Singleton
 */
final class Env {
	/**
	 * Objet de configuration global
	 * @var Config
	 */
	private static $_config;
	/**
	 * Indique que l'environnement à été initialisé
	 * @var boolean
	 */
	private static $_inited = false;
	/**
	 * Timestamp de démarrage de la requête
	 * @var array
	 */
	private static $_requestTime;
	/**
	 * Micro-timestamp de démarrage du script
	 * @var array
	 */
	private static $_initTime;
	/**
	 * Dossier de cache local
	 * @var string
	 */
	private static $_cachePath;
	/**
	 * Liste des dossiers d'autoload
	 * @var array
	 */
	private static $_autoloadDir = array();
	/**
	 * Cache des classes
	 * @var array
	 */
	private static $_autoloadCache;
	/**
	 * Indique s'il faut mettre à jour le cache des classes
	 * @var bolean
	 */
	private static $_updateAutoloadCache = false;
	
	/**
	 * Constructeur de la classe
	 */
	private function __construct()
	{
		// Erreur
		throw new SCException('Impossible d\'instancier la classe', 1, 'Classe Env non instanciable');
	}
	
	/**
	 * Surcharge de la méthode clone
	 * @return void
	 * @throws SCException
	 */
	private function __clone()
	{
		// Erreur
		throw new SCException('Impossible de cloner la classe', 2, 'Classe Env non clonable');
	}
	
	/**
	 * Initialisation de la classe d'environnement
	 * @return void
	 * @throws Exception
	 */
	public static function init()
	{
		// Si pas déjà initialisé
		if (!self::$_inited)
		{
			// Session
			session_start();
			
			// Supprime les avertissements sur la timezone pendant l'initialisation (la valeur définitive est affectée plus bas)
			if (@date_default_timezone_set(date_default_timezone_get()) === false)
			{
				date_default_timezone_set('UTC');
			}
			
			// Autoload
			self::addAutoloadDirectory(PATH__CLASSES, true);
			self::addAutoloadDirectory(PATH_CLASSES, true);
			spl_autoload_register(array('Env', 'autoload'));
			
			// Init
			self::$_requestTime = $_SERVER['REQUEST_TIME'];
			self::$_initTime = microtime(true);
			
			// Configuration
			$config = array();
			
			// Chargement de la configuration globale
			require(PATH__CONFIG.'defaults.php');
			
			// Configuration commune à tous les serveurs
			$hasCommonConfig = false;
			if (file_exists(PATH_CONFIG.'common.php'))
			{
				require(PATH_CONFIG.'common.php');
				$hasCommonConfig = true;
			}
			
			// Détermine si le serveur n'est pas dans la liste des machines de développement
			define('PRODUCTION', !self::ipMatches($_SERVER['SERVER_ADDR'], $config['sys']['dev']));
			
			// Configuration locale
			$globalConfFile = PRODUCTION ? 'prod' : 'dev';
			if (file_exists(PATH_CONFIG.$globalConfFile.'.php'))
			{
				include(PATH_CONFIG.$globalConfFile.'.php');
			}
			else if (!$hasCommonConfig)
			{
				// Erreur
				throw new Exception('La configuration du système est erronée', 1);
			}
			
			// Objet config
			self::$_config = new Config($config);
			
			// Démarrage de la gestion des erreurs
			set_error_handler(array('Env', 'errorHandler'));
			if (function_exists('set_exception_handler'))
			{
				set_exception_handler(array('Env', 'exceptionHandler'));
			}
			
			// Si site en mise à jour
			if (self::getConfig('sys')->get('updating', false))
			{
				self::_dieAsMaintenance();
			}
			
			// Déclarations suivant la configuration
			$zone = self::getConfig('zone');
			Lang::setDefault($zone->get('locale'));
			date_default_timezone_set($zone->get('timezone', 'Europe/Paris'));
			
			// Mémorisation
			self::$_inited = true;
		}
		else
		{
			// Erreur
			throw new SCException('La classe est déjà initialisée', 3, 'Classe : Env');
		}
	}
	
	/**
	 * Indique si l'environnement a été initialisé ou non
	 * @return boolean la confirmation ou non
	 */
	public static function isInited()
	{
		return self::$_inited;
	}
	
	/**
	 * Interrompt l'affichage d'un site en maintenance
	 * @return void
	 * @todo Ajouter le support de pages de maintenance personnalisées
	 */
	private static function _dieAsMaintenance()
	{
		// Headers
		header(Request::getProtocol().' 503 Service Unavailable', true, 503);
		header('Retry-After: 600'); // Ou date : header('Retry-After: Sat, 8 Oct 2011 18:27:00 GMT');
		header('Content-Type: text/html; charset=utf-8');

		// Affichage
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".'<html xmlns="http://www.w3.org/1999/xhtml">'."\n".'<head>'."\n".' < meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n".' <title>Site en cours de mise à jour</title>'."\n".'</head>'."\n\n".'<body>'."\n".'<h1>Site temporairement désactivé</h1>'."\n".'<p>Le site est en cours de mise à jour, veuillez tenter de vous connecter à nouveau dans quelques instants.</p>'."\n".'</body>'."\n".'</html>';
		
		// Terminaison
		exit();
	}
	
	/**
	 * Renvoie le chemin du dossier cache local
	 * @return string le chemin local, dont l'existence est vérifiée
	 */
	public static function getCachePath()
	{
		if (!isset(self::$_cachePath))
		{
			self::$_cachePath = PATH_CACHE;
			if (!file_exists(self::$_cachePath))
			{
				mkdir(self::$_cachePath, 0755, true);
			}
		}
		
		return self::$_cachePath;
	}
	
	/**
	 * Récupère le cache du chargeur de classes
	 * @return array le cache existant
	 */
	protected static function _getAutoloadCache()
	{
		// Mise à jour forcée
		if (self::$_updateAutoloadCache)
		{
			self::_updateAutoloadCache();
			self::$_updateAutoloadCache = false;
		}
		elseif (!isset(self::$_autoloadCache))
		{
			// Init
			self::$_autoloadCache = array();
			
			// Détection de cache absent ou trop ancien
			$cacheFile = self::getCachePath().'autoload.php';
			if (!file_exists($cacheFile) or time()-filemtime($cacheFile) > 3600)
			{
				self::_updateAutoloadCache();
			}
			else
			{
				// Chargement
				require($cacheFile);
				
				// Détection de changement
				if (!isset(self::$_autoloadCache['dirs']) or !is_array(self::$_autoloadCache['dirs']) or count(array_diff(array_keys(self::$_autoloadDir), self::$_autoloadCache['dirs'])) > 0)
				{
					self::_updateAutoloadCache();
				}
			}
		}
		
		return self::$_autoloadCache;
	}
	
	/**
	 * Mise à jour du cache du chargeur de classes
	 * @return array le cache existant
	 */
	protected static function _updateAutoloadCache()
	{
		// Init
		self::$_autoloadCache = array(
			'dirs' => array(),			// Liste des dossiers de base
			'classes' => array()		// Chemin fichier des classes/interfaces
		);
		
		// Parcours
		foreach (self::$_autoloadDir as $path => $recursive)
		{
			self::_parseAutoloadCacheDir($path, $recursive);
			self::$_autoloadCache['dirs'][] = $path;
		}
		
		// Mise à jour du fichier
		file_put_contents(self::getCachePath().'autoload.php', '<?php'."\n".'// Cache des fichiers de classes, supprimer le fichier en cas de problème'."\n".'self::$_autoloadCache = '.var_export(self::$_autoloadCache, true).';');
	}
	
	/**
	 * Récupère les éléments à mettre en cache sur un dossier de fichiers de classes
	 * @param string $path le chemin du dossier
	 * @param boolean $recursive indique s'il faut parcourir les sous-dossiers (facultatif, défaut : false)
	 * @return void
	 */
	protected static function _parseAutoloadCacheDir($path, $recursive = false)
	{
		$dir = dir($path);
		while (false !== ($entry = $dir->read()))
		{
			if ($entry !== '.' and $entry !== '..')
			{
				if (is_dir($path.$entry))
				{
					if ($recursive)
					{
						self::_parseAutoloadCacheDir($path.$entry.'/', $recursive);
					}
				}
				else
				{
					// Découpe du contenu du fichier
					$tokens = token_get_all(file_get_contents($path.$entry));
					$tokens = array_filter($tokens, 'is_array');
					
					$class = false;
					foreach ($tokens as $token)
					{
						if ($token[0] === T_INTERFACE or $token[0] === T_CLASS)
						{
							$class = true;
							continue;
						}
						if ($class and $token[0] === T_STRING)
						{
							self::$_autoloadCache['classes'][$token[1]] = $path.$entry;
							$class = false;
						}
					}
				}
			}
		}
		$dir->close();
	}
	
	/**
	 * Ajoute un dossier de stockage des classes
	 * @param string $path le chemin du dossier
	 * @param boolean $recursive indique s'il faut également parcourir les sous-dossiers (facultatif, défaut : false)
	 * @return void
	 */
	public static function addAutoloadDirectory($path, $recursive = false)
	{
		$path = addTrailingSlash($path);
		self::$_autoloadDir[$path] = $recursive;
		
		// Détection de nouveau dossier
		if (isset(self::$_autoloadCache) and !in_array($path, self::$_autoloadCache['dirs']))
		{
			// Marquage pour mise à jour
			self::$_updateAutoloadCache = true;
		}
	}
	
	/**
	 * Méthode magique d'auto-chargement des classes
	 * @param string $class_name Le nom de la classe à charger
	 * @return boolean true si la classe est chargée, false sinon
	 */
	public static function autoload($class_name)
	{
		// Données en cache
		$cache = self::_getAutoloadCache();
		if (isset($cache['classes'][$class_name]))
		{
			// Chargement
			require_once($cache['classes'][$class_name]);
			
			// Initialisation
			if (method_exists($class_name, 'initClass'))
			{
				call_user_func(array($class_name, 'initClass'));
			}
			
			return true;
		}
		
		// Par défaut
		return false;
	}
	
	/**
	 * Gestion des erreurs php
	 * @param int $errno code erreur php
	 * @param string $errstr message de l'erreur
	 * @param string $errfile chemin du fichier courant
	 * @param int $errline ligne courante
	 * @param array $errcontext données contextuelles
	 * @return void
	 */
	public static function errorHandler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = array())
	{
		// Check if the error code is not included in error_reporting
		if (!(error_reporting() & $errno))
		{
			return;
		}
		
		// Restore default handlers to prevent errors in errors
		restore_error_handler();
		if (function_exists('restore_exception_handler'))
		{
			restore_exception_handler();
		}
		
		// Vidage du buffer
		if (ob_get_level() > 0)
		{
			ob_end_clean();
		}
		
		// Chargement de la page d'erreur
		$buffered = class_exists('Response', false);
		if ($buffered)
		{
			ob_start();
		}
		
		// Si fichier d'erreur
		if (defined('PATH_ERRORS') and file_exists(PATH_ERRORS.'error.php'))
		{
			require(PATH_ERRORS.'error.php');
		}
		else
		{
			echo 'Error <strong>'.$errno.'</strong> in <strong>'.$errfile.'</strong> @ line <strong>'.$errline.'</strong><br><strong>Message:</strong> '.$errstr.'<br><strong>Env:</strong> <pre>'.var_export($errcontext, true).'</pre>';
		}
		
		// sortie
		if ($buffered)
		{
			$retour = ob_get_contents();
			ob_end_clean();
			Response::output($retour);
		}
		exit();
	}
	
	
	/**
	 * Gestion des exceptions non interceptées
	 * @param Exception $exception l'objet exception
	 * @return void
	 */
	public static function exceptionHandler($exception)
	{
		// Restore default handlers to prevent errors in errors
		restore_error_handler();
		if (function_exists('restore_exception_handler'))
		{
			restore_exception_handler();
		}
		
		// Vidage du buffer
		if (ob_get_level() > 0)
		{
			ob_end_clean();
		}
		
		// Chargement de la page d'erreur
		$buffered = class_exists('Response', false);
		if ($buffered)
		{
			ob_start();
		}
		
		// Si fichier d'erreur
		if (defined('PATH_ERRORS') and file_exists(PATH_ERRORS.'error.php'))
		{
			require(PATH_ERRORS.'error.php');
		}
		else
		{
			echo 'Error <strong>'.$exception->getCode().'</strong> in <strong>'.$exception->getFile().'</strong> @ line <strong>'.$exception->getLine().'</strong><br><strong>Message:</strong> '.$exception->getMessage().'<br><strong>Env:</strong> <pre>'.$exception->getTraceAsString().'</pre>';
		}
		
		// sortie
		if ($buffered)
		{
			$retour = ob_get_contents();
			ob_end_clean();
			Response::output($retour);
		}
		exit();
	}
	
	/**
	 * Renvoi le timestamp de démarrage du script, ou si $diffToNow vaut true, le temps écoulé depuis le début du script, en secondes
	 * @param boolean $diffToNow indique s'il faut renvoyer le temps écoulé (true) ou simplement le timer de départ (false) (facultatif, défaut : false)
	 * @return int le temps écoulé en secondes
	 */
	public static function getStartupTime($diffToNow = false)
	{
		// Si différence
		if ($diffToNow)
		{
			// Renvoi
			return time()-self::$_requestTime;
		}
		else
		{
			// Renvoi
			return self::$_requestTime;
		}
	}
	
	/**
	 * Renvoi le micro-timer
	 *  
	 * Renvoi le microtime écoulé depuis le début du script
	 * @return float le temps au format microtime flottant (sec.millisecondes)
	 */
	public static function getMicrotime()
	{
		return microtime(true)-self::$_initTime;
	}
	
	/**
	 * Obtention de paramètre de configuration
	 * @param string $param le nom du paramètre à obtenir
	 * @param mixed $defaut la valeur par défaut si le paramètre n'est pas défini (optionnel)
	 * @return mixed Retourne la valeur du paramètre, ou la valeur par défaut si le paramètre n'est pas défini.
	 */
	public static function getConfig($param, $defaut = NULL)
	{
		return self::$_config->get($param, $defaut);
	}
	
	/**
	 * Chemin relatif entre deux fichiers
	 * 
	 * Renvoi le chemin relatif entre deux chemins absolus
	 * @param string $from Le fichier de départ
	 * @param string $to Le fichier de destination
	 * @return string Renvoie le chemin relatif
	 */
	public static function relativPath($from, $to)
	{
		// Nettoyage : retrait des slashes inutiles
		$fromSlash = false;
		$toSlash = false;
		$from = removeInitialSlash($from);
		if (substr($from, -1, 1) == '/')
		{
			$from = substr($from, 0, -1);
			$fromSlash = true;
		}
		$to = removeInitialSlash($to);
		if (substr($to, -1, 1) == '/')
		{
			$to = substr($to, 0, -1);
			$toSlash = true;
		}
		
		// Découpe
		$fromParts = 	(strlen($from) > 0) ? explode('/', trim($from)) : array();
		$toParts = 		(strlen($to) > 0) ? explode('/', trim($to)) : array();
		
		// Détection de fichiers
		$lastFrom = 	isset($fromParts[0]) ? $fromParts[count($fromParts)-1] : '';
		$lastTo = 		isset($toParts[0]) ? $toParts[count($toParts)-1] : '';
		
		// Nettoyage des fichiers
		if (!$fromSlash and strlen($lastFrom) > 0 and strpos($lastFrom, '.') !== false)
		{
			array_pop($fromParts);
		}
		if (!$fromSlash and strlen($lastTo) > 0 and strpos($toParts[count($toParts)-1], '.') !== false)
		{
			$toFile = array_pop($toParts);
		}
		else
		{
			$toFile = '';
		}
		
		// Remontée
		$lastPart = '';
		while (count($toParts) > 0 and count($fromParts) > 0)
		{
			// Si identique
			if ($toParts[0] == $fromParts[0])
			{
				// Retrait
				$lastPart = array_shift($toParts);
				array_shift($fromParts);
			}
			else
			{
				// Sortie
				break;
			}
		}
		
		// Chemin
		$path = str_repeat('../', max(0, count($fromParts))).implode('/', $toParts);
		
		// Ajout de la jointure chemin/fichier
		if (strlen($path) > 0 and count($toParts) > 0 and ($toSlash or strlen($toFile) > 0))
		{
			$path .= '/';
		}
		elseif (strlen($path) == 0 and strlen($toFile) == 0 and strlen($lastPart) > 0)
		{
			// Cas : la cible est le dossier parent du fichier d'origine
			$path = './';
		}
		
		// Composition
		return $path.$toFile;
	}
	
	/**
	 * Vérifie si une ip correspond à la liste fournie
	 * 
	 * Cette fonction parcours les différentes valeurs et/ou masques fournis en paramètre, et tente de
	 * déterminer si l'ip fournie correspond à l'un d'entre eux. Les masques correspondent au début d'une 
	 * adresse ip, par exemple 127.0.0
	 * @param string $ip l'ip à vérifier
	 * @param array $matches la liste des valeurs ou masques possibles
	 * @return boolean Renvoie true si une concordance est trouvée, false sinon
	 */
	public static function ipMatches($ip, $values)
	{
		// Parcours
		foreach ($values as $value)
		{
			// Si correspond
			if (strpos($ip, $value) === 0)
			{
				// Confirmation
				return true;
			}
		}
		
		// Par défaut
		return false;
	}
	
	/**
	 * Détermine si le système tourne sous windows
	 * @return boolean Renvoie true si c'est le cas, false sinon
	 */
	public static function isOsWindows()
	{
		// Renvoi
		return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
	}
}