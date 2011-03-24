<?php
/**
 * Fichier de définition de la classe de manipulation de dates
 * 
 * Ce fichier contient la déclaration de la classe Date, qui permet de manipuler les dates et les périodes
 * @author Sébastien Hutter
 */

/**
 * Classe de manipulation de dates
 * 
 * La classe Date permet de manipuler les dates et les périodes
 */
class Date {
	/**
	 * Valeur de date fournie (sous forme de timestamp)
	 * @var int
	 */
	protected $_date;
	/**
	 * Indique que la date est vide
	 * @var boolean
	 */
	protected $_empty;
	/**
	 * Intervales de durée
	 * @var array
	 */
	protected static $_durees = array(
		array('%d année', '%d années', 31536000),
		array('%d mois', '%d mois_2', 2592000), // _2 pour la traduction
		array('%d jour', '%d jours', 86400),
		array('%d heure', '%d heures', 3600),
		array('%d minute', '%d minutes', 60),
		array('%d seconde', '%d secondes', 1)
	);
	/**
	 * Format de champ SQL datetime
	 * @var string
	 */
	const FORMAT_SQL_DATETIME = 'Y\-m\-d H\:i\:s';
	/**
	 * Format de champ SQL date
	 * @var string
	 */
	const FORMAT_SQL_DATE = 'Y\-m\-d';
	/**
	 * Format de champ SQL time
	 * @var string
	 */
	const FORMAT_SQL_TIME = 'H\:i\:s';
	
	/**
	 * Constructeur de la classe
	 * @param mixed $date la date à utiliser : timestamp ou chaîne à parser, ou true pour
	 * 	 utiliser la date courante (facultatif, défaut : true)
	 * @throws SCException
	 */
	public function __construct($date = true)
	{
		// Init
		$this->_empty = false;
		
		// Format de date
		if ($date === '' or $date === 0 or $date === '0' or is_null($date))
		{
			// Dates vides
			$this->_empty = true;
		}
		elseif (is_bool($date))
		{
			$this->_date = time();
		}
		elseif (is_numeric($date))
		{
			$this->_date = intval($date);
		}
		elseif (is_string($date))
		{
			// Conversion
			$date = strtotime($date);
			
			// Si non valide
			if ($date === false)
			{
				throw new SCException('Paramètre de date non valide', 1, 'Chaîne de date non valide', $date);
			}
			
			// Mémorisation
			$this->_date = $date;
		}
		else
		{
			// Echec
			throw new SCException('Paramètre de date non valide', 1, 'Aucun format reconnu', $date);
		}
	}
	
	/**
	 * Indique si la date est vide (données non valides)
	 * @return boolean une confirmation
	 */
	public function isEmpty()
	{
		return $this->_empty;
	}
	
	/**
	 * Renvoie une copie de la date
	 * @return Date l'objet copié
	 */
	public function duplicate()
	{
		return new Date($this->_date);
	}
	
	/**
	 * Décale la date du nombre de secondes fournies
	 * @param int $offset le décalage à appliquer
	 * @return Date l'objet en cours pour chaînage
	 */
	public function offset($offset)
	{
		if (!$this->_empty)
		{
			$this->_date += intval($offset);
		}
		
		// Renvoi
		return $this;
	}
	
	/**
	 * Décale la date au jour précédent
	 * @param array $days la liste des jours de la semaine à utiliser (de 1 (lundi) à 7 (dimanche)) ou NULL pour tous
	 * @return Date l'objet en cours pour chaînage
	 */
	public function previousDay($days = NULL)
	{
		// Si valide
		if (!$this->_empty)
		{
			// Décalage d'une journée
			$this->_date -= 86400;
			
			// Si jour
			if (is_array($days) and count($days) > 0)
			{
				// Sécurisation pour éviter les boucles infinies
				$counter = 0;
				
				// On boucle pour trouver un jour valide
				while ($counter < 7 and !in_array($this->toFormat('N'), $days))
				{
					$this->_date -= 86400;
					++$counter;
				}
			}
		}
		
		// Renvoi
		return $this;
	}
	
