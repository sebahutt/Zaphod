<?php
/**
 * Classe de connexion à la base de donnée - fonctions génériques
 */
class Database
{
	/**
	 * Configuration de la connexion
	 * @var Config
	 */
	protected $_config;
	/**
	 * Objet PDO de connexion
	 * @var PDO
	 */
	protected $_link;
	/**
	 * Listing des objets tables
	 * @var array
	 */
	protected $_fields;
	/**
	 * Listing des serveurs connectés
	 * @var array
	 */
	protected static $_servers = array();
	
	/**
	 * Constructeur de la classe
	 * @param Config $config les données de configuration
	 */
	public function __construct($config)
	{
		// Init
		$this->_fields = array();
		
		// Mémorisation
		$this->_config = $config;
		
		// Connection
		$this->_connect();
	}
	
	/**
	 * Destructeur de la classe
	 * @return void
	 */
	public function __destruct()
	{
		// Fermeture de la connexion
		$this->_close();
	}
	
	/**
	 * Connexion à la base
	 * @return void
	 * @throws SCException
	 */
	protected function _connect()
	{
		// Host
		$host = 'mysql:host='.$this->_config->get('host', 'localhost').';';
		if (strlen($this->_config->get('port', '')) > 0)
		{
			$host .= 'port='.$this->_config->get('port').';';
		}
		
		try {
			// Tentative de connection
			$this->_link = new PDO($host.'dbname='.$this->_config->get('base'), $this->_config->get('user'), $this->_config->get('pass'));
			
			// Configuration
			$this->_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->_link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); 
		}
		catch (PDOException $ex)
		{
			// Erreur
			throw new SCException('Echec de la connection à la base de données : '.$ex->getMessage());
		}
		
