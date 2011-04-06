<?php
/**
 * Classe de stockage avec surveillance de changement des champs
 */
class DataHolderWatcher extends DataHolder
{
	/**
	 * Données modifiées
	 * @var array
	 */
	protected $_modified;
		
	/**
	 * Constructeur de la classe
	 *
	 * @param array|object $data les données à stocker
	 */
	public function __construct($data)
	{
		parent::__construct($data);
		
		// Init
		$this->_modified = array();
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
		if (is_array($index))
		{
			parent::set($index, $value);
		}
		else
		{
			$initValue = isset($this->_data[$index]) ? $this->_data[$index] : NULL;
			parent::set($index, $value);
			
			// Détection de changement
			if ($this->_data[$index] !== $initValue and !in_array($index, $this->_modified))
			{
				$this->_modified[] = $index;
			}
		}
	}
	
	/**
	 * Indique si l'objet a été modifié
	 *
	 * @return boolean une confirmation
	 */
	public function isModified()
	{
		return (count($this->_modified) > 0);
	}
	
	/**
	 * Renvoie la liste des index modifiés
	 *
	 * @return array la liste
	 */
	public function getModifiedList()
	{
		return $this->_modified;
	}
	
	/**
	 * Efface la liste des champs modifiés
	 *
	 * @return void
	 */
	public function resetModifiedList()
	{
		$this->_modified = array();
	}
	
	/**
	 * Définit la liste des champs modifiés
	 *
	 * @param array $indexes la liste des champs
	 * @return void
	 */
	public function setModifiedList($indexes)
	{
		$this->_modified = $indexes;
	}
}