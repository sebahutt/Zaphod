<?php
/**
 * Classe de cookie de navigateur
 * Les fonctions d'encryption sont basées sur l'article http://bigornot-fr.blogspot.com/2008/06/scurisation-des-cookies-une.html
 */
class Cookie {
	/**
	 * Nom du cookie
	 * @var string
	 */
	protected $_name;
	/**
	 * Valeur du cookie
	 * @var string
	 */
	protected $_value;
	/**
	 * Date d'expiration au format timestamp UNIX
	 * @var int
	 */
	protected $_expires;
	/**
	 * Chemin interne de validité
	 * @var string
	 */
	protected $_path;
	/**
	 * Domaine de validité
	 * @var string
	 */
	protected $_domain;
	/**
	 * Indique si le cookie n'est envoyé que sur une connexion sécurisée
	 * @var boolean
	 */
	protected $_secured;
	/**
	 * Indique si le cookie ne doit être lisible que via HTTP
	 * @var boolean
	 */
	protected $_httponly;
	/**
	 * Indique si le cookie est en attente de mise à jour
	 * @var boolean
	 */
	protected $_modified;
	/**
	 * Liste des cookies gérés
	 * @var array
	 */
	protected static $_cookies = array();
	/**
	 * Indique si on encode les cookies
	 * @var boolean
	 */
	protected static $_crypt = false;
	/**
	 * Clé secrête de cryptage
	 * @var string
	 */
	protected static $_secret;
	/**
	 * Algorithme de chiffrement
	 * @var int
	 */
	protected static $_algorithm;
	/**
	 * Mode de chiffrement par bloc
	 * @var int
	 */
	protected static $_mode;
	/**
	 * Utilisation de l'identifiant de session SSL
	 * @var boolean
	 */
	protected static $_ssl = false;
	/**
	 * Ressource du module de chiffrement
	 * @var int
	 */
	protected static $_cryptModule;
	
	/**
	 * Initialise la classe
	 *
	 * @return void
	 */
	public static function initClass()
	{
		// Ces deux constantes sont utilisées pour l'encodage, on les définit si elles n'existent pas pour éviter les erreurs
		if (!defined('MCRYPT_RIJNDAEL_256'))
		{
			define('MCRYPT_RIJNDAEL_256', 0);
		}
		if (!defined('MCRYPT_MODE_CBC'))
		{
			define('MCRYPT_MODE_CBC', 0);
		}
		
		// Si la librairie de chiffrement est disponible
		if (extension_loaded('mcrypt'))
		{
			$config = Env::getConfig('cookie');
			
			// Est-ce que l'encodage est actif et correctement configuré ?
			if ($config->get('encode') and strlen($config->get('secret')) > 0)
			{
				self::$_crypt = true;
				self::$_algorithm = $config->get('algorithm', MCRYPT_RIJNDAEL_256);
				self::$_mode = $config->get('mode', MCRYPT_MODE_CBC);
				self::$_ssl = $config->get('ssl', false);
			}
		}
	}
	