	/**
	 * Décale la date au jour suivant
	 * @param array $days la liste des jours de la semaine à utiliser (de 1 (lundi) à 7 (dimanche)) ou NULL pour tous
	 * @return Date l'objet en cours pour chaînage
	 */
	public function nextDay($days = NULL)
	{
		// Si valide
		if (!$this->_empty)
		{
			// Décalage d'une journée
			$this->_date += 86400;
			
			// Si jour
			if (is_array($days) and count($days) > 0)
			{
				// Sécurisation pour éviter les boucles infinies
				$counter = 0;
				
				// On boucle pour trouver un jour valide
				while ($counter < 7 and !in_array($this->toFormat('N'), $days))
				{
					$this->_date += 86400;
					++$counter;
				}
			}
		}
		
		// Renvoi
		return $this;
	}

	/**
	 * Définit les secondes de la date
	 * @param int $second la valeur pour les secondes
	 * @return Date l'objet Date en cours
	 */
	public function setSecond($second)
	{
		// Si valide
		if (!$this->_empty)
		{
			$this->_date = mktime($this->toFormat('G'), intval($this->toFormat('i')), $second, $this->toFormat('n'), $this->toFormat('j'), $this->toFormat('Y'));
		}

		return $this;
	}

	/**
	 * Définit les minutes de la date
	 * @param int $minute la valeur pour les minutes
	 * @return Date l'objet Date en cours
	 */
	public function setMinute($minute)
	{
		// Si valide
		if (!$this->_empty)
		{
			$this->_date = mktime($this->toFormat('G'), $minute, intval($this->toFormat('s')), $this->toFormat('n'), $this->toFormat('j'), $this->toFormat('Y'));
		}

		return $this;
	}

	/**
	 * Définit l'heure de la date, sans changer le jour.
	 * @param int $hour la valeur pour les heures
	 * @param int $minute la valeur pour les minutes (facultatif, défaut : NULL)
	 * @param int $second la valeur pour les secondes (facultatif, défaut : NULL)
	 * @return Date l'objet Date en cours
	 */
	public function setHour($hour, $minute = NULL, $second = NULL)
	{
		// Si valide
		if (!$this->_empty)
		{
			$this->_date = mktime($hour, is_null($minute) ? intval($this->toFormat('i')) : $minute, is_null($second) ? intval($this->toFormat('s')) : $second, $this->toFormat('n'), $this->toFormat('j'), $this->toFormat('Y'));
		}

		return $this;
	}

	/**
	 * Définit le jour du mois de la date
	 * @param int $day le jour du mois
	 * @return Date l'objet Date en cours
	 */
	public function setDay($day)
	{
		// Si valide
		if (!$this->_empty)
		{
			$this->_date = mktime($this->toFormat('G'), intval($this->toFormat('i')), intval($this->toFormat('s')), $this->toFormat('n'), $day, $this->toFormat('Y'));
		}

		return $this;
	}

	/**
	 * Définit le mois de la date
	 * @param int $month le mois
	 * @return Date l'objet Date en cours
	 */
	public function setMonth($month)
	{
		// Si valide
		if (!$this->_empty)
		{
			$this->_date = mktime($this->toFormat('G'), intval($this->toFormat('i')), intval($this->toFormat('s')), $month, $this->toFormat('j'), $this->toFormat('Y'));
		}

		return $this;
	}

	/**
	 * Définit l'année de la date
	 * @param int $year l'année
	 * @return Date l'objet Date en cours
	 */
	public function setYear($year)
	{
		// Si valide
		if (!$this->_empty)
		{
			$this->_date = mktime($this->toFormat('G'), intval($this->toFormat('i')), intval($this->toFormat('s')), $this->toFormat('n'), $month, $this->toFormat('j'), $year);
		}

		return $this;
	}
	
	/**
	 * Renvoie le timestamp actuel de la date
	 * @return int le timestamp, ou NULL si vide
	 */
	public function getTime()
	{
		return $this->_empty ? NULL : $this->_date;
	}
	
