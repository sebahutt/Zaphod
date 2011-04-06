<?php
/**
 * Classe de stockage de tableau sans clés associatives
 */
class ArrayHolder implements ArrayAccess, Iterator {
	/**
	 * Tableau
	 * @var array
	 */
	protected $_array;
	/**
	 * Position de l'iterateur
	 * @var int
	 */
	protected $_position;
	
	/**
	 * Constructeur de la classe de stockage
	 * 
	 * @param array $array le tableau à stocker
	 */
	public function __construct($array)
	{
		// Mémorisation
		$this->_array = $array;
	}
	
	/**
	 * Renvoie le tableau stocké en interne
	 * 
	 * @return array le tableau
	 */
	public function getArray()
	{
		return $this->_array;
	}
	
	/**
	 * Obtention du nombre de lignes stockées
	 * 
	 * @return int le nombre de lignes
	 */
	public function count()
	{
		return count($this->_array);
	}
	
	/**
	 * Obtention d'une ligne
	 * 
	 * @param string $index la ligne à renvoyer
	 * @return mixed Retourne la ligne, ou la NULL si inexistante
	 */
	public function get($index)
	{
		// Si non défini
		if (!isset($this->_array[$index]))
		{
			return NULL;
		}
		
		return $this->_array[$index];
	}
	
	/**
	 * Définition d'une ligne
	 * 
	 * @param string $index la ligne à modifier
	 * @param mixed $value la valeur à affecter
	 * @return void
	 */
	public function set($index, $value)
	{
		$this->_array[$index] = $value;
	}
	
	/**
	 * Ajoute une ligne au début
	 * 
	 * @param mixed $line les données de la ligne
	 * @return int le nouveau nombre d'éléments
	 */
	public function unshift($line)
	{
		return array_unshift($this->_array, $line);
	}
	
	/**
	 * Ajoute une ligne à la fin
	 * 
	 * @param mixed $line les données de la ligne
	 * @return int le nouveau nombre d'éléments
	 */
	public function push($line)
	{
		$this->_array[] = $line;
		return $this->count();
	}
	
	/**
	 * Supprime et renvoie la première ligne
	 * 
	 * @return mixed les données de la première ligne
	 */
	public function shift()
	{
		return shift($this->_array);
	}
	
	/**
	 * Supprime et renvoie la dernière ligne
	 * 
	 * @return mixed les données de la dernière ligne
	 */
	public function pop()
	{
		return pop($this->_array);
	}
	
	/**
	 * Ajoute les entrées du tableau fourni au début de la liste existante
	 * 
	 * @param array $array le tableau à ajouter
	 * @return int le nouveau nombre de lignes
	 */
	public function prepend($array)
	{
		$this->_array = array_merge(array_values($array), $this->_array);
		return $this->count();
	}
	
	/**
	 * Ajoute les entrées du tableau fourni à la fin de la liste existante
	 * 
	 * @param array $array le tableau à ajouter
	 * @return int le nouveau nombre de lignes
	 */
	public function append($array)
	{
		$this->_array = array_merge($this->_array, array_values($array));
		return $this->count();
	}

	/**
	 * Implémentation Iterator - Reset de la position
	 * 
	 * @return void
	 */
	public function rewind()
	{
		$this->_position = 0;
	}

	/**
	 * Implémentation Iterator - Valeur de l'index courant
	 * 
	 * @return mixed la valeur de l'index
	 */
	public function current()
	{
		return $this->_array[$this->_position];
	}

	/**
	 * Implémentation Iterator - Obtention de l'index courant
	 * 
	 * @return int|string la clé courante
	 */
	public function key()
	{
		return $this->_position;
	}

	/**
	 * Implémentation Iterator - Index suivant
	 * 
	 * @return void
	 */
	public function next()
	{
		++$this->_position;
	}

	/**
	 * Implémentation Iterator - Test de la validité de l'index courant
	 * 
	 * @return void
	 */
	public function valid()
	{
		return isset($this->_array[$this->_position]);
	}
	
	/**
	 * Implémentation ArrayAccess - Affectation d'une valeur
	 * 
	 * @param string $index La ligne à affecter
	 * @param mixed $valeur La valeur à affecter
	 * @return void
	 */
	public function offsetSet($index, $valeur)
	{
		// Relai
		$this->set($index, $valeur);
	}
	
	/**
	 * Implémentation ArrayAccess - Teste un index
	 * 
	 * @param string $index La ligne à tester
	 * @return boolean la confirmation si l'index existe
	 */
	public function offsetExists($index)
	{
		// Relai
		return isset($this->_array[$index]);
	}
	
	/**
	 * Implémentation ArrayAccess - Efface un index
	 * 
	 * @param string $index La ligne à effacer
	 * @return void
	 */
	public function offsetUnset($index)
	{
		// Relai
		unset($this->_array[$index]);
	}
	
	/**
	 * Implémentation ArrayAccess - Renvoie un index
	 * 
	 * @param string $index La ligne à récupérer
	 * @return mixed la valeur de l'index demandé, ou null s'il n'existe pas
	 */
	public function offsetGet($index)
	{
		// Relai
		return $this->get($index);
	}
	
	/**
	 * Méthode magique - lecture d'une valeur
	 * 
	 * @param string $index la clé de la valeur
	 * @return mixed the value if defined, else NULL
	 */
	public function __get($index)
	{
		return $this->get($index);
	}
	
	/**
	 * Méthode magique - teste si une clé est définie
	 * 
	 * @param string $index la clé à tester
	 * @return boolean true if value is defined, else false
	 */
	public function __isset($index)
	{
		return $this->offsetExists($index);
	}
	
	/**
	 * Méthode magique - écriture d'une valeur
	 * 
	 * @param string $index la clé à modifier
	 * @param mixed $value la nouvelle valeur
	 * @return void
	 */
	public function __set($index, $value)
	{
		$this->set($index, $value);
	}
	
	/**
	 * Méthode magique - suppression d'une clé
	 * 
	 * @param string $index la clé à supprimer
	 * @return void
	 */
	public function __unset($index)
	{
		$this->offsetUnset($index);
	}
}