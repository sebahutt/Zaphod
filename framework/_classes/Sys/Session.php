<?php
/**
 * Classe de manipulation de session
 */
class Session extends StaticClass {
	/**
	 * Racine des données
	 * @var array 
	 * @static
	 */
	protected static $_root;
	/**
	 * Racine des données publiques
	 * @var array 
	 * @static
	 */
	public static $data;
	
	/**
	 * Initialise la classe
	 */
	public static function initClass()
	{
		// Domaine
		if (strlen(URL_FOLDER) > 0)
		{
			// Vérification
			if (!isset($_SESSION[URL_FOLDER]))
			{
				$_SESSION[URL_FOLDER] = array();
			}
			
			// Référencement
			self::$_root = &$_SESSION[URL_FOLDER];	
		}
		else
		{
			self::$_root = &$_SESSION;
		}
		
		// Data
		if (!isset(self::$_root['data']))
		{
			self::$_root['data'] = array();
		}
		
		// Stockage
		self::$data = &self::$_root['data'];
		
		// Log
		Logger::globalLog('Classe Session initialisée');
	}
	
	/**
	 * Vérifie si des données existent en cache
	 * @param string $name le nom du cache
	 * @param string $index l'index des données
	 * @return boolean renvoie une confirmation
	 */
	public static function checkCacheExists($name, $index)
	{
		// Si existant
		return isset(self::$_root['cache'][$name][$index]);
	}
	
	/**
	 * Récupération des données en cache
	 * @param string $name le nom du cache
	 * @param string $index l'index des données
	 * @param mixed $default la valeur par défaut si l'index n'existe pas (facultatif, défaut : NULL)
	 * @return array|boolean renvoie les données si existantes, ou false sinon
	 */
	public static function getCache($name, $index, $default = NULL)
	{
		return isset(self::$_root['cache'][$name][$index]) ? self::$_root['cache'][$name][$index] : $default;
	}
	
	/**
	 * Stockage de données en cache
	 * @param string $name le nom du cache
	 * @param string $index l'index des données
	 * @param mixed $data les données à stocker
	 * @return void
	 */
	public static function setCache($name, $index, $data)
	{
		// Vérification
		if (!isset(self::$_root['cache']) or !is_array(self::$_root['cache']))
		{
			self::$_root['cache'] = array();
		}
		if (!isset(self::$_root['cache'][$name]) or !is_array(self::$_root['cache'][$name]))
		{
			self::$_root['cache'][$name] = array();
		}
		
		// Stockage
		self::$_root['cache'][$name][$index] = $data;
	}
	
	/**
	 * Nettoyage des données en cache
	 * @param string $name le nom du cache
	 * @param string $index l'index des données
	 * @return void
	 */
	public static function clearCache($name, $index)
	{
		// Suppression
		unset(self::$_root['cache'][$name][$index]);
	}
	
	/**
	 * Définit une variable par défaut pour la session
	 * @param string $var Le nom de la variable à définir
	 * @param mixed $value La valeur à affecter si la variable n'existe pas
	 */
	public static function setDefault($var, $value = false)
	{
		// Si non défini
		if (!isset(self::$data[$var]))
		{
			// Application
			self::$data[$var] = $value;
		}
	}
	
	/**
	 * Efface toutes les données $data
	 */
	public static function resetData()
	{
		// Reset
		self::$_root['data'] = array();
		
		// Stockage
		self::$data = &self::$_root['data'];
	}
	
	/**
	 * Ajoute une erreur
	 * @param string $message le message d'erreur
	 * @param string $domain le domaine de l'erreur (facultatif)
	 */
	public static function addError($message, $domain = 'global')
	{
		// Ajout
		self::$_root['errors'][$domain][] = $message;
	}
	
	/**
	 * Recherche si une erreur du niveau demandé est en attente
	 * @param string|boolean $domain Le code de domaine souhaité, ou false pour tous (facultatif, défaut : 'info')
	 * @return boolean confirmation ou non de la présence d'au moins une erreur
	 */
	public static function hasErrors($domain = 'global')
	{
		// Renvoi
		if ($domain)
		{
			return isset(self::$_root['errors'][$domain]);
		}
		else
		{
			return (isset(self::$_root['errors']) and count(self::$_root['messages']) > 0);
		}
	}
	
	/**
	 * Obtention de la liste des erreurs du niveau demandé
	 * 
	 * Recherche dans la liste des messages en attente ceux qui correspondent au niveau passé en option, et renvoie la
	 * liste. Les messages renvoyés sont effacés de la liste.
	 * @param string $domain Le code de domaine souhaité (facultatif, défaut : 'info')
	 * @return array la liste des messages correspondants
	 */
	public static function getErrors($domain = 'global')
	{
		// Si défini
		if (isset(self::$_root['errors'][$domain]))
		{
			// Récupération et nettoyage
			$messages = self::$_root['errors'][$domain];
			unset(self::$_root['errors'][$domain]);
			
			// Renvoi
			return $messages;
		}
		else
		{
			// Vide
			return array();
		}
	}
	
	/**
	 * Ajoute un message en session
	 * @param mixed $message message l'attention de l'utilisateur. Peut prendre toute forme, à traiter 
	 * 	 par le script final d'affichage
	 * @param string $domain Un code arbitraire de domaine, qui permet de récupérer les messages par
	 * 	 usage : 'info', 'form', 'validation'... (facultatif, défaut : 'info')
	 */
	public static function addMessage($message, $domain = 'info')
	{
		// Ajout
		self::$_root['messages'][$domain][] = $message;
	}
	
	/**
	 * Recherche si un message du niveau demandé est en attente
	 * @param string|boolean $domain Le code de domaine souhaité, ou false pour tous (facultatif, défaut : 'info')
	 * @return boolean confirmation ou non de la présence d'au moins un message
	 */
	public static function hasMessages($domain = 'info')
	{
		// Renvoi
		if ($domain)
		{
			return isset(self::$_root['messages'][$domain]);
		}
		else
		{
			return (isset(self::$_root['messages']) and count(self::$_root['messages']) > 0);
		}
	}
	
	/**
	 * Obtention de la liste des messages du niveau demandé
	 * 
	 * Recherche dans la liste des messages en attente ceux qui correspondent au niveau passé en option, et renvoie la
	 * liste. Les messages renvoyés sont effacés de la liste.
	 * @param string $domain Le code de domaine souhaité (facultatif, défaut : 'info')
	 * @return array la liste des messages correspondants
	 */
	public static function getMessages($domain = 'info')
	{
		// Si défini
		if (isset(self::$_root['messages'][$domain]))
		{
			// Récupération et nettoyage
			$messages = self::$_root['messages'][$domain];
			unset(self::$_root['messages'][$domain]);
			
			// Renvoi
			return $messages;
		}
		else
		{
			// Vide
			return array();
		}
	}
}