<?php
/**
 * Classe de gestion de réponse HTTP
 */
class HttpResponse extends Response {
	/**
	 * Définit le contenu de la réponse
	 * @param mixed $content le contenu à préparer et à afficher
	 * @return boolean true si le contenu a bien été ajouté, false sinon
	 */
	public function addContent($content)
	{
		// Si site dans un sous-dossier
		if (strlen(URL_FOLDER) > 0)
		{
			// Réécriture
			$content = preg_replace('/(href|src|action)="\//i', '$1="/'.URL_FOLDER, $content);
		}
		
		parent::addContent($content);
	}
	
	/**
	 * Indique qu'une erreur est intervenue pendant le traitement de la requête, et termine la réponse
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return void
	 */
	public function error($code = 0, $message = '', $data = NULL)
	{
		// Nettoyage
		$this->clearContent();
		
		// Vidage du buffer
		if (ob_get_level() > 0)
		{
			ob_end_clean();
		}
		
		// Détection des pages personnalisées
		$custom = Env::getConfig('errors')->get($code);
		if (!is_null($custom) and $this->_loadCustomErrorPage($custom, $code, $message, $data))
		{
			return;
		}
		$custom = Env::getConfig('errors')->get('all');
		if (!is_null($custom) and $this->_loadCustomErrorPage($custom, $code, $message, $data))
		{
			return;
		}
		
		// Par défaut
		$this->addContent($this->_getDefaultErrorOutput($code, $message, $data));
		$this->send();
	}
	
	/**
	 * Tente de charger une page d'erreur personnalisée
	 * @param string $custom le chemin fichier/ressource à charger
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return boolean true si la page a été chargée, false sinon
	 */
	protected function _loadCustomErrorPage($custom, $code = 0, $message = '', $data = NULL)
	{
		// Si fichier
		$extension = strtolower(substr($custom, -4));
		if ($extension === '.php' or $extension === '.html')
		{
			// Si valide
			if (file_exists($custom))
			{
				// Chargement de la page d'erreur
				ob_start();
				require($custom);
				
				// Sortie
				$output = ob_get_contents();
				ob_end_clean();
				$this->addContent($output);
				$this->send();
				
				return true;
			}
		}
		elseif (Request::internalRedirect($custom))
		{
			$this->send();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Construit l'affichage d'erreur par défaut
	 * @param int $code le code d'erreur (facultatif, défaut : 0)
	 * @param string $message un message additonnel (facultatif, défaut : '')
	 * @param mixed $data toute données additonnelle (facultatif, défaut : NULL)
	 * @return string
	 */
	protected function _getDefaultErrorOutput($code = 0, $message = '', $data = NULL)
	{
		$message = empty($message) ? self::getCodeMessage($code) : $message;
		
		return '<!DOCTYPE html>'."\n".
			'<html lang="fr">'."\n".
			'<head>'."\n".
			'<title>'.htmlspecialchars(__('Erreur système')).'</title>'."\n".
			'<meta charset="utf-8">'."\n".
			'<meta name="robots" content="none">'."\n".
			'<body>'."\n".
			'<h1>'.htmlspecialchars($code.' : '.__($message)).'</h1>'."\n".
			'</body>'."\n".
			'</html>';
	}
	
	/**
	 * Envoie un header location de redirection
	 * @param string $target la page à charger
	 * @return void
	 */
	public function redirect($target)
	{
		// Mode
		if (preg_match('/^[0-9a-z]+:\//i', $target))
		{
			header('Location: '.$target);
		}
		else
		{
			header('Location: '.URL_BASE.$target);
		}
		exit();
	}
	
	/**
	 * Cherche si une page de redirection est définie ('redirect' en GET ou POST), sinon retourne à la page précédente, 
	 * ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default l'url par défaut si aucune page précédente n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public function redirectOrGoBack($default = '', $append = '')
	{
		if (Request::issetParam('redirect'))
		{
			$this->redirect(trim(Request::getParam('redirect').$append));
		}
		else
		{
			$this->goBack($default);
		}
	}
	
	/**
	 * Cherche si une page de redirection est définie ('redirect' en GET ou POST), sinon va à la page par défaut
	 * @param string $default l'url par défaut si aucune page de redirection n'est trouvée (défaut : accueil)
	 * @param string $append une chaîne à rajouter à l'url de redirection si elle est définie
	 * @return void
	 */
	public function redirectOrGo($default = '', $append = '')
	{
		if (Request::issetParam('redirect'))
		{
			$this->redirect(trim(Request::getParam('redirect').$append));
		}
		else
		{
			$this->redirect($default);
		}
	}
	
	/**
	 * Retourne à la page précédente, ou à la page par défaut si aucune page précédente n'est définie
	 * @param string $default la requête par défaut si aucune page précédente n'est trouvée (défaut : '')
	 * @return void
	 * throws SCException
	 */
	public function goBack($default = '')
	{
		// Recherche de la page précédente
		$previous = History::getPrevious();
		
		// Redirection
		$this->redirect(($previous !== false) ? self::buildUrl($previous['query'], $previous['params']) : $default);
	}
	
	/**
	 * Envoie un header 403 - accès refusé
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public function header403()
	{
		header(Request::getProtocol().' 403 Forbidden', true, 403);
		exit();
	}
	
	/**
	 * Envoie un header 404 - non trouvé
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public function header404()
	{
		header(Request::getProtocol().' 404 Not Found', true, 404);
		exit();
	}
	
	/**
	 * Envoie un header 500 - erreur interne
	 * @return void
	 * @todo ajouter le support des pages personnalisées
	 */
	public function header500()
	{
		header(Request::getProtocol().' 500 Internal Server Error', true, 500);
		exit();
	}
}