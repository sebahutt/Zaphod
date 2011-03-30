<?php
/**
 * Interface pour les objets de routes
 */
interface iRoute {
	/**
	 * Tente de router la requête courante
	 * @param iRequestParser $parser le parseur de requête
	 * @return boolean true si la requête a pu être mappée, false sinon
	 */
	public function match($parser);
	
	/**
	 * Vérifie la présence de la ressource cible, sa capacité à s'exécuter et les droits d'accès si nécessaire
	 * @return boolean|int true si la ressource demandée est accessible, ou un code d'erreur
	 */
	public function init();
	
	/**
	 * Tente d'effectuer la redirection de la route vers une nouvelle ressource et de l'initialiser.
	 * Les références à la requête initiale sont conservées (paramètres, etc...), en revanche les actions sont
	 * désormais relatives à la nouvelle ressource. Si la redirection échoue, la configuration initiale est conservée.
	 * Note : la route n'a pas forcément été intialisée avant.
	 * @param iRequestParser $parser le parseur de requête
	 * @return boolean true si la requête a pu être mappée, false sinon
	 */
	public function redirect($parser);
	
	/**
	 * Effectue toutes les actions nécessaires à la cloture de la requête
	 * @return void
	 */
	public function close();
}