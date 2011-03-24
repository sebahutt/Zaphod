<?php
/**
 * Environnement d'éxécution général
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */
 
// Librairies et mise en place de l'environnement
require_once('basics.php');
require_once('init.php');

// Effacement de l'utilisateur
if (isset($_GET['logout']) and $_GET['logout'] == 1)
{
	User::logOut();
}

// Vérification des droits
if (!Request::getPage()->isAccessible())
{
	if (User::getCurrent()->isDefault())
	{
		// Chargement de la page de login
		Request::internalRedirect('login');
	}
	else
	{
		throw new SCException('Vous n\'avez pas accès à la page demandée');
	}
}

// Run, baby, run
Response::output(Request::exec());