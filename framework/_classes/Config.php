<?php
/**
 * Classe de stockage de configuration
 */
class Config extends DataHolder {
	/**
	 * Configurations parente
	 * @var Config
	 */
	protected $_parents;
	
	/**
	 * Constructeur de la classe de configuration
	 * @param array $config tableau associatif des paramètres
	 * @param Config configuration parente, ou NULL pour aucune (facultatif, défaut : NULL)
	 * @throws SCException
	 */
	public function __construct($config, $parent = NULL)
	{
		try
		{
			parent::__construct($config);
		}
		catch (SCException $ex)
		{
			throw new SCException('Paramètres de configuration non valides', 2, $ex);
		}
		
		// Init
		if (is_object($parent))
		{
			$this->_parent = $parent;
		}
		
		// Conversion
		foreach ($this->_data as $index => $value)
		{
			if ($this->_isConfigData($value))
			{
				// Tableau de paramètres, conversion
				$this->_data[$index] = new Config($value);
			}
		}
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
		
		// Fonctionnement avec héritage
		if (isset($this->_data[$index]))
		{
			return $this->_data[$index];
		}
		else
		{
			return isset($this->_parent) ? $this->_parent->get($index, $defaut) : $defaut;
		}
	}
	
	/**
	 * Indique si le type de donnée fourni doit être converti
	 * @return boolean une confirmation
	 */
	protected function _isConfigData($data)
	{
		return (is_object($data) or (is_array($data) and count($data) > 0 and !isset($data[0])));
	}
	
	/**
	 * Définition d'un index
	 * @param string $param le nom de l'index à modifier
	 * @param mixed $value la valeur à affecter
	 * @return void
	 */
	public function set($param, $value = NULL)
	{
		// Format
		if ($this->_isConfigData($value))
		{
			// Tableau de paramètres, conversion
			$value = new Config($value);
		}
		
		parent::set($param, $value);
	}
}