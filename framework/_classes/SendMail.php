<?php
/**
 * Classe d'envoi de mails
 */
class SendMail {
	/**
	 * Sujet
	 * @var string 
	 */
	protected $_subject;
	/**
	 * Expéditeur
	 * @var string 
	 */
	protected $_from;
	/**
	 * Destinataires
	 * @var array 
	 */
	protected $_to;
	/**
	 * Destinataires en CC
	 * @var array 
	 */
	protected $_cc;
	/**
	 * Destinataires en BCC
	 * @var array 
	 */
	protected $_bcc;
	/**
	 * Type d'e-mail (texte et/ou html)
	 * @var int 
	 */
	protected $_mode;
	/**
	 * Contenu HTML et texte
	 * @var array 
	 */
	protected $_content;
	/**
	 * Priorité d'envoi
	 * @var int 
	 */
	protected $_priority;
	/**
	 * Pièces jointes
	 * @var array 
	 */
	protected $_files;
	/**
	 * Identifiant unique de mail
	 * @var string 
	 */
	protected $_uniqId;
	/**
	 * Confirmation de lecture
	 * @var string|boolean 
	 */
	protected $_confirm;
	/**
	 * Indique si l'envoi aux destinataires en TO se fait de façon séparée ou groupée
	 * @var boolean 
	 */
	protected $_separateSend;
	/**
	 * Objet du serveur d'envoi
	 * @var string
	 */
	protected static $_server;
	/**
	 * Adresse système, utilisée pour x-spam
	 * @var string 
	 */
	protected static $_core;
	/**
	 * Adresse d'archivage
	 * @var string 
	 */
	protected static $_archive;
	/**
	 * Adresse de redirection
	 * @var string 
	 */
	protected static $_redirect;
	/**
	 * Version mime
	 * @var string 
	 */
	protected static $_mime;
	/**
	 * Headers statiques
	 * @var array 
	 */
	protected static $_staticHeaders = array();
	/**
	 * Type d'e-mail - mode auto
	 * @var int
	 */
	const MODE_MIXED = 0;
	/**
	 * Type d'e-mail - mode texte
	 * @var int
	 */
	const MODE_TEXT = 1;
	/**
	 * Type d'e-mail - mode html
	 * @var int
	 */
	const MODE_HTML = 2;
	/**
	 * Priorité - basse
	 * @var int
	 */
	const PRIORITY_LOW = 5;
	/**
	 * Priorité - normale
	 * @var int
	 */
	const PRIORITY_NORMAL = 3;
	/**
	 * Priorité - haute
	 * @var int
	 */
	const PRIORITY_HIGH = 1;
	
	/**
	 * Constructeur de la classe
	 * @param string $subject le sujet du message (facultatif, défaut : '(Pas de sujet)')
	 */
	public function __construct($subject = '(Pas de sujet)')
	{
		// Init
		$this->from('Webmaster '.$_SERVER['HTTP_HOST'].' <'.Env::getConfig('mail')->get('contact').'>');
		$this->_subject = $subject;
		$this->_to = array();
		$this->_cc = array();
		$this->_bcc = array();
		$this->_mode = self::MODE_MIXED;
		$this->_priority = self::PRIORITY_NORMAL;
		$this->_files = array();
		$this->_uniqId = md5(uniqid(rand(), true));
		$this->_confirm = false;
		$this->_content = array(
			'html' => '',
			'txt' => ''
		);
		$this->_separateSend = true;
		
		// Si adresse d'archive
		if (self::$_archive and !self::$_redirect)
		{
			$this->bcc(self::$_archive);
		}
	}
	
	/**
	 * Initialisation de la classe
	 * @return void
	 */
	public static function initClass()
	{
		// Objet configuration
		$config = Env::getConfig('mail');
		
		// Type de serveur d'envoi
		switch ($config->get('mode'))
		{
			case 'smtp':
				self::$_server = new SMTPSender();
				break;
			
			default:
				self::$_server = new PhpSender();
				break;
		}
		
		// Mails système
		self::$_core = $config->get('core');				// Adresse x-spam
		self::$_redirect = $config->get('redirect');		// Redirection forcée
		self::$_archive = $config->get('archive');			// Adresse d'archivage des mails
		
		// Version mime
		self::$_mime = $config->get('mime');
		
		// Headers statiques
		self::$_staticHeaders[] = 'X-Client-IP: '.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 0)
		{
			self::$_staticHeaders[] = 'X-Client-PROXY: '.$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		self::$_staticHeaders[] = 'X-Client-Agent: '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Internet Explorer');
		self::$_staticHeaders[] = 'X-Client-Host: '.(($_SERVER['REMOTE_ADDR'] != '127.0.0.1') ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : 'localhost');
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$acceptLanguage = preg_split('/[^a-z]/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 2);
			self::$_staticHeaders[] = 'X-Client-Language: '.$acceptLanguage[0];
		}
		self::$_staticHeaders[] = 'Organization: '.Env::getConfig('site')->get('publisher');
	}
	
