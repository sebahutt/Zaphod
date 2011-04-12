<?php
/**
 * Fichier de définition de la classe de gestion des statuts utilisateurs - fonctions génériques
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

/**
 * Classe de gestion des statuts utilisateurs - fonctions génériques
 */
class Status extends BaseClass
{
	/**
	 * Table de référence
	 * @var string
	 */
	public static $table = 'status';

	/**
	 * Obtention d'un statut par son id
	 * 
	 * @param int $id l'id du statut voulu
	 * @return Vendeur le statut désiré, ou false si inexistant
	 */
	public static function getStatus($id)
	{
		// Récupération
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'` WHERE `id_status`=?', intval($id));
		
		// Si trouvé
		if ($result->count() > 0)
		{
			return Factory::getInstance('Status', $result[0]);
		}
		
		// Renvoi par défaut
		return false;
	}
	
	/**
	 * Liste des statuts existants
	 * 
	 * @return array la liste des statuts
	 */
	public static function getList()
	{
		$result = Database::get(self::$server)->query('SELECT * FROM `'.self::$table.'`');
		return $result->castAs('Status');
	}
}