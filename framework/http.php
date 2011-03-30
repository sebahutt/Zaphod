<?php
/**
 * Environnement d'éxécution spécifique aux requêtes HTTP
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */

// Librairies et mise en place de l'environnement
require_once('basics.php');
require_once('init.php');

// Routes disponibles
Request::addRoute(new HttpPageRoute());				// Chargement par la base de pages

// Gestionnaires
Request::addHandler(new HttpAjaxPageHandler());		// Requête AJAX
Request::addHandler(new HttpPageHandler());			// Requête standard

// Exécution
Request::run();