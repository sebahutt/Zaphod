<?php
/**
 * Interface pour les gestionnaires de requête
 */
interface iRequestHandler {
	/**
	 * Initialise la requête en cours, et prépare les données
	 * @return boolean true si la requête est correctement initialisée, false sinon
	 */
	public function init();
	
	/**
	 * Vérifie le droit d'accès
	 * @return boolean une confirmation que la ressource demandée est accessible
	 */
	public function isAccessible();
	
	/**
	 * Autorise l'accès à la ressource
	 * @return void
	 */
	public function allowAccess();
	
	/**
	 * Refuse l'accès à la ressource
	 * @return void
	 */
	public function denyAccess();
	
	/**
	 * Démarre la requête : effectue toutes les actions avant la génération du contenu
	 * @return void
	 */
	public function start();
	
	/**
	 * Execute la requête
	 * @return string le contenu à afficher
	 */
	public function exec();
	
	/**
	 * Termine la requête : effectue toutes les actions après la génération du contenu
	 * @return void
	 */
	public function end();
}