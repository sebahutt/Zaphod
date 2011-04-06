<?php
/**
 * Fichier de définition de la classe Url, qui gère les opérations sur les urls et les chemins
 * @author Sébastien Hutter
 */

/**
 * Classe de gestion des urls
 */
class Url {
	/**
	 * Url complète fournie
	 * @var string 
	 */
	protected $_url;
	/**
	 * Stockage des composants de l'url
	 * @var array 
	 */
	protected $_parts;
	
	/**
	 * Constructeur de la classe
	 * 
	 * @param string l'url à utiliser
	 */
	public function __construct($url)
	{
		// Mémorisation
		$this->_url = $url;
		
		// Analyse
		$this->_parts = array_merge(array(
			'scheme' => NULL,				// Protocole
			'host' => NULL,					// Domaine ou IP
			'user' => NULL,					// Nom d'utilisateur
			'pass' => NULL,					// Mot de passe
			'path' => NULL,					// Chemin après le domaine
			'query' => NULL,				// Paramètres de la requête
			'fragment' => NULL,				// Ancre nommée (#ancre)
			'subdomain' => NULL,			// Sous-domain (si existant)
			'domain' => NULL,				// Domaine sans le sous-domaine ni l'extension
			'tld' => NULL,					// Extension (Top Level Domain)
			'ip' => NULL					// Adresse ip si host est une ip
		), parse_url($this->_url));
		
		// Si domaine IP
		if (!is_null($this->_parts['host']) and preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $this->_parts['host'], $matches))
		{
			$this->_parts['ip'] = $matches[0];
		}
		
		// Si paramètres
		if (!is_null($this->_parts['query']) and strlen($this->_parts['query']) > 0)
		{
			parse_str($this->_parts['query'], $this->_parts['query']);
		}
		else
		{
			$this->_parts['query'] = array();
		}
		
