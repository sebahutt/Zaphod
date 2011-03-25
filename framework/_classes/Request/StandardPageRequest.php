<?php
/**
 * Gestionnaire de requête de page web standard
 */
class StandardPageRequest extends PageRequest {
	/**
	 * Initialise la requête en cours, et prépare les données
	 * @return boolean true si la requête est correctement initialisée, false sinon
	 */
	public function init()
	{
		parent::init();
	}
	
	/**
	 * Refuse l'accès à la ressource
	 * @return void
	 */
	public function denyAccess()
	{
		if (User::getCurrent()->isDefault())
		{
			// Chargement de la page de login
			$this->internalRedirect('login');
		}
		else
		{
			parent::denyAccess();
		}
	}
	
	/**
	 * Execute la requête
	 * @return string le contenu à afficher
	 */
	public function exec()
	{
		$content = parent::exec();
		$controler = self::getControler();
		
		// Construction de la page
		$content .= $controler->build();
		
		return $content;
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @return void
	 */
	public function end()
	{
		// Ajout à l'historique
		History::add();
	}
}