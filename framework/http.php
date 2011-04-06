<?php
/**
 * Environnement d'éxécution spécifique aux requêtes HTTP
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

// Librairies et mise en place de l'environnement
require_once('basics.php');
require_once('init.php');

// Parsers disponibles
Request::addParser('HttpRequestParser');		// Utilisation de l'url

// Routes disponibles
Request::addRoute('HttpPageRoute');				// Chargement par la base de pages

// Gestionnaires
Request::addHandler('HttpAjaxPageHandler');		// Requête AJAX
Request::addHandler('HttpPageHandler');			// Requête standard

// Exécution
Request::run();