		// Analyse du domaine
		$this->_parseHost();
	}
	
	/**
	 * Découpe des composantes de host en : subdomain, domain et tld
	 * 
	 * @return void
	 */
	protected function _parseHost()
	{
		// Reset
		$this->_parts['subdomain'] = NULL;
		$this->_parts['domain'] = NULL;
		$this->_parts['tld'] = NULL;
		
		// Si domaine défini
		if (!is_null($this->_parts['host']) and is_null($this->_parts['ip']))
		{
			// Récupération
			if (strpos($this->_parts['host'], '.') === false)
			{
				// Stockage
				$this->_parts['domain'] = $this->_parts['host'];
			}
			else
			{
				// Découpe
				$host = explode('.', $this->_parts['host']);
				
				// Récupération
				$this->_parts['tld'] = array_pop($host);
				$this->_parts['domain'] = array_pop($host);
				
				// Sous-domaine
				if (count($host) > 0)
				{
					$this->_parts['subdomain'] = implode('.', $host);
				}
			}
		}
	}
	
	/**
	 * Méthode magique d'accès aux propriétés de l'élément
	 * 
	 * @param string $name la propriété à récupérer
	 * @return mixed la valeur si définie, NULL sinon
	 */
	public function __get($name)
	{
		return isset($this->_parts[$name]) ? $this->_parts[$name] : NULL;
	}
	
	/**
	 * Méthode magique de test de définition
	 * 
	 * @param string $name la propriété à tester
	 * @return void
	 */
	public function __isset($name)
	{
		return isset($this->_parts[$name]);
	}
	
	/**
	 * Méthode magique d'écriture dans les propriétés de l'élément
	 * 
	 * @param string $name la propriété à écrire
	 * @param mixed $value la valeur à écrire
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->setPart($name, $value);
	}
	
	/**
	 * Méthode magique d'effacement d'une propriété
	 * 
	 * @param string $name la propriété à effacer
	 * @return void
	 */
	public function __unset($name)
	{
		unset($this->_parts[$name]);
	}
	
	/**
	 * Modifie une des données analysées de l'url fournie
	 * 
	 * @param string $name le nom de la donnée à modifier
	 * @param mixed $value la valeur à affecter
	 * @return void
	 */
	public function setPart($name, $value)
	{
		// Mémorisation
		$this->_parts[$name] = $value;
		
		// Si on modifie une des composantes de host, réassemblage
		if ($name == 'subdomain' or $name == 'domain' or $name == 'tld')
		{
			// Parties
			$host = array();
			if (!is_null($this->_parts['subdomain']))
			{
				$host[] = $this->_parts['subdomain'];
			}
			if (!is_null($this->_parts['domain']))
			{
				$host[] = $this->_parts['domain'];
			}
			if (!is_null($this->_parts['tld']))
			{
				$host[] = $this->_parts['tld'];
			}
			
			// Si au moins une section  est définie
			if (count($host) > 0)
			{
				$this->_parts['host'] = implode('.', $host);
			}
			else
			{
				$this->_parts['host'] = NULL;
			}
		}
		elseif ($name == 'host')
		{
			$this->_parseHost();
		}
	}
	
	/**
	 * Indique si l'url fournie est basée sur une IP
	 * 
	 * @return boolean la confirmation que l'url est une ip ou non
	 */
	public function isIP()
	{
		return !is_null($this->_parts['ip']);
	}
	
	/**
	 * Renvoie le domaine
	 * 
	 * @return string le domaine (nom + extension) ou NULL si inexistant
	 */
	public function getDomain()
	{
		// Si non défini
		if (is_null($this->_parts['domain']))
		{
			return NULL;
		}
		
		// Si pas d'extension
		if (is_null($this->_parts['tld']))
		{
			return $this->_parts['domain'];
		}
		else
		{
			return $this->_parts['domain'].'.'.$this->_parts['tld'];
		}
	}
	
	/**
	 * Lit la valeur d'un paramètre de la requête
	 * 
	 * @param string $name le nom du paramètre
	 * @return mixed la valeur si définie, NULL sinon
	 */
	public function getParam($name)
	{
		return isset($this->_parts['query'][$name]) ? $this->_parts['query'][$name] : NULL;
	}
	
	/**
	 * Ajoute un paramètre à la requête. Si il existe déjà, il est modifié
	 * 
	 * @param string $name le nom du paramètre
	 * @param string|int $value la valeur
	 * @return Url l'objet pour chaînage
	 */
	public function setParam($name, $value)
	{
		// Mémorisation
		$this->_parts['query'][$name] = $value;
		
		// Renvoi pour chaînage
		return $this;
	}

	/**
	 * Supprime un paramètre de la requête.
	 * 
	 * @param string $name le nom du paramètre
	 * @return Url l'objet pour chaînage
	 */
	public function removeParam($name)
	{
		if (isset($this->_parts['query'][$name]))
		{
			unset($this->_parts['query'][$name]);
		}

		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Renvoie l'url avec tous ses composants
	 * 
	 * @return string l'url finale
	 */
	public function get()
	{
		// Init
		$url = '';
		
		// Composition
		if (!is_null($this->_parts['scheme']))
		{
			$url .= $this->_parts['scheme'].'://';
		}
		if (!is_null($this->_parts['user']))
		{
			$url .= $this->_parts['user'];
		}
		if (!is_null($this->_parts['pass']) and !is_null($this->_parts['user']))
		{
			$url .= ':'.$this->_parts['pass'];
		}
		if (!is_null($this->_parts['host']))
		{
			if (!is_null($this->_parts['user']))
			{
				$url .= '@';
			}
			$url .= $this->_parts['host'];
		}
		if (!is_null($this->_parts['path']))
		{
			$url .= $this->_parts['path'];
		}
		if (count($this->_parts['query']) > 0)
		{
			$url .= '?'.http_build_query($this->_parts['query']);
		}
		if (!is_null($this->_parts['fragment']) and strlen($this->_parts['fragment']) > 0)
		{
			$url .= '#'.$this->_parts['fragment'];
		}
		
		return $url;
	}
}