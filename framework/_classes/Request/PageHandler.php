<?php
/**
 * Classe de base pour les handlers gérant les pages
 */
abstract class PageHandler {
	/**
	 * Objet de route de la requête
	 * @var iRequestRoute
	 */
	protected $_route;
	
	/**
	 * Constructeur
	 * @param iRequestRoute $route l'objet route de la requête
	 */
	public function __construct($route)
	{
		// Mémorisation
		$this->_route = $route;
	}
	
	/**
	 * Renvoie l'objet route de rattachement
	 * 
	 * @return iRequestRoute l'objet route
	 */
	public function getRoute()
	{
		return $this->_route;
	}
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * 
	 * @return void
	 */
	public function begin()
	{
		// Traitement des actions
		$actions = $this->_route->getActionRequest();
		if ($actions)
		{
			$controler = $this->_route->getControler();
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
	 * 
	 * @return void
	 */
	public function end()
	{
		
	}
}