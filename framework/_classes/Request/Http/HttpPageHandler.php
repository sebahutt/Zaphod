<?php
/**
 * Gestionnaire de requête HTTP standard pour une page
 */
class HttpPageHandler extends PageHandler implements iHandler {
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function begin($route)
	{
		// Si retour en arrière
		if (Request::getGET('__back', 0) == 1)
		{
			// Nettoyage de l'historique
			History::trim(Request::getParser()->getBaseQuery());
			Request::clearGet('__back');
		}
		
		parent::begin($route);
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
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function end($route)
	{
		parent::end($route);
		
		// Ajout à l'historique
		if ($this->_page->addToHistory == 1)
		{
			History::add(Request::getParser()->getBaseQuery(), ($this->_page->saveParams == 1) ? Request::getParams() : array());
		}
	}
}