<?php

/**
 * Classe de log
 */
class Logger {
	/**
	 * Nom du logger
	 * @var string
	 */
	protected $_name;
	/**
	 * Indique si le logging est actif pour cette instance - sortie web
	 * @var boolean
	 */
	protected $_activeDisplay;
	/**
	 * Indique si le logging est actif pour cette instance - stockage fichier
	 * @var boolean
	 */
	protected $_activeStore;
	/**
	 * Indique si le logging est actif pour cette instance - tous usages
	 * @var boolean
	 */
	protected $_active;
	/**
	 * Indique si le logging est actif de façon globale - sortie web
	 * @var boolean
	 */
	protected static $_globalActiveDisplay = false;
	/**
	 * Indique si le logging est actif de façon globale - stockage fichier
	 * @var boolean
	 */
	protected static $_globalActiveStore = false;
	/**
	 * Indique si le logging est actif de façon globale - tous usages
	 * @var boolean
	 */
	protected static $_globalActive = false;
	/**
	 * Indique si le mode verbeux est actif
	 * @var boolean
	 */
	protected static $_verbose = false;
	/**
	 * Identifiant de ressource fichier
	 * @var boolean|mixed
	 */
	protected static $_fichier = false;
	/**
	 * Indicateur d'initialisation
	 * @var boolean
	 */
	protected static $_inited = false;
	/**
	 * Indentation des messages
	 * @var int
	 */
	protected static $_indent = 0;
	/**
	 * Stock du log
	 * @var array
	 */
	protected static $_stock = array();
	/**
	 * Log global
	 * @var Logger
	 */
	protected static $_global;
	
	/**
	 * Constructeur de la classe de log
	 * 
	 * Initialise l'objet Logger
	 * @param string $name nom du logger
	 * @param boolean $activeDisplay indique si l'affichage du log est actif. Si $activeDisplay n'est
	 * 	 pas précisé, il prend la même valeur (facultatif, défaut : false)
	 * @param boolean $activeStore indique si la sauvegarde du log est actif (facultatif, défaut : $activeDisplay)
	 */
	public function __construct($name, $activeDisplay = false, $activeStore = NULL)
	{
		// Mémorisation
		$this->_name = $name;
		$this->_activeDisplay = $activeDisplay;
		$this->_activeStore = is_null($activeStore) ? $activeDisplay : $activeStore;
		$this->_active = ($this->_activeDisplay or $this->_activeStore);
	}
	
	/**
	 * Initialise la classe
	 * @return void
	 */
	public static function initClass()
	{
		self::$_global = new Logger('Global');
		
		// Configuration
		$config = Env::getConfig('log');
		self::$_globalActiveDisplay = $config->get('display');
		self::$_globalActiveStore = $config->get('store');
		self::$_verbose = $config->get('verbose');
	}
	
	/**
	 * Active/désactive l'affichage du log
	 * 
	 * Permet de modifier la valeur de $this->activeDisplay pour activer ou désactiver l'affichage du log
	 * @param boolean $valeur la valeur à définir
	 * @return void
	 */
	public function setDisplay($valeur)
	{
		$this->_activeDisplay = ($valeur == true);
		$this->_active = ($this->_activeDisplay or $this->_activeStore);
	}
	
	/**
	 * Renvoie l'activation de l'affichage du log
	 * 
	 * Renvoie la confirmation indiquant si l'affichage du log est actif ou non
	 * @return boolean la confirmation ou non
	 * @return void
	 */
	public function isDisplayActive()
	{
		return $this->_activeDisplay;
	}
	
	/**
	 * Active/désactive l'enregistrement du log
	 * 
	 * Permet de modifier la valeur de $this->_activeStore pour activer ou désactiver l'enregistrement du log
	 * @param boolean $valeur la valeur à définir
	 * @return void
	 */
	public function setStore($valeur)
	{
		$this->_activeStore = ($valeur == true);
		$this->_active = ($this->_activeDisplay or $this->_activeStore);
	}
	
