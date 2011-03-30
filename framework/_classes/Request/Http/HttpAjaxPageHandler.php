<?php
/**
 * Gestionnaire de requête HTTP en Ajax pour une page
 */
class HttpAjaxPageHandler extends PageHandler implements iHandler {
	/**
	 * Données de la requête AJAX
	 * @var array
	 */
	protected $_ajax = false;
	
	/**
	 * Indique si le handler gère le type de route en cours
	 * @param iRoute la route en cours de traitement
	 * @return boolean une confirmation
	 */
	public function handles($route)
	{
		if (parent::handles($route))
		{
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
			
			// Nettoyage
			Request::clearGet('__ajax');
			
			// Si requête Ajax
			if ($this->_ajax)
			{
				return true;
			}
		}
		
		return false;
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
	 * @param iRoute $route l'objet route en cours
	 * @return string le contenu à afficher
	 */
	public function exec($route)
	{
		// Construction de la page
		return $route->getControler()->build();
		
		$controler = $route->getControler();
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
			return $content[0];
		}
		else
		{
			return $content;
		}
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function end($route)
	{
		parent::end();
		
		// Ajout à l'historique
		if ($this->_page->addToHistory == 1 and $this->_page->saveParams == 1)
		{
			History::updateParams(Request::getParser()->getBaseQuery(), Request::getParams());
		}
	}
}