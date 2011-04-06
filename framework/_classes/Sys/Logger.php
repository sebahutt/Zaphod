<?php
/**
 * Classe de logger par défaut, enregistre les messages dans un fichier
 *
 * Cette classe ne doit pas être appellée directement, mais servir d'interface à l'adaptateur Log
 */
class Logger {
	/**
	 * Niveau maximal de log
	 * @var int
	 */
	protected $_level;
	/**
	 * Chemin du fichier de log
	 * @var string
	 */
	protected $_file;
	
	/**
	 * Niveau d'erreur - erreur fatale, système corrompu
	 * @var int
	 */
	const LEVEL_FATAL = 0;
	/**
	 * Niveau d'erreur - erreur possible à récupérer
	 * @var int
	 */
	const LEVEL_ERROR = 1;
	/**
	 * Niveau d'erreur - avertissement, risque d'erreur
	 * @var int
	 */
	const LEVEL_WARNING = 2;
	/**
	 * Niveau d'erreur - information sur le déroulement de la requête
	 * @var int
	 */
	const LEVEL_INFO = 4;
	/**
	 * Niveau d'erreur - information de debug
	 * @var int
	 */
	const LEVEL_DEBUG = 8;

	/**
	 * Constructeur
	 *
	 * @param int $level le niveau maximal de log (facultatif, par défaut : LEVEL_INFO)
	 */
	public function __construct($level = 4)
	{
		// Initialisation
		$this->setLevel($level);
	}
	
	/**
	 * Renvoie le chemin du fichier de log
	 *
	 * @return string le chemin du fichier de log
	 * @throws SCException si le dossier de log n'est pas accessible en écriture
	 */
	protected function _getFile()
	{
		// Si pas chargé
		if (!isset($this->_file))
		{
			$this->_file = Log::getDailyLogDir().Date::string('His').'_'.uniqid().'.txt';
		}
		
		return $this->_file;
	}

	/**
	 * Définit le niveau de log
	 *
	 * @param int $level le niveau maximal de log
	 * @return void
	 * @throws SCException si le niveau de log n'est pas correct
	 */
	public function setLevel($level)
	{
		$level = (int)$level;
		
		// Si valide
		if (in_array($level, array(0, 1, 2, 4, 8)))
		{
			$this->_level = $level;
		}
		else
		{
			throw new SCException('Niveau d\'erreur non valide ('.$level.')');
		}
	}

	/**
	 * Renvoie le niveau de log actuel
	 *
	 * @return int
	 */
	public function getLevel()
	{
		return $this->_level;
	}

	/**
	 * Log d'un message de debug
	 *
	 * @param mixed $data tout type de données à logger
	 * @return void
	 */
	public function debug($data)
	{
		$this->_log($data, self::LEVEL_DEBUG, 'DEBUG');
	}

	/**
	 * Log d'un message d'information sur le déroulement de la requête
	 *
	 * @param mixed $data tout type de données à logger
	 * @return void
	 */
	public function info($data)
	{
		$this->_log($data, self::LEVEL_INFO, 'INFO');
	}

	/**
	 * Log d'un message d'avertissement, risque d'erreur
	 *
	 * @param mixed $data tout type de données à logger
	 * @return void
	 */
	public function warning($data)
	{
		$this->_log($data, self::LEVEL_WARNING, 'WARNING');
	}

	/**
	 * Log d'un message d'erreur possible à récupérer
	 *
	 * @param mixed $data tout type de données à logger
	 * @return void
	 */
	public function error($data)
	{
		$this->_log($data, self::LEVEL_ERROR, 'ERROR');
	}

	/**
	 * Log d'un message d'erreur fatale, système corrompu
	 *
	 * @param mixed $data tout type de données à logger
	 * @return void
	 */
	public function fatal($data)
	{
		$this->_log($data, self::LEVEL_FATAL, 'FATAL');
	}

	/**
	 * Traitement du message de log
	 *
	 * @param mixed $data tout type de données à logger
	 * @param int $level le niveau de log
	 * @param int $levelName le nom du niveau de log
	 * @return void
	 */
	protected function _log($data, $level, $levelName)
	{
		if ($level <= $this->getLevel())
		{
			// Composition
			$this->_write(sprintf("[%s] %s - %s\r\n", $levelName, Date::string('c'), (string)$data));
		}
	}

	/**
	 * Ecriture du message de log
	 *
	 * @param string $line la ligne de log
	 * @return void
	 */
	protected function _write($line)
	{
		@file_put_contents($this->_getFile(), $line, FILE_APPEND | LOCK_EX);
	}
}