	/**
	 * Renvoie l'activation de l'enregistrement du log
	 * 
	 * Renvoie la confirmation indiquant si l'enregistrement du log est actif ou non
	 * @return boolean la confirmation ou non
	 * @return void
	 */
	public function isStoreActive()
	{
		return $this->_activeStore;
	}
	
	/**
	 * Renvoie l'activation du log
	 * 
	 * Renvoie la confirmation indiquant si le log est actif ou non, quel que soient les
	 * modes activés
	 * @return boolean la confirmation ou non
	 * @return void
	 */
	public function isActive()
	{
		return ($this->_active or self::$_globalActive);
	}
	
	/**
	 * Log d'un message
	 * 
	 * Gère le log d'un message
	 * @param string $message le message à logger
	 * @param int $indent Indique s'il faut indenter la sortie : indique le nombre de 
	 * 	 tabulations à ajouter (si > 0) ou à retirer (si < 0)
	 * 	 (facultatif, défaut = 0)
	 * @return void
	 */
	public function log($message, $indent = 0)
	{
		// Si actif
		if ($this->isActive())
		{
			// Indentation
			if ($indent > 0)
			{
				self::$_indent += $indent;
			}
			
			// Timer
			$timer = Env::getMicrotime();
			
			// Stockage
			self::$_stock[] = array(
				'timer' =>		$timer,
				'class' => 		$this->_name,
				'message' => 	$message
			);
			
			// Si actif - sortie web
			if ($this->_activeDisplay or self::$_globalActiveDisplay)
			{
				// Relai
				$this->_displayLog($timer, $message);
			}
			
			// Si actif - stockage fichier
			if ($this->_activeStore or self::$_globalActiveStore)
			{
				// Relai
				$this->_storeLog($timer, $message);
			}
			
			// Indentation
			if ($indent < 0)
			{
				self::$_indent += $indent;
			}
		}
	}
	
	/**
	 * Obtention du fichier de log
	 * 
	 * Crée et renvoie la ressource du fichier de log pour la session active
	 * @return mixed la ressource de fichier
	 */
	protected static function _getLogFile()
	{
		// Si pas chargé
		if (!self::$_fichier)
		{
			// Dossier logs
			if (!is_dir(PATH_LOGS))
			{
				mkdir(PATH_LOGS);
			}
			
			// Création
			self::$_fichier = fopen(PATH_LOGS.Date::getDate()->toFormat('Ymd_His').'_'.uniqid().'.txt', 'a');
		}
		
		// Renvoi
		return self::$_fichier;
	}
	
	/**
	 * Fermeture du fichier de log
	 * 
	 * Ferme le fichier de log en fin de session
	 * @return void
	 */
	protected static function _closeLogFile()
	{
		// Si chargé
		if (self::$_fichier)
		{
			// Fermeture
			fclose(self::$_fichier);
			
			// Effacement
			self::$_fichier = false;
		}
	}
	
	/**
	 * Log final d'un message - affichage web
	 * 
	 * Effectue la sortie du message de log vers l'affichage
	 * @param float $timer le timer de l'évènement
	 * @param string $message le message à logger
	 * @return void
	 */
	protected function _displayLog($timer, $message)
	{
		// Indentation
		$indent = str_repeat("\t", self::$_indent);
		
		// Composition
		$message = str_pad(number_format($timer, 3), 7, ' ', STR_PAD_LEFT).' '.str_pad('['.$this->name.']', 20, ' ').'. '.$indent.str_replace("\n", "\n".$indent, $message);
		
		// Affichage
		Response::trace($message);
	}
	
	/**
	 * Log final d'un message - stockage fichier
	 * 
	 * Effectue la sortie du message de log vers le fichier de log
	 * @param float $timer le timer de l'évènement
	 * @param string $message le message à logger
	 * @return void
	 */
	protected function _storeLog($timer, $message)
	{
		// Si fichier
		if ($fichier = self::_getLogFile())
		{
			// Indentation
			$indent = str_repeat("\t", self::$_indent);
			
			// Affichage
			fwrite($fichier, str_pad(number_format($timer, 3), 7, ' ', STR_PAD_LEFT).' '.str_pad('['.$this->name.']', 20, ' ').'. '.$indent.str_replace("\n", "\n".$indent, $message)."\n");
		}
	}
	