		// Mode UTF8
		$this->_link->exec('SET NAMES \'utf8\'');
	}
	
	/**
	 * Fermeture de la connexion
	 * @return void
	 */
	protected function _close()
	{
		// Suppression de la connection
		unset($this->_link);
	}
	
	/**
	 * Exécution finale d'une requête. La requête est préparée, c'est-à-dire que les paramètres
	 * doivent être remplacés par des ? et fournis dans un tableau (dans le même ordre que dans la requête).
	 * @param string $request la requête à exécuter
	 * @param mixed $params les paramètres de la requête (facultatif, défaut : array())
	 * @return PDOStatement l'objet résultat
	 * @throws SCException
	 */
	protected function _execute($request, $params = array())
	{
		// Sécurisation
		if (!is_array($params))
		{
			$params = array($params);
		}
		
		// Ajout du préfixe des tables
		$prefix = $this->_config->get('prefix');
		if (strlen($prefix) > 0)
		{
			$request = preg_replace('/(INTO|UPDATE|FROM|JOIN|UNION|DESCRIBE)(\s+`?)/i', '$1$2'.$prefix, $request);
		}
		
		// Préparation
		$result = $this->_link->prepare($request);
		
		// Exécution si valide
		if ($result === false or !$result->execute($params))
		{
			throw new SCException('Requête non valide : '.$result->errorInfo());
		}
		
		// Renvoi
		return $result;
	}
	
	/**
	 * Exécution d'une requête de type SELECT. La requête est préparée, les paramètres sont à fournir séparément
	 * @param mixed $params les paramètres de la requête (facultatif, défaut : array())
	 * @return DatabaseResult un objet de résultat contenant la liste des lignes renvoyées
	 */
	public function query($request, $params = array())
	{
		// Exécution
		$result = $this->_execute($request, $params);
		
		// Récupération
		$rows = $result->fetchAll(PDO::FETCH_ASSOC);
		
		// Libération du pointeur
		$result->closeCursor();
		
		// Renvoi de l'objet de résultat
		return new DatabaseResult($this, $rows, $request, $params);
	}
	
	/**
	 * Renvoi une valeur unique (première valeur de la première ligne) d'une requête SELECT. La requête est préparée, les paramètres sont à fournir séparément
	 * @param string $request la requête à exécuter
	 * @param mixed $params les paramètres de la requête (facultatif, défaut : array())
	 * @return mixed la valeur unique
	 * @throws SCException
	 */
	public function value($request, $params = array())
	{
		// Exécution
		$result = $this->_execute($request, $params);
		
		// Init
		$retour = $result->fetchColumn();
		
		// Libération du pointeur
		$result->closeCursor();
		
		// Renvoi
		return $retour;
	}
	
	/**
	 * Renvoi une ligne unique (première ligne) d'une requête SELECT. La requête est préparée, les paramètres sont à fournir séparément
	 * @param string $request la requête à exécuter
	 * @param mixed $params les paramètres de la requête (facultatif, défaut : array())
	 * @return array|boolean la ligne unique, ou false en cas d'échec
	 * @throws SCException
	 */
	public function row($request, $params = array())
	{
		// Exécution
		$result = $this->_execute($request, $params);
		
		// Récupération
		$retour = $result->fetch(PDO::FETCH_ASSOC);
		
		// Libération du pointeur
		$result->closeCursor();
		
		// Renvoi
		return $retour;
	}
	
	/**
	 * Exécution d'une requête de type UPDATE, INSERT, DELETE. La requête est préparée, les paramètres sont à fournir séparément
	 * @param string $request la requête à exécuter
	 * @param mixed $params les paramètres de la requête (facultatif, défaut : array())
	 * @return int le nombre de lignes affectées ou le nouvel id suivant la requête
	 */
	public function exec($request, $params = array())
	{
		// Exécution
		$result = $this->_execute($request, $params);
		
		// Mode
		if (strtoupper(substr(ltrim($request), 0, 6)) == 'INSERT')
		{
			// Renvoi du nouvel id
			return $this->_link->lastInsertId();
		}
		else
		{
			// Renvoi du nombre de lignes affectées
			return $result->rowCount();
		}
	}
	
	/**
	 * Renvoie la liste des champs de la table
	 * @param string $table le nom de la table
	 * @return array la liste des champs
	 */
	public function getTable($table)
	{
		// Si pas encore défini
		if (!isset($this->_fields[$table]))
		{
			// Init
			$this->_fields[$table] = new DatabaseTable($this, $table);
		}
		
		// Renvoi
		return $this->_fields[$table];
	}
	
	/**
	 * Obtient un serveur de base de donnée
	 * @param string $name le nom du serveur dans le fichier de configuration (facultatif, défaut : DEFAULT_DB)
	 * @return Database l'objet de connection au serveur
	 * @throws SCException
	 */
	public static function get($name = DEFAULT_DB)
	{
		if (!isset(self::$_servers[$name]))
		{
			if ($config = Env::getConfig('servers')->get($name, false))
			{
				self::$_servers[$name] = new Database($config);
			}
			else
			{
				throw new SCException('Impossible de se connecter à la base de données', 1, 'Aucune configuration fournie');
			}
		}
		
		return self::$_servers[$name];
	}
}

/**
 * Objet de configuration de table - fonctions génériques
 */
class DatabaseTable implements Iterator
{
	/**
	 * Base de donnée de rattachement
	 * @var Database
	 */
	protected $_database;
	/**
	 * Nom de la table cible
	 * @var string
	 */
	protected $_name;
	/**
	 * Liste des champs
	 * @var array
	 */
	protected $_fields;
	/**
	 * Index primaire de la table
	 * @var DatabaseField
	 */
	protected $_primary;
	/**
	 * Position de l'iterateur
	 * @var int
	 */
	protected $_position;
	/**
	 * Liste des clés pour l'itérateur
	 * @var int
	 */
	protected $_keys;
	