	/**
	 * Définit le sujet du message
	 * @param string $subject le sujet du message
	 * @return SendMail l'objet pour chaînage
	 */
	public function setSubject($subject)
	{
		// Mémorisation
		$this->_subject = $subject;
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Définit l'émetteur du message. Par défaut, c'est la valeur de configuration mail/contact qui est utilisé
	 * @param mixed $address l'adresse à utiliser, voir _parseAddress() pour les formats autorisés
	 * @return SendMail l'objet pour chaînage
	 */
	public function from($address)
	{
		// Mémorisation
		$from = self::_parseAddress($address);
		$this->_from = array_shift($from);
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Ajoute un destinataire au message
	 * @param mixed $address l'adresse à utiliser, voir _parseAddress() pour les formats autorisés
	 * @return SendMail l'objet pour chaînage
	 */
	public function to($address)
	{
		// Mémorisation
		$this->_to = array_merge($this->_to, self::_parseAddress($address));
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Ajoute un destinataire en copie au message
	 * @param mixed $address l'adresse à utiliser, voir _parseAddress() pour les formats autorisés
	 * @return SendMail l'objet pour chaînage
	 */
	public function cc($address)
	{
		// Mémorisation
		$this->_cc = array_merge($this->_cc, self::_parseAddress($address));
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Ajoute un destinataire en copie cachée au message
	 * @param mixed $address l'adresse à utiliser, voir _parseAddress() pour les formats autorisés
	 * @return SendMail l'objet pour chaînage
	 */
	public function bcc($address)
	{
		// Mémorisation
		$this->_bcc = array_merge($this->_bcc, self::_parseAddress($address));
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Définit le contenu HTML du message
	 * @param string $html le code html à utiliser
	 * @return SendMail l'objet pour chaînage
	 */
	public function setHTML($html)
	{
		// Mémorisation
		$this->_content['html'] = self::_formatHTML($html);
		
		// Détection de mode
		$this->_detectMode();
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Formatte le code HTML pour utilisation dans un e-mail
	 * @param string $html le code html à formatter
	 * @return void
	 */
	protected static function _formatHTML($html)
	{
		// Racine web des médias
		$mediaRoot = ($mediasManager = FileServer::get('images')) ? $mediasManager->getWebPath() : '';
		
		// Détection de chemin local
		if (substr($mediaRoot, 0, 1) === '/')
		{
			$mediaRoot = URL_BASE.substr($mediaRoot, 1);
		}
		
		// Conversion des adresses d'images en adresses absolues
		return preg_replace('/("|\()[\.\/]*images\//i', '$1'.$mediaRoot, $html);
	}
	
	/**
	 * Définit le contenu texte du message
	 * @param string $text le texte à utiliser
	 * @return SendMail l'objet pour chaînage
	 */
	public function setText($text)
	{
		// Mémorisation
		$this->_content['txt'] = $text;
		
		// Détection de mode
		$this->_detectMode();
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Charge une template, et détermine automatiquement les formats disponibles
	 * @param string $path le chemin de la template. Si une extension (.html ou .txt) est donnée, seul ce format sera utilisé, sinon le système tente de charger les deux. 
	 * Si le chemin ne commence pas par PATH_ROOT, le système considèrera qu'il est relatif au dossier PATH_TEMPLATES
	 * @return SendMail l'objet pour chaînage
	 */
	public function useTemplate($path)
	{
		// Init
		$infos = pathinfo($path);
		$basePath = (isset($infos['dirname']) and $infos['dirname'] != '.') ? $infos['dirname'].'/'.$infos['filename'] : $infos['filename'];
		if (strpos($basePath, PATH_ROOT) !== 0)
		{
			$basePath = PATH_TEMPLATES.$basePath;
		}
		$found = false;
		
		// Si extension
		if (isset($infos['extension']) and (strtolower($infos['extension']) == 'txt' or strtolower($infos['extension']) == 'html'))
		{
			$extensions = array(strtolower($infos['extension']));
		}
		else
		{
			$extensions = array('html', 'txt');
		}
		
		// Chargement
		foreach ($extensions as $extension)
		{
			// Si existant
			if (file_exists($basePath.'.'.$extension))
			{
				// Mode HTML
				if ($extension == 'html')
				{
					// Formattage
					$this->_content[$extension] = self::_formatHTML(file_get_contents($basePath.'.'.$extension));
				}
				else
				{
					// Stockage
					$this->_content[$extension] = file_get_contents($basePath.'.'.$extension);
				}
				
				// Mémorisation
				$found = true;
			}
		}
		
		// Si aucun fichier trouvé
		if (!$found)
		{
			throw new SCException('Aucun fichier de template correspondant trouvé (Chemin fourni : '.$path.', interprété : '.$basePath.', formats recherchés : '.implode(', ', $extensions).')');
		}
		
		// Détection de mode
		$this->_detectMode();
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Spécifie un champ de remplacement et sa valeur
	 * @param string|array $name le champ à remplacer (avec les crochets), ou un tableau associatif de type array(champ => value)
	 * @param mixed $value la valeur à affecter en remplacement, ignoré si $name est un tableau associatif (facultatif, défaut : NULL)
	 * @return SendMail l'objet pour chaînage
	 */
	public function replace($name, $value = NULL)
	{
		// Sécurisation
		if (strlen(trim($this->_content['txt'])) == 0 and strlen(trim($this->_content['html'])) == 0)
		{
			throw new SCException('Aucun contenu sur lequel effectuer le remplacement');
		}
		
		// Formattage
		if (is_array($name))
		{
			$value = array_values($name);
			$name = array_keys($name);
		}
		else
		{
			$name = (array)$name;
			$value = (array)$value;
		}
		
		// Remplacement
		if (strlen(trim($this->_content['txt'])) > 0)
		{
			$this->_content['txt'] = str_ireplace($name, array_map('strip_tags', $value), $this->_content['txt']);
		}
		if (strlen(trim($this->_content['html'])) > 0)
		{
			$this->_content['html'] = str_ireplace($name, $value, $this->_content['html']);
		}
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Détecte le format du mail, à partir des contenus fournis
	 * @return void
	 */
	protected function _detectMode()
	{
		// Parsing
		if (strlen(trim($this->_content['html'])) > 0)
		{
			if (strlen(trim($this->_content['txt'])) > 0)
			{
				$this->_mode = self::MODE_MIXED;
			}
			else
			{
				$this->_mode = self::MODE_HTML;
			}
		}
		elseif (strlen(trim($this->_content['txt'])) > 0)
		{
			$this->_mode = self::MODE_TEXT;
		}
	}
	
	/**
	 * Définit la priorité
	 * @param mixed $priority une des constantes PRIORITY_LOW, PRIORITY_NORMAL, PRIORITY_HIGH
	 * @throws SCException
	 * @return SendMail l'objet pour chaînage
	 */
	public function setPriority($priority)
	{
		// Sécurisation
		if ($priority != self::PRIORITY_LOW and $priority != self::PRIORITY_NORMAL and $priority != self::PRIORITY_HIGH)
		{
			throw new SCException('Valeur de priorité non valide : '.$priority);
		}
		
		// Mémorisation
		$this->_priority = $priority;
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Indique si le message demande une confirmation de lecture
	 * @param boolean $require true pour demander une confirmation, false sinon (facultatif, défaut : true)
	 * @return SendMail l'objet pour chaînage
	 */
	public function requireConfirmation($require = true)
	{
		// Mémorisation
		$this->_confirm = (bool)$require;
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Indique si il faut envoyer le message séparément ou en groupe aux destinataires définis en TO
	 * @param boolean $separate true pour envoyer séparément, false sinon (facultatif, défaut : true)
	 * @return SendMail l'objet pour chaînage
	 */
	public function sendSeparatly($separate = true)
	{
		// Mémorisation
		$this->_separateSend = (bool)$separate;
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Renvoie le lien web complet de l'e-mail, pour l'afficher dans un navigateur
	 * @return string le lien complet (domaine compris)
	 */
	public function getUrl()
	{
		// Extension
		switch ($this->_mode)
		{
			case self::MODE_TEXT:
				$extension = 'txt';
				break;
			
			default:
				$extension = 'html';
				break;
		}
		
		// Renvoi
		return URL_MAILS.'sent/mail'.$this->_uniqId.'.'.$extension;
	}
	
	/**
	 * Analyse les paramètres passés pour en extraire et en formatter les adresses correspondantes
	 * @param mixed $address une ou plusieurs adresses pour les champs to, cc ou bcc, dans le format suivant :
	 * 	 - une adresse e-mail nue
	 * 	 - une adresse e-mail avec nom : nom <adresse>
	 * 	 - une chaîne contenant plusieurs paramètres au format ci-dessus, avec un espace comme séparateur
	 * 	 - un objet User, qui a au moins le champ mail défini, et éventuellement nom et prénom
	 * 	 - un tableau contenant un ou plusieurs des paramètres ci-dessus
	 * 	 La fonction accepte autant de paramètres que nécessaire, chacun pouvant être dans un format différent
	 * @return array la liste de tous les destinataires formattés en tableau, même s'il n'y en a qu'un. Chaque élément comporte les index suivants :
	 * 	 - mail : l'adresse mail brute
	 * 	 - formatted : l'adresse finale (préfixée du nom si fourni)
	 * 	 - user : l'objet utilisateur (si fourni)
	 * @throws SCException si l'adresse est un objet User qui n'a pas d'adresse mail ou qu'elle n'est pas valide
	 */
	protected static function _parseAddress($address)
	{
		// Init
		$list = array();
		
		// Détection de paramètres multiples
		$args = func_get_args();
		if (count($args) > 1)
		{
			$address = $args;
		}
		
		// Si tableau
		if (is_array($address))
		{
			// Parcours
			$max = count($address);
			for ($i = 0; $i < $max; ++$i)
			{
				$list = array_merge($list, self::_parseAddress($address[$i]));
			}
		}
		else
		{
			// Si objet
			if (is_object($address) and $address instanceof User)
			{
				// Init
				$name = array();
				$mail = '';
			
				// Données
				$mail = $address->get('mail');
				if (is_null($mail) or strlen(trim($mail)) == 0 or !($mail = filter_var($mail, FILTER_VALIDATE_EMAIL)))
				{
					throw new SCException('Destinataire non valide (Objet '.get_class($address).'('.$address->id().')');
				}
				$prenom = $address->get('first_name');
				if (!is_null($prenom))
				{
					$name[] = $prenom;
				}
				$nom = $address->get('last_name');
				if (!is_null($nom))
				{
					$name[] = $nom;
				}
				
				// Si redirection forcée
				if (self::$_redirect)
				{
					$mail = self::$_redirect;
				}
				
				// Ajout
				$list[] = array(
					'mail' =>		$mail,
					'formatted' =>	(count($name) > 0) ? '"'.str_replace(',', '', implode(' ', $name)).'" <'.$mail.'>' : $mail,
					'user' =>		$address
				);
			}
			elseif (preg_match_all('/([^<]*)<([^>]+)>;*,*/i', $address, $matches))
			{
				// Parcours
				$max = count($matches[0]);
				for ($i = 0; $i < $max; ++$i)
				{
					// Si valide
					if ($mail = filter_var($matches[2][$i], FILTER_VALIDATE_EMAIL))
					{
						// Si redirection forcée
						if (self::$_redirect)
						{
							$mail = self::$_redirect;
						}
						
						// Ajout
						$list[] = array(
							'mail' =>		$mail,
							'formatted' =>	(strlen(trim($matches[1][$i])) > 0) ? '"'.str_replace(',', '', trim($matches[1][$i])).'" <'.$mail.'>' : $mail,
							'user' =>		false
						);
					}
				}
			}
			else
			{
				// Analyse
				while ($mail = filter_var($address, FILTER_VALIDATE_EMAIL))
				{
					// Si redirection forcée
					$finalMail = self::$_redirect ? self::$_redirect : $mail;
					
					// Ajout
					$list[] = array(
						'mail' =>		$finalMail,
						'formatted' =>	$finalMail,
						'user' =>		false
					);
					
					// Préparation pour la boucle suivante (adresses qui se suivent)
					$address = str_replace($mail, '', $address);
				}
			}
		}
		
		// Renvoi
		return $list;
	}
	
	/**
	 * Ajout d'un fichier joint
	 * @param string|File $file le chemin local du fichier, ou un objet File
	 * @param string|boolean $filename le nom du fichier à afficher dans le mail, ou NULL pour utiliser la détection auto à partir de l'url fournie (facultatif, défaut : NULL)
	 * @param string|boolean $mime le type mime du fichier, ou NULL pour utiliser la détection auto (facultatif, défaut : NULL)
	 * @return SendMail l'objet pour chaînage
	 * @throws SCException
	 */
	public function addFile($file, $filename = NULL, $mime = NULL)
	{
		// Type d'argument
		if (is_string($file))
		{
			$file = new File($file);
		}
		
		// Vérification
		if (!$file->isFile())
		{
			throw new SCException('Le fichier ajouté est un dossier ('.$file->getPath().')');
		}
		elseif (!$file->exists())
		{
			throw new SCException('Fichier ajouté inexistant ('.$file->getPath().')');
		}
		
		// Autres données
		if (is_null($filename))
		{
			$filename = $file->getBasename();
		}
		if (is_null($mime))
		{
			$mime = $file->getMimeType();
		}
		
		// Ajout
		return $this->addRawFile($file->getContents(), $filename, $mime);
	}
	
	/**
	 * Ajout d'un fichier joint - mode données brutes
	 * @param string $data les données brutes du fichier
	 * @param string $filename le nom du fichier à afficher dans le mail
	 * @param string $mime le type mime du fichier,
	 * @return SendMail l'objet pour chaînage
	 * @throws SCException
	 */
	public function addRawFile($data, $filename, $mime)
	{
		// Ajout
		$this->_files[] = array(
			'data' => $data,
			'filename' => $filename,
			'mime' => $mime
		);
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Envoi le message
	 * @param string|array $toAdd le ou les destinataires, ou NULL pour utiliser uniquement ceux définis par to()
	 * (facultatif, défaut : NULL). Peut être omis pour préciser $personnalise directement (ex : send(true))
	 * @param string|array $personnalise indique s'il faut personnaliser le contenu envoyé sur les champs suivants :
	 * 	 - [first_name]
	 * 	 - [last_name]
	 * 	 - [mail]
	 * 	 - [uniqId]
	 * 	 (facultatif, défaut : false)
	 * @return SendMail l'objet pour chaînage
	 * @throws SCException si aucun destinataire n'est défini, ou que le sujet ou le contenu est vide
	 */
	public function send($toAdd = NULL, $personnalise = false)
	{
		// Destinataires
		$to = $this->_to;
		
		// Paramètres
		if (is_bool($toAdd))
		{
			$personnalise = $toAdd;
			$toAdd = NULL;
		}
		if (!is_null($toAdd))
		{
			$to = array_merge($to, self::_parseAddress($toAdd));
		}
		
		// Vérifications
		if (count($to) == 0 and count($this->_cc) == 0 and count($this->_bcc) == 0)
		{
			throw new SCException('Aucun destinataire défini pour le message', 1);
		}
		if (strlen($this->_subject) == 0)
		{
			throw new SCException('Sujet manquant pour le message', 2);
		}
		if (strlen($this->_content['txt']) == 0 and strlen($this->_content['html']) == 0)
		{
			throw new SCException('Aucun contenu pour le message', 7);
		}
		
		// Si aucun destinataire en to
		if (count($to) == 0)
		{
			$to[] = $this->_from;
		}
		
		// Si envoi groupé, on rassemble toutes les adresses
		if (!$this->_separateSend)
		{
			$to = array($to);
		}
		
		// Mise en place
		self::$_server->init();
		
		// Parcours des destinataires
		foreach ($to as $dest)
		{
			// Envoi
			list($headers, $message) = $this->_buildMessage($dest, $personnalise, self::$_server->useTo());
			self::$_server->send($this->_from, $dest, $this->_cc, $this->_bcc, $this->_subject, $message, $headers);
			
			// Préparation pour l'envoi suivant
			$this->_uniqId = md5(uniqid(rand(), true));
		}
		
		// Fermeture du serveur
		self::$_server->close();
		
		// Renvoi pour chaînage
		return $this;
	}
	
	/**
	 * Construit le code du message
	 * @param array $to le ou la liste des destinataires
	 * @param string|array $personnalise indique s'il faut personnaliser
	 * @param boolean $useTo indique si on ajoute les headers pour le champ To: (défaut : true)
	 * @return array un tableau avec deux entrées : le message et la liste des headers
	 */
	protected function _buildMessage($to, $personnalise, $useTo = true)
	{
		// Init
		$headers = array();
		$message = array();
		$boudary = '------------Boundary-00=_'.$this->_uniqId.'0000000000000';
		$newline = self::$_server->getNewLineString();
		
		// Mise en tableau si destinataire unique
		if (isset($to['formatted']))
		{
			$to = array($to);
		}
		
		// Composition
		$headers[] = 'X-Sender: <'.$this->_from['mail'].'>';
		$headers[] = 'X-auth-smtp-user: <'.self::$_core.'>';
		$headers[] = 'X-abuse-contact: <'.self::$_core.'>';
		$headers[] = 'MIME-Version: '.self::$_mime;						// Version Mime
		$headers[] = 'Message-ID: <'.$this->_uniqId.'@'.Env::getConfig('mail')->get('domain').'>';
		$headers[] = 'Date: '.Date::string('r');
		
		// Type de contenu
		$hasFiles = (count($this->_files) > 0);
		if ($hasFiles)
		{
			$headers[] = 'Content-Type: multipart/mixed; boundary="'.$boudary.'"';
		}
		elseif ($this->_mode == self::MODE_MIXED)
		{
			$headers[] = 'Content-Type: Multipart/alternative; boundary="'.$boudary.'"';
		}
		elseif ($this->_mode == self::MODE_HTML)
		{
			$headers[] = 'Content-Type: text/html; charset="utf-8"';
		}
		else
		{
			$headers[] = 'Content-Type: text/plain; charset="utf-8"';
		}
		$headers[] = 'Content-Transfer-Encoding: 8bit';
		
		// Emetteur
		$headers[] = 'X-Mailer: Constellation / Php '.phpversion();
		$headers[] = 'From: '.$this->_from['formatted'];
		$headers[] = 'Reply-to: '.$this->_from['formatted'];
		$headers[] = 'Return-Path: '.$this->_from['formatted'];
		
		// Priorité
		if ($this->_priority != self::PRIORITY_NORMAL)
		{
			$headers[] = 'X-Priority: '.$this->_priority;
		}
		
		// To
		if ($useTo)
		{
			$finalTo = array();
			$max = count($to);
			for ($i = 0; $i < $max; ++$i)
			{
				$finalTo[] = $to[$i]['formatted'];
			}
			if (count($finalTo) > 0)
			{
				$headers[] = 'To: '.implode(','.$newline."\t", $finalTo);
			}
		}
		
		// CC
		$finalCC = array();
		$max = count($this->_cc);
		for ($i = 0; $i < $max; ++$i)
		{
			$finalCC[] = $this->_cc[$i]['formatted'];
		}
		if (count($finalCC) > 0)
		{
			$headers[] = 'Cc: '.implode(','.$newline."\t", $finalCC);
		}
		
		// BCC - uniquement en mode php, gérés lors de l'envoi en SMTP
		if (self::$_server instanceof PhpSender)
		{
			$finalBCC = array();
			$max = count($this->_bcc);
			for ($i = 0; $i < $max; ++$i)
			{
				$finalBCC[] = $this->_bcc[$i]['formatted'];
			}
			if (count($finalBCC) > 0)
			{
				$headers[] = 'Bcc: '.implode(','.$newline."\t", $finalBCC);
			}
		}
		
		// Sujet
		$headers[] = 'Subject: '.$this->_subject;
		
		// Confirmation de lecture
		if ($this->_confirm)
		{
			$headers[] = 'Disposition-Notification-To: <'.$this->_from['mail'].'>';
		}
		
		// Headers statiques
		$headers = array_merge($headers, self::$_staticHeaders);
		
		// Si personnalisation
		if ($personnalise)
		{
			// Valeurs de remplacement
			$names = array('[first_name]', '[last_name]', '[mail]', '[uniqId]');
			$replace = array('', '', $to[0]['mail'], '');
			if ($to[0]['user'])
			{
				$replace[0] = $to[0]['user']->getData('first_name', '');
				$replace[1] = $to[0]['user']->getData('last_name', '');
				$replace[2] = $to[0]['user']->getData('mail', '');
				$replace[3] = $to[0]['user']->getData('uniqId', '');
			}
		}
		
		// Message
		if ($this->_mode == self::MODE_MIXED or $this->_mode == self::MODE_TEXT)
		{
			// Si personnalisation
			if ($personnalise)
			{
				// Mise en place
				$finalText = str_ireplace($names, $replace, $this->_content['txt']);
			}
			else
			{
				$finalText = $this->_content['txt'];
			}

			// Mode
			if ($this->_mode == self::MODE_MIXED or $hasFiles)
			{
				// Séparation
				$message[] = '--'.$boudary;

				// Message texte
				$message[] = 'Content-Type: text/plain; charset="utf-8"';
				$message[] = 'Content-Transfer-Encoding: 8bit';
				$message[] = '';
				$message[] = $finalText;
				$message[] = '';
			}
			else
			{
				$message[] = $finalText;
			}
		}
		if ($this->_mode == self::MODE_MIXED or $this->_mode == self::MODE_HTML)
		{
			// Si personnalisation
			if ($personnalise)
			{
				// Mise en place
				$finalHTML = str_ireplace($names, $replace, $this->_content['html']);
			}
			else
			{
				$finalHTML = $this->_content['html'];
			}
			
			// Mode
			if ($this->_mode == self::MODE_MIXED or $hasFiles)
			{
				// Séparation
				$message[] = '--'.$boudary;

				// Message texte
				$message[] = 'Content-Type: text/html; charset="utf-8"';
				$message[] = 'Content-Transfer-Encoding: 8bit';
				$message[] = '';
				$message[] = $finalHTML;
				$message[] = '';
			}
			else
			{
				$message[] = $finalHTML;
			}
		}
		
		// Pièces jointes
		$max = count($this->_files);
		for ($i = 0; $i < $max; ++$i)
		{
			// Séparation
			$message[] = '--'.$boudary;
			
			// Fichier
			$message[] = 'Content-Type: '.$this->_files[$i]['mime'].';';
			$message[] = ' name="'.$this->_files[$i]['filename'].'"';
			$message[] = 'Content-Transfer-Encoding: base64';
			$message[] = 'Content-Disposition: attachement;';
			$message[] = ' filename="'.$this->_files[$i]['filename'].'"';
			$message[] = '';
			$message[] = chunk_split(base64_encode($this->_files[$i]['data']));
		}
		
		// Fin
		if ($this->_mode == self::MODE_MIXED or $hasFiles)
		{
			$message[] = '--'.$boudary.'--';
		}
		
		// Assemblage
		$headers = implode($newline, $headers);
		$message = implode($newline, $message);
		
		// Découpe
		$message = wordwrap($message, 70);
		
		// Correction du message (Windows uniquement)
		if (Env::isOsWindows())
		{
			$message = str_replace($newline.".", $newline."..", $message);
		}
		
		return array($headers, $message);
	}
}

/**
 * Interface pour les classes de méthodes d'envoi de mail
 * @package Constellation
 * @subpackage Interfaces
 * @version 1.0
 */
interface MailSender {
	/**
	 * Prépare le serveur d'envoi si nécessaire
	 * @return void
	 */
	public function init();
	
	/**
	 * Envoie le message
	 * @param array $from l'émetteur du mail
	 * @param array $to les destinataires du mail
	 * @param array $cc les destinataires en cc du mail
	 * @param array $bcc les destinataires en bcc du mail
	 * @param string $subject le sujet du mail
	 * @param string $message le contenu du mail
	 * @param string $headers les headers du mail
	 * @return void
	 * @throws SCException
	 */
	public function send($from, $to, $cc, $bcc, $subject, $message, $headers);
	
	/**
	 * Ferme le serveur d'envoi si nécessaire
	 * @return void
	 */
	public function close();

	/**
	 * Renvoie le type de retour à la ligne attendu, suivant l'OS
	 * @return void
	 */
	public function getNewLineString();

	/**
	 * Indique s'il faut ajouter le champ To: aux headers
	 * @return boolean une confirmation
	 */
	public function useTo();
}

/**
 * Serveur d'envoi Php, utilise la fonction mail()
 * @package Constellation
 * @subpackage Interfaces
 * @version 1.0
 */
class PhpSender implements MailSender {
	/**
	 * Prépare le serveur d'envoi
	 * @return void
	 */
	public function init()
	{
	}
	
	/**
	 * Envoie le message
	 * @param array $from l'émetteur du mail
	 * @param array $to les destinataires du mail
	 * @param array $cc les destinataires en cc du mail
	 * @param array $bcc les destinataires en bcc du mail
	 * @param string $subject le sujet du mail
	 * @param string $message le contenu du mail
	 * @param string $headers les headers du mail
	 * @return void
	 * @throws SCException
	 */
	public function send($from, $to, $cc, $bcc, $subject, $message, $headers)
	{
		// Mise en tableau si destinataire unique
		if (isset($to['formatted']))
		{
			$to = array($to);
		}

		$finalTo = array();
		$max = count($to);
		for ($i = 0; $i < $max; ++$i)
		{
			$finalTo[] = $to[$i]['formatted'];
		}

		// Relai
		if (!mail(implode(', ', $finalTo), $subject, $message, $headers))
		{
			throw new SCException('Echec de l\'envoi du message (Mode mail)');
		}
	}
	
	/**
	 * Ferme le serveur d'envoi
	 * @return void
	 */
	public function close()
	{
	}

	/**
	 * Renvoie le type de retour à la ligne attendu, suivant l'OS
	 * @return void
	 */
	public function getNewLineString()
	{
		return PHP_EOL;
	}

	/**
	 * Indique s'il faut ajouter le champ To: aux headers
	 * @return boolean une confirmation
	 */
	public function useTo()
	{
		return false;
	}
}

/**
 * Serveur d'envoi SMTP
 * @package Constellation
 * @subpackage Interfaces
 * @version 1.0
 */
class SMTPSender implements MailSender {
	/**
	 * Objet de configration SMTP
	 * @var \SYS\Config 
	 */
	protected static $_smtp;
	/**
	 * Objet de connection au serveur
	 * @var string 
	 */
	protected $_server;
	/**
	 * Compteur du nombre d'envois
	 * @var int 
	 */
	protected $_counter;
	
	/**
	 * Prépare le serveur d'envoi
	 * @return void
	 * @throws SCException
	 */
	public function init()
	{
		// Init
		$this->_counter = 0;
		
		// Obtention de la configuration
		if (!isset(self::$_smtp))
		{
			self::$_smtp = Env::getConfig()->get('servers')->get('smtp');
		}
		
		// Log
		Log::info('Connection au serveur SMTP '.self::$_smtp->get('server').':'.self::$_smtp->get('port').' (timeout : '.self::$_smtp->get('timeout').')');
		
		// Connection au serveur SMTP
		$this->_server = fsockopen(self::$_smtp->get('server'), self::$_smtp->get('port'), $num_erreur, $msg_erreur, self::$_smtp->get('timeout'));
		if (!$this->_server)
		{
			throw new SCException('Erreur lors de la connexion au serveur d\'envoi (Mode : SMPT, erreur ('.$num_erreur.') : '.$msg_erreur.')');
		}
		
		// Log
		$response = $this->_getSMTPData();
		Log::info('Réponse de connection du serveur SMTP : ['.$response['code'].'] '.$response['message']);
		
		// Configuration du timeout (Linux uniquement)
		if (!Env::isOsWindows())
		{
			socket_set_timeout($this->_server, self::$_smtp->get('timeout'), 0);
		}
		
		// Test commande EHLO
		$domain = Env::getConfig()->get('mail')->get('domain');
		if (!$this->_command('EHLO '.$domain, 250))
		{
			// Commande HELO
			if (!$this->_command('HELO '.$domain, 250))
			{
				throw new SCException('Le serveur SMTP refuse l\'identification (EHLO et HELO)');
			}
		}
		
		// Connexion sécurisée
		if (self::$_smtp->get('tls') and !$this->_command('STARTTLS', 220, 'Le serveur refuse la connection sécurisée ( STARTTLS ) !!!'))
		{
			throw new SCException('Le serveur refuse la connexion sécurisée (STARTTLS)');
		}
		
		// Si identification
		$user = self::$_smtp->get('user');
		$pass = self::$_smtp->get('pass');
		if (is_string($user) and strlen(trim($user)) > 0 and is_string($pass))
		{
			// Authentification
			if (!$this->_command('AUTH LOGIN', 334))
			{
				throw new SCException('Le serveur SMTP refuse l\'authentification (AUTH LOGIN)');
			}
			
			// Login
			if (!$this->_command(base64_encode($user), 334))
			{
				throw new SCException('Login SMTP incorrect');
			}
			
			// Mot de passe
			if (!$this->_command(base64_encode($pass), 235))
			{
				throw new SCException('Mot de passe SMTP incorrect');
			}
		}
	}
	
	/**
	 * Lecture des données renvoyées par le serveur SMTP
	 * @return array les données renvoyées, avec les index 'code' et 'message'
	 * @throws SCException
	 */
	protected function _getSMTPData()
	{
		// Sécurisation
		if (!isset($this->_server))
		{
			throw new SCException('Aucune connexion serveur disponible');
		}
		
		// Init
		$data = '';
		
		// Récupération
		while ($donnees = fgets($this->_server, 515))
		{
			// Ajout
			$data .= $donnees;
			
			// Si fin de données
			if (substr($donnees, 3, 1) == ' ' and !empty($data))
			{
				break;
			}
		}
		
		return array('code'=>intval(substr($data, 0, 3)), 'message'=>$data);
	}
	
	
	/**
	 * Exécution de commande SMTP
	 * @param string $command la _command à exécuter
	 * @param string|array $validResponse le code ou la liste des codes de retour valides
	 * @param boolean $fullResponse indique s'il faut renvoyer toute la réponse ou juste un booléen de confirmation de succès
	 * @return array les données renvoyées, avec les index 'valid' et 'message' si $fullResponse vaut true, ou un boolean de confirmation
	 * @throws SCException
	 */
	protected function _command($command, $validResponse, $fullResponse = false)
	{
		// Sécurisation
		if (!isset($this->_server))
		{
			throw new SCException('Aucune connexion serveur disponible');
		}
		
		// Envoi
		Log::info('_command SMTP '.$command);
		fputs($this->_server, $command."\n");
		$response = $this->_getSMTPData();
		Log::info('Réponse de connection du serveur SMTP : ['.$response['code'].'] '.$response['message']);
		
		// Si retour non valide
		if ((is_array($validResponse) and !in_array($response['code'], $validResponse)) or (!is_array($validResponse) and $response['code'] !== $validResponse))
		{
			return $fullResponse ? array('valid' => false, 'message' => $response['msg']) : false;
		}
		else
		{
			return $fullResponse ? array('valid' => true, 'message' => $response['msg']) : true;
		}
	}
	
	/**
	 * Envoie le message
	 * @param array $from l'émetteur du mail
	 * @param array $to les destinataires du mail
	 * @param array $cc les destinataires en cc du mail
	 * @param array $bcc les destinataires en bcc du mail
	 * @param string $subject le sujet du mail
	 * @param string $message le contenu du mail
	 * @param string $headers les headers du mail
	 * @return void
	 * @throws SCException
	 */
	public function send($from, $to, $cc, $bcc, $subject, $message, $headers)
	{
		// Si pas premier envoi
		if ($this->_counter > 0)
		{
			// Reset des paramètres
			if (!$this->_command('RSET', 250))
			{
				throw new SCException('Echec de l\'effacement des paramètres');
			}
		}
		
		// Emetteur
		if (!$this->_command('MAIL FROM:<'.$from['mail'].'>', 250))
		{
			throw new SCException('Commande MAIL FROM refusée');
		}
		
		// Parcours
		$max = count($to);
		for ($i = 0; $i < $max; ++$i)
		{
			// Commande
			if (!$this->_command('RCPT TO:<'.$to[$i]['mail'].'>', array(250,251)))
			{
				throw new SCException('Echec lors du listing des destinataires TO');
			}
		}
		$max = count($cc);
		for ($i = 0; $i < $max; ++$i)
		{
			// Commande
			if (!$this->_command('RCPT TO:<'.$cc[$i]['mail'].'>', array(250,251)))
			{
				throw new SCException('Echec lors du listing des destinataires CC');
			}
		}
		$max = count($bcc);
		for ($i = 0; $i < $max; ++$i)
		{
			// Commande
			if (!$this->_command('RCPT TO:<'.$bcc[$i]['mail'].'>', array(250,251)))
			{
				throw new SCException('Echec lors du listing des destinataires BCC');
			}
		}
		
		// Envoi des entêtes et du message
		fputs($this->_server, $headers."\n\n\n".$message);
		fputs($this->_server, "\r\n.\r\n");
		
		// Retour
		$response = $this->_getSMTPData();
		Log::info('Réception des données par le serveur SMTP : ['.$response['code'].'] '.$response['message']);
		
		// Si erreur
		if ($response['code'] != 250 and $response['code'] != 354)
		{
			throw new SCException('Echec de l\'envoi du contenu du message');
		}
		
		// Compteur
		$this->_counter++;
	}
	
	/**
	 * Ferme le serveur d'envoi
	 * @return void
	 */
	public function close()
	{
		if (isset($this->_server))
		{
			// Log
			Log::info('Déconnection du serveur SMTP '.self::$_smtp->get('server'));
			
			// Commande
			if (!$this->_command('QUIT', 221))
			{
				throw new SCException('Erreur lors de la déconnexion du serveur SMTP');
			}
			
			// Délai pour que le serveur termine toutes les instructions
			@sleep(2);
			
			// Fermeture du flux
			if (!fclose($this->_server))
			{
				throw new SCException('Impossible de fermer le socket');
			}
			
			// Reset
			unset($this->_server);
		}
	}

	/**
	 * Renvoie le type de retour à la ligne attendu, suivant l'OS
	 * @return void
	 */
	public function getNewLineString()
	{
		return self::$_smtp->get('newline');
	}

	/**
	 * Indique s'il faut ajouter le champ To: aux headers
	 * @return boolean une confirmation
	 */
	public function useTo()
	{
		return true;
	}
}