	/**
	 * Constructeur
	 *
	 * @param string $name le nom du cookie
	 * @param string $value la valeur du cookie
	 * @param mixed $expire la date d'expiration au format timestamp UNIX (facultatif, défaut : 0)
	 * @param string $path chemin interne de validité (facultatif, défaut : '/')
	 * @param string $domain domaine de validité, ou NULL pour utiliser le domaine courant (facultatif, défaut : NULL)
	 * @param boolean $secure indique si le cookie n'est envoyé que sur une connexion sécurisée (facultatif, défaut : false)
	 * @param boolean $httponly indique si le cookie ne doit être lisible que via HTTP (facultatif, défaut : false)
	 */
	protected function __construct($name, $value, $expires = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
	{
		// Init
		$this->_modified = false;
		
		// Mémorisation
		$this->_name = $name;
		
		// Affectation
		$this->setValue($value);
		$this->setExpires($expires);
		$this->setPath($path);
		$this->setDomain($domain);
		$this->secure($secure);
		$this->httpOnly($httponly);
	}
	
	/**
	 * Marque le cookie comme modifié, et planifie son envoi au navigateur
	 *
	 * @return void
	 */
	public function markAsModified()
	{
		if (!$this->_modified)
		{
			// Enregistrement pour l'envoi
			Env::addAction('headers.sent', array($this, 'send'));
			
			// Mémorisation
			$this->_modified = true;
		}
	}
	
	/**
	 * Indique si le cookie a été modifié
	 *
	 * @return boolean une confirmation
	 */
	public function isModified()
	{
		return $this->_modified;
	}

	/**
	 * Renvoie le nom du cookie
	 *
	 * @return string le nom du cookie
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Définir la valeur du cookie
	 *
	 * @param string $value la valeur à affecter
	 * @return void
	 */
	public function setValue($value)
	{
		// Détection de changement
		$value = (string)$value;
		if ($this->_value !== $value)
		{
			$this->_value = $value;
			if (!$this->_modified)
			{
				$this->markAsModified();
			}
		}
	}

	/**
	 * Renvoie la valeur du cookie
	 *
	 * @return string la valeur courante
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Définit la date d'expiration du cookie
	 *
	 * @param int la date d'expiration au format timestamp UNIX
	 * @return void
	 */
	public function setExpires($expire)
	{
		// Détection de changement
		$expire = (int)$expire;
		if ($this->_expires !== $expire)
		{
			$this->_expires = $expire;
			$this->markAsModified();
		}
	}

	/**
	 * Renvoie la date d'expiration du cookie au format timestamp UNIX
	 *
	 * @return int la date d'expiration
	 */
	public function getExpires()
	{
		return $this->_expires;
	}

	/**
	 * Définit le chemin interne de validité
	 *
	 * @param string $path le chemin interne
	 * @return void
	 */
	public function setPath($path)
	{
		// Détection de changement
		$path = (string)$path;
		if ($this->_path !== $path)
		{
			$this->_path = $path;
			$this->markAsModified();
		}
	}

	/**
	 * Renvoie le chemin interne de validité
	 *
	 * @return string le chemin interne
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Définit le domaine de validité du cookie
	 *
	 * @param string $domain le domaine à définir
	 * @return void
	 */
	public function setDomain($domain)
	{
		// Détection de changement
		$domain = (string)$domain;
		if ($this->_domain !== $domain)
		{
			$this->_domain = $domain;
			$this->markAsModified();
		}
	}

	/**
	 * Renvoie le domaine de validité du cookie
	 *
	 * @return string le domaine de validité
	 */
	public function getDomain()
	{
		return $this->_domain;
	}

	/**
	 * Définit si le cookie n'est envoyé que via une connexion sécurisée (HTTPS/SSL)
	 *
	 * @param boolean $enable true pour activer, false sinon
	 * @return void
	 */
	public function secure($enable)
	{
		// Détection de changement
		$enable = (bool)$enable;
		if ($this->_secured !== $enable)
		{
			$this->_secured = $enable;
			$this->markAsModified();
		}
	}

	/**
	 * Indique si le cookie n'est envoyé que via une connexion sécurisée (HTTPS/SSL)
	 *
	 * @return boolean une confirmation
	 */
	public function isSecured()
	{
		return $this->_secured;
	}

	/**
	 * Définit si le cookie n'est accessible que via protocole HTTP (et non d'autres moyens comme javascript),
	 * par exemple pour limiter le risque d'attaque XSL. Supporté à partir de Php 5.2 et par les navigateurs récents.
	 *
	 * @param boolean $enable true pour activer, false sinon
	 * @return void
	 */
	public function httpOnly($enable)
	{
		// Détection de changement
		$enable = (bool)$enable;
		if ($this->_httponly !== $enable)
		{
			$this->_httponly = $enable;
			$this->markAsModified();
		}
	}

	/**
	 * Indique si le cookie n'est accessible que via protocole HTTP
	 *
	 * @return boolean une confirmation
	 */
	public function isHttpOnly()
	{
		return $this->_httponly;
	}
	
	/**
	 * Supprime un cookie (lui affecte une date ancienne pour effacement par le navigateur)
	 *
	 * @return void
	 */
	public function delete()
	{
		// Affecte le 01-01-1980 comme date d'expiration
		$this->setExpires(315554400);
	}
	
	/**
	 * Indique si un cookie est marqué pour effacement
	 *
	 * @return void
	 */
	public function isDeleted()
	{
		$expires = $this->getExpires();
		return ($expires > 0 and $expires < time());
	}
	
	/**
	 * Envoie le cookie
	 *
	 * @return void
	 */
	public function send()
	{
		// Préparation de la valeur
		$value = self::_secureCookieValue($this->getValue(), $this->getExpires());
		
		// L'option $httponly n'est disponible que pour Php >= 5.2
		if (version_compare(PHP_VERSION, '5.2') >= 0)
		{
			setcookie($this->getName(), $value, $this->getExpires(), $this->getPath(), $this->getDomain(), $this->isSecured(), $this->isHttpOnly());
		}
		else
		{
			setcookie($this->getName(), $value, $this->getExpires(), $this->getPath(), $this->getDomain(), $this->isSecured());
		}
	}
	
	/**
	 * Indique si les cookies sont chiffrés
	 *
	 * @return boolean une confirmation
	 */
	public static function isCrypted()
	{
		return self::$_crypt;
	}
	
	/**
	 * Renvoie la ressource du module de chiffrement
	 *
	 * @return int la ressource du module
	 * @throws SCException si le module ne s'initialise pas correctement
	 */
	protected static function _getCryptModule()
	{
		if (!isset(self::$_cryptModule))
		{
			if (self::$_crypt)
			{
				self::$_cryptModule = mcrypt_module_open(self::$_algorithm, '', self::$_mode, '');
				if (self::$_cryptModule === false)
				{
					throw new SCException('Erreur lors du chargement du module mcrypt');
				}
			}
			else
			{
				self::$_cryptModule = 0;
			}
		}
		
		return self::$_cryptModule;
	}
	
	/**
	 * Renvoie l'objet cookie correspondant au nom demandé
	 *
	 * @param string $name le nom du cookie
	 * @param mixed $expire la date d'expiration au format timestamp UNIX (facultatif, défaut : 0)
	 * @param string $path chemin interne de validité (facultatif, défaut : '/')
	 * @param string $domain domaine de validité, ou NULL pour utiliser le domaine courant (facultatif, défaut : NULL)
	 * @param boolean $secure indique si le cookie n'est envoyé que sur une connexion sécurisée (facultatif, défaut : false)
	 * @param boolean $httponly indique si le cookie ne doit être lisible que via HTTP (facultatif, défaut : false)
	 * @return Cookie l'objet cookie
	 */
	public static function get($name, $expires = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
	{
		if (!isset(self::$_cookies[$name]))
		{
			// Par défaut
			$value = '';
			
			// Valeur
			if (isset($_COOKIE[$name]))
			{
				// Récupération des éléments
				$cookieValues = explode('|', $_COOKIE[$name]);
				
				// Si valide
				if ((count($cookieValues) === 4) and ($cookieValues[1] == 0 or $cookieValues[1] >= time()))
				{
					// Clé
					$key = hash_hmac('sha1', $cookieValues[0].$cookieValues[1], self::$_secret);
					$data = base64_decode($cookieValues[2]);
					
					// Décodage
					if (self::$_crypt)
					{
						$data = self::_decrypt($data, $key, md5($cookieValues[1]));
					}
					
					// Si SSL
					if (self::$_ssl and isset($_SERVER['SSL_SESSION_ID']))
					{
						$verifKey = hash_hmac('sha1', $cookieValues[0].$cookieValues[1].$data.$_SERVER['SSL_SESSION_ID'], $key);
					}
					else
					{
						$verifKey = hash_hmac('sha1', $cookieValues[0].$cookieValues[1].$data, $key);
					}
					
					// Si vérifié
					if ($verifKey == $cookieValues[3])
					{
						$value = $data;
					}
				}
			}
			
			// Création
			self::$_cookies[$name] = new Cookie($name, $value, $expires, $path, $domain, $secure, $httponly);
		}
		
		return self::$_cookies[$name];
	}
	
	/**
	 * Définit un cookie (écrite statique simplifiée)
	 *
	 * @param string $name le nom du cookie
	 * @param mixed $value la valeur du coookie
	 * @param mixed $expire la date d'expiration au format timestamp UNIX (facultatif, défaut : 0)
	 * @param string $path chemin interne de validité (facultatif, défaut : '/')
	 * @param string $domain domaine de validité, ou NULL pour utiliser le domaine courant (facultatif, défaut : NULL)
	 * @param boolean $secure indique si le cookie n'est envoyé que sur une connexion sécurisée (facultatif, défaut : false)
	 * @param boolean $httponly indique si le cookie ne doit être lisible que via HTTP (facultatif, défaut : false)
	 * @return Cookie l'objet cookie
	 */
	public static function set($name, $value, $expires = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
	{
		self::get($name, $expires, $path, $domain, $secure, $httponly)->setValue($value);
	}

	/**
	 * Sécurise la valeur d'un cookie, sous la forme :
	 *
	 * username|expire|base64((value)k,expire)|HMAC(user|expire|value,k)
	 *
	 * Avec :
	 * k = HMAC(user|expire, sk)
	 * sk = server's secret key
	 *
	 * @param string $value la valeur à sécuriser
	 * @param integer $expire le timestamp UNIX d'expiration du cookie
	 * @return string la valeur sécurisée
	 */
	protected static function _secureCookieValue($value, $expire)
	{
		// Identifiant d'utilisateur toujours identique (conservé de la solution originale pour une éventuelle future utilité)
		$username = 'user';
		
		// Encodage de la clé
		$key = hash_hmac('sha1', $username.$expire, self::$_secret);
		if (self::$_crypt)
		{
			$encryptedValue = base64_encode(self::_encrypt($value, $key, md5($expire)));
		}
		else
		{
			$encryptedValue = base64_encode($value);
		}
		
		// SSL
		if (self::$_ssl and isset($_SERVER['SSL_SESSION_ID']))
		{
			$verifKey = hash_hmac('sha1', $username.$expire.$value.$_SERVER['SSL_SESSION_ID'], $key);
		}
		else
		{
			$verifKey = hash_hmac('sha1', $username.$expire.$value, $key);
		}

		return(implode('|', array($username, $expire, $encryptedValue, $verifKey)));
	}

	/**
	 * Chiffre une chaîne avec la clé et le vecteur d'initialisation fournis
	 *
	 * @param string $data la chaîne à chiffrer
	 * @param string $key la clé
	 * @param string $iv le vecteur d'initialisation
	 * @return string la chaîne chiffrée
	 */
	protected static function _encrypt($data, $key, $iv)
	{
		$iv = self::_validateIv($iv);
		$key = self::_validateKey($key);
		$cryptModule = self::_getCryptModule();

		mcrypt_generic_init($cryptModule, $key, $iv);
		$res = mcrypt_generic($cryptModule, $data);
		mcrypt_generic_deinit($cryptModule);

		return $res;
	}

	/**
	 * Déchiffre une chaîne avec la clé et le vecteur d'initialisation fournis
	 *
	 * @param string $data la chaîne à déchiffrer
	 * @param string $key la clé
	 * @param string $iv le vecteur d'initialisation
	 * @return string la chaîne déchiffrée
	 */
	protected static function _decrypt($data, $key, $iv)
	{
		$iv = self::_validateIv($iv);
		$key = self::_validateKey($key);
		$cryptModule = self::_getCryptModule();
		
		mcrypt_generic_init($cryptModule, $key, $iv);
		$decryptedData = mdecrypt_generic($cryptModule, $data);
		$res = str_replace("\x0", '', $decryptedData);
		mcrypt_generic_deinit($cryptModule);
		
		return $res;
	}

	/**
	 * Valide le vecteur d'initialisation (si trop long, il sera raccourci)
	 *
	 * @param string $iv le vecteur d'initialisation à vérifier
	 * @return string le vecteur d'initialisation vérifié
	 */
	protected static function _validateIv($iv)
	{
		$ivSize = mcrypt_enc_get_iv_size(self::_getCryptModule());
		if (strlen($iv) > $ivSize)
		{
			$iv = substr($iv, 0, $ivSize);
		}
		return $iv;
	}

	/**
	 * Valide la clé de chiffrage (si trop longue, elle sera raccourcie)
	 *
	 * @param string $key la clé à vérifier
	 * @return string la clé vérifiée
	 */
	protected static function _validateKey($key)
	{
		$keySize = mcrypt_enc_get_key_size(self::_getCryptModule());
		if (strlen($key) > $keySize)
		{
			$key = substr($key, 0, $keySize);
		}
		return $key;
	}
}