	/**
	 * Constructeur de la classe
	 * @param Database $database l'objet Database de rattachement
	 * @param string $table le nom de la table cible
	 */
	public function __construct($database, $table)
	{
		// Init
		$this->_position = 0;
		
		// Mémorisation
		$this->_database = $database;
		$this->_name = $table;
		
		// Construction
		$this->update();
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
		return $this->getField($this->_keys[$this->_position]);
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
	 * Renvoie l'objet Database de rattachement
	 * @return Database l'objet Database
	 */
	public function getDatabase()
	{
		return $this->_database;
	}
	
	/**
	 * Renvoie le nom de la table cible
	 * @return string le nom de la table
	 */
	public function getName()
	{
		return $this->_name;
	}
	
	/**
	 * Mise à jour des données de la table
	 * @return void
	 */
	public function update()
	{
		// Init
		$this->_fields = array();
		$this->_primary = NULL;
		
		// Récupération
		$fields = $this->_database->query('DESCRIBE `'.$this->_name.'`');
		foreach ($fields as $field)
		{
			// Données
			$datas = array(
				'field' =>		$field['Field'],
				'key' => 		false,
				'primary' => 	false,
				'typeSQL' => 	'varchar',
				'unsigned' => 	false,
				'length' => 	false,
				'float' => 		0,
				'default' =>	NULL,
				'type' =>		'text'
			);
			
			// Si clé primaire
			if (!is_null($field['Key']) and strlen($field['Key']) > 0)
			{
				$datas['key'] = true;
				if ($field['Key'] == 'PRI')
				{
					// Ajout
					$datas['primary'] = true;
				}
			}
			
			// Extraction
			if (strpos($field['Type'], '(') !== false)
			{
				// Type
				$datas['typeSQL'] = strtolower(substr($field['Type'], 0, strpos($field['Type'], '(')));
				
				// Paramètres complémentaires
				$data = substr($field['Type'], 0, strpos($field['Type'], ')'));
				$data = explode(',', substr($data, strpos($data, '(')+1));
				if (isset($data[0]))
				{
					// Taille
					$datas['length'] = intval($data[0]);
				}
				if (isset($data[1]))
				{
					// Flottant
					$datas['decimals'] = intval($data[1]);
				}
				
				// Options
				$unsigned = substr($field['Type'], strpos($field['Type'], ')')+2);
				
				// Si options
				if ($unsigned and $unsigned == 'unsigned')
				{
					// Conversion
					$datas['unsigned'] = true;
				}
			}
			else
			{
				$datas['typeSQL'] = strtolower($field['Type']);
			}
			
			// Si défaut
			if ($field['Null'] == 'YES')
			{
				// Si non null
				if (!is_null($field['Default']))
				{
					// Typage
					if (is_numeric($field['Default']))
					{
						// Récupération
						$datas['default'] = intval($field['Default']);
					}
					else
					{
						// Récupération
						$datas['default'] = $field['Default'];
					}
				}
			}
			
			// Format php
			switch ($datas['typeSQL'])
			{
				case 'bit':
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
				case 'year':
					// Type
					$datas['type'] = 'number';
					break;
				
				case 'float':
				case 'double':
				case 'decimal':
					// Type
					$datas['type'] = 'float';
					break;
				
				case 'date':
				case 'datetime':
				case 'time':
					$datas['type'] = $datas['typeSQL'];
					break;
					
				case 'timestamp':
					$datas['type'] = 'datetime';
					break;
			}
			
			// Ajout
			$name = $field['Field'];
			$this->_fields[$name] = new DatabaseField($this, $datas);
			
			// Si primaire
			if ($this->_fields[$name]->primary and !$this->_primary)
			{
				// Stockage
				$this->_primary = $name;
			}
		}
		
		// Mise à jour des clés pour l'itérateur
		$this->_keys = array_keys($this->_fields);
	}
	
	/**
	 * Indique si un champ est défini - méthode magique
	 * @param string $name nom du champ
	 * @return boolean un confirmation si le champ est défini ou non
	 */
	public function __isset($name)
	{
		return $this->hasField($name);
	}
	
	/**
	 * Indique si un champ est défini
	 * @param string $name nom du champ
	 * @return boolean un confirmation si le champ est défini ou non
	 */
	public function hasField($name)
	{
		// Si non défini
		return isset($this->_fields[$name]);
	}
	
	/**
	 * Renvoie un champ - méthode magique
	 * @param string $name nom du champ
	 * @return DatabaseField le champ
	 * @throws SCException
	 */
	public function __get($name)
	{
		// Relai
		return $this->getField($name);
	}
	
	/**
	 * Renvoie la liste des champs
	 * @return array la liste des champs, sous la forme array( nom => objet )
	 */
	public function getFields()
	{
		return $this->_fields;
	}
	
	/**
	 * Renvoie un champ
	 * @param string $name nom du champ
	 * @return DatabaseField le champ
	 * @throws SCException
	 */
	public function getField($name)
	{
		// Si non défini
		if (!$this->hasField($name))
		{
			throw new SCException('Récupération d\'un champ non existant : '.$name);
		}
		
		// Renvoi
		return $this->_fields[$name];
	}
	
	/**
	 * Renvoie les noms des champs de la table
	 * @return array les noms des champs
	 */
	public function getFieldsNames()
	{
		// Relai
		return array_keys($this->_fields);
	}
	
	/**
	 * Renvoie le nom du champ primaire
	 * @return string le nom du champ, ou NULL si non défini
	 */
	public function getPrimary()
	{
		return $this->_primary;
	}
	
	/**
	 * Remplit un tableau avec les valeurs par défaut
	 * @param array $data les données déjà existantes (facultatif, défaut : array())
	 * @return DatabaseResultRow l'objet de ligne de résultat
	 */
	public function newRow($data = array())
	{
		// Parcours
		foreach ($this->_fields as $field => $config)
		{
			// Si pas déjà existant
			if (!isset($data[$field]))
			{
				$data[$field] = $config->default;
			}
		}
		
		// Génération du pseudo-résultat
		$result = new DatabaseResult($this->getDatabase(), array($data), $this);
		
		// Renvoi
		return $result[0];
	}
}

/**
 * Objet de configuration de champs - fonctions génériques
 */
class DatabaseField
{
	/**
	 * Table de rattachement
	 * @var DatabaseTable
	 */
	protected $_table;
	/**
	 * Configuration du champ
	 * @var array
	 */
	protected $_config;
	
