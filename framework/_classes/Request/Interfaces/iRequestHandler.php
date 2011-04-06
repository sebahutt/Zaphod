<?php
/**
 * Interface pour les routers de requête
 */
interface iRequestHandler {
	/**
	 * Teste si le handler gère le type de route en cours
	 * 
	 * @param iRequestRoute la route en cours de traitement
	 * @return iRequestHandler|boolean une instance de la classe si elle peut gérer le route, false sinon
	 */
	public static function handles($route);
	
	/**
	 * Renvoie l'objet route de rattachement
	 * 
	 * @return iRequestRoute l'objet route
	 */
	public function getRoute();
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * 
	 * @return void
	 */
	public function begin();
	
	/**
	 * Execute la requête
	 * 
	 * @return string le contenu à afficher
	 */
	public function exec();
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * 
	 * @return void
	 */
	public function end();
}