<?php
/**
 * Class étendue de log, affiche une fenêtre de sortie dans la page web générée
 *
 * Cette classe ne doit pas être appellée directement, mais servir d'interface à l'adaptateur Log
 */
class WebLogger extends Logger {
	/**
	 * Stock des messages en attente d'affichage
	 * @var array
	 */
	protected $_buffer;

	/**
	 * Constructeur
	 *
	 * @param int $level le niveau maximal de log (facultatif, par défaut : LEVEL_INFO)
	 */
	public function __construct($level = 4)
	{
		parent::__construct($level);
		
		// Initialisation
		$this->_buffer = array();
		
		// Données en cache
		$cached = Session::getCache('WebLogger', 'buffer');
		if (!is_null($cached))
		{
			$this->_buffer = $cached;
			Session::clearCache('WebLogger', 'buffer');
			if (count($this->_buffer) > 0)
			{
				$this->info('******* Reprise *******');
			}
		}
		
		// Enregistrement
		Env::addAction('log.output', array($this, 'output'));
		Env::addAction('response.redirect', array($this, 'saveOnRedirect'));
	}

	/**
	 * Ecriture du message de log
	 *
	 * @param string $line la ligne de log
	 * @return void
	 */
	protected function _write($line)
	{
		$this->_buffer[] = $line;
	}
	
	/**
	 * Envoie le log stocké
	 *
	 * @return boolean true s'il a pu être affiché ou s'il est vide, false sinon
	 */
	public function output()
	{
		if (count($this->_buffer) > 0)
		{
			$response = Request::getResponse();
			if ($response->canDisplayDebug())
			{
				return Request::getResponse()->displayDebug($this->_buffer);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Sauvegarde le log lors d'une redirection HTTP
	 *
	 * @return void
	 */
	public function saveOnRedirect()
	{
		Session::setCache('WebLogger', 'buffer', $this->_buffer);
	}
}