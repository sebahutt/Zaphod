<?php
/**
 * Fichier de définition de la classe de contrôleur de page - fonctions génériques
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

/**
 * Classe de contrôleur de page - fonctions génériques
 */
class DefaultControler
{
	/**
	 * Objet page de la page en cours
	 * @var Page
	 */
	protected $_page;
	
	/**
	 * Paramètres de requête systèmes
	 * @var array
	 */
	protected static $_sysParams = array('__ajax', '__back');
	
	/**
	 * Constructeur de la classe
	 * @param Page $page l'objet Page de la page en cours
	 */
	public function __construct($page)
	{
		// Stockage
		$this->_page = $page;
		
		// Préparation
		$this->_init();
	}
	
	/**
	 * Initialise la page
	 * @return void
	 */
	protected function _init()
	{
	}
	
	/**
	 * Renvoie l'objet page
	 * @return Page l'objet page
	 */
	public function getPage()
	{
		return $this->_page;
	}
	
	/**
	 * Renvoie l'objet racine de la page courante
	 * @return Page l'objet page racine
	 */
	public function getRoot()
	{
		return $this->_page->getRoot();
	}
	
	/**
	 * Construit la page
	 * @return string le code final de la page
	 */
	public function build()
	{
		// Chargement
		ob_start();
		
		// Chargement du contenu
		require(PATH_BASE.$this->_page->file);
		
		// Récupération
		$retour = ob_get_contents();
		ob_end_clean();
		return $retour;
	}
	
	/**
	 * Renvoie le titre de la page
	 * @return string le titre
	 */
	public function getTitle()
	{
		return $this->_page->title;
	}
	
	/**
	 * Construit un lien vers la page courante avec des paramètres additionnels
	 * @param array $params un tableau associatif avec les paramètres additionnels
	 * @return string le lien complet
	 */
	public function selfLink($params)
	{
		return $this->_basePath.'?'.http_build_query(array_merge($this->_params, $params));
	}
	
	/**
	 * Effectue un renvoi de type header() en tenant compte de la configuration
	 * @param string $target la page cible, sans le / initial (facultatif, défaut : '' (accueil))
	 * @return void
	 */
	public function redirect($target = '')
	{
		// Nettoyage
		$target = removeInitialSlash($target);
		if (strlen($GLOBALS['_config']['document_folder']) > 0 and strpos($target, $GLOBALS['_config']['document_folder']) === 0)
		{
			$target = substr($target, strlen($GLOBALS['_config']['document_folder']));
		}
		
		// Renvoi
		header('location:/'.$GLOBALS['_config']['document_folder'].$target);
		exit();
	}
	
	/**
	 * Construit le fil d'ariane
	 * @return array un tableau avec le code html du chemin, une page par entrée
	 */
	public function buildBreadcrumb()
	{
		// Init
		$path = array();
		
		// Remontée
		$parent = $this->_page;
		while ($parent)
		{
			// Ajout
			$path[] = $parent->getLink();
			
			// Remontée
			$parent = $parent->getParent();
		}
		
		// Renvoi
		return array_reverse($path);
	}
	
	/**
	 * Ajoute une erreur
	 * @param string $message le message d'erreur
	 * @param string $domain le domaine de l'erreur (facultatif)
	 * @return void
	 */
	public function addError($message, $domain = 'global')
	{
		return Session::addError($message, $domain);
	}
	
	/**
	 * Indique si il y a des erreurs
	 * @param string $domain le domaine des erreurs (facultatif)
	 * @return boolean une confirmation
	 */
	public function hasErrors($domain = 'global')
	{
		return Session::hasErrors($domain);
	}
	
	/**
	 * Renvoie la liste des erreurs
	 * @param string $domain le domaine des erreurs (facultatif)
	 * @return array la liste des erreurs
	 */
	public function getErrors($domain = 'global')
	{
		return Session::getErrors($domain);
	}
	
	/**
	 * Ajoute un message
	 * @param string $message le message
	 * @param string $domain le domaine du message (facultatif)
	 * @return void
	 */
	public function addMessage($message, $domain = 'global')
	{
		return Session::addMessage($message, $domain);
	}
	
	/**
	 * Indique si il y a des messages
	 * @param string $domain le domaine des messages (facultatif)
	 * @return boolean une confirmation
	 */
	public function hasMessages($domain = 'global')
	{
		return Session::hasMessages($domain);
	}
	
	/**
	 * Renvoie la liste des messages
	 * @param string $domain le domaine des messages (facultatif)
	 * @return array la liste des messages
	 */
	public function getMessages($domain = 'global')
	{
		return Session::getMessages($domain);
	}
	
	/**
	 * Charge une template de page
	 * @param string $name le nom de fichier de la template (dans le dossier _templates)
	 * @param string $content le contenu à insérer à la place de [content], ou un tableau avec en index le nom des placeholders
	 * @return string le contenu de la template
	 */
	public function loadTemplate($name, $content = array())
	{
		// Vérification
		$path = PATH_TEMPLATES.$name;
		if (!file_exists($path))
		{
			return '';
		}
		
		// Format
		if (!is_array($content))
		{
			$content = array('content' => $content);
		}
		
		// Ajout des constantes
		$content['URL_BASE'] = URL_BASE;
		$content['URL_IMG'] = URL_IMG;
		
		// Remplacement
		$template = file_get_contents($path);
		foreach ($content as $code => $text)
		{
			$template = str_replace('['.$code.']', $text, $template);
		}
		return $template;
	}
}