	/**
	 * Renvoie la date au format compact YYYYMMJJ, par exemple pour comparer des dates
	 * @return int la valeur de la date au format YYYYMMJJ, ou NULL si vide
	 */
	public function getDateInt()
	{
		return $this->_empty ? NULL : intval($this->toFormat('Ymd'));
	}

	/**
	 * Renvoie la date au format compact YYYYMMJJHHMMSS, par exemple pour comparer des dates
	 * @return int la valeur de la date au format YYYYMMJJHHMMSS, ou NULL si vide
	 */
	public function getDateTimeInt()
	{
		return $this->_empty ? NULL : $this->toFormat('YmdHis');
	}
	
	/**
	 * Le nom complet du jour formatté selon la locale
	 * @return string le jour de la semaine complet
	 */
	public function getDay()
	{
		return self::getDayName(intval($this->toFormat('w')), $this->getLocale());
	}
	
	/**
	 * Le nom court du jour formatté selon la locale
	 * @return string le jour de la semaine complet
	 */
	public function getShortDay()
	{
		return self::getShortDayName(intval($this->toFormat('w')), $this->getLocale());
	}
	
	/**
	 * Le nom complet du mois formatté selon la locale
	 * @return string le nom du mois complet
	 */
	public function getMonth()
	{
		return self::getMonthName(intval($this->toFormat('n')), $this->getLocale());
	}
	
	/**
	 * Le nom court du mois formatté selon la locale
	 * @return string le nom du mois complet
	 */
	public function getShortMonth()
	{
		return self::getShortMonthName(intval($this->toFormat('n')), $this->getLocale());
	}
	
	/**
	 * Renvoie la date en utilisant une chaîne de format
	 * @param string $format un format identique à ceux de la fonction date(), ou une des constantes
	 * 	 FORMAT_* de la classe
	 * @return string la date formattée, ou NULL si vide
	 */
	public function toFormat($format)
	{
		return $this->_empty ? NULL : date($format, $this->_date);
	}
	
	/**
	 * Renvoie la date au format DATETIME SQL
	 * @return string la date formattée, ou NULL si vide
	 */
	public function getSQLDatetime()
	{
		return $this->_empty ? NULL : $this->toFormat(self::FORMAT_SQL_DATETIME);
	}
	
	/**
	 * Renvoie la date au format DATE SQL
	 * @return string la date formattée, ou NULL si vide
	 */
	public function getSQLDate()
	{
		return $this->_empty ? NULL : $this->toFormat(self::FORMAT_SQL_DATE);
	}
	
	/**
	 * Renvoie la date au format TIME SQL
	 * @return string la date formattée, ou NULL si vide
	 */
	public function getSQLTime()
	{
		return $this->_empty ? NULL : $this->toFormat(self::FORMAT_SQL_TIME);
	}
	
	/**
	 * Calcule la différence (nombre de secondes écoulées) entre la date de l'objet et la date fournie
	 * Si la date courante est antérieure, la différence sera positive, sinon elle sera négative
	 * @param mixed $date un objet date ou toute valeur valide pour une date (voir __construct()) 
	 * 	 (optionnel, défaut : true)
	 * @return int la différence en secondes, ou NULL si vide
	 */
	public function getDiff($date = true)
	{
		// Si non défini
		if ($this->_empty)
		{
			return NULL;
		}
		
		// Formattage
		if (!$date instanceof Date)
		{
			$date = new Date($date);
		}
		
		// Différence
		return $date->getTime()-$this->getTime();
	}

	/**
	 * Indique si la date courante se situe avant celle passée en paramètre.
	 * @param mixed $date un objet date ou toute valeur valide pour une date (voir __construct())
	 * 	 (optionnel, défaut : true)
	 * @param boolean $confirmIfSame indique si la condition est vérifiée si les dates sont identiques
	 * 	 (facultatif, défaut : true)
	 * @return boolean la confirmation que la date est antérieure ou non
	 */
	public function isBefore($date, $confirmIfSame = true)
	{
		return $confirmIfSame ? ($this->getDiff($date) >= 0) : ($this->getDiff($date) > 0);
	}

