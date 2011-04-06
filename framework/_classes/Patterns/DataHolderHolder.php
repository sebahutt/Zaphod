<?php
/**
 * Classe de stockage d'objet de stockage
 */
class DataHolderHolder implements ArrayAccess, Iterator {
	/**
	 * Objet stocké
	 * @var DataHolder
	 */
	protected $_data;
	
	/**
	 * Constructeur de la classe
	 * 
	 * @param DataHolder $data l'objet à stocker
	 * @throws SCException
	 */
	public function __construct($data)
	{
		// Vérification
		if (!($data instanceof DataHolder))
		{
			throw new SCException('Données non valides, le paramètre fourni doit être de type DataHolder');
		}
		
		$this->_data = $data;
	}
	
	/**
	 * Renvoie le tableau des données
	 * 
	 * @return array le tableau
	 */
	public function getArray()
	{
		return $this->_data->getArray();
	}
	
	/**
	 * Définit les valeurs par défaut
	 * 
	 * @param array $defaults les valeurs par défaut, sous forme de tableau associatif
	 * @return void
	 */
	public function setDefaults($defaults)
	{
		$this->_data->setDefaults($defaults);
	}
	
	/**
	 * Indique si un index est défini
	 * 
	 * @param string $index l'index à tester
	 * @return boolean une confirmation que l'index est défini ou non
	 */
	public function has($index)
	{
		// Relai
		return $this->_data->offsetExists($index);
	}
	
	/**
	 * Obtention de la valeur d'un index
	 * 
	 * @param string|boolean $index le nom de l'index à renvoyer, ou true pour tout l'objet de données
	 * @param mixed $defaut la valeur par défaut si l'index n'est pas défini (optionnel)
	 * @return mixed Retourne la valeur du paramètre, ou la valeur par défaut si l'index n'est pas défini
	 */
	public function get($index, $defaut = NULL)
	{
		return $this->_data->get($index, $defaut);
	}
	
	/**
	 * Définition d'un index
	 * 
	 * @param string|array $index le nom de l'index à modifier, ou un tableau de valeurs avec les index en clés
	 * @param mixed $value la valeur à affecter (ignorée si $index est un tableau) (facultatif, défaut : NULL)
	 * @return void
	 */
	public function set($index, $value = NULL)
	{
		$this->_data->set($index, $value);
	}

	/**
	 * Implémentation Iterator - Reset de la position
	 * 
	 * @return void
	 */
	public function rewind()
	{
		$this->_data->rewind();
	}

	/**
	 * Implémentation Iterator - Valeur de l'index courant
	 * 
	 * @return mixed la valeur de l'index
	 */
	public function current()
	{
		$this->_data->current();
	}

	/**
	 * Implémentation Iterator - Obtention de l'index courant
	 * 
	 * @return int|string la clé courante
	 */
	public function key()
	{
		$this->_data->key();
	}

	/**
	 * Implémentation Iterator - Index suivant
	 * 
	 * @return void
	 */
	public function next()
	{
		$this->_data->next();
	}

	/**
	 * Implémentation Iterator - Test de la validité de l'index courant
	 * 
	 * @return void
	 */
	public function valid()
	{
		$this->_data->valid();
	}
	
	/**
	 * Implémentation ArrayAccess - Affectation d'une valeur
	 * 
	 * @param string $index L'index à définir
	 * @param mixed $valeur La valeur à affecter
	 * @return void
	 */
	public function offsetSet($index, $valeur)
	{
		$this->_data->set($index, $valeur);
	}
	
	/**
	 * Implémentation ArrayAccess - Teste un index
	 * 
	 * @param string $index L'index à tester
	 * @return boolean la confirmation si l'index existe
	 */
	public function offsetExists($index)
	{
		$this->_data->offsetExists($index);
	}
	
	/**
	 * Implémentation ArrayAccess - Efface un index
	 * 
	 * @param string $index L'index à effacer
	 * @return void
	 */
	public function offsetUnset($index)
	{
		$this->_data->offsetUnset($index);
	}
	
	/**
	 * Implémentation ArrayAccess - Renvoie un index
	 * 
	 * @param string $index L'index à récupérer
	 * @return mixed la valeur de l'index demandé, ou null s'il n'existe pas
	 */
	public function offsetGet($index)
	{
		return $this->_data->get($index);
	}
	
	/**
	 * Méthode magique - lecture d'une valeur
	 * 
	 * @param string $index la clé de la valeur
	 * @return mixed the value if defined, else NULL
	 */
	public function __get($index)
	{
		return $this->_data->get($index);
	}
	
	/**
	 * Méthode magique - teste si une clé est définie
	 * 
	 * @param string $index la clé à tester
	 * @return boolean true if value is defined, else false
	 */
	public function __isset($index)
	{
		return $this->_data->offsetExists($index);
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
		$this->_data->set($index, $value);
	}
	
	/**
	 * Méthode magique - suppression d'une clé
	 * 
	 * @param string $index la clé à supprimer
	 * @return void
	 */
	public function __unset($index)
	{
		$this->_data->offsetUnset($index);
	}
}