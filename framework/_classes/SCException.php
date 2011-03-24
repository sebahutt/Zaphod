<?php

/**
 * Classe d'exception de base
 * 
 * La classe d'exception SCException sert de base pour toutes les autres classes d'exceptions
 */
class SCException extends Exception {
	/**
	 * Informations complémentaires de debug
	 * @var string
	 */
	protected $_infos;
	/**
	 * Variables pour debug
	 * @var mixed
	 */
	protected $_vars;
	
	/**
	 * Constructeur de la classe
	 * @param string $message le message de l'exception
	 * @param int $code le code de l'erreur (facultatif - défaut : 0)
	 * @param string $infos infos techniques l'erreur (facultatif - défaut : '')
	 * @param mixed $vars toutes variables nécessaires pour le debug
	 * 	 (facultatif - défaut : NULL)
	 */
	public function __construct($message, $code = 0, $infos = '', $vars = NULL)
	{
		// Relai
		parent::__construct($message, $code);
		
		// Détection de paramètres flottants
		if (!is_string($infos) and is_null($vars))
		{
			// Décalage
			$vars = $infos;
			$infos = '';
		}
		
		// Stockage
		$this->_infos = $infos;
		$this->_vars = $vars;
		
		// Sortie
		Logger::globalLog('<strong>'.get_class($this).' n°'.$code.' :</strong> '.$message."\n".'<strong>Infos :</strong> '.$infos);
	}
	
	/**
	 * Obtention des informations techniques fournies
	 * @return string les infos fournies
	 */
	public function getInfos()
	{
		return $this->_infos;
	}
	
	/**
	 * Obtention des variables fournies pour analyse
	 * @return mixed les variables fournies
	 */
	public function getVars()
	{
		return $this->_vars;
	}
}