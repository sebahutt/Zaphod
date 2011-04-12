<?php
/**
 * Wrapper de la classe Page pour y ajouter le raccord à une URL particulière
 */
class PageUrl extends BaseClassWrapper {
	/**
	 * Url originale de chargement
	 * @var string
	 */
	protected $_url;
	/**
	 * Paramètres chargés avec la requête originale
	 * @var array
	 */
	protected $_params;
	
	/**
	 * Constructeur
	 *
	 * @param Page $page l'objet page de rattachement
	 * @param string $url l'url d'origine
	 * @param array $params les paramètres chargés avec la requête originale (facultatif, défaut : array())
	 * @throws SCException si les paramètres ne correspondent pas au pattern d'url de la page
	 */
	public function __construct($page, $url, $params = array())
	{
		parent::__construct($page);
		
		// Préparation des paramètres
		$names = $this->_wrapped->getParamNames();
		if (count($names) != count($params))
		{
			throw new SCException('Le nombre de paramètres n\'est pas valide (attendus : '.implode(', ', $names).')');
		}
		
		// Mémorisation
		$this->_url = $url;
		$this->_params = (count($names) > 0) ? array_combine($this->_wrapped->getParamNames(), $params) : array();
	}
	
	/**
	 * URL de la page
	 *
	 * @param array $params les identifiants à passer dans l'url aux emplacements définis, sous la forme clé => valeur (facultatif, défaut : array())
	 * @return string l'url de la page
	 * @throws SCException si des paramètres sont attendus mais pas fournis
	 */
	public function getUrl($params = array())
	{
		return $this->_wrapped->getUrl(array_merge($this->_params, $params));
	}
	
	/**
	 * Extrait les paramètres passés dans l'url fournie en fonction des marqueurs de l'url interne
	 *
	 * @param string $url l'url à analyser, ou NULL pour utiliser l'url de chargement (facultatif, défaut : NULL)
	 * @return array un tableau associatif clé => valeur
	 */
	public function extractUrlParams($url = NULL)
	{
		return $this->_wrapped->extractUrlParams(is_null($url) ? $this->_url : $url);
	}
	
	/**
	 * Lien complet vers la page
	 *
	 * @param string $content le contenu du lien, ou NULL pour utiliser le titre de la page (facultatif, défaut : NULL)
	 * @param string $title le contenu de l'attribut title, ou NULL pour utiliser le titre de la page (facultatif, défaut : NULL)
	 * @param array $params les identifiants à passer dans l'url aux emplacements définis, sous la forme clé => valeur (facultatif, défaut : array())
	 * @return string le lien complet
	 */
	public function getLink($content = NULL, $title = NULL, $params = array())
	{
		return $this->_wrapped->getLink($content, $title, array_merge($this->_params, $params));
	}
}