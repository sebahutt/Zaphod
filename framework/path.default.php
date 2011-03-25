<?php
/**
 * Chemins des principaux éléments
 */
	
	// Important ! Tous les chemins non vides doivent se terminer par un / pour être valides
	
	define('PATH_LOCAL',		dirname(PATH_SYSTEM).'/local/');	// Chemin des fichiers systèmes locaux
	define('PATH_CACHE',		PATH_LOCAL.'_cache/');				// Dossier de cache
	define('PATH_CONFIG',		PATH_LOCAL.'_config/');				// Dossier de configuration
	define('PATH_CLASSES',		PATH_LOCAL.'_classes/');			// Dossier des classes
	define('PATH_LANG',			PATH_LOCAL.'_lang/');				// Dossier des fichiers de langues
	define('PATH_LOGS',			PATH_LOCAL.'_logs/');				// Dossier des fichiers de log
	
	define('PATH_TEMPLATES',	PATH_BASE.'_templates/');			// Dossier des fichiers de templates
	define('PATH_INCLUDES',		PATH_BASE.'_includes/');			// Dossier des fichiers d'include
	define('PATH_ERRORS',		PATH_BASE.'_errors/');				// Dossier des fichiers d'erreur
	define('PATH_IMG',			PATH_BASE.'images/');				// Dossier local des images
	define('URL_IMG',			'/images/');						// URL de base des images
