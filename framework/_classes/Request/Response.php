<?php
/**
 * Classe de gestion de sortie de contenu
 */
abstract class Response {
	/**
	 * Contenu à afficher
	 * @var string
	 */
	protected $_content;
	/**
	 * Liste des messages de statuts
	 * @var array
	 */
	protected static $_statuses = array(
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
	}
	
	/**
	 * Définit le contenu de la réponse
	 *
	 * @param mixed $content le contenu à préparer et à afficher
	 * @return boolean true si le contenu a bien été ajouté, false sinon
	 */
	public function setContent($content)
	{
		// Nettoyage
		if ($this->hasContent())
		{
			$this->clearContent();
		}
		
		$this->_content = $this->_formatContent($content);
	}
	
	/**
	 * Ajoute du contenu à la réponse existante
	 *
	 * @param mixed $content le contenu à préparer et à afficher
	 * @return boolean true si le contenu a bien été ajouté, false sinon
	 */
	public function addContent($content)
	{
		$this->_content .= $this->_formatContent($content);
	}
	
	/**
	 * Indique si la réponse contient déjà quelquechose
	 *
	 * @return boolean une confirmation
	 */
	public function hasContent()
	{
		return strlen($this->_content) > 0;
	}
	
	/**
	 * Taille du contenu de la réponse
	 *
	 * @return int la longueur totale
	 */
	public function contentLength()
	{
		return strlen($this->_content);
	}
	
	/**
	 * Efface le contenu de la réponse
	 *
	 * @return boolean true si le contenu a bien été effacé, false sinon
	 */
	public function clearContent()
	{
		$this->_content = '';
	}
	
	/**
	 * Fonction interne de formattage du contenu
	 *
	 * @param string $content le contenu à formatter
	 * @return string le contenu formatté
	 */
	protected function _formatContent($content)
	{
		return $content;
	}
	
	/**
	 * Envoie le contenu de la réponse
	 *
	 * @return boolean true si le contenu a bien été envoyé, false sinon
	 */
	public function send()
	{
		if ($this->hasContent())
		{
			echo $this->_content;
			$this->clearContent();
		}
		
		return true;
	}
	
	/**
	 * Indique qu'une erreur est intervenue pendant le traitement de la requête, et termine la réponse
	 *
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toutes données additonnelle (facultatif, défaut : NULL)
	 * @return void
	 */
	public function error($code = 0, $message = '', $data = NULL)
	{
		// Nettoyage
		if ($this->hasContent())
		{
			$this->clearContent();
		}
		
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
	 *
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return string
	 */
	protected function _getDefaultErrorOutput($code = 0, $message = '', $data = NULL)
	{
		return __(empty($message) ? self::getStatusMessage($code) : $message);
	}
	
	/**
	 * Envoie le contenu de la réponse (si nécessaire) et indique la requête comme terminée
	 *
	 * @return boolean true si le contenu a bien été envoyé, false sinon
	 */
	public function end()
	{
		// Actions
		Env::callActions('response.end');
		
		// Envoi
		$this->send();
	}
	
	/**
	 * Indique si la réponse gère la sortie de données de debug
	 *
	 * @return boolean une confirmation
	 */
	public function canDisplayDebug()
	{
		return false;
	}
	
	/**
	 * Affiche des données de debug
	 *
	 * @param string|array $output un ligne ou un tableau de lignes à afficher
	 * @return boolean une confirmation que les données ont bien été affichées
	 */
	public function displayDebug($output)
	{
		return false;
	}
	
	/**
	 * Indique si le statut HTTP fourni existe
	 *
	 * @param int $code le code de statut
	 * @return string la description, ou NULL si code non valide
	 */
	public static function statusExists($code)
	{
		return isset(self::$_statuses[$code]) ? true : false;
	}
	
	/**
	 * Renvoie la description correspondant à un code de statut HTTP
	 *
	 * @param int $code le code de statut
	 * @return string la description, ou NULL si code non valide
	 */
	public static function getStatusMessage($code)
	{
		return isset(self::$_statuses[$code]) ? self::$_statuses[$code] : NULL;
	}
}