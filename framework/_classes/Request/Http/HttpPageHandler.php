<?php
/**
 * Gestionnaire de requête HTTP standard pour une page
 */
class HttpPageHandler extends PageHandler implements iRequestHandler {
	/**
	 * Teste si le handler gère le type de route en cours
	 * 
	 * @param iRequestRoute la route en cours de traitement
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestHandler|boolean une instance de la classe si elle peut gérer le route, false sinon
	 */
	public static function handles($route, $internalRedirect = false)
	{
		if ($route instanceof HttpPageRoute)
		{
			return new HttpPageHandler($route, $internalRedirect);
		}
		
		return false;
	}
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * 
	 * @return void
	 */
	public function begin()
	{
		// Si retour en arrière
		if (Request::getGET('__back', 0) == 1)
		{
			// Nettoyage de l'historique
			History::trim(Request::getParser()->getBaseQuery());
			Request::clearGet('__back');
		}
		
		parent::begin();
	}
	
	/**
	 * Execute la requête
	 * 
	 * @return string le contenu à afficher
	 */
	public function exec()
	{
		// Construction de la page
		return $this->_route->getControler()->build();
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * 
	 * @return void
	 */
	public function end()
	{
		parent::end();
		
		// Ajout à l'historique
		$page = $this->_route->getPage();
		if ($page->addToHistory == 1)
		{
			History::add($this->_route->getParser()->getBaseQuery(), ($page->saveParams == 1) ? Request::getParams() : array());
		}
	}
}