	/**
	 * Indique si la date courante est la même que celle passée en paramètre
	 * @param mixed $date un objet date ou toute valeur valide pour une date (voir __construct())
	 * 	 (optionnel, défaut : true)
	 * @return boolean la confirmation que les dates sont équivalentes ou non
	 */
	public function isSameAs($date)
	{
		return ($this->getDiff($date) == 0);
	}

	/**
	 * Indique si la date courante se situe après celle passée en paramètre. Renvoie
	 * true si les dates sont équivalentes
	 * @param mixed $date un objet date ou toute valeur valide pour une date (voir __construct())
	 * 	 (optionnel, défaut : true)
	 * @param boolean $confirmIfSame indique si la condition est vérifiée si les dates sont identiques
	 * 	 (facultatif, défaut : true)
	 * @return boolean la confirmation que la date est postérieure ou non
	 */
	public function isAfter($date, $confirmIfSame = true)
	{
		return $confirmIfSame ? ($this->getDiff($date) <= 0) : ($this->getDiff($date) < 0);
	}
	
	/**
	 * Renvoie le texte d'affichage relatif (heure si aujourd'hui ou date si trop ancien) d'une date
	 * @param boolean $addTime indique s'il faut ajouter l'heure (uniquement si affichage de date)
	 * @param string $dateDefault le format de date à utiliser
	 * @return string la date formatée
	 */
	public function getRelativeDate($addTime = true, $dateDefault = 'd/m')
	{
		// Date du jour
		$now = Date::now();
		
		// Si pas le même jour qu'aujourd'hui
		if ($this->toFormat('N') != $now->toFormat('N'))
		{
			// Nombre de jours écoulés
			$nbJours = floor($now->getTime()/86400)-floor($this->getTime()/86400);
			if ($nbJours == 1)
			{
				return $addTime ? sprintf(__('Hier à %s'), $this->toFormat('H:i')) : __('Hier');
			}
			else
			{
				return $addTime ? $this->toFormat(__($dateDefault)).' - '.$this->toFormat('H:i') : $this->toFormat(__($dateDefault));
			}
		}
		else
		{
			return $this->toFormat('H:i');
		}
	}
	
	/**
	 * Vérifie si la date est dans l'intervale en deux autres.
	 * 
	 * Vérifie si la date appartient bien à l'intervale en deux autres. Il est possible de vérifier si une date est hors
	 * de cet intervale en inversant $debut et $fin ($debut est alors postérieur à $fin).
	 * @param mixed $debut la date de début d'intervale : un objet date ou toute valeur
	 * 	 valide pour une date (voir __construct()), ou NULL pour ne pas 
	 * 	 définir de début
	 * @param mixed $fin la date de fin d'intervale : un objet date ou toute valeur
	 * 	 valide pour une date (voir __construct()), ou NULL pour ne pas 
	 * 	 définir de fin
	 * @param boolean $defaut valeur à retourner en cas d'impossibilité de calcul (facultatif,
	 * 	 défaut : true)
	 * @return string une confirmation si la date est bien dans l'intervale ou non, ou $defaut si vide
	 */
	public function inInterval($debut, $fin, $defaut = false)
	{
		// Si non défini
		if ($this->_empty)
		{
			return $defaut;
		}
		
		// Dates
		if (!is_null($debut) and !$debut instanceof Date)
		{
			$debut = new Date($debut);
		}
		if (!is_null($fin) and !$fin instanceof Date)
		{
			$fin = new Date($fin);
		}
		
		// Si début
		if (!is_null($debut))
		{
			// Si fin
			if (!is_null($fin))
			{
				// Mode
				if ($debut->getTime() < $fin->getTime())
				{
					// Si dans l'intervale
					return ($this->getTime() >= $debut->getTime() and $this->getTime() < $fin->getTime());
				}
				else
				{
					// Si à l'extérieur de l'intervale
					return ($this->getTime() < $fin->getTime() or $this->getTime() >= $debut->getTime());
				}
			}
			else
			{
				// Si postérieure au début
				return ($this->getTime() >= $debut->getTime());
			}
		}
		elseif (!is_null($fin))
		{
			// Si antérieur à la fin
			return ($this->getTime() < $fin->getTime());
		}
		
		// Par défaut
		return $defaut;
	}
	
