<?php
/**
 * Classe de gestion de réponse HTTP
 */
class HttpResponse extends Response {
	/**
	 * Statut actuel de la requête
	 * @var string
	 */
	protected $_status;
	/**
	 * Headers à envoyer
	 * @var array
	 */
	protected $_headers;
	
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Init
		$this->_status = 200;
		$this->_headers = array();
		$this->header('Content-Type', 'text/html');
	}

	/**
	 * Définit ou renvoie le code de statut de la requête
	 * @param int $status le statut à affecter, ou NULL pour ne pas modifier
	 * @return int le statut actuel
	 * @throws SCException
	 */
	public function status($status = NULL)
	{
		// Si modification
		if (!is_null($status))
		{
			// Sécurisation
			if (headers_sent())
			{
				throw new SCException('Les headers ont déjà été envoyés, impossible de modifier la réponse', 999, 'Status : '.$status);
			}
			if (!self::statusExists($status))
			{
				throw new SCException('Statut HTTP pour la réponse non valide', 999, 'Status : '.$status);
			}
			
			// Affectation
			$this->_status = intval($status);
			
			// Si statut sans contenu
			if ($this->_status == 204 or $this->_status == 304)
			{
				// Pas de contenu
				$this->clearContent();
				$this->unsetHeader('Content-Type');
			}
		}
		
		return $this->_status;
	}

	/**
	 * Renvoie l'intégralité des headers définis
	 * @return array
	 */
	public function headers()
	{
		return $this->_headers;
	}

	/**
	 * Définit ou renvoie un header de réponse HTTP
	 * @param string $name le nom du header
	 * @param string $value la valeur
	 * @return string la valeur du header si défini, ou NULL si pas défini
	 * @throws SCException
	 */
	public function header($name, $value = NULL)
	{
		// Si modification
		if (!is_null($value))
		{
			// Sécurisation
			if (headers_sent())
			{
				throw new SCException('Les headers ont déjà été envoyés, impossible de modifier la réponse', 999, 'Header : '.$name.', valeur : '.$value);
			}
			
			$this->_headers[$name] = $value;
		}
		
		return isset($this->_headers[$name]) ? $this->_headers[$name] : NULL;
	}

	/**
	 * Efface un header de réponse HTTP
	 * @param string $name le nom du header
	 * @return void
	 */
	public function unsetHeader($name)
	{
		if (isset($this->_headers[$name]))
		{
			unset($this->_headers[$name]);
		}
	}
	
	/**
	 * Définit le contenu de la réponse
	 * @param mixed $content le contenu à préparer et à afficher
	 * @return boolean true si le contenu a bien été ajouté, false sinon
	 */
	public function content($content)
	{
		parent::content($content);
		
		// Mise à jour de la longueur
		$this->header('Content-Length', $this->contentLength());
	}
	
	/**
	 * Ajoute du contenu à la réponse existante
	 * @param mixed $content le contenu à préparer et à afficher
	 * @return boolean true si le contenu a bien été ajouté, false sinon
	 */
	public function addContent($content)
	{
		parent::addContent($content);
		
		// Mise à jour de la longueur
		$this->header('Content-Length', $this->contentLength());
	}
	
	/**
	 * Fonction interne de formattage du contenu
	 * @param string $content le contenu à formatter
	 * @return string le contenu formatté
	 */
	protected function _formatContent($content)
	{
		// Si site dans un sous-dossier
		if (strlen(URL_FOLDER) > 0)
		{
			// Réécriture
			$content = preg_replace('/(href|src|action)="\//i', '$1="/'.URL_FOLDER, $content);
		}
		
		return parent::_formatContent($content);
	}

	/**
	 * Indique si la requête peut avoir du contenu
	 * @return bool
	 */
	public function canHaveBody()
	{
		return (($this->_status < 100 or $this->_status >= 200) and $this->_status != 204 and $this->_status != 304);
	}

	/**
	 * Envoie les headers HTTP
	 * @return void
	 */
	protected function _sendHeaders()
	{
		// Détection de mode fastCGI
		$status = $this->status();
		if (substr(PHP_SAPI, 0, 3) === 'cgi')
		{
			header('Status: '.$status.' '.self::getStatusMessage($status));
		}
		else
		{
			header(Request::getProtocol().' '.$status.' '.self::getStatusMessage($status));
		}
		
		// Envoi des headers à proprement parler
		$headers = $this->headers();
		foreach ($headers as $name => $value )
		{
			header($name.': '.$value);
		}
		
		// Send cookies
		/*foreach ( $this->getCookieJar()->getResponseCookies() as $name => $cookie ) {
			setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpires(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHttpOnly());
		}*/
	}
	
	/**
	 * Envoie le contenu de la réponse
	 * @return boolean true si le contenu a bien été envoyé, false sinon
	 */
	public function send()
	{
		// Headers
		if (!headers_sent())
		{
			$this->_sendHeaders();
		}
		
		// Contenu
		if ($this->canHaveBody() and !Request::isHead())
		{
			parent::send();
		}
		
		return true;
	}
	
	/**
	 * Indique qu'une erreur est intervenue pendant le traitement de la requête, et termine la réponse
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return void
	 */
	public function error($code = 0, $message = '', $data = NULL)
	{
		// Statut
		$this->status($code);
		
		// Nettoyage
		$this->clearContent();
		
		// Vidage du buffer
		if (ob_get_level() > 0)
		{
			ob_end_clean();
		}
		
		// Détection des pages personnalisées
		$custom = Env::getConfig('errors')->get($code);
		if (!is_null($custom) and $this->_loadCustomErrorPage($custom, $code, $message, $data))
		{
			return;
		}
		$custom = Env::getConfig('errors')->get('all');
		if (!is_null($custom) and $this->_loadCustomErrorPage($custom, $code, $message, $data))
		{
			return;
		}
		
		// Par défaut
		$this->addContent($this->_getDefaultErrorOutput($code, $message, $data));
		$this->send();
	}
	
	/**
	 * Tente de charger une page d'erreur personnalisée
	 * @param string $custom le chemin fichier/ressource à charger
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return boolean true si la page a été chargée, false sinon
	 */
	protected function _loadCustomErrorPage($custom, $code = 0, $message = '', $data = NULL)
	{
		// Si fichier
		$extension = strtolower(substr($custom, -4));
		if ($extension === '.php' or $extension === '.html')
		{
			// Si valide
			if (file_exists($custom))
			{
				// Chargement de la page d'erreur
				ob_start();
				require($custom);
				
				// Sortie
				$output = ob_get_contents();
				ob_end_clean();
				$this->addContent($output);
				$this->send();
				
				return true;
			}
		}
		elseif (Request::internalRedirect($custom))
		{
			$this->send();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Construit l'affichage d'erreur par défaut
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return string
	 */
	protected function _getDefaultErrorOutput($code = 0, $message = '', $data = NULL)
	{
		$message = empty($message) ? self::getStatusMessage($code) : $message;
		
		return '<!DOCTYPE html>'."\n".
			'<html lang="fr">'."\n".
			'<head>'."\n".
			'<title>'.htmlspecialchars(__('Erreur système')).'</title>'."\n".
			'<meta charset="utf-8">'."\n".
			'<meta name="robots" content="none">'."\n".
			'<body>'."\n".
			'<h1>'.htmlspecialchars($code.' : '.__($message)).'</h1>'."\n".
			'</body>'."\n".
			'</html>';
	}
	
	/**
	 * Envoie un header location de redirection
	 * @param string $target la page à charger
	 * @return void
	 */
	public function redirect($target)
	{
		// Mode
		if (preg_match('/^[0-9a-z]+:\//i', $target))
		{
			header('Location: '.$target);
		}
		else
		{
			header('Location: '.URL_BASE.$target);
		}
		exit();
	}
	
	/**
	 * Cherche si une page de redirection est définie ('redirect' en GET ou POST), sinon retourne à la page précédente, 
	 * ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default l'url par défaut si aucune page précédente n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public function redirectOrGoBack($default = '', $append = '')
	{
		if (Request::issetParam('redirect'))
		{
			$this->redirect(trim(Request::getParam('redirect').$append));
		}
		else
		{
			$this->goBack($default);
		}
	}
	
	/**
	 * Cherche si une page de redirection est définie ('redirect' en GET ou POST), sinon va à la page par défaut
	 * @param string $default l'url par défaut si aucune page de redirection n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public function redirectOrGo($default = '', $append = '')
	{
		if (Request::issetParam('redirect'))
		{
			$this->redirect(trim(Request::getParam('redirect').$append));
		}
		else
		{
			$this->redirect($default);
		}
	}
	
	/**
	 * Retourne à la page précédente, ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default la requête par défaut si aucune page précédente n'est trouvée (défaut : '')
	 * @return void
	 * throws SCException
	 */
	public function goBack($default = '')
	{
		// Recherche de la page précédente
		$previous = History::getPrevious();
		
		// Redirection
		$this->redirect(($previous !== false) ? self::buildUrl($previous['query'], $previous['params']) : $default);
	}
}