<?php
/**
 * Classe de manipulation de nombres
 */
class Number extends StaticClass
{
	/**
	 * Tableau des formats de taille de fichier
	 * @var array
	 */
	protected static $_filesizes = array('octets', 'Ko', 'Mo', 'Go', 'To', 'Po');
	
	/**
	 * Conversion d'une couleur RVB en Hex
	 * 
	 * Convertitune couleur RVB en code Hex (sans le préfixe #) pour utilisation dans le code HTML par exemple
	 * 
	 * @param mixed $red La valeur de rouge, de 0 à 255
	 * @param mixed $green La valeur de vert, de 0 à 255, ou NULL pour utiliser $red (facultatif, défaut : NULL)
	 * @param mixed $blue La valeur de bleu, de 0 à 255, ou NULL pour utiliser $red (facultatif, défaut : NULL)
	 * @return string Renvoie le code Hex de la couleur (sans #)
	 */
	public static function convertRVB2Hex($red = 0, $green = NULL, $blue = NULL)
	{
		// Valeurs auto
		if (is_null($green))
		{
			$green = $red;
		}
		if (is_null($blue))
		{
			$blue = $red;
		}
		
		// Debug
		$red = max(0, min(255, $red));
		$green = max(0, min(255, $green));
		$blue = max(0, min(255, $blue));
		
		// Conversion
		return strtoupper(str_pad(dechex($red), 2, '0', STR_PAD_LEFT).str_pad(dechex($green), 2, '0', STR_PAD_LEFT).str_pad(dechex($blue), 2, '0', STR_PAD_LEFT));
	}
	
	/**
	 * Conversion d'une couleur RVB en Hex
	 * 
	 * Convertitune couleur RVB en code Hex (sans le préfixe #) pour utilisation dans le code HTML par exemple
	 * 
	 * @param string $hex le code hexa
	 * @return array un tableau avec les 3 valeurs : array(r, v, b)
	 */
	public static function convertHex2RVB($hex)
	{
		// Récupération
		$red = 16*hexdec(substr($hex, 0, 1))+hexdec(substr($hex, 1, 1));
		$green = 16*hexdec(substr($hex, 2, 1))+hexdec(substr($hex, 3, 1));
		$blue = 16*hexdec(substr($hex, 4, 1))+hexdec(substr($hex, 5, 1));
		
		// Conversion
		return array($red, $green, $blue);
	}
	
	/**
	 * Obtention du poids de fichier en affichage humain (ko, mo...)
	 * 
	 * @param int $size le poids en octets
	 * @return string le poids pour affichage, NULL si non défini (dossier par exemple)
	 * @todo ajouter le support des locales pour le formattage des nombres
	 */
	public static function getReadableSize($size)
	{
		// On détermine l'ordre de grandeur
		$currentSize = 0;
		$maxSize = count(self::$_filesizes)-1;
		while ($size > pow(1024, $currentSize+1)-1 and $currentSize < $maxSize)
		{
			$currentSize++;
		}
		
		// Conversion
		$size /= pow(1024, $currentSize);
		
		// Tronquage
		if ($currentSize > 0 and $size > intval($size))
		{
			if (intval($size) > 99)
			{
				$size = intval($size);
			}
			elseif (intval($size) > 9)
			{
				$size = number_format($size, 1, ',', '.');
			}
			else
			{
				$size = number_format($size, 2, ',', '.');
			}
		}
		
		// Renvoi avec unité
		return $size.' '.self::$_filesizes[$currentSize];
	}
	
	/**
	 * Convertit la taille maximale d'upload en octets
	 * 
	 * @param string $upload_size la valeur de configuration
	 * @return int la taille convertie
	 */
	public static function convertUploadSize($upload_size)
	{
		if (!is_numeric($upload_size))
		{
			$quantite = intval(substr($upload_size, 0, -1));
			$unit = strtolower(substr($upload_size, -1));
			switch ($unit)
			{
				case 'k':
					$upload_size = $quantite*1024;
					break;
				
				case 'm':
					$upload_size = $quantite*1048576;
					break;
				
				case 'g':
					$upload_size = $quantite*1073741824;
					break;
			}
		}
		
		return $upload_size;
	}
}
