<?php
/**
 * Gestionnaire de requête de page web en ajax
 */
class AjaxPageRequest extends PageRequest {
	/**
	 * Données de la requête AJAX
	 * @var array
	 */
	protected $_ajax = false;
	
	/**
	 * Initialise la requête en cours, et prépare les données
	 * @return boolean true si la requête est correctement initialisée, false sinon
	 */
	public function init()
	{
		parent::init();
		
		// Récupération
		$this->_ajax = Request::getParam('__ajax', '');
		
		// Formattage et sécurisation
		if (!is_array($this->_ajax))
		{
			// Si valide
			if (strlen(trim($this->_ajax)) > 0)
			{
				$this->_ajax = array($this->_ajax);
			}
			else
			{
				$this->_ajax = false;
			}
		}
		elseif (count($this->_ajax) == 0 or strlen(trim($this->_ajax[0])) == 0)
		{
			$this->_ajax = false;
		}
		
		// Détection d'incohérence
		if (!$this->_ajax)
		{
			throw new SCException('Aucune donnée trouvée pour la requête AJAX', 999, 'Ça ressemble méchament à un bug, ça...');
		}
		
		// Nettoyage
		self::clearGet('__ajax');
	}
	
	/**
	 * Obtient les appels de la requête AJAX
	 * @return boolean|array la liste des requêtes AJAX, ou false si aucune
	 */
	public function getAjaxRequest()
	{
		return $this->_ajax;
	}
	
	/**
	 * Execute la requête
	 * @return string le contenu à afficher
	 */
	public function exec()
	{
		$parentContent = parent::exec();
		$controler = self::getControler();
		$ajax = $this->getAjaxRequest();
		
		$content = array();
		foreach ($ajax as $call)
		{
			if (method_exists($controler, $call))
			{
				$content[] = call_user_func(array($controler, $call));
			}
		}
		
		// Si requête unique
		if (count($ajax) === 1)
		{
			$content = $parentContent.$content[0];
		}
		elseif (strlen($parentContent) > 0)
		{
			array_unshift($content, $parentContent);
		}
		
		return $content;
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @return void
	 */
	public function end()
	{
		// Ajout à l'historique
		History::addParams();
	}
}