	/**
	 * Constructeur de la classe
	 * @param DatabaseTable $table la table de rattachement
	 * @param array $config les données de configuration
	 */
	public function __construct($table, $config)
	{
		// Mémorisation
		$this->_table = $table;
		$this->_config = $config;
	}
	
	/**
	 * Renvoie l'objet DatabaseTable de rattachement
	 * @return DatabaseTable l'objet DatabaseTable
	 */
	public function getTable()
	{
		return $this->_table;
	}
	
	/**
	 * Accesseur des données de l'objet
	 * @param string $var nom de la propriété demandée
	 * @return mixed la valeur si défini, NULL sinon
	 * @throws SCException
	 */
	public function __get($var)
	{
		// Si non défini
		if (!isset($this->_config[$var]) and !is_null($this->_config[$var]))
		{
			throw new SCException('Récupération d\'une propriété non définie', 999, 'Propriété : '.$var);
		}
		
		// Renvoi
		return $this->_config[$var];
	}
	
	/**
	 * Formate un champ en fonction de sa configuration (longueur, format...)
	 * @param mixed $value la valeur à stocker
	 * @return string|int la valeur prête pour insertion
	 */
	public function formatField($value)
	{
		if (is_null($value))
		{
			return $value;
		}
		
		// Type
		switch ($this->_config['type'])
		{
			case 'number':
				return intval($value);
				break;
			
			case 'float':
				return round(floatval($value), $this->_config['decimals']);
				break;
			
			case 'datetime':
			case 'date':
			case 'time':
				// Si non valide
				if (is_null($value) or strlen($value) < 1 or $value == 0)
				{
					// Effacement
					return NULL;
				}
				else
				{
					// Si int
					if (is_numeric($value))
					{
						// Type
						if ($this->_config['type'] == 'date')
						{
							$format = Date::FORMAT_SQL_DATE;
						}
						elseif ($this->_config['type'] == 'time')
						{
							$format = Date::FORMAT_SQL_TIME;
						}
						else
						{
							$format = Date::FORMAT_SQL_DATETIME;
						}
						
						// Conversion
						$value = Date::getDate(intval($value))->toFormat($format);
					}
					
					// Renvoi
					return $value;
				}
				break;
			
			default:
				// Si longueur
				if ($this->_config['length'] and strlen($value) > $this->_config['length'])
				{
					// Découpe
					$value = substr($value, 0, $this->_config['length']);
				}
				
				// Renvoi
				return $value;
				break;
		}
	}
}

/**
 * Classe de stockage de résultat de requête SELECT
 */
class DatabaseResult extends ArrayHolder {
	/**
	 * Objet Database de rattachement
	 * @var Database
	 */
	protected $_database;
	/**
	 * Liste des tables concernées
	 * @var array
	 */
	protected $_tables;
	/**
	 * Liste des champs
	 * @var array
	 */
	protected $_fields;
	/**
	 * Liste des noms des tables concernées
	 * @var array
	 */
	protected $_tablesNames;
	/**
	 * Requête originale
	 * @var string
	 */
	protected $_request;
	/**
	 * Paramètres de la requête originale
	 * @var array
	 */
	protected $_params;
	/**
	 * Statistiques de pagination
	 * @var array
	 */
	protected $_pagination;
	/**
	 * Total des résultats possibles
	 * @var int
	 */
	protected $_total;
	
