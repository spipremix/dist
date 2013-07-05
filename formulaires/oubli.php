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
 * Gestion du formulaire de récupération de mot de passe oublié
 *
 * @package SPIP\Dist\Formulaires
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Chargement des valeurs par défaut du formulaire de récupération de mot de passe oublié
 *
 * @return array
 *     Environnement du formulaire
**/
function formulaires_oubli_charger_dist(){
	$valeurs = array('oubli'=>'','nobot'=>'');
	return $valeurs;
}

/**
 * Envoie un email contenant une URL (avec un jeton) pour réinitialiser le mot de passe
 *
 * Le contenu du mail est déterminé par le modèle `mail_oubli`.
 * 
 * @use formulaires_oubli_mail()
 * @use auteur_attribuer_jeton()
 * @use notifications_envoyer_mails()
 *
 * @param string $email
 *     Email de l'auteur souhaitant réinitialiser son mot de passe
 * @param string $param
 *     Nom du paramètre pour l'URL qui contiendra le jeton
 * @return string
 *     Message indiquant que le mail a été envoyé (on non)
**/
function message_oubli($email, $param)
{
	$r = formulaires_oubli_mail($email);
	if (is_array($r) AND $r[1]) {
		include_spip('inc/texte'); # pour corriger_typo

		include_spip('action/inscrire_auteur');
		$cookie = auteur_attribuer_jeton($r[1]['id_auteur']);

		$msg = recuperer_fond(
			"modeles/mail_oubli",
			array(
				'url_reset'=>generer_url_public('spip_pass',"$param=$cookie", true, false)
			)
		);
		include_spip("inc/notifications");
		notifications_envoyer_mails($email, $msg);
	  return _T('pass_recevoir_mail');
	}
	return  _T('pass_erreur_probleme_technique');
}

/**
 * Traitements du formulaire de récupération de mot de passe oublié
 *
 * Génère et envoie l'email pour réinitialiser son mot de passe.
 * 
 * @use message_oubli()
 * 
 * @return array
 *     Retours des traitements
**/
function formulaires_oubli_traiter_dist(){

	$message = message_oubli(_request('oubli'),'p');
	return array('message_ok'=>$message);
}


/**
 * Teste que l'email indiqué lorsqu'on réinitialise son mot de passe
 * est correct.
 *
 * Cette fonction vérifie simplement la conformité de l'adresse mail,
 * pas que l'email appartient bien à un auteur.
 *
 * La fonction peut être redefinie pour filtrer les adresses mail
 *
 * @param string $email
 *     Adresse email
 * @return array|string
 *     - string : message d'erreur
 *     - array : tableau 
**/
function test_oubli_dist($email)
{
	include_spip('inc/filtres'); # pour email_valide()
	if (!email_valide($email) )
		return _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($email)));
	return array('mail' => $email);
}

/**
 * Vérifications du formulaire de récupération de mot de passe oublié
 *
 * Teste la validité du mail et sa présence dans les auteurs de SPIP
 *
 * Un auteur à la poubelle ou qui n'a pas de mot de passe n'a pas
 * l'autorisation de générer un nouveau mot de passe.
 *
 * @use formulaires_oubli_mail()
 * 
 * @return array
 *     Tableau des erreurs
**/
function formulaires_oubli_verifier_dist(){
	$erreurs = array();

	$email = strval(_request('oubli'));

	$r = formulaires_oubli_mail($email);

	if (!is_array($r))
		$erreurs['oubli'] = $r;
	else {
		if (!$r[1])
			$erreurs['oubli'] = _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($email)));

		elseif ($r[1]['statut'] == '5poubelle' OR $r[1]['pass'] == '')
			$erreurs['oubli'] =  _T('pass_erreur_acces_refuse');
	}

	if (_request('nobot'))
		$erreurs['message_erreur'] = _T('pass_rien_a_faire_ici');

	return $erreurs;
}

/**
 * Teste la validité de l'adresse email fournie lors d'un oubli de mot de passe,
 * en vérifiant sa conformité et en retrouvant l'auteur concerné.
 *
 * @use test_oubli_dist()
 * 
 * @param string $email
 *     Adresse email
 * @return string|array
 *     - string : Message d'erreur
 *     - array : Liste
 *       - 0 : Tableau des données connues : array('mail' => email)
 *       - 1 : Tableau des données de l'auteur concerné par l'email, sinon false.
**/
function formulaires_oubli_mail($email)
{
	if (function_exists('test_oubli'))
		$f = 'test_oubli';
	else
		$f = 'test_oubli_dist';
	$declaration = $f($email);

	if (!is_array($declaration))
		return $declaration;
	else {
		include_spip('base/abstract_sql');
		return array($declaration, sql_fetsel("id_auteur,statut,pass", "spip_auteurs", "email =" . sql_quote($declaration['mail'])));
	}
}
?>
