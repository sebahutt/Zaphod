<?php
/**
 * Modèle de classe statique
 */
abstract class StaticClass {
	/**
	 * Constructeur de la classe
	 */
	private function __construct()
	{
		// Erreur
		throw new SCException('Impossible d\'instancier la classe', 1, 'Classe '.get_class($this).' non instanciable');
	}
	
	/**
	 * Surcharge de la méthode clone
	 * @return void
	 * @throws SCException
	 */
	public function __clone()
	{
		// Erreur
		throw new SCException('Impossible de cloner la classe', 2, 'Classe '.get_class($this).' non clonable');
	}
}