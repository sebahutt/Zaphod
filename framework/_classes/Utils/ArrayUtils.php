<?php
/**
 * Classe de manipulation de tableaux
 */
class ArrayUtils extends StaticClass
{
	/**
	 * Nettoie un tableau de ses valeurs vides
	 *
	 * @param array $array le tableau à nettoyer
	 * @param boolean $preserveKeys indique s'il faut conserver les clés (facultatif, défaut : false)
	 * @return array le tableau nettoyé
	 */
	public static function removeEmptyValues($array, $preserveKeys = false)
	{
		foreach ($array as $index => $value)
		{
			if (empty($value))
			{
				unset($array[$index]);
			}
		}
		
		return $preserveKeys ? $array : array_values($array);
	}
	
	/**
	 * Supprime une valeur d'un tableau
	 *
	 * @param array $array le tableau à nettoyer
	 * @param mixed $remove la valeur à supprimer
	 * @param boolean $strict indique si l'équivalence doit être stricte - opérateur === (facultatif, défaut : true)
	 * @param boolean $preserveKeys indique s'il faut conserver les clés (facultatif, défaut : false)
	 * @return array le tableau nettoyé
	 */
	public static function removeValue($array, $remove, $strict = true, $preserveKeys = false)
	{
		foreach ($array as $index => $value)
		{
			if (($strict and $value === $remove) or (!$strict and $value == $remove))
			{
				unset($array[$index]);
			}
		}
		
		return $preserveKeys ? $array : array_values($array);
	}
	
	/**
	 * Assemble un tableau en rajoutant une séparation spécifique au dernier élément (par exemple : 1, 2, 3 et 4)
	 *
	 * @param array $array le tableau à assembler
	 * @param string $separator le séparateur normal (facultatif, défaut : ', ')
	 * @param string $last le séparateur pour le dernier élément (facultatif, défaut : ' et ')
	 * @return string le tableau assemblé
	 */
	public static function implodeWithLast($array, $separator = ', ', $last = ' et ')
	{
		if (count($array) > 1)
		{
			$end = array_pop($array);
			return implode($separator, $array).$last.$end;
		}
		else
		{
			return implode($separator, $array);
		}
	}
}
