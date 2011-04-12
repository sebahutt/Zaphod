<?php
/**
 * Gestionnaire de requête HTTP en Ajax pour une page
 */
class HttpAjaxPageHandler extends PageHandler implements iRequestHandler {
	/**
	 * Données de la requête AJAX
	 * @var array
	 */
	protected $_ajax = false;
	
	/**
	 * Constructeur
	 * @param iRequestRoute $route l'objet route de la requête
	 * @param array $ajax les actions de la requête ajax
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 */
	public function __construct($route, $ajax, $internalRedirect = false)
	{
		// Mémorisation
		parent::__construct($route, $internalRedirect);
		$this->_ajax = $ajax;
	}
	
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
			// Récupération
			$ajax = Request::getParam('__ajax', '');
			
			// Formattage et sécurisation
			if (!is_array($ajax))
			{
				// Si valide
				if (strlen(trim($ajax)) > 0)
				{
					$ajax = array($ajax);
				}
				else
				{
					$ajax = false;
				}
			}
			elseif (count($ajax) == 0 or strlen(trim($ajax[0])) == 0)
			{
				$ajax = false;
			}
			
			// Nettoyage
			Request::clearGet('__ajax');
			
			// Si requête Ajax
			if ($ajax)
			{
				return new HttpAjaxPageHandler($route, $ajax, $internalRedirect);
			}
		}
		
		return false;
	}
	
	/**
	 * Obtient les appels de la requête AJAX
	 * 
	 * @return boolean|array la liste des requêtes AJAX, ou false si aucune
	 */
	public function getAjaxRequest()
	{
		return $this->_ajax;
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
		
		$controler = $this->_route->getControler();
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
	 * 
	 * @return void
	 */
	public function end()
	{
		parent::end();
		
		// Ajout à l'historique
		$page = $this->_route->getPage();
		if ($page->addToHistory == 1 and $page->saveParams == 1)
		{
			History::updateParams($this->_route->getParser()->getBaseQuery(), Request::getParams());
		}
	}
}