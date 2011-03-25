<?php
/**
 * Classe de manipulation de l'historique de navigation
 */
class History extends StaticClass {
	/**
	 * Ajoute la page courante à l'historique
	 * @return void
	 */
	public static function add()
	{
		if (!$page = Request::getHandler()->getPage())
		{
			return;
		}
		
		// Si la page est compatible avec l'historique
		if ($page->addToHistory == 1)
		{
			// Récupération
			$query = Request::getHandler()->getBaseQuery();
			$list = Session::getCache('History', 'list', array());
			
			// On n'ajoute la page qu'une fois
			if (!isset($list[0]) or $list[0]['query'] != $query)
			{
				// Ajout
				array_unshift($list, array(
					'query' => $query,
					'params' => ($page->saveParams == 1) ? Request::getParams() : array()
				));
				
				// Limite
				if (count($list) > 10)
				{
					$list = array_slice($list, 0, 10);
				}
			}
			elseif ($page->saveParams == 1 and $list[0]['query'] == $query)
			{
				// Mise à jour des paramètres
				$list[0]['params'] = Request::getParams();
			}
			
			// Ecriture
			Session::setCache('History', 'list', $list);
		}
	}
	
	/**
	 * Nettoie l'historique jusqu'à la page courante
	 * @return void
	 */
	public static function trim()
	{
		$query = Request::getHandler()->getBaseQuery();
		$list = Session::getCache('History', 'list', array());
		
		$max = count($list);
		for ($i = 0; $i < $max; ++$i)
		{
			if ($list[$i]['query'] == $query)
			{
				// Nettoyage
				$list = array_slice($list, $i+1);
				
				// Ecriture
				Session::setCache('History', 'list', $list);
				
				// Fin
				break;
			}
		}
	}
	
	/**
	 * Ajoute les paramètres de la page courante à l'historique (ex: requête AJAX sur la même page)
	 * @return void
	 */
	public static function addParams()
	{
		$list = Session::getCache('History', 'list', array());
		if (Request::getHandler()->getPage()->saveParams == 1 and isset($list[0]) and $list[0]['query'] == Request::getHandler()->getBaseQuery())
		{
			// Mise à jour des paramètres
			$list[0]['params'] = Request::getParams();
			Session::setCache('History', 'list', $list);
		}
	}
	
	/**
	 * Renvoie l'url à l'index donné dans l'historique
	 * @param int $index l'index (toujours négatif) dans l'historique (défaut : -1)
	 * @return array|boolean un tableau avec 2 index ('query' et 'params'), ou false si aucune
	 */
	public static function get($index = -1)
	{
		$list = Session::getCache('History', 'list', array());
		if ($index < 0 and isset($list[-1-$index]))
		{
			return $list[-1-$index];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Renvoie l'url de la première page dans l'historique différente de la page actuelle
	 * @return array|boolean un tableau avec 2 index ('query' et 'params'), ou false si aucune
	 */
	public static function getPrevious()
	{
		$index = -1;
		$previous = self::getHistory($index);
		$query = Request::getHandler()->getBaseQuery();
		while ($previous !== false and $previous['query'] == $query)
		{
			--$index;
			$previous = self::getHistory($index);
		}
		
		return $previous;
	}
	
	/**
	 * Retourne à la page précédente, ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default l'url par défaut si aucune page précédente n'est trouvée (défaut : accueil)
	 * @return void
	 */
	public static function goBack($default = '')
	{
		// Recherche de la page précédente
		$previous = self::getPreviousHistory();
		
		// Redirection
		Request::getHandler()->redirect(($previous !== false) ? self::buildUrl($previous['query'], $previous['params']) : $default);
	}
}