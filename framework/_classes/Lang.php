<?php
/**
 * Fichier de manipulation des traductions
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

/**
 * Classe de manipulation des traductions
 */
class Lang
{
	/**
	 * Table des traductions
	 * @var array
	 */
	protected $_lang;
	/**
	 * Locale en cours
	 * @var string|boolean
	 */
	protected $_locale;
	/**
	 * Locale par défaut
	 * @var string
	 */
	protected static $_default = 'EN_us';
	
	/**
	 * Constructeur de la classe
	 *
	 * @param string|array|boolean la langue à utiliser ou une liste de langues par priorité, ou false pour une détection automatique
	 */
	public function __construct($lang = false)
	{
		// Par défaut
		$this->_lang = array();
		$this->_locale = false;
		
		// Détection de langue
		if (is_bool($lang))
		{
			// Init
			$lang = array();
			
			// Valeur fournie
			if (Request::issetGET('lang'))
			{
				$get = Request::getGET('lang');
				Session::setCache('Lang', 'current', $get);
				Cookie::set('lang', $get);
			}
			
			// Valeur courante
			$session = Session::getCache('Lang', 'current');
			if (!is_null($session))
			{
				$lang[] = $session;
			}
			elseif (Cookie::exists('lang'))
			{
				$cookie = Cookie::get('lang');
				$lang[] = $cookie->getValue();
				Session::setCache('Lang', 'current', $cookie->getValue());
				
				// Prolongation du cookie
				$cookie->extend();
			}
			
			// Valeur par défaut
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) > 0)
			{
				// Exemple : fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4
				$parts = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach ($parts as $part)
				{
					$lang[] = substr(trim($part), 0, 2);
				}
			}
		}
		elseif (!is_array($lang))
		{
			$lang = array($lang);
		}
		
		// Test de chargement
		foreach ($lang as $langue)
		{
			if (file_exists(PATH__LANG.$langue.'.php'))
			{
				$this->_locale = $langue;
				include(PATH__LANG.$langue.'.php');
				break;
			}
		}
	}
	
	/**
	 * Obtention de la locale active
	 *
	 * @param string|boolean $default la valeur par défaut si aucune locale n'est active
	 * @return string|boolean la locale, ou $default si aucune n'est active
	 */
	public function getLocale($default = false)
	{
		return $this->_locale ? $this->_locale : $default;
	}
	
	/**
	 * Obtention de traduction
	 *
	 * @param string $text le texte à traduire
	 * @return string la traduction trouvée, ou le texte original si aucune n'est trouvée
	 */
	public function get($text)
	{
		return isset($this->_lang[$text]) ? $this->_lang[$text] : preg_replace('/_[0-9]+$/', '', $text);
	}
	
	/**
	 * Affiche une traduction
	 *
	 * @param string $text le texte à traduire
	 * @return void
	 */
	public function output($text)
	{
		echo htmlspecialchars($this->get($text));
	}
	
	/**
	 * Affiche une traduction en échappant les apostrophes
	 *
	 * @param string $text le texte à traduire
	 * @return void
	 */
	public function outputAndEscape($text)
	{
		echo addslashes(htmlspecialchars($this->get($text)));
	}
	
	/**
	 * Affiche une traduction soit au singulier soit au pluriel
	 *
	 * @param int $value la valeur à tester
	 * @param string $none le texte pour une valeur de 0 (paramètre facultatif)
	 * @param string $singular le texte pour le singulier (et 0 si le paramètre $none n'est pas précisé)
	 * @param string $plural le texte pour le pluriel
	 * @return void
	 */
	public function getValue($value, $none, $singular, $plural = NULL)
	{
		// Format des paramètres
		if (is_null($plural))
		{
			$plural = $singular;
			$singular = $none;
		}
		
		if ($value == 0)
		{
			return sprintf($this->get($none), 0);
		}
		elseif ($value == 1)
		{
			return sprintf($this->get($singular), 1);
		}
		else
		{
			return sprintf($this->get($plural), $value);
		}
	}
	
	/**
	 * Affiche une traduction soit au singulier soit au pluriel
	 *
	 * @param int $value la valeur à tester
	 * @param string $none le texte pour une valeur de 0 (paramètre facultatif)
	 * @param string $singular le texte pour le singulier (et 0 si le paramètre $none n'est pas précisé)
	 * @param string $plural le texte pour le pluriel
	 * @return void
	 */
	public function outputValue($value, $none, $singular, $plural = NULL)
	{
		echo htmlspecialchars($this->getValue($value, $none, $singular, $plural));
	}
	
	/**
	 * Définit la locale par défaut
	 *
	 * @param string $loale la locale par défaut
	 * @return void
	 */
	public static function setDefault($locale)
	{
		$parts = explode('_', $locale);
		self::$_default = array_shift($parts);
	}
}
