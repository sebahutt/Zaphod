<?php
/**
 * Valeurs par défaut des variables d'environnement
 */

	// Informations par défaut
	$config['site']['name'] = 				'Site';									// Nom du site
	$config['site']['publisher'] = 			'JCD Développement';					// Publicateur par défaut
	
	// Options système
	$config['sys']['allParamRewrite'] = 	false;									// Autorise l'utilisation d'urls uniquement constituées de paramètres additonnels
	$config['sys']['updating'] = 			false;									// Site en cours de mise à jour
	$config['sys']['maxFileSize'] = 		ini_get('upload_max_filesize');			// Taille maximale d'upload de fichiers
	$config['sys']['dev'] = 				array('127.0.',							// Serveurs de développement
												  '192.168.',						
												  '10.100.');
	
	// Localisation
	$config['zone']['country'] = 			'fra';									// Code pays par défaut
	$config['zone']['locale'] = 			'FR-fr';								// Locale par défaut
	$config['zone']['timezone'] = 			'Europe/Paris';							// Fuseau horaire par défaut
	
	// Logging
	$config['log']['display'] = 			false;									// Affiche log
	$config['log']['store'] = 				false;									// Enregistre le log dans un fichier
	$config['log']['verbose'] = 			false;									// Log plus détaillé
	$config['log']['delayed'] = 			true;									// Retarde l'affichage du log jusqu'à l'affichage de la page,
																					// Pour ne pas générer de sortie avant l'envoi des headers
	
	// Mail
	$config['mail']['mode'] = 				'php';									// Mode d'envoi : php ou smtp
	$config['mail']['core'] = 				'webmaster@jcd-dev.fr';					// Adresse x-spam
	$config['mail']['contact'] = 			'webmaster@jcd-dev.fr';					// Adresse de contact par défaut
	$config['mail']['redirect'] = 			false;									// Redirection forcée (false pour aucune)
	$config['mail']['archive'] = 			'webmaster@jcd-dev.fr';					// Adresse d'archivage des mails (false pour aucune)
	$config['mail']['mime'] = 				'1.0';									// Version Mimi
	$config['mail']['domain'] = 			$_SERVER['SERVER_NAME'];				// Domaine d'envoi des mails
	
	// Serveur smtp
	$config['servers']['smtp']['server'] = 'exchange.jcd.local';
	$config['servers']['smtp']['port'] = 	25;
	$config['servers']['smtp']['login'] = 	'';
	$config['servers']['smtp']['pass'] = 	'';
	$config['servers']['smtp']['timeout'] = 10;
	$config['servers']['smtp']['tls'] = 	false;	// Mode SSL
	$config['servers']['smtp']['newline'] = "\n";
	
	/*
	 * Pages d'erreur (mode http uniquement)
	 * Pour chaque code d'erreur, il est possible d'indiquer :
	 * - un chemin absolu vers le fichier à afficher (doit comporter une extension .php ou .html)
	 * - un chemin indiquant la ressource à charger (si géré par le router en cours)
	 * 
	 * La clé 'all' permet d'indiquer le fichier/la ressource à charger pour toutes les autres erreurs, et sera également utilisée
	 * si les valeurs précisées ci-dessus échouent
	 */
	$config['errors'] =						array();