<?php
/**
 * Classe de stockage
 */
class DataHolder implements ArrayAccess, Iterator {
	/**
	 * Tableau stocké
	 * @var array
	 */
	protected $_data;
	/**
	 * Position de l'itérateur
	 * @var int
	 */
	protected $_position;
	/**
	 * Liste des clés pour l'itérateur
	 * @var int
	 */
	protected $_keys;
	
	/**
	 * Constructeur de la classe de stockage
	 * @param array|object $data les données à stocker
	 * @throws SCException
	 */
	public function __construct($data)
	{
		// Vérification
		if (!is_array($data) and !is_object($data))
		{
			throw new SCException('Données non valides', 2, 'Le paramètre fourni doit être de type array ou object', $data);
		}
		
		// Stockage
		$this->_data = (array) $data;
		
		// Init
		$this->_loadIteratorKeys();
		$this->_position = 0;
	}
	
	/**
	 * Charge les clés pour l'itérateur
	 * @return void
	 */
	protected function _loadIteratorKeys()
	{
		$this->_keys = array_keys($this->_data);
	}
	
	/**
	 * Renvoie le tableau des données
	 * @return array le tableau
	 */
	public function getArray()
	{
		return $this->_data;
	}
	
	/**
	 * Définit les valeurs par défaut
	 * @param array $defaults les valeurs par défaut, sous forme de tableau associatif
	 * @return void
	 */
	public function setDefaults($defaults)
	{
		$this->_data = array_merge($defaults, $this->_data);
		
		// Mise à jour des clés
		$this->_loadIteratorKeys();
	}
	
	/**
	 * Indique si un index est défini
	 * @param string $index l'index à tester
	 * @return boolean une confirmation que l'index est défini ou non
	 */
	public function has($index)
	{
		// Relai
		return $this->offsetExists($index);
	}
	
	/**
	 * Obtention de la valeur d'un index
	 * @param string|boolean $index le nom de l'index à renvoyer, ou true pour tout l'objet de données
	 * @param mixed $defaut la valeur par défaut si l'index n'est pas défini (optionnel)
	 * @return mixed Retourne la valeur du paramètre, ou la valeur par défaut si l'index n'est pas défini
	 */
	public function get($index, $defaut = NULL)
	{
		// Mode
		if (is_bool($index))
		{
			return $this->_data;
		}
		
		return isset($this->_data[$index]) ? $this->_data[$index] : $defaut;
	}
	
	/**
	 * Définition d'un index
	 * @param string|array $index le nom de l'index à modifier, ou un tableau de valeurs avec les index en clés
	 * @param mixed $value la valeur à affecter (ignorée si $index est un tableau) (facultatif, défaut : NULL)
	 * @return void
	 */
	public function set($index, $value = NULL)
	{
		// Mode tableau
		if (is_array($index))
		{
			// Application
			foreach ($index as $name => $value)
			{
				$this->set($name, $value);
			}
		}
		else
		{
			$this->_data[$index] = $value;
		}
		
		// Mise à jour des clés
		$this->_loadIteratorKeys();
	}

	/**
	 * Implémentation Iterator - Reset de la position
	 * @return void
	 */
	public function rewind()
	{
		$this->_position = 0;
	}

	/**
	 * Implémentation Iterator - Valeur de l'index courant
	 * @return mixed la valeur de l'index
	 */
	public function current()
	{
		return $this->get($this->_keys[$this->_position]);
	}

	/**
	 * Implémentation Iterator - Obtention de l'index courant
	 * @return int|string la clé courante
	 */
	public function key()
	{
		return $this->_keys[$this->_position];
	}

	/**
	 * Implémentation Iterator - Index suivant
	 * @return void
	 */
	public function next()
	{
		++$this->_position;
	}

	/**
	 * Implémentation Iterator - Test de la validité de l'index courant
	 * @return void
	 */
	public function valid()
	{
		return isset($this->_keys[$this->_position]);
	}
	
	/**
	 * Implémentation ArrayAccess - Affectation d'une valeur
	 * @param string $index L'index à définir
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
	 * @param string $index L'index à tester
	 * @return boolean la confirmation si l'index existe
	 */
	public function offsetExists($index)
	{
		// Relai
		return isset($this->_data[$index]);
	}
	
	/**
	 * Implémentation ArrayAccess - Efface un index
	 * @param string $index L'index à effacer
	 * @return void
	 */
	public function offsetUnset($index)
	{
		// Relai
		unset($this->_data[$index]);
		
		// Mise à jour des clés
		$this->_loadIteratorKeys();
	}
	
	/**
	 * Implémentation ArrayAccess - Renvoie un index
	 * @param string $index L'index à récupérer
	 * @return mixed la valeur de l'index demandé, ou null s'il n'existe pas
	 */
	public function offsetGet($index)
	{
		// Relai
		return $this->get($index);
	}
	
	/**
	 * Méthode magique - lecture d'une valeur
	 * @param string $index la clé de la valeur
	 * @return mixed the value if defined, else NULL
	 */
	public function __get($index)
	{
		return $this->get($index);
	}
	
	/**
	 * Méthode magique - teste si une clé est définie
	 * @param string $index la clé à tester
	 * @return boolean true if value is defined, else false
	 */
	public function __isset($index)
	{
		return $this->offsetExists($index);
	}
	
	/**
	 * Méthode magique - écriture d'une valeur
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
	 * @param string $index la clé à supprimer
	 * @return void
	 */
	public function __unset($index)
	{
		$this->offsetUnset($index);
	}
}