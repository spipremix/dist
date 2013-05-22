<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion du formulaire de recréation de mot de passe
 *
 * @package SPIP\Dist\Formulaires
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');

/**
 * Retrouve un auteur soit par son identifiant, soit par un jeton attribué
 * par le formulaire d'oubli de mot de passe
 *
 * L'auteur doit en plus : ne pas être à la poubelle et avoir un mot de passe.
 *
 * @use auteur_verifier_jeton()
 * 
 * @param int|null $id_auteur
 *     Identifiant de l'auteur, si connu
 * @param string|null $jeton
 *     Jeton d'oubli de mot de passe permettant de retrouver l'auteur, si connu
 * @return array|bool
 *     - array : Description de l'auteur dans spip_auteurs,
 *     - false si auteur non retrouvé ou non valide.
**/
function retrouve_auteur($id_auteur,$jeton=''){
	if ($id_auteur=intval($id_auteur)) {
		return sql_fetsel('*','spip_auteurs',array('id_auteur='.intval($id_auteur),"statut<>'5poubelle'","pass<>''"));
	}
	elseif ($jeton) {
		include_spip('action/inscrire_auteur');
		if ($auteur = auteur_verifier_jeton($jeton)
		  AND $auteur['statut']<>'5poubelle'
		  AND $auteur['pass']<>''){
			return $auteur;
		}
	}
	return false;
}

/**
 * Chargement du formulaire de recréation de mot de passe d'un auteur.
 * 
 * Soit un cookie d'oubli fourni par `#FORMULAIRE_OUBLI` est passé dans l'URL par `&p=`,
 * soit un id_auteur est passé en paramètre `#FORMULAIRE_MOT_DE_PASSE{#ID_AUTEUR}`.
 * 
 * Dans les deux cas on verifie que l'auteur est autorisé
 *
 * @use retrouve_auteur()
 * 
 * @param int|null $id_auteur
 *     Identifiant de l'auteur, si connu
 * @param string|null $jeton
 *     Jeton d'oubli de mot de passe permettant de retrouver l'auteur, si connu
 * @return array
 *     Environnement par défaut du formulaire
 */
function formulaires_mot_de_passe_charger_dist($id_auteur=null, $jeton=null){

	$valeurs = array();
	// compatibilite anciens appels du formulaire
	if (is_null($jeton)) $jeton = _request('p');
	$auteur = retrouve_auteur($id_auteur,$jeton);

	if ($auteur){
		$valeurs['id_auteur'] = $id_auteur; // a toutes fins utiles pour le formulaire
		if ($jeton)
			$valeurs['_hidden'] = '<input type="hidden" name="p" value="'.$jeton.'" />';
	}
	else {
		$valeurs['_hidden'] = _T('pass_erreur_code_inconnu');
		$valeurs['editable'] =  false; // pas de saisie
	}
	$valeurs['oubli']='';
	return $valeurs;
}

/**
 * Vérification de la saisie du nouveau mot de passe.
 * 
 * On vérifie qu'un mot de passe est saisi, et que sa longuer est suffisante
 * Ce serait le lieu pour vérifier sa qualité (caractères spéciaux ...)
 *
 * @param int|null $id_auteur
 *     Identifiant de l'auteur, si connu
 * @param string|null $jeton
 *     Jeton d'oubli de mot de passe permettant de retrouver l'auteur, si connu
 * @return array
 *     Erreurs du formulaire
 */
function formulaires_mot_de_passe_verifier_dist($id_auteur=null, $jeton=null){
	$erreurs = array();
	if (!_request('oubli'))
		$erreurs['oubli'] = _T('info_obligatoire');
	else if (strlen($p=_request('oubli')) < _PASS_LONGUEUR_MINI)
		$erreurs['oubli'] = _T('info_passe_trop_court_car_pluriel',array('nb'=>_PASS_LONGUEUR_MINI));
	else {
		if (!is_null($c = _request('oubli_confirm'))){
			if (!$c)
				$erreurs['oubli_confirm'] = _T('info_obligatoire');
			elseif ($c!==$p)
				$erreurs['oubli'] = _T('info_passes_identiques');
		}
	}
	if (isset($erreurs['oubli'])){
		set_request('oubli');
		set_request('oubli_confirm');
	}

	return $erreurs;
}

/**
 * Modification du mot de passe d'un auteur.
 * 
 * Utilise le cookie d'oubli fourni en URL ou l'argument du formulaire pour identifier l'auteur
 *
 * @use retrouve_auteur()
 * @use auteur_effacer_jeton()
 * 
 * @param int|null $id_auteur
 *     Identifiant de l'auteur, si connu
 * @param string|null $jeton
 *     Jeton d'oubli de mot de passe permettant de retrouver l'auteur, si connu
 * @return array
 *     Retours du traitement
 */
function formulaires_mot_de_passe_traiter_dist($id_auteur=null, $jeton=null){
	$message = '';

	// compatibilite anciens appels du formulaire
	if (is_null($jeton)) $jeton = _request('p');
	$row = retrouve_auteur($id_auteur,$jeton);

	if ($row
	 && ($id_auteur = $row['id_auteur'])
	 && ($oubli = _request('oubli'))) {
		include_spip('action/editer_auteur');
		include_spip('action/inscrire_auteur');
		auteurs_set($id_auteur, array('pass'=>$oubli));
		auteur_effacer_jeton($id_auteur);

		$login = $row['login'];
		$message = "<b>" . _T('pass_nouveau_enregistre') . "</b>".
		"<br />" . _T('pass_rappel_login', array('login' => $login));
	}
	return array('message_ok'=>$message);
}
?>
