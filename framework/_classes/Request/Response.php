<?php
/**
 * Classe de gestion de sortie de contenu
 */
abstract class Response {
	/**
	 * Liste des tracés pour renvoi spécial en AJAX
	 * @var array
	 */
	protected $_traces;
	/**
	 * Indique l'affichage du log est bloqué (headers pas encore envoyés)
	 * @var boolean
	 */
	protected $_lockDisplay;
	/**
	 * Liste des affichages bloqués
	 * @var array
	 */
	protected $_waiting;
	/**
	 * Données à ajouter avant la sortie lorsqu'elle sera envoyée
	 * @var string
	 */
	protected $_prepend;
	/**
	 * Données à ajouter après la sortie lorsqu'elle sera envoyée
	 * @var string
	 */
	protected $_append;
	/**
	 * Contenu à afficher
	 * @var string
	 */
	protected $_content;
	/**
	 * Liste des messages d'erreur HTTP
	 * @var array
	 */
	protected static $_errors = array(
        // Informations
        100 => 'Continue',
        101 => 'Switching Protocols',
        
        // Succès
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        
        // Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        
        // Erreur client
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        
        // Erreur serveur
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );
	
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		// Init
		$this->_content = '';
		
		// Configuration
		$this->_lockDisplay = Env::getConfig('log')->get('delayed', true);
	}
	
	/**
	 * Définit le contenu de la réponse
	 * @param mixed $content le contenu à préparer et à afficher
	 * @return boolean true si le contenu a bien été ajouté, false sinon
	 */
	public function addContent($content)
	{
		$this->_content .= $content;
	}
	
	/**
	 * Efface le contenu de la réponse
	 * @return boolean true si le contenu a bien été effacé, false sinon
	 */
	public function clearContent()
	{
		$this->_content = '';
	}
	
	/**
	 * Envoie le contenu de la réponse
	 * @return boolean true si le contenu a bien été envoyé, false sinon
	 */
	public function send()
	{
		echo $this->_content;
		$this->clearContent();
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
		// Nettoyage
		$this->clearContent();
		
		// Vidage du buffer
		if (ob_get_level() > 0)
		{
			ob_end_clean();
		}
		
		// Envoi
		$this->addContent($this->_getDefaultErrorOutput($code, $message, $data));
		$this->send();
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
		return __(empty($message) ? self::getCodeMessage($code) : $message);
	}
	
	/**
	 * Envoie le contenu de la réponse (si nécessaire) et indique la requête comme terminée
	 * @return boolean true si le contenu a bien été envoyé, false sinon
	 */
	public function end()
	{
		$this->send();
	}
	
	/**
	 * Renvoie la description correspondant à un code d'erreur
	 * @param int $code le code d'erreur
	 * @return string la description, ou NULL si code non valide
	 */
	public static function getCodeMessage($code)
	{
		return isset(self::$_errors[$code]) ? self::$_errors[$code] : NULL;
	}
	
	/**
	 * Test si l'affichage web du log est bloqué
	 * 
	 * Indique si l'affichage web des messages du log est bloqué, ce qui est le
	 * cas tant que les headers n'ont pas été envoyés.
	 * @return void
	 */
	/*public function isDisplayLocked()
	{
		// Renvoi
		return $this->_lockDisplay;
	}*/
	
	/**
	 * Active l'affichage web du log
	 * 
	 * Active la sortie web des message de logs, qui sont désactivés tant que les
	 * headers n'ont pas encore été envoyés, et affiche les messages en attente.
	 * @return void
	 */
	/*public function unLockDisplay()
	{
		if ($this->_lockDisplay)
		{
			// Préfixes
			echo $this->_format($this->getPrepend());
			$this->clearPrepend();
			
			// Mémorisation
			$this->_lockDisplay = false;
			
			// Envoi
			foreach ($this->_waiting as $output)
			{
				Response::_outputTrace($output);
			}
			
			// Reset
			$this->_waiting = array();
		}
	}*/
	
	/**
	 * Traçage de sortie amélioré : permet l'affichage de message de debuggage sans risquer de causer des bugs en mode request ou d'afficher des
	 * infos non désirées en production (elles sont alors masquées dans le code).
	 * @param mixed $message Le message à afficher
	 */
	/*public function trace($message)
	{
		// Si il s'agit d'une variable, conversion
		if (!is_string($message) and !is_numeric($message))
		{
			$message = var_export($message, true);
		}
		
		// Si affichage bloqué
		if ($this->isDisplayLocked())
		{
			// Mise en cache
			$this->_waiting[] = $message;
		}
		else
		{
			// Sortie
			$this->_outputTrace($message);
		}
	}*/
	
	/**
	 * Formatte le contenu renvoyé
	 * @param string $string le contenu à traiter
	 * @param string le contenu traité
	 */
	/*private function _format($string)
	{
		// Si site dans un sous-dossier
		if (strlen(URL_FOLDER) > 0)
		{
			// Réécriture
			$string = preg_replace('/(href|src|action)="\//i', '$1="/'.URL_FOLDER, $string);
		}
		
		return $string;
	}*/
	
	/**
	 * Fonction finale de sortie
	 * 
	 * Effectue l'affichage final du message, en fonction du mode courant
	 * @param string $message Le message à afficher
	 */
	/*private function _outputTrace($message)
	{
		// Mode remote
		if (Request::isAjax())
		{
			// Si le système n'est pas en production
			if (!PRODUCTION)
			{
				$this->_traces[] = $message;
			}
		}
		elseif (PRODUCTION)
		{
			// Sortie cachée
			echo '<pre style="display:none">'.$this->_format($message).'</pre>'."\n";
		}
		else
		{
			// Sortie standard
			echo '<pre style="padding-left:18.4em; text-indent:-18.4em;">'.$this->_format($message).'</pre>'."\n";
		}
	}*/
	
	/**
	 * Ajoute du contenu à envoyer avant la sortie lorsqu'elle sera envoyée
	 * @param mixed $output les données à ajouter
	 * @return void
	 */
	/*public function prepend($output)
	{
		// Types de données
		if (!is_string($output))
		{
			// Conversion
			$output = json_encode($output);
		}
		
		// Ajout
		$this->_prepend .= $output;
	}*/

	/**
	 * Renvoie le contenu ajouté pour affichage avant la sortie
	 * @return string le contenu pour affichage avant la sortie
	 */
	/*public function getPrepend()
	{
		return $this->_prepend;
	}*/

	/**
	 * Efface le contenu ajouté pour affichage avant la sortie
	 * @return void
	 */
	/*public function clearPrepend()
	{
		return $this->_prepend = '';
	}*/
	
	/**
	 * Ajoute du contenu à envoyer après la sortie lorsqu'elle sera envoyée
	 * @param mixed $output les données à ajouter
	 * @return void
	 */
	/*public function append($output)
	{
		// Types de données
		if (!is_string($output))
		{
			// Conversion
			$output = json_encode($output);
		}
		
		// Ajout
		$this->_append .= $output;
	}*/

	/**
	 * Renvoie le contenu ajouté pour affichage après la sortie
	 * @return string le contenu pour affichage après la sortie
	 */
	/*public function getAppend()
	{
		return $this->_append;
	}*/

	/**
	 * Efface le contenu ajouté pour affichage après la sortie
	 * @return void
	 */
	/*public function clearAppend()
	{
		return $this->_append = '';
	}*/
	
	/**
	 * Renvoie le contenu généré
	 * @param mixed $output les données à afficher
	 * @return void
	 */
	/*public function output($output)
	{
		// Dévérouillage
		$this->unLockDisplay();
		
		// Si mode ajax
		if (Request::isAjax())
		{
			// Types de données 
			if (!is_string($output))
			{
				// Conversion
				echo $this->_format(json_encode($output));
				return;
			}
			else
			{
				// Traces
				$tracesOut = '';
				$traces = array_merge($this->_waiting, $this->_traces);
				if (count($traces) > 0)
				{
					$tracesOut = '<script type="text/javascript">';
					foreach ($traces as $trace)
					{
						$tracesOut .= 'alert(\''.str_replace(array("\n", "\r"), array('\n', ''), addslashes($trace)).'\');';
					}
					$tracesOut .= '</script>';
				}
				
				// Sortie brute
				echo $this->_format($this->getPrepend().$output.$tracesOut.$this->getAppend());
				
				// Vidage
				$this->_waiting = array();
				$this->_traces = array();
			}
		}
		else
		{
			// Sortie brute
			echo $this->_format($this->getPrepend().$output.$this->getAppend());
		}
		
		// Vidage
		$this->clearPrepend();
		$this->clearAppend();
	}*/
}