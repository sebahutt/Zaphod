<?php
/**
 * Interface pour les objet d'analyse de requête
 */
interface iRequestParser {
	/**
	 * Tente d'analyser la requête
	 * @return boolean la confirmation que la requête a pu être traitée
	 */
	public function run();
	
	/**
	 * Tente d'effectuer la redirection interne de la requête. Si la redirection échoue, la configuration initiale est conservée.
	 * Les références à la requête initiale sont conservées (url, etc...), seules les fonctions relatives au routage
	 * doivent être impactées (getBaseQuery, par exemple)
	 * @param string $request la nouvelle requête à prendre en compte
	 * @return boolean la confirmation que la requête a pu être traitée
	 */
	public function redirect($request);
	
	/**
	 * Renvoie la requête de référence pour le routage
	 * @return string la requête
	 */
	public function getRouteQuery();
}