	/**
	 * Log global d'un message
	 * 
	 * Permet le log d'un message en provenance de l'environnement global
	 * @param string $message le message à logger
	 * @param int $indent Indique s'il faut indenter la sortie : indique le nombre de 
	 * 	 tabulations à ajouter (si > 0) ou à retirer (si < 0)
	 * 	 (facultatif, défaut = 0)
	 * @return void
	 */
	public static function globalLog($message, $indent = 0)
	{
		self::$_global->log($message, $indent);
	}
	
	/**
	 * Efface l'indentation
	 * 
	 * Remet l'indentation des sorties de log à 0
	 * @return void
	 */
	public static function resetIndent()
	{
		// Reset
		self::$_indent = 0;
	}
	
	/**
	 * Active/désactive l'affichage global du log
	 * 
	 * Permet de modifier la valeur de self::$_globalActiveDisplay pour activer ou désactiver l'affichage du log
	 * @param boolean $valeur la valeur à définir
	 * @return void
	 */
	public static function setGlobalDisplay($valeur)
	{
		self::$_globalActiveDisplay = ($valeur == true);
		self::$_globalActive = (self::$_globalActiveDisplay or self::$_globalActiveStore);
	}
	
	/**
	 * Active/désactive l'enregistrement global du log
	 * 
	 * Permet de modifier la valeur de self::$_globalActiveStore pour activer ou désactiver l'enregistrement du log
	 * @param boolean $valeur la valeur à définir
	 * @return void
	 */
	public static function setGlobalStore($valeur)
	{
		self::$_globalActiveStore = ($valeur == true);
		self::$_globalActive = (self::$_globalActiveDisplay or self::$_globalActiveStore);
	}
	
	/**
	 * Renvoie l'activation du log global
	 * 
	 * Renvoie la confirmation indiquant si le log global est actif ou non, quel que soient les
	 * modes activés
	 * @return boolean la confirmation ou non
	 * @return void
	 */
	public static function isGlobalActive()
	{
		return self::$_globalActive;
	}
	
	/**
	 * Active/désactive le mode verbeux
	 * 
	 * Permet de modifier la valeur de self::$_verbose pour activer ou désactiver le mode verbeux
	 * @param boolean $valeur la valeur à définir
	 * @return void
	 */
	public static function setVerbose($valeur)
	{
		self::$_verbose = ($valeur == true);
	}
	
	/**
	 * Indique si le log est en mode verbeur
	 * 
	 * Indique si le logging est en mode verbeux, c'est-à-dire qu'il indique des stats additionnelles
	 * @return boolean indique si le mode verbeux est actif
	 */
	public static function isVerbose()
	{
		return self::$_verbose;
	}
	
	/**
	 * Initialise la classe logger
	 * @return void
	 */
	public static function init()
	{
		// Si pas déjà initialisé
		if (!self::$_inited)
		{
			// Mémorisation
			self::$_inited = true;
			
			// Si mode verbeux
			if (self::isVerbose())
			{
				// Intro
				self::globalLog('Démarrage de la session à '.Date::getDate()->toFormat('c'));
			}
		}
	}
	
	/**
	 * Termine le logger
	 * 
	 * Finalise le log et ferme le fichier si ouvert
	 * @return void
	 */
	public static function endLog()
	{
		// Si mode verbeux
		if (self::isVerbose())
		{
			// Fichiers chargés
			$required = get_included_files();
			
			// Commentaires de fin
			self::globalLog(count($required).' fichiers chargés');
			self::globalLog('Temps d\'exécution : '.round(Env::getMicrotime(), 3).' sec');
			self::globalLog('Fin de la session à '.Date::getDate()->toFormat('c'));
		}
		
		// Fermeture du fichier
		self::_closeLogFile();
	}
	
	/**
	 * Indique si le log est actif
	 * 
	 * Indique si le logging est actif, soit en sortie, soit en stockage, soit les deux
	 * @return boolean indique si le logging est actif
	 */
	public static function isLogging()
	{
		// Evaluation
		return (self::$_globalActiveDisplay or self::$_globalActiveStore);
	}
}