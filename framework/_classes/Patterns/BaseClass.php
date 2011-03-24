<?php
/**
 * Classe de base - fonctions génériques
 */
abstract class BaseClass extends DataHolderWatcherHolder
{
	/**
	 * Indique si l'enregistrement est autorisé pour l'objet courant
	 * @var boolean
	 */
	protected $_savable;
	/**
	 * Indique si le changement de mode d'enregistrement est vérouillé
	 * @var boolean
	 */
	protected $_savableLocked;
	/**
	 * Nom du serveur de la table de référence
	 * @var string
	 */
	protected $_serverName;
	/**
	 * Objet Database du serveur de la table de référence
	 * @var Database
	 */
	protected $_server;
	/**
	 * Nom de la table de référence
	 * @var string
	 */
	protected $_tableName;
	/**
	 * Objet DatabaseTable de la table de référence
	 * @var DatabaseTable
	 */
	protected $_table;
	/**
	 * Table de référence
	 * @var string
	 */
	public static $table;
	/**
	 * Serveur de référence
	 * @var string
	 */
	public static $server = DEFAULT_DB;
	
	/**
	 * Constructeur de la classe
	 * @param array|DatabaseResultRow $data données pour l'objet, ou array() pour un nouvel objet (facultatif, défaut : array())
	 */
	public function __construct($data = array())
	{
		// Type de données
		if (is_array($data))
		{
			$data = $this->getTable()->newRow($data);
		}
		
		parent::__construct($data);
		
		// Init
		$this->_savable = true;
		$this->_savableLocked = false;
	}
	
	/**
	 * Indique si l'élément est enregistrable
	 * @return boolean une confirmation
	 */
	public function isSavable()
	{
		return $this->_savable;
	}
	
	/**
	 * Active l'enregistrement de l'élément
	 * @param boolean $lock indique s'il faut vérouiller l'état de manière définitive (facultatif, défaut : false)
	 * @return boolean true si l'enregistrement a été activé, false sinon
	 */
	public function enableSave($lock = false)
	{
		if (!$this->_savableLocked)
		{
			$this->_savable = true;
			$this->_savableLocked = $lock;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Désactive l'enregistrement de l'élément
	 * @param boolean $lock indique s'il faut vérouiller l'état de manière définitive (facultatif, défaut : false)
	 * @return boolean true si l'enregistrement a été désactivé, false sinon
	 */
	public function disableSave($lock = false)
	{
		if (!$this->_savableLocked)
		{
			$this->_savable = false;
			$this->_savableLocked = $lock;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Obtention du nom du serveur de la table de correspondance
	 * @return string le nom de la table
	 */
	public function getServerName()
	{
		if (!isset($this->_serverName))
		{
			$class_vars = get_class_vars(get_class($this));
			$this->_serverName = $class_vars['server'];
		}
		
		return $this->_serverName;
	}
	
	/**
	 * Obtention du nom de la table de correspondance
	 * @return string le nom de la table
	 */
	public function getTableName()
	{
		if (!isset($this->_tableName))
		{
			$class_vars = get_class_vars(get_class($this));
			$this->_tableName = $class_vars['table'];
		}
		
		return $this->_tableName;
	}
	
	/**
	 * Obtention de l'objet Database de référence
	 * @return Database l'objet Database
	 */
	public function getServer()
	{
		if (!isset($this->_server))
		{
			$this->_server = Database::get($this->getServerName());
		}
		
		// Renvoi
		return $this->_server;
	}
	
	/**
	 * Obtention de l'objet DatabaseTable de référence
	 * @return DatabaseTable l'objet DatabaseTable
	 */
	public function getTable()
	{
		if (!isset($this->_table))
		{
			$this->_table = $this->getServer()->getTable($this->getTableName());
		}
		
		// Renvoi
		return $this->_table;
	}
	
	/**
	 * Renvoie la liste des champs de l'objet
	 * @return array la liste des champs
	 */
	public function getFieldsNames()
	{
		return $this->getTable()->getFieldsNames();
	}
	
	/**
	 * Renvoie le nom du champ primaire
	 * @return string|boolean le nom du champ primaire, ou NULL s'il n'y en a pas
	 */
	public function getPrimaryField()
	{
		return $this->getTable()->getPrimary();
	}
	
	/**
	 * Renvoie l'identifiant primaire de l'entrée
	 * @return int la valeur du champ primaire si défini, ou NULL
	 */
	public function id()
	{
		$primary = $this->getPrimaryField();
		return $primary ? $this->get($primary) : NULL;
	}
	
	/**
	 * Indique si l'objet est en cours de création
	 * @return boolean une confirmation
	 */
	public function isNew()
	{
		return $this->_data->isNew($this->getTableName());
	}
	
	/**
	 * Vérifie si un champ existe sur la table
	 * @param string $field le nom du champ
	 * @return boolean la confirmation que le champ existe ou non
	 */
	public function hasField($field)
	{
		return $this->getTable()->hasField($var);
	}
	
	/**
	 * Mise à jour des données de l'objet et enregistrement : si l'objet est nouveau (pas d'id), il est créé dans la base, sinon il est mis à jour.
	 * @param array $data les données à mettre à jour (facultatif, défaut : array())
	 * @return int l'id de l'élément, ou false en cas d'erreur (ex : aucun champ défini)
	 */
	public function save($data = array())
	{
		// Application des données complémentaires
		$this->set($data);
		
		// Si pas enregistrable
		if (!$this->isSavable())
		{
			return false;
		}
		
		// Relai
		return $this->_data->save($this->getTableName());
	}
	
	/**
	 * Suppression d'un élément
	 * @return boolean la confirmation de la suppression
	 */
	public function delete()
	{
		// Si pas enregistrable
		if (!$this->isSavable())
		{
			return false;
		}
		
		// Mise à jour du cache de la factory
		$id = $this->id();
		if (!is_null($id))
		{
			Factory::clearInstanceCache(get_class($this), $id);
		}
		
		// Relai
		return $this->_data->delete($this->getTableName());
	}
}