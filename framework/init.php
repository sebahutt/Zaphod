<?php

$path = array();

// Chemins principaux
$path['url_root'] = addTrailingSlash($_SERVER['HTTP_HOST']);			// Domaine
$path['path_root'] = addTrailingSlash($_SERVER['DOCUMENT_ROOT']);		// Racine des fichiers web

// Base des fichiers web (peut être différent de DOCUMENT_ROOT)
$path['base'] = (isset($_SERVER['SCRIPT_FILENAME']) and strlen($_SERVER['SCRIPT_FILENAME']) > 0) ? dirname($_SERVER['SCRIPT_FILENAME']).'/' : $path['path_root'];
$path['system'] = dirname(__FILE__).'/';		// Chemin racine du framework

// Protocole
if (!isset($_SERVER['SERVER_PROTOCOL']))
{
	$path['url_protocol'] = 'http://';
}
else
{
	if (strpos($_SERVER['SERVER_PROTOCOL'], '/') === false)
	{
		$path['url_protocol'] = strtolower($_SERVER['SERVER_PROTOCOL']).'://';
	}
	else
	{
		$path['url_protocol'] = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))).'://';
	}
}

// Dossier éventuel dans le système de fichier et l'url
$path['path_folder'] = (strlen($path['base']) > strlen($path['path_root'])) ? substr($path['base'], strlen($path['path_root'])) : '';
$path['url_folder'] = $path['path_folder'];

// Définition des constantes de chemin
define('PATH_SYSTEM',		$path['system']);					// Chemin du framework
define('PATH_ROOT',			$path['path_root']);				// Racine locale des fichiers
define('PATH_FOLDER',		$path['path_folder']);				// Dossier après la racine des fichiers, si existant
define('URL_PROTOCOL',		$path['url_protocol']);				// Protocole d'accès (http:// ou https://)
define('URL_ROOT',			$path['url_root']);					// Domaine
define('URL_FOLDER',		$path['url_folder']);				// Dossier après le domaine, si existant

// Nettoyage
unset($path);

// Sous-dossiers
define('PATH__CONFIG',		PATH_SYSTEM.'_config/');			// Dossier de configuration, à la racine du site
define('PATH__CLASSES',		PATH_SYSTEM.'_classes/');			// Dossier des classes
define('PATH__LANG',		PATH_SYSTEM.'_lang/');				// Dossier des fichiers de langues

// Raccourcis
define('PATH_BASE',			PATH_ROOT.PATH_FOLDER);				// Chemin des fichiers locaux
define('URL_BASE',			URL_PROTOCOL.URL_ROOT.URL_FOLDER);	// URL racine du site

// Noms par défaut
define('DEFAULT_DB',		'db');								// Serveur de bases de données par défaut

// Chemins modifiables
require(file_exists(PATH_SYSTEM.'path.php') ? PATH_SYSTEM.'path.php' : PATH_SYSTEM.'path.default.php');

// Classe d'environnement
require_once(PATH__CLASSES.'Env.php');

// Initialisation de l'environnement
Env::init();

// Log
Logger::globalLog('Environnement initialisé');
if (isset($_SERVER['REQUEST_URI']))
{
	Logger::globalLog('Requête : '.$_SERVER['REQUEST_URI']);
}
if (isset($_SERVER['HTTP_REFERER']))
{
	Logger::globalLog('Origine : '.$_SERVER['HTTP_REFERER']);
}