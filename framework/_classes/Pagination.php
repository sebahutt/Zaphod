<?php
/**
 * Classe de construction de pagination
 */
class Pagination
{
	/**
	 * Le nombre total de lignes
	 * @var int 
	 */
	protected $_total;
	/**
	 * La longueur des pages
	 * @var int 
	 */
	protected $_range;
	/**
	 * La page actuelle, commence à 0
	 * @var int 
	 */
	protected $_page;
	/**
	 * Le numéro de page maximum
	 * @var int 
	 */
	protected $_maxPage;
	/**
	 * Les options de rendu
	 * @var array 
	 */
	protected $_options;
	/**
	 * Pagination construite
	 * @var string 
	 */
	protected $_output;
	/**
	 * Options par défaut
	 * @var array 
	 * @static
	 */
	protected static $_defaultOptions = array(
		'begin' =>					'<span class="total">Page [page] sur [total]</span>',						// Code avant la liste
		'previous' =>				'<a href="?page=[index]" title="Page précédente">&laquo; Précédent</a>',	// Vers la page précédente
		'previousNone' =>			'<span class="disabled" title="Page précédente">&laquo; Précédent</span>',	// Vers la page précédente si aucune
		'page' =>					'<a href="?page=[index]" title="Page [number] sur [total]">[number]</a>',	// Vers les pages
		'current' =>				'<span>[number]</span>',													// Page courante
		'ellipsis' =>				'...',																		// Ellispe si trop de pages
		'next' =>					'<a href="?page=[index]" title="Page suivante">Suivant &raquo;</a>',		// Vers la page suivante
		'nextNone' =>				'<span class="disabled" title="Page suivante">Suivant &raquo;</span>',		// Vers la page suivante si aucune
		'end' =>					''																			// Code après la liste
	);
	
	/**
	 * Constructeur de la classe
	 * 
	 * @param int|DatabaseResult $total le nombre total de lignes, ou un résultat de requête Select
	 * @param int $range la longueur des pages, ou false pour tout (désactive la pagination)
	 * @param int $page la page actuelle, commence à 0 (facultatif, défaut : 0)
	 * @param array $options les options blocs de rendu (voir ci-dessus), qui peuvent comporter
	 * 			les balises suivantes :
	 * 			- [page] : page courante
	 * 			- [pages] : nombre total de pages
	 * 			- [total] : nombre de lignes de résultat
	 * 			- [number] : numéro d'affichage de la page
	 * 			- [index] : numéro interne (pour les liens) des pages
	 * 			Pour 'previousNone', 'current' et 'nextNone' : 
	 * 			si ces valeurs valent false, elles prennent respectivement les valeurs de 
	 * 			'previous', 'page' et 'next'.
	 * 			(facultatif, défaut : array())
	 */
	public function __construct($total, $range = false, $page = 0, $options = array())
	{
		// Type d'arguments
		if (is_object($total))
		{
			$range = $total->getRange();
			$page = $total->getPage();
			$total = $total->getTotal();
		}
		
		// Calculs et vérifications
		$maxPage = ($range > 0 and $total > 0) ? ceil($total/$range)-1 : 0;
		if ($page > $maxPage)
		{
			$page = $maxPage;
		}
		
		// Mémorisation
		$this->_total = $total;
		$this->_range = $range;
		$this->_page = $page;
		$this->_maxPage = $maxPage;
		$this->_options = array_merge(self::$_defaultOptions, $options);
		
		// Parsing des options
		if (!$this->_options['previousNone'])
		{
			$this->_options['previousNone'] = $this->_options['previous'];
		}
		if (!$this->_options['current'])
		{
			$this->_options['current'] = $this->_options['page'];
		}
		if (!$this->_options['nextNone'])
		{
			$this->_options['nextNone'] = $this->_options['next'];
		}
	}
	
	/**
	 * Renvoie le code de la pagination
	 * 
	 * @return string le code HTML prêt pour affichage
	 */
	public function output()
	{
		// Si pas encore rendu
		if (!isset($this->_output))
		{
			// Init
			$this->_ouput = '';
			
			// Construction de la pagination
			$ellipsisPrevious = true;
			$ellispisNext = true;
			$rangePrevious = $this->_page-3;
			$rangeNext = $this->_page+3;
			
			// Si on est suffisement prêt du début ou de la fin, on supprime l'ellipse (saut de certaines pages)
			if ($rangePrevious <= 2)
			{
				$rangePrevious = 0;
				$ellipsisPrevious = false;
			}
			if ($rangeNext >= $this->_maxPage-2)
			{
				$rangeNext = $this->_maxPage;
				$ellispisNext = false;
			}
			
			// Assemblage
			$search = array(
				'%5B' =>		'[',					// Décodage des crochets
				'%5D' =>		']',					// Décodage des crochets
				'[page]' =>		$this->_page+1,
				'[pages]' =>	$this->_maxPage+1,
				'[total]' =>	$this->_total,
				'[number]' =>	'',
				'[index]' =>	'',
				'[' =>			'%5B',					// Ré-encodage des crochets
				']' =>			'%5D'					// Ré-ecodage des crochets
			);
			
			// Début
			$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['begin']);
			
			// Page précédente
			$search['[index]'] = $this->_page-1;
			$search['[number]'] = $this->_page;
			if ($this->_page > 0)
			{
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['previous']);
			}
			else
			{
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['previousNone']);
			}
			
			// Si ellipse avant la première page
			if ($ellipsisPrevious)
			{
				$search['[index]'] = 0;
				$search['[number]'] = 1;
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['page']);
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['ellipsis']);
			}
			
			// Parcours
			for ($i = $rangePrevious; $i <= $rangeNext; ++$i)
			{
				// Données
				$search['[index]'] = $i;
				$search['[number]'] = $i+1;
				
				// Si actif
				if ($i == $this->_page)
				{
					$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['current']);
				}
				else
				{
					$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['page']);
				}
			}
			
			// Si ellipse avant la dernière page
			if ($ellispisNext)
			{
				$search['[index]'] = $this->_maxPage;
				$search['[number]'] = $this->_maxPage+1;
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['ellipsis']);
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['page']);
			}
			
			// Page suivante
			$search['[index]'] = $this->_page+1;
			$search['[number]'] = $this->_page+2;
			if ($this->_page < $this->_maxPage)
			{
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['next']);
			}
			else
			{
				$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['nextNone']);
			}
			
			// Fermeture
			$this->_ouput .= str_replace(array_keys($datas), array_values($datas), $this->_options['end']);
		}
		
		return $this->_ouput;
	}
}