	/**
	 * Constructeur de la classe de résultat
	 * @param Database $database l'objet Database de rattachement
	 * @param array $rows les lignes de résultat
	 * @param string|DatabaseTable|array $request la requête originale, ou la table ou liste de tables correspondant aux résultats (sous la forme array( nom => objet )
	 * @param array $params les paramètres de la requête originale (facultatif, défaut : array(), ignoré si request est une liste de tables)
	 */
	public function __construct($database, $rows, $request, $params = array())
	{
		// Conversion
		$list = array();
		foreach ($rows as $row)
		{
			$list[] = new DatabaseResultRow($this, $row);
		}
		
		parent::__construct($list);
		
		// Mémorisation
		$this->_database = $database;
		if (is_string($request))
		{
			$this->_request = trim($request);
			$this->_params = $params;
		}
		else
		{
			$this->_tables = is_array($request) ? $request : array( $request->getName() => $request );
			$this->_request = NULL;
			$this->_params = NULL;
		}
	}
	
	/**
	 * Renvoie la base de donnée de rattachement
	 * @return Database l'objet de la base de données
	 */
	public function getDatabase()
	{
		return $this->_database;
	}
	
	/**
	 * Renvoie la liste des tables concernées par la requête
	 * @return array la liste des tables
	 */
	public function getTables()
	{
		if (!isset($this->_tables))
		{
			// Init
			$this->_tables = array();
			
			// Détection
			if (!is_null($this->_request) and preg_match_all('/\s+(?:FROM|JOIN|UNION)\s+`?([^\s`]+)`?/i', $this->_request, $matches))
			{
				foreach ($matches[1] as $tableName)
				{
					$this->_tables[$tableName] = $this->_database->getTable($tableName);
				}
			}
		}
		
		return $this->_tables;
	}
	
	/**
	 * Renvoie l'objet de la table si existant
	 * @param string $name le nom de la table
	 * @return DatabaseTable l'objet si existant, NULL sinon
	 */
	public function getTable($name)
	{
		$tables = $this->getTables();
		return isset($tables[$name]) ? $tables[$name] : NULL;
	}
	
	/**
	 * Renvoie la liste des noms des tables concernées par la requête
	 * @return array la liste des noms des tables
	 */
	public function getTablesNames()
	{
		if (!isset($this->_tablesNames))
		{
			$this->_tablesNames = array_keys($this->getTables());
		}
		
		return $this->_tablesNames;
	}
	
	/**
	 * Renvoie le nom de la première table de la requête, si définie
	 * @return string le nom de la table, ou NULL
	 */
	public function getFirstTableName()
	{
		$tablesNames = $this->getTablesNames();
		return isset($tablesNames[0]) ? $tablesNames[0] : NULL;
	}
	
	/**
	 * Renvoie la liste des champs des tables concernées par la requête (peuvent ne pas être inclus dans le requête)
	 * @return array la liste des champs, sous la forme array( nom => objet )
	 */
	public function getTablesFields()
	{
		if (!isset($this->_fields))
		{
			// Init
			$this->_fields = array();
			$tables = $this->getTables();
			
			// Enregistrement
			foreach ($tables as $table)
			{
				$this->_fields = array_merge($table->getFields(), $this->_fields);
			}
		}
		
		return $this->_fields;
	}
	
