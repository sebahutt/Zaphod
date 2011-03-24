<?php
/**
 * La classe Response gère le formattage et le renvoi des données générées
 */
class Response extends StaticClass {
	/**
	 * Liste des tracés pour renvoie spécial en AJAX
	 * @var array
	 */
	private static $_traces = array();
	/**
	 * Indique l'affichage du log est bloqué (headers pas encore envoyés)
	 * @var boolean
	 */
	private static $_lockDisplay = true;
	/**
	 * Liste des affichages bloqués
	 * @var array
	 */
	private static $_waiting = array();
	/**
	 * Données à ajouter avant la sortie lorsqu'elle sera envoyée
	 * @var string
	 */
	private static $_prepend = '';
	/**
	 * Données à ajouter après la sortie lorsqu'elle sera envoyée
	 * @var string
	 */
	private static $_append = '';
	
	/**
	 * Initialise la classe
	 * @return void
	 */
	public static function initClass()
	{
		// Configuration
		self::$_lockDisplay = Env::getConfig('log')->get('delayed', true);
	}
	
	/**
	 * Test si l'affichage web du log est bloqué
	 * 
	 * Indique si l'affichage web des messages du log est bloqué, ce qui est le
	 * cas tant que les headers n'ont pas été envoyés.
	 * @return void
	 */
	public static function isDisplayLocked()
	{
		// Renvoi
		return self::$_lockDisplay;
	}
	
	/**
	 * Active l'affichage web du log
	 * 
	 * Active la sortie web des message de logs, qui sont désactivés tant que les
	 * headers n'ont pas encore été envoyés, et affiche les messages en attente.
	 * @return void
	 */
	public static function unLockDisplay()
	{
		if (self::$_lockDisplay)
		{
			// Préfixes
			echo self::_format(self::getPrepend());
			self::clearPrepend();
			
			// Mémorisation
			self::$_lockDisplay = false;
			
			// Envoi
			foreach (self::$_waiting as $output)
			{
				Response::_outputTrace($output);
			}
			
			// Reset
			self::$_waiting = array();
		}
	}
	
	/**
	 * Traçage de sortie amélioré : permet l'affichage de message de debuggage sans risquer de causer des bugs en mode request ou d'afficher des
	 * infos non désirées en production (elles sont alors masquées dans le code).
	 * @param mixed $message Le message à afficher
	 */
	public static function trace($message)
	{
		// Si il s'agit d'une variable, conversion
		if (!is_string($message) and !is_numeric($message))
		{
			$message = var_export($message, true);
		}
		
		// Si affichage bloqué
		if (self::isDisplayLocked())
		{
			// Mise en cache
			self::$_waiting[] = $message;
		}
		else
		{
			// Sortie
			self::_outputTrace($message);
		}
	}
	
	/**
	 * Formatte le contenu renvoyé
	 * @param string $string le contenu à traiter
	 * @param string le contenu traité
	 */
	private static function _format($string)
	{
		// Si site dans un sous-dossier
		if (strlen(URL_FOLDER) > 0)
		{
			// Réécriture
			$string = preg_replace('/(href|src|action)="\//i', '$1="/'.URL_FOLDER, $string);
		}
		
		return $string;
	}
	
	/**
	 * Fonction finale de sortie
	 * 
	 * Effectue l'affichage final du message, en fonction du mode courant
	 * @param string $message Le message à afficher
	 */
	private static function _outputTrace($message)
	{
		// Mode remote
		if (Request::isAjax())
		{
			// Si le système n'est pas en production
			if (!PRODUCTION)
			{
				self::$_traces[] = $message;
			}
		}
		elseif (PRODUCTION)
		{
			// Sortie cachée
			echo '<pre style="display:none">'.self::_format($message).'</pre>'."\n";
		}
		else
		{
			// Sortie standard
			echo '<pre style="padding-left:18.4em; text-indent:-18.4em;">'.self::_format($message).'</pre>'."\n";
		}
	}
	
	/**
	 * Ajoute du contenu à envoyer avant la sortie lorsqu'elle sera envoyée
	 * @param mixed $output les données à ajouter
	 * @return void
	 */
	public static function prepend($output)
	{
		// Types de données
		if (!is_string($output))
		{
			// Conversion
			$output = json_encode($output);
		}
		
		// Ajout
		self::$_prepend .= $output;
	}

	/**
	 * Renvoie le contenu ajouté pour affichage avant la sortie
	 * @return string le contenu pour affichage avant la sortie
	 */
	public static function getPrepend()
	{
		return self::$_prepend;
	}

	/**
	 * Efface le contenu ajouté pour affichage avant la sortie
	 * @return void
	 */
	public static function clearPrepend()
	{
		return self::$_prepend = '';
	}
	
	/**
	 * Ajoute du contenu à envoyer après la sortie lorsqu'elle sera envoyée
	 * @param mixed $output les données à ajouter
	 * @return void
	 */
	public static function append($output)
	{
		// Types de données
		if (!is_string($output))
		{
			// Conversion
			$output = json_encode($output);
		}
		
		// Ajout
		self::$_append .= $output;
	}

	/**
	 * Renvoie le contenu ajouté pour affichage après la sortie
	 * @return string le contenu pour affichage après la sortie
	 */
	public static function getAppend()
	{
		return self::$_append;
	}

	/**
	 * Efface le contenu ajouté pour affichage après la sortie
	 * @return void
	 */
	public static function clearAppend()
	{
		return self::$_append = '';
	}
	
	/**
	 * Renvoie le contenu généré
	 * @param mixed $output les données à afficher
	 * @return void
	 */
	public static function output($output)
	{
		// Dévérouillage
		self::unLockDisplay();
		
		// Si mode ajax
		if (Request::isAjax())
		{
			// Types de données 
			if (!is_string($output))
			{
				// Conversion
				echo self::_format(json_encode($output));
				return;
			}
			else
			{
				// Traces
				$tracesOut = '';
				$traces = array_merge(self::$_waiting, self::$_traces);
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
				echo self::_format(self::getPrepend().$output.$tracesOut.self::getAppend());
				
				// Vidage
				self::$_waiting = array();
				self::$_traces = array();
			}
		}
		else
		{
			// Sortie brute
			echo self::_format(self::getPrepend().$output.self::getAppend());
		}
		
		// Vidage
		self::clearPrepend();
		self::clearAppend();
	}
	
	/**
	 * Termine la requête : envoie les données fournies et termine le script
	 * @param string $output les données à afficher (facultatif, défaut : '')
	 * @return void
	 */
	public static function end($output = '')
	{
		// Sortie
		self::output($output);
		
		// Fin
		exit();
	}
}