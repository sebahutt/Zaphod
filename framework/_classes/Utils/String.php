<?php
/**
 * Classe de gestion chaînes de caractères - fonctions génériques
 */
class String extends StaticClass {
	/**
	 * Nettoyage d'une chaîne de tous ses accents et ses caractères spéciaux, par exemple pour des comparaisons
	 * 
	 * @param string $string la chaine à nettoyer
	 * @return string la chaine nettoyée
	 */
	public static function removeAccents($string)
	{
		$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
		$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
		return str_replace($a, $b, $string); 
	}
	
	/**
	 * Nettoyage d'une chaîne pour en faire un nom de fichier url valide. On part du principe que seul le nom de fichier est passé,
	 * donc tous les / sont convertis. En revanche, l'extension est conservée telle quelle.
	 * 
	 * @param string $name le nom à nettoyer
	 * @return string le nom nettoyé
	 */
	public static function cleanForUrl($name)
	{
		// Tableau de remplacement
		$map = array(
			'/à|á|å|â|ä|ã/ui' 	=> 'a',
			'/è|é|ê|ë/ui' 		=> 'e',
			'/ì|í|î|ï/ui' 		=> 'i',
			'/ò|ó|ô|ø/ui' 		=> 'o',
			'/ù|ú|û/ui' 		=> 'u',
			'/ç/ui' 			=> 'c',
			'/ñ/ui' 			=> 'n',
			'/ä|æ/ui' 			=> 'ae',
			'/ö/ui' 			=> 'oe',
			'/ü/ui' 			=> 'ue',
			'/Ä/ui' 			=> 'ae',
			'/Ü/ui' 			=> 'ue',
			'/Ö/ui' 			=> 'oe',
			'/ß/ui' 			=> 'ss',
			'/\./' 				=> ' ',
			'/[^\w]/' 			=> ' '
		);
		
		// Découpe si fichier
		$pathinfo = pathinfo(str_replace('/', '_', $name));
		if (isset($pathinfo['extension']) and strlen($pathinfo['extension']) > 0)
		{
			$pathinfo['extension'] = '.'.$pathinfo['extension'];
		}
		else
		{
			$pathinfo['extension'] = '';
		}
		
		// Traitement
		$pathinfo['filename'] = trim(preg_replace(array_keys($map), array_values($map), strtolower($pathinfo['filename'])));
		$pathinfo['filename'] = preg_replace('/_{2,}/', '_', str_replace(' ', '_', $pathinfo['filename']));
		
		return $pathinfo['filename'].$pathinfo['extension'];
	}
	
	/**
	 * Découpe une chaîne à la longueur souhaitée si nécessaire, avec des points de suite
	 * 
	 * @param string $string la chaîne à découper
	 * @param int $length la longueur maximale
	 * @param string $replace le caractère à utiliser pour masquer la partie coupée (facultatif, défaut : '...')
	 */
	public static function limitLength($string, $length, $replace = '...')
	{
		if (strlen($string) > $length)
		{
			$string = substr($string, 0, $length-strlen($replace)).$replace;
		}
		
		return $string;
	}
	
	/**
	 * Découpe un nom de fichier à la longueur souhaitée si nécessaire, avec des points de suite
	 * 
	 * @param string $string la chaîne à découper
	 * @param int $length la longueur maximale
	 * @param string $replace le caractère à utiliser pour masquer la partie coupée (facultatif, défaut : '..')
	 */
	public static function limitFilenameLength($string, $length, $replace = '..')
	{
		if (strlen($string) > $length)
		{
			$extension = pathinfo($string, PATHINFO_EXTENSION);
			if ($extension and strlen($extension) > 0)
			{
				$string = substr($string, 0, $length-strlen($replace)-strlen($extension)-1).$replace.'.'.$extension;
			}
			else
			{
				$string = substr($string, 0, $length-strlen($replace)).$replace;
			}
		}
		
		return $string;
	}
}