	/**
	 * Indique si une des tables de la requête contient le champ demandé (pas forcément inclu dans la requête originale)
	 * @param string $name le nom du champ
	 * @return boolean une confirmation
	 */
	public function hasTableField($name)
	{
		$fields = $this->getTablesFields();
		return isset($this->_fields[$name]);
	}
	
	/**
	 * Renvoi l'objet du champ demandé, s'il existe sur une des tables de la requête (pas forcément inclu dans la requête originale)
	 * @param string $name le nom du champ
	 * @return DatabaseField le champ si existant, NULL sinon
	 */
	public function getTableField($name)
	{
		$fields = $this->getTablesFields();
		return isset($this->_fields[$name]) ? $this->_fields[$name] : NULL;
	}
	
	/**
	 * Renvoie la requête d'origine
	 * @return string la requête (NULL si non définie)
	 */
	public function getRequest()
	{
		return $this->_request;
	}
	
	/**
	 * Renvoie les paramètres de la requête d'origine
	 * @return array les paramètres (NULL si non définis)
	 */
	public function getParams()
	{
		return $this->_params;
	}
	
	/**
	 * Récupère la pagination de la requête
	 * @return array les données de pagination : start, end, range, page
	 */
	protected function _getPagination()
	{
		if (!isset($this->_pagination))
		{
			// Par défaut
			$this->_pagination = array(
				'start' => 0,
				'end' => max(0, $this->count()-1),
				'range' => false,
				'page' => 0
			);
			
			if (!is_null($this->_request) and preg_match('/\sLIMIT([0-9\s,]+);?$/i', $this->_request, $matches))
			{
				// Paramètres de pagination
				$params = array_map('intval', explode(',', preg_replace('/\s+/', '', $matches[1])));
				
				// Mode
				if (count($params) < 2)
				{
					$this->_pagination['end'] = $params[0]-1;
					$this->_pagination['range'] = $params[0];
				}
				else
				{
					$this->_pagination['start'] += $params[0];
					$this->_pagination['end'] += $params[0];
					$this->_pagination['range'] = $params[1];
					$this->_pagination['page'] = floor($params[0]/$params[1]);
				}
			}
		}
		
		return $this->_pagination;
	}
	
	/**
	 * Renvoie l'index de la première ligne dans le total des résultats de la requête (commence à 0)
	 * @return int l'index de début
	 */
	public function getStart()
	{
		$pagination = $this->_getPagination();
		return $pagination['start'];
	}
	
	/**
	 * Renvoie l'index de la dernière ligne dans le total des résultats de la requête (commence à 0)
	 * @return int l'index de fin
	 */
	public function getEnd()
	{
		$pagination = $this->_getPagination();
		return $pagination['end'];
	}
	
	/**
	 * Renvoie la limite maximum de résultat
	 * @return int|boolean la limite si existante, false sinon
	 */
	public function getRange()
	{
		$pagination = $this->_getPagination();
		return $pagination['range'];
	}
	
	/**
	 * Renvoie la page courante dans la pagination des résultats de la requête (commence à 0)
	 * @return int la page courante
	 */
	public function getPage()
	{
		$pagination = $this->_getPagination();
		return $pagination['page'];
	}
	
	/**
	 * Obtention du total des résultats possibles
	 * @param boolean $fallbackToCount si true, la fonction renvoie le nombre de lignes stocké si elle n'est pas en mesure d'obtenir le total (facultatif, défaut : true) 
	 * @return int|boolean le nombre total, ou false si $fallbackToCount vaut false
	 */
	public function getTotal($fallbackToCount = true)
	{
		if (!isset($this->_total))
		{
			// Par défaut
			$this->_total = false;
			
			// Si requête valide
			if (!is_null($this->_request) and strlen($this->_request) > 0)
			{
				if (preg_match('/SELECT.+FROM(.+)LIMIT[0-9\s,]+;?$/i', $this->_request, $matches))
				{
					// Requête du total
					$this->_total = $this->_database->value('SELECT COUNT(*) FROM'.$matches[1]);
				}
				else
				{
					// Pas de limite, l'ensemble des résultats représente donc le total
					$this->_total = $this->count();
				}
			}
		}
		
		return ($this->_total === false and $fallbackToCount) ? $this->count() : $this->_total;
	}
	
