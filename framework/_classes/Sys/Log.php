<?php
/**
 * Adapteur de log
 *
 * Cet adaptateur peut recevoir tout type de logger personnalisé, à partir du moment ou il dispose des méthodes suivantes :
 *
 * debug( mixed $data )
 * info( mixed $data )
 * warning( mixed $data )
 * error( mixed $data )
 * fatal( mixed $data )
 */
class Log extends StaticClass {
	/**
	 * Liste des objets de log
	 * @var array
	 */
	protected static $_loggers = array();
	/**
	 * Chemin du dossier de log journalier
	 * @var string
	 */
	protected static $_dailyLogDir;
	
	/**
	 * Initialise la classe
	 */
	public static function initClass()
	{
		// Loggers automatiques
		$store = Env::getConfig('log')->get('store');
		$display = Env::getConfig('log')->get('display');
		if (is_numeric($store))
		{
			self::addLogger(new Logger($store));
		}
		if (is_numeric($display))
		{
			self::addLogger(new WebLogger($display));
		}
		
		// Nettoyage du dossier de log
		Env::registerCron('log.clean', array('Log', 'cleanOldLogFiles'), 86400);
	}
	
	/**
	 * Fonction de nettoyage du dossier de log
	 *
	 * @return boolean true si le nettoyage est terminé, false sinon
	 */
	public static function cleanOldLogFiles()
	{
		// Si dossier existant
		if (is_dir(PATH_LOGS))
		{
			try
			{
				// Durée de stockage
				$duration = max(1, intval(Env::getConfig('log')->get('rotate')));
				$currentDate = Date::getDate()->offset(-$duration*86400);
				
				// Parcours des dossiers journaliers
				$dir = new Folder(PATH_LOGS);
				$folders = $dir->getChildren(array('folders' => true));
				
				// Suppression des dossiers trop anciens
				foreach ($folders as $folder)
				{
					$name = $folder->getFilename();
					if (preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $name, $matches))
					{
						$date = Date::getDate($matches[1].'-'.$matches[2].'-'.$matches[3]);
						if (!$date->isEmpty() and $date->isBefore($currentDate))
						{
							$folder->delete();
						}
					}
				}
				
				return true;
			}
			catch (SCException $ex)
			{
				return false;
			}
		}
	}
	
	/**
	 * Renvoie le dossier journalier pour les logs
	 *
	 * @return string le chemin du dossier
	 */
	public static function getDailyLogDir()
	{
		if (!isset(self::$_dailyLogDir))
		{
			// Dossier logs
			if (!is_dir(PATH_LOGS))
			{
				mkdir(PATH_LOGS, 0755);
			}
			
			// Droits
			if (!is_writable(PATH_LOGS))
			{
				throw new SCException('Dossier de gestion des logs non accessible en écriture');
			}
			
			// Dossier journalier
			self::$_dailyLogDir = PATH_LOGS.Date::string('Ymd').'/';
			if (!is_dir(self::$_dailyLogDir))
			{
				mkdir(self::$_dailyLogDir, 0755);
			}
		}
		
		return self::$_dailyLogDir;
	}

	/**
	 * Log d'un message de debug
	 *
	 * @param mixed $data tout type de données à logger
	 * @return true si au moins un logger était disponible pour le message, false sinon
	 */
	public static function debug($data)
	{
		$return = false;
		
		foreach (self::$_loggers as $logger)
		{
			$logger->debug($data);
			$return = true;
		}
		
		return $return;
	}

	/**
	 * Log d'un message d'information sur le déroulement de la requête
	 *
	 * @param mixed $data tout type de données à logger
	 * @return true si au moins un logger était disponible pour le message, false sinon
	 */
	public static function info($data)
	{
		$return = false;
		
		foreach (self::$_loggers as $logger)
		{
			$logger->info($data);
			$return = true;
		}
		
		return $return;
	}

	/**
	 * Log d'un message d'avertissement, risque d'erreur
	 *
	 * @param mixed $data tout type de données à logger
	 * @return true si au moins un logger était disponible pour le message, false sinon
	 */
	public static function warning($data)
	{
		$return = false;
		
		foreach (self::$_loggers as $logger)
		{
			$logger->warning($data);
			$return = true;
		}
		
		return $return;
	}

	/**
	 * Log d'un message d'erreur possible à récupérer
	 *
	 * @param mixed $data tout type de données à logger
	 * @return true si au moins un logger était disponible pour le message, false sinon
	 */
	public static function error($data)
	{
		$return = false;
		
		foreach (self::$_loggers as $logger)
		{
			$logger->error($data);
			$return = true;
		}
		
		return $return;
	}

	/**
	 * Log d'un message d'erreur fatale, système corrompu
	 *
	 * @param mixed $data tout type de données à logger
	 * @return true si au moins un logger était disponible pour le message, false sinon
	 */
	public static function fatal($data)
	{
		$return = false;
		
		foreach (self::$_loggers as $logger)
		{
			$logger->fatal($data);
			$return = true;
		}
		
		return $return;
	}

	/**
	 * Ajoute une objet logger
	 *
	 * @param mixed $logger toute instance de classe respectant la liste des méthodes requises
	 * @return void
	 */
	public static function addLogger($logger)
	{
		self::$_loggers[] = $logger;
	}

	/**
	 * Renvoie la liste des loggers existants
	 *
	 * @return array la liste des loggers
	 */
	public static function getLoggers()
	{
		return self::$_loggers;
	}
}