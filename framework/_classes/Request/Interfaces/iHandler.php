<?php
/**
 * Interface pour les routers de requête
 */
interface iHandler {
	/**
	 * Indique si le handler gère le type de route en cours
	 * @param iRoute la route en cours de traitement
	 * @return boolean une confirmation
	 */
	public function handles($route);
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function begin($route);
	
	/**
	 * Execute la requête
	 * @param iRoute $route l'objet route en cours
	 * @return string le contenu à afficher
	 */
	public function exec($route);
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @param iRoute $route l'objet route en cours
	 * @return void
	 */
	public function end($route);
}