	/**
	 * Convertit la liste des résultats en instances de la classe fournie
	 * @param string $class le nom de la classe
	 * @return array la liste des instances crées
	 */
	public function castAs($class)
	{
		$list = array();
		foreach ($this->_array as $row)
		{
			$list[] = Factory::getInstance($class, $row);
		}
		
		return $list;
	}
}

/**
 * Classe de stockage de ligne de résultat de requête SELECT
 */
class DatabaseResultRow extends DataHolderWatcher {
	/**
	 * Objet DatabaseResult de rattachement
	 * @var DatabaseResult
	 */
	protected $_result;
	
	/**
	 * Constructeur de la classe de résultat
	 * @param DatabaseResult $result l'objet DatabaseResult de rattachement
	 * @param array $data les données de la ligne de résultat
	 */
	public function __construct($result, $data)
	{
		parent::__construct($data);
		
		// Mémorisation
		$this->_result = $result;
	}
	
	/**
	 * Renvoie l'objet de résultat de rattachement
	 * @return DatabaseResult l'objet de résultat
	 */
	public function getResult()
	{
		return $this->_result;
	}
	
	/**
	 * Indique si la ligne est en cours de création pour la table donnée
	 * @param string $table la table, ou true pour récupérer la première table (facultatif, défaut : true)
	 * @return boolean une confirmation
	 */
	public function isNew($table = true)
	{
		// Table automatique
		if ($table === true)
		{
			$table = $this->getResult()->getFirstTableName();
		}
		
		// Validité
		if ($tableObject = $this->getResult()->getTable($table))
		{
			$primary = $tableObject->getPrimary();
			return $primary ? ($this->get($primary, 0) == 0) : true;
		}
		else
		{
			throw new SCException('Test d\'existence sur une table non utilisée', 999, 'Table : '.$table);
		}
	}
	
	/**
	 * Méthode de définition des données de la ligne
	 * @param string|array $index le nom de l'index à modifier, ou un tableau de valeurs avec les index en clés
	 * @param mixed $value la valeur à affecter (ignorée si $index est un tableau) (facultatif, défaut : NULL)
	 * @return void
	 * @throws SCException
	 */
	public function set($index, $value = NULL)
	{
		// Mode tableau
		if (is_array($index))
		{
			// Application des données complémentaires
			foreach ($index as $name => $value)
			{
				// Si existant
				$field = $this->getResult()->getTableField($name);
				if (!is_null($field) and !$field->primary)
				{
					// Application
					$this->set($name, $value);
				}
			}
		}
		else
		{
			// Champ
			$field = $this->getResult()->getTableField($index);
			
			// Si non existant
			if (is_null($field))
			{
				throw new SCException('Ecriture d\'un champ non existant', 'Champ : '.$index);
			}
			
			// Type de champ
			if ($field->primary)
			{
				throw new SCException('Modification d\'un champ primaire interdite', 'Champ : '.$index.', table : '.$field->getTable()->getName());
			}
			
			// Ecriture
			parent::set($index, $field->formatField($value));
		}
	}
	
