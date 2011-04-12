<?php

// Si register_globals actif
if (ini_get('register_globals'))
{
	// Émulation de register_globals à off
	function unregister_GLOBALS()
	{
		// Si tentative d'effacement des GLOBALS
		if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
		{
			// Erreur
			trigger_error('La configuration de votre serveur n\'est pas correcte. Veuillez contacter votre administrateur', E_USER_ERROR);
		}
		
		// Les variables à ne jamais effacer
		$noUnset = array('GLOBALS', '_GET',	'_POST', '_COOKIE',	'_REQUEST', '_SERVER', '_ENV', '_FILES');
		
		// Liste des superglobales
		$input = array_merge($_GET, $_POST,	$_COOKIE, $_SERVER,	$_ENV, $_FILES);
		
		// Parcours
		foreach ($input as $key=> $value)
		{
			// Si globalisée
			if (!in_array($key, $noUnset) AND isset($GLOBALS[$key]))
			{
				// Effacement
				unset($GLOBALS[$key]);
			}
		}
	}
	
	// Appel
	unregister_GLOBALS();
}

// Si magic_quotes actif
if (get_magic_quotes_gpc())
{
	// Émulation de magic_quotes à off
	function stripslashes_deep($value)
	{
		// Si tableau
		if (is_array($value))
		{
			// Relai
			$value = array_map('stripslashes_deep', $value);
		}
		else
		{
			// Retrait
			$value = stripslashes($value);
		}
		
		return $value;
	}
	
	// Applications aux superglobales d'entrée
	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

/**
 * Fallback si json_encode n'est pas défini
 * Original code from multiple contributors on json_encode's manual page :
 * @url http://fr.php.net/manual/en/function.json-encode.php
 */
if (!function_exists('json_encode'))
{
	function json_encode($a)
	{
		// Generic types
		if (is_null($a))
		{
			return 'null';
		}
		if ($a === false)
		{
			return 'false';
		}
		if ($a === true)
		{
			return 'true';
		}
		
		if (is_scalar($a))
		{
			if (is_float($a))
			{
				// Always use "." for floats.
				return floatval(str_replace(",", ".", strval($a)));
			}
			
			if (is_string($a))
			{
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $a).'"';
			}
			else
			{
				return $a;
			}
		}
		
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a))
		{
			if (key($a) !== $i)
			{
				$isList = false;
				break;
			}
		}
		
		$result = array();
		if ($isList)
		{
			foreach ($a as $v)
			{
				$result[] = json_encode($v);
			}
			return '['.join(',', $result).']';
		}
		else
		{
			foreach ($a as $k => $v)
			{
				$result[] = json_encode($k).':'.json_encode($v);
			}
			return '{'.join(',', $result).'}';
		}
	}
}

/**
 * Traduction d'un texte
 *
 * @param string $text le texte à traduire
 * @return string la traduction trouvée, ou le texte original si aucune n'est trouvée
 */
function __($text)
{
	if (!isset($GLOBALS['_lang']))
	{
		$GLOBALS['_lang'] = new Lang();
	}
	
	return $GLOBALS['_lang']->get($text);
}

/**
 * Affiche une traduction
 *
 * @param string $text le texte à traduire
 * @return void
 */
function _e($text)
{
	if (!isset($GLOBALS['_lang']))
	{
		$GLOBALS['_lang'] = new Lang();
	}
	
	$GLOBALS['_lang']->output($text);
}

/**
 * Affiche une traduction en échappant les apostrophes (ex : pour javascript)
 *
 * @param string $text le texte à traduire
 * @return void
 */
function _es($text)
{
	if (!isset($GLOBALS['_lang']))
	{
		$GLOBALS['_lang'] = new Lang();
	}
	
	$GLOBALS['_lang']->outputAndEscape($text);
}

/**
 * Renvoie une traduction avec une valeur soit au singulier soit au pluriel
 *
 * @param int $value la valeur à tester
 * @param string $none le texte pour une valeur de 0 (paramètre facultatif)
 * @param string $singular le texte pour le singulier (et 0 si le paramètre $none n'est pas précisé)
 * @param string $plural le texte pour le pluriel
 * @return void
 */
function _n($value, $none, $singular, $plural = NULL)
{
	if (!isset($GLOBALS['_lang']))
	{
		$GLOBALS['_lang'] = new Lang();
	}
	
	return $GLOBALS['_lang']->getValue($value, $none, $singular, $plural);
}

/**
 * Obtention de la locale active
 *
 * @param string|boolean $default la valeur par défaut si aucune locale n'est active
 * @return string|boolean la locale, ou $default si aucune n'est active
 */
function __locale($default = false)
{
	if (!isset($GLOBALS['_lang']))
	{
		$GLOBALS['_lang'] = new Lang();
	}
	
	return $GLOBALS['_lang']->getLocale($default);
}

/**
 * Ajoute un slash initial à un chemin si nécessaire
 *
 * @param string $path le chemin à compléter
 * @param string le chemin complété
 */
function addInitialSlash($path)
{
	if (substr($path, 0, 1) != '/')
	{
		$path = '/'.$path;
	}
	
	return $path;
}

/**
 * Ajoute un slash final à un chemin si nécessaire
 *
 * @param string $path le chemin à compléter
 * @param string le chemin complété
 */
function addTrailingSlash($path)
{
	if (substr($path, -1, 1) != '/')
	{
		$path .= '/';
	}
	
	return $path;
}

/**
 * Retire le slash initial d'un chemin si nécessaire
 *
 * @param string $path le chemin à nettoyer
 * @param string le chemin nettoyé
 */
function removeInitialSlash($path)
{
	return ltrim($path, '/');
}

/**
 * Retire le slash final d'un chemin si nécessaire
 *
 * @param string $path le chemin à nettoyer
 * @param string le chemin nettoyé
 */
function removeTrailingSlash($path)
{
	return rtrim($path, '/');
}

/**
 * Retire les slashs initiaux et finaux d'un chemin si nécessaire
 *
 * @param string $path le chemin à nettoyer
 * @param string le chemin nettoyé
 */
function removeSlashes($path)
{
	return trim($path, '/');
}

/**
 * Effectue un htmlentities compatible avec l'UTF8
 *
 * @param string|array $string la chaîne ou un tableau de chaîne à traiter
 * @return string|array la chaîne ou le tableau de chaîne traité
 */
function utf8entities($string)
{
	if (is_array($string))
	{
		return array_map('utf8entities', $string);
	}
	else
	{
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}
}