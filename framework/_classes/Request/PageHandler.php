<?php
/**
 * Classe de base pour les handlers gérant les pages
 */
abstract class PageHandler {
	/**
	 * Indique si le handler gère le type de route en cours
	 * @param iRoute la route en cours de traitement
	 * @return boolean une confirmation
	 */
	public function handles($route)
	{
		return ($route instanceof HttpPageRoute);
	}
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function begin($route)
	{
		// Traitement des actions
		$actions = $route->getActionRequest();
		if ($actions)
		{
			$controler = $route->getControler();
			foreach ($actions as $action)
			{
				if (method_exists($controler, $action))
				{
					call_user_func(array($controler, $action));
				}
			}
		}
	}
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function end($route)
	{
		
	}
}