	/**
	 * Déclenche l'enregistrement des champs modifiés, et crée les entrées si nécessaire
	 * @param string|array|boolean $tables la ou les tables à enregistrer, true pour utiliser automatiquement la première, ou false pour toutes (facultatif, défaut : false)
	 * @return int|boolean l'id de l'entrée de la première table enregistrée, ou false en cas d'erreur
	 */
	public function save($tables = false)
	{
		// Init
		$tablesNames = $this->getResult()->getTablesNames();
		if (count($tablesNames) === 0)
		{
			throw new SCException('Aucune table définie pour la requête', 999, 'Requête : INSERT/UPDATE');
		}
		$return = false;
		$modified = $this->getModifiedList();
		
		// Tables concernées
		if (is_string($tables))
		{
			$tables = array($tables);
		}
		elseif ($tables === false)
		{
			$tables = $tablesNames;
		}
		elseif ($tables === true)
		{
			$tables = array($this->getResult()->getFirstTableName());
		}
		
		// Parcours
		foreach ($tables as $name)
		{
			// Si existant
			if ($table = $this->getResult()->getTable($name))
			{
				// Récupération
				$primary = $table->getPrimary();
				$isNew = $this->isNew($name);
				
				// Sécurisation
				if (!$primary)
				{
					continue;
				}
				elseif (!$isNew and count($modified) === 0)
				{
					// Aucun champ modifié, pas la peine de parcourir toutes les tables
					return $this->_data[$primary];
				}
				
				// Détection si nouvel objet
				if ($isNew)
				{
					// Si nouveau, on force l'écriture de tous les champs
					$tableModified = $table->getFieldsNames();
				}
				else
				{
					$tableModified = array_intersect($modified, $table->getFieldsNames());
				}
				
				// Requête
				$insert = array();
				$update = array();
				$values = array();
				if (count($tableModified) == 0)
				{
					if ($return === false)
					{
						$return = $this->_data[$primary];
					}
					continue;
				}
				foreach ($tableModified as $field)
				{
					// Si pas index
					if ($field != $primary)
					{
						// Champs
						$insert[] = '`'.$field.'`';
						$update[] = '`'.$field.'`=?';
						
						// Valeur
						$values[] = isset($this->_data[$field]) ? $this->_data[$field] : $table->getField($field)->default;
					}
				}
				
				// Si aucun champ modifié
				if (count($insert) == 0)
				{
					if ($return === false)
					{
						$return = $isNew ? false : $this->_data[$primary];
					}
					continue;
				}
				
				// Mode
				if (!$isNew)
				{
					// Requête
					$result = $this->getResult()->getDatabase()->exec('UPDATE `'.$name.'` SET '.implode(', ', $update). ' WHERE `'.$primary.'`='.intval($this->_data[$primary]).';', $values);
				}
				else
				{
					// Requête
					$this->_data[$primary] = $this->getResult()->getDatabase()->exec('INSERT INTO `'.$name.'` ('.implode(', ', $insert).') VALUES ('.implode(', ', array_fill(0, count($insert), '?')).');', $values);
					
					// Mise à jour du cache de la factory
					Factory::updateInstanceCache(get_class($this), $this, $this->_data[$primary]);
				}
				
				// Valeur renvoyée
				if ($return === false)
				{
					$return = $this->_data[$primary];
				}
			}
			else
			{
				throw new SCException('Opération sur une table non utilisée', 999, 'Requête : INSERT/UPDATE, table : '.$table);
			}
		}
		
		// Reset
		$this->resetModifiedList();
		
		return $return;
	}
	
	/**
	 * Suppression d'une ligne de résultat
	 * @param string|array|boolean $tables la ou les tables à effacer, true pour utiliser automatiquement la première, ou false pour toutes (facultatif, défaut : false)
	 * @return boolean la confirmation de la suppression de l'entrée de la première table enregistrée
	 */
	public function delete($tables)
	{
		// Init
		$tablesNames = $this->getResult()->getTablesNames();
		if (count($tablesNames) === 0)
		{
			throw new SCException('Aucune table définie pour la requête', 999, 'Requête : DELETE');
		}
		$firstTable = true;
		$return = false;
		
		// Tables concernées
		if (is_string($tables))
		{
			$tables = array($tables);
		}
		elseif ($tables === false)
		{
			$tables = $tablesNames;
		}
		elseif ($tables === true)
		{
			$tables = array($this->getResult()->getFirstTableName());
		}
		
		// Parcours
		foreach ($tables as $name)
		{
			// Si existant
			if ($table = $this->getResult()->getTable($name))
			{
				// Sécurisation
				$primary = $table->getPrimary();
				if (!$primary or $this->isNew($name))
				{
					continue;
				}
				
				// Requête
				$this->getResult()->getDatabase()->exec('DELETE FROM `'.$name.'` WHERE `'.$primary.'`=?;', intval($this->_data[$primary]));
				
				// Effacement de l'index
				$this->_data[$primary] = NULL;
				
				// Indication de succès s'il s'agit de la première table
				if ($firstTable)
				{
					$return = true;
				}
			}
			else
			{
				throw new SCException('Opération sur une table non utilisée', 999, 'Requête : DELETE, table : '.$table);
			}
			
			$firstTable = false;
		}
		
		return $return;
	}
}