<?php
/**
 * Interface pour les objets de routes
 */
interface iRequestRoute {
	/**
	 * Tente de router la requête courante
	 * 
	 * @param iRequestParser $parser le parseur de requête
	 * @param boolean $internalRedirect indique s'il s'agit d'une redirection interne (facultatif, défaut : false)
	 * @return iRequestRoute|boolean une instance de la classe si la requête a pu être mappée, false sinon
	 */
	public static function match($parser, $internalRedirect = false);
	
	/**
	 * Renvoie l'objet parser de rattachement
	 * 
	 * @return iRequestParser l'objet parser
	 */
	public function getParser();
	
	/**
	 * Vérifie la présence de la ressource cible, sa capacité à s'exécuter et les droits d'accès si nécessaire
	 * 
	 * @return boolean|int true si la ressource demandée est accessible, ou un code d'erreur
	 */
	public function init();
	
	/**
	 * Effectue toutes les actions nécessaires à la cloture de la requête
	 * 
	 * @return void
	 */
	public function close();
}