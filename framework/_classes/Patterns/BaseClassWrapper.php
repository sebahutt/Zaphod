<?php
/**
 * Wrapper de la classe Page pour y ajouter le raccord à une URL particulière
 */
class BaseClassWrapper {
	/**
	 * Objet wrappé
	 * @var BaseClass
	 */
	protected $_wrapped;
	
	/**
	 * Constructeur
	 *
	 * @param BaseClass $wrapped l'objet wrappé
	 */
	public function __construct($wrapped)
	{
		// Mémorisation
		$this->_wrapped = $wrapped;
	}
	
	/**
	 * Renvoie l'objet wrappé
	 *
	 * @return BaseClass l'objet wrappé
	 */
	public function getWrapped()
	{
		return $this->_wrapped;
	}
	
	/**
	 * Relai des appels de méthodes
	 *
	 * @param string $index le nom de la méthode appellée
	 * @param array $arguments les arguments
	 * @return mixed le résultat de l'appel
	 */
	public function __call($index, $arguments)
	{
		return call_user_func_array(array($this->_wrapped, $index), $arguments);
	}
	
	/**
	 * Récupération d'une propriété
	 *
	 * @param string $index le nom de la propriété
	 * @return la valeur si définie, NULL sinon
	 */
	public function __get($index)
	{
		return $this->_wrapped->get($index);
	}
	
	/**
	 * Ecriture d'une propriété
	 *
	 * @param string $index le nom de la propriété
	 * @param mixed $value la valeur à définir
	 * @return void
	 */
	public function __set($index, $value)
	{
		$this->_wrapped->set($index, $value);
	}
	
	/**
	 * Test d'existence d'une propriété
	 *
	 * @param string $index le nom de la propriété
	 * @return boolean true si définie, false sinon
	 */
	public function __isset($index)
	{
		return $this->_wrapped->offsetExists($index);
	}
	
	/**
	 * Effacement d'une propriété
	 *
	 * @param string $index le nom de la propriété
	 * @return void
	 */
	public function __unset($index)
	{
		$this->_wrapped->offsetUnset($index);
	}
}