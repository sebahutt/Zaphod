<?php
/**
 * Environnement d'éxécution général
 * @author Sébastien Hutter <sebastien@jcd-dev.fr>
 */
 
// Librairies et mise en place de l'environnement
require_once('basics.php');
require_once('init.php');

// Run, baby, run
Response::output(Request::exec());