	/**
	 * Définit l'éventuel début de l'interval spécifié par rapport à la date.
	 * 
	 * Définit l'éventuel début de l'interval spécifié par rapport à la date. Il est possible d'obtenir le délai d'ici à la fin
	 * de cet intervale en inversant $debut et $fin ($debut est alors postérieur à $fin).
	 * @param string|int $debut la date de début d'intervale
	 * @param string|int $fin la date de fin d'intervale
	 * @return boolean|int en mode normal : false si l'intervale est déjà dépassé, true s'il la date appartient 
	 * 	 à l'intervale, ou le nombre de secondes de la date au début de l'intervale
	 * 	 en mode inversé : true si on est hors de l'intervale, ou le nombre de secondes d'ici à la fin
	 * 	 de l'intervale
	 * 	 dans les deux cas : NULL si vide
	 */
	public function nextIntervalStart($debut, $fin)
	{
		// Si non défini
		if ($this->_empty)
		{
			return NULL;
		}
		
		// Dates
		if (!is_null($debut) and !$debut instanceof Date)
		{
			$debut = new Date($debut);
		}
		if (!is_null($fin) and !$fin instanceof Date)
		{
			$fin = new Date($fin);
		}
		
		// Si début
		if (!is_null($debut))
		{
			// Si fin
			if (!is_null($fin))
			{
				// Mode
				if ($debut->getTime() < $fin->getTime())
				{
					// Si dans l'intervale
					if ($this->getTime() >= $debut->getTime() and $this->getTime() < $fin->getTime())
					{
						// Disponible
						return true;
					}
					elseif ($this->getTime() < $debut->getTime())
					{
						// Disponible dans
						return $debut->getTime()-$this->getTime();
					}
					else
					{
						// Plus disponible
						return false;
					}
				}
				else
				{
					// Si à l'extérieur de l'intervale
					if ($this->getTime() < $fin->getTime() or $this->getTime() >= $debut->getTime())
					{
						// Disponible
						return true;
					}
					else
					{
						// Disponible dans
						return $debut->getTime()-$this->getTime();
					}
				}
			}
			else
			{
				// Si après le début
				if ($this->getTime() >= $debut->getTime())
				{
					// Disponible
					return true;
				}
				else
				{
					// Disponible dans
					return $debut->getTime()-$this->getTime();
				}
			}
		}
		elseif (!is_null($fin))
		{
			// Si avant la fin
			if ($this->getTime() < $fin->getTime())
			{
				// Disponible
				return true;
			}
			else
			{
				// Plus disponible
				return false;
			}
		}
		else
		{
			// Aucune limite
			return true;
		}
	}
	
	/**
	 * Renvoie un objet date
	 * @param mixed $date la date à utiliser : timestamp ou chaîne à parser, ou true pour
	 * 	 utiliser la date courante (facultatif, défaut : true)
	 * @return Date l'objet date créé
	 */
	public static function getDate($date = true)
	{
		return new Date($date);
	}
	
	/**
	 * Renvoie l'objet de la date en cours (raccourci de Date::getDate(true))
	 * @return Date l'objet date créé
	 */
	public static function now()
	{
		return new Date(true);
	}
	
	/**
	 * Conversion d'une durée en périodes
	 * 
	 * Convertit une durée donnée en périodes de temps
	 * @param int $duration la durée à convertir
	 * @param int|boolean $detail le nombre de périodes de détail, ou false pour toutes
	 * @param boolean $simplify indique s'il faut supprimer les unités trop petites par rapport
	 * 	 à la précédente, pour éviter par exemple '3j et 5s' (facultatif,
	 * 	 défaut : true)
	 * @return array un tableau avec toutes les périodes couvertes
	 * @todo Rajouter le support des locales pour les intervales de temps
	 */
	public static function convertToPeriods($duration, $detail = 2, $simplify = true)
	{
		// Init
		$periods = array();
		$remain = $duration;
		
		// Debug
		if (!$detail)
		{
			$simplify = false;
		}
		
		// Parcours
		for ($i = 0; $i < count(self::$_durees); $i++)
		{
			// Nombre de périodes
			$nombre = floor($restant/self::$_durees[$i][2]);
			
			// Si valide
			if ($nombre >= 1)
			{
				// Ajout
				$periods[] = _n($nombre, self::$_durees[$i][0], self::$_durees[$i][1]);
				
				// Retrait
				$restant -= $nombre*self::$_durees[$i][2];
				
				// Si limite atteinte
				if (count($periods) == $detail)
				{
					// Sortie
					break;
				}
			}
			elseif ($simplify and count($periods) > 0)
			{
				// Sortie
				break;
			}
		}
		
		// Si restant
		if ((count($periods) != $detail and $restant > 0 and !$simplify) or count($periods) == 0)
		{
			// Ajout des secondes
			$periods[] = _n($restant, self::$_durees[count(self::$_durees)-1][0], self::$_durees[count(self::$_durees)-1][1]);
		}
		
		// Renvoi
		return $periods;
	}

	/**
	 * Renvoie le numéro de semaine maximal pour une année
	 * @param int $year l'année à utiliser
	 * @return int le nombre de semaines : 52 ou 53
	 */
	public static function getYearMaxWeekNumber($year)
	{
		// Renvoie le numéro de semaine du dernier jour de l'année
		$lastWeekNumber = intval(date('W', mktime(0, 0, 0, 1, 0, $year+1)));
		return ($lastWeekNumber == 1) ? 52 : $lastWeekNumber;
	}

	/**
	 * Calcule le mois correspondant au jour d'un numéro de semaine
	 * @param int $year l'année à utiliser
	 * @param int $week le numéro de semaine (de 1 à 53)
	 * @param int $day le numéro de jour ISO (de 1 à 7) (facultatif, défaut : 1 - lundi)
	 * @return int lnuméro du mois : de 1 à 12
	 */
	public static function getWeekNumberMonth($year, $week, $day = 1)
	{
		// Premier jour de l'année
		$firstDay = mktime(0, 0, 0, 1, 1, $year);

		// Si la première semaine comporte un jeudi
		$firstDayDay = date('N', $firstDay);
		if ($firstDayDay <= 4)
		{
			// La semaine 01 est la première de l'année
			$addDays = -$firstDayDay+1;
		}
		else
		{
			// La semaine 01 n'est pas la première de l'année
			$addDays = 8-$firstDayDay;
		}

		return date('n', $firstDay+(($addDays+(($week-1)*7)+$day-1)*86400));
	}

