<?php
/**
 * Classe de manipulation de l'historique de navigation
 */
class History extends StaticClass {
	/**
	 * Ajoute la requête à l'historique
	 * @param string $query la requête
	 * @param array $params les paramètres (facultatif, défaut : array())
	 * @return void
	 */
	public static function add($query, $params = array())
	{
		// Récupération
		$list = Session::getCache('History', 'list', array());
		
		// On n'ajoute la page qu'une fois
		if (!isset($list[0]) or $list[0]['query'] != $query)
		{
			// Ajout
			array_unshift($list, array(
				'query' => $query,
				'params' => $params
			));
			
			// Limite
			if (count($list) > 10)
			{
				$list = array_slice($list, 0, 10);
			}
		}
		elseif ($list[0]['query'] == $query)
		{
			// Mise à jour des paramètres
			$list[0]['params'] = $params;
		}
		
		// Ecriture
		Session::setCache('History', 'list', $list);
	}
	
	/**
	 * Nettoie l'historique jusqu'à la requête
	 * @param string $query la requête
	 * @return void
	 */
	public static function trim($query)
	{
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
	 * Met à jour les paramètres de la dernière occurence de la requête dans l'historique (ex: requête AJAX sur la même page)
	 * @param string $query la requête
	 * @param array $params les paramètres (facultatif, défaut : array())
	 * @return void
	 */
	public static function updateParams($query, $params = array())
	{
		$list = Session::getCache('History', 'list', array());
		foreach ($list as $index => $history)
		{
			if ($history['query'] == $query)
			{
				// Mise à jour des paramètres
				$list[$index]['params'] = $params;
				
				// Ecriture
				Session::setCache('History', 'list', $list);
				
				// Fin
				break;
			}
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
	 * Renvoie l'url de la première page dans l'historique différente de la requête fournie
	 * @param string $query la requête dont on recherche la page précédente, ou NULL pour la récupérer automatiquement (facultatif, défaut : NULL)
	 * @return array|boolean un tableau avec 2 index ('query' et 'params'), ou false si aucune
	 */
	public static function getPrevious($query = NULL)
	{
		if (is_null($query))
		{
			$query = Request::getParser()->getBaseQuery();
		}
		
		// Remontée
		$index = -1;
		$previous = self::getHistory($index);
		while ($previous !== false and $previous['query'] == $query)
		{
			--$index;
			$previous = self::getHistory($index);
		}
		
		return $previous;
	}
}