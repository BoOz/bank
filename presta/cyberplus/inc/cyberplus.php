<?php
/*
 * Paiement Bancaire
 * module de paiement bancaire multi prestataires
 * stockage des transactions
 *
 * Auteurs :
 * Cedric Morin, Nursit.com
 * (c) 2012 - Distribue sous licence GNU/GPL
 *
 */
if (!defined('_ECRIRE_INC_VERSION')) return;

function cyberplus_site_id(){
	return _CYBERPLUS_SITE_ID;
}

/**
 * Generer les hidden du form en signant les parametres au prealable
 * @param array $parms
 * @return string
 */
function cyberplus_form_hidden($parms){
	$parms['signature'] = cybperplus_signe_contexte($parms,_CYBERPLUS_CLE);
	$hidden = "";
	foreach($parms as $k=>$v){
		$hidden .= "<input type='hidden' name='$k' value='".str_replace("'", "&#39;", $v)."' />";
	}

	return $hidden;
}


/**
 * Signer le contexte en SHA, avec une cle secrete $key
 * @param array $contexte
 * @param string $key
 * @return string
 */
function cybperplus_signe_contexte($contexte,$key) {

	// on ne prend que les infos vads_*
	// a signer
	$sign = array();
	foreach($contexte as $k=>$v) {
		if (strncmp($k,'vads_',5)==0)
			$sign[$k] = $v;
	}
	// tri des parametres par ordre alphab�tique
	ksort($sign);
	$contenu_signature = implode("+",$sign);
	$contenu_signature .= "+$key";

	$s = sha1($contenu_signature);
	return $s;
}


/**
 * Verifier la signature de la reponse Cyberplus
 * @param $values
 * @param $key
 * @return bool
 */
function cybperplus_verifie_signature($values,$key) {
	$signature = cybperplus_signe_contexte($values,$key);

	if(isset($values['signature'])
		AND ($values['signature'] == $signature))	{

		return true;
	}

	return false;
}
