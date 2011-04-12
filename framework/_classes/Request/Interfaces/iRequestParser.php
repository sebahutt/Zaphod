<?php
/**
 * Interface pour les objet d'analyse de requête
 */
interface iRequestParser {
	/**
	 * Vérifie si la classe courante est en mesure d'analyser la requête
	 *
	 * @param string $request la requête à analyser, ou NULL pour utiliser l'environnement
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestParser|boolean une instance de la classe si la requête peut être gérée, false sinon
	 */
	public static function match($request = NULL, $internalRedirect = false);
	
	/**
	 * Renvoie la requête de référence pour le routage
	 *
	 * @return string la requête
	 */
	public function getRouteQuery();
}