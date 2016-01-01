<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2016                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion du formulaire de recherche pour l'espace public
 *
 * @package SPIP\Dist\Formulaires
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Chargement des valeurs par défaut des champs du `#FORMULAIRE_RECHERCHE`
 * 
 * On peut lui passer l'URL de destination en premier argument.
 * On peut passer une deuxième chaine qui va différencier le formulaire
 * pour pouvoir en utiliser plusieurs sur une même page.
 *
 * @param string $lien  URL où amène le formulaire validé
 * @param string $class Une class différenciant le formulaire
 * @return array
 */
function formulaires_recherche_charger_dist($lien = '', $class = '') {
	if ($GLOBALS['spip_lang'] != $GLOBALS['meta']['langue_site'])
		$lang = $GLOBALS['spip_lang'];
	else
		$lang = '';

	return
		array(
			'action' => ($lien ? $lien : generer_url_public('recherche')), # action specifique, ne passe pas par Verifier, ni Traiter
			'recherche' => _request('recherche'),
			'lang' => $lang,
			'class' => $class,
			'_id_champ' => $class ? substr(md5($action.$class), 0, 4) : 'recherche'
		);
}