	/**
	 * Convertit une date utilisateur en date SQL, si nécessaire
	 * @param string $value la date à traiter
	 * @param boolean $date indique si la date comporte une date (défaut : true)
	 * @param boolean $time indique si la date comporte une heure (défaut : false)
	 * @param array $options un tableau avec les options suivantes :
	 * 	 - useYear
	 * 	 - useMonth
	 * 	 - useDay
	 * 	 - useHour
	 * 	 - useMinute
	 * 	 - useSecond
	 * @return string la date traitée, ou NULL si non valide
	 */
	public static function convertUserDateToSQL($value, $date = true, $time = false, $options = array())
	{
		// Init
		$value = trim($value);

		// Si déjà valide
		if ($date)
		{
			$dateFormat = $time ? '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/' : '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
		}
		else
		{
			$dateFormat = '/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/';
		}
		if (preg_match($dateFormat, $value))
		{
			return $value;
		}
		
		// Options
		$options = array_merge(array(
			'useYear' => true,
			'useMonth' => true,
			'useDay' => true,
			'useHour' => true,
			'useMinute' => true,
			'useSecond' => true
		), $options);

		// Format de vérification
		$format = '';
		if ($date)
		{
			$formatDate = array();
			if (!isset($options['useDay']) or $options['useDay'])
				$formatDate[] = '([0-9]{1,2})';
			if (!isset($options['useMonth']) or $options['useMonth'])
				$formatDate[] = '([0-9]{1,2})';
			if (!isset($options['useYear']) or $options['useYear'])
				$formatDate[] = '([0-9]{2,4})';

			if (count($formatDate) > 0)
			{
				$format .= implode('[\/\.\-:]', $formatDate);
			}
		}
		if ($time)
		{
			$formatTime = array();
			if (!isset($options['useHour']) or $options['useHour'])
				$formatTime[] = '([0-9]{1,2})';
			if (!isset($options['useMinute']) or $options['useMinute'])
				$formatTime[] = '([0-9]{1,2})';
			if (!isset($options['useSecond']) or $options['useSecond'])
				$formatTime[] = '([0-9]{1,2})';

			if (count($formatTime) > 0)
			{
				if (strlen($format) > 0)
				{
					$format.' ';
				}

				$format .= implode('[\/\.\-:]', $formatTime);
			}
		}
		
		// Vérification
		if (strlen($value) > 0)
		{
			// Vérification
			if (preg_match('/^'.$format.'$/', $value, $matches))
			{
				// Type
				if ($date and $time)
				{
					// Valeurs manquantes
					if (isset($options['useDay']) and !$options['useDay'])
						array_splice($matches, 1, 0, '01');
					if (isset($options['useMonth']) and !$options['useMonth'])
						array_splice($matches, 2, 0, '01');
					if (isset($options['useYear']) and !$options['useYear'])
						array_splice($matches, 3, 0, date('Y'));

					if (isset($options['useHour']) and !$options['useHour'])
						array_splice($matches, 4, 0, '00');
					if (isset($options['useMinute']) and !$options['useMinute'])
						array_splice($matches, 5, 0, '00');
					if (isset($options['useSecond']) and !$options['useSecond'])
						array_splice($matches, 6, 0, '00');

					$value = str_pad($matches[3], 4, '20', STR_PAD_LEFT).'-'.
							 str_pad($matches[2], 2, '0', STR_PAD_LEFT).'-'.
							 str_pad($matches[1], 2, '0', STR_PAD_LEFT).' '.
							 str_pad($matches[4], 2, '0', STR_PAD_LEFT).':'.
							 str_pad($matches[5], 2, '0', STR_PAD_LEFT).':'.
							 str_pad($matches[6], 2, '0', STR_PAD_LEFT);
				}
				elseif ($date)
				{
					// Valeurs manquantes
					if (isset($options['useDay']) and !$options['useDay'])
						array_splice($matches, 1, 0, '01');
					if (isset($options['useMonth']) and !$options['useMonth'])
						array_splice($matches, 2, 0, '01');
					if (isset($options['useYear']) and !$options['useYear'])
						array_splice($matches, 3, 0, date('Y'));

					$value = str_pad($matches[3], 4, '20', STR_PAD_LEFT).'-'.
							 str_pad($matches[2], 2, '0', STR_PAD_LEFT).'-'.
							 str_pad($matches[1], 2, '0', STR_PAD_LEFT);
				}
				else
				{
					// Valeurs manquantes
					if (isset($options['useHour']) and !$options['useHour'])
						array_splice($matches, 1, 0, '00');
					if (isset($options['useMinute']) and !$options['useMinute'])
						array_splice($matches, 2, 0, '00');
					if (isset($options['useSecond']) and !$options['useSecond'])
						array_splice($matches, 3, 0, '00');

					$value = str_pad($matches[1], 2, '0', STR_PAD_LEFT).':'.
							 str_pad($matches[2], 2, '0', STR_PAD_LEFT).':'.
							 str_pad($matches[3], 2, '0', STR_PAD_LEFT);
				}
			}
			else
			{
				$value = NULL;
			}
		}
		else
		{
			$value = NULL;
		}

		return $value;
	}
}