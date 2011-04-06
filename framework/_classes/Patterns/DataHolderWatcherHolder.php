<?php
/**
 * Classe de stockage de classe de stockage avec surveillance de changement des champs
 */
class DataHolderWatcherHolder extends DataHolderHolder
{
	/**
	 * Constructeur de la classe
	 * 
	 * @param DataHolderWatcher $data l'objet à stocker
	 */
	public function __construct($data)
	{
		// Vérification
		if (!($data instanceof DataHolderWatcher))
		{
			throw new SCException('Données non valides, le paramètre fourni doit être de type DataHolderWatcher');
		}
		
		parent::__construct($data);
	}
	
	/**
	 * Indique si l'objet a été modifié
	 * 
	 * @return boolean une confirmation
	 */
	public function isModified()
	{
		return $this->_data->isModified();
	}
	
	/**
	 * Renvoie la liste des index modifiés
	 * 
	 * @return array la liste
	 */
	public function getModifiedList()
	{
		return $this->_data->getModifiedList();
	}
	
	/**
	 * Efface la liste des champs modifiés
	 * 
	 * @return void
	 */
	public function resetModifiedList()
	{
		$this->_data->resetModifiedList();
	}
	
	/**
	 * Définit la liste des champs modifiés
	 * 
	 * @param array $indexes la liste des champs
	 * @return void
	 */
	public function setModifiedList($indexes)
	{
		$this->_data->setModifiedList($indexes);
	}
}