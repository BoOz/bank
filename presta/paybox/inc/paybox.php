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

/**
 * @param string $boutique
 *   string : abo ou ''
 * @return array
 */
function paybox_pbx_ids($boutique=''){
	$boutique = $boutique ? "_".$boutique:"";
	$config = 'config'.$boutique.'_paybox';

	include_spip('inc/config');
	$v = lire_config("bank_paiement/$config");
	if (isset($v['pubkey']))
		unset($v['pubkey']);
	return $v;
}

function paybox_shell_args($params){
	return bank_shell_args($params);
}

/**
 * Generer les hidden du formulaire d'envoi a paybox
 * selon methode cle secrete + hash ou binaire executable
 *
 * @param $params
 * @return array|string
 */
function paybox_form_hidden($params){

	if (isset($params['PBX_HMAC_KEY']) AND !trim($params['PBX_HMAC_KEY']))
		unset($params['PBX_HMAC_KEY']);

	// methode hash avec cle secrete partagee fournie
	if (isset($params['PBX_HMAC_KEY'])){
		$key = trim($params['PBX_HMAC_KEY']);
		unset($params['PBX_HMAC_KEY']);

		if (isset($params['DIRECT_PLUS_CLE']))
			unset($params['DIRECT_PLUS_CLE']);

		if (!isset($params['PBX_TIME']))
			$params['PBX_TIME'] = date("c");

		// On calcule l?empreinte (a renseigner dans le parametre PBX_HMAC) grace � la fonction hash_hmac et
		// la cle binaire
		// On envoie via la variable PBX_HASH l'algorithme de hachage qui a ete utilise (SHA512 dans ce cas)
		// Pour afficher la liste des algorithmes disponibles sur votre environnement, decommentez la ligne
		// suivante
		// print_r(hash_algos());
		$hash_method = "sha512";
		if (function_exists("hash_algos")){
			$algos = hash_algos();
			foreach(array("sha512","sha256","sha1","md5") as $method){
				if (in_array($method,$algos)){
					$hash_method = $method;
					break;
				}
			}
		}
		$params['PBX_HASH'] = strtoupper($hash_method);


		// On cree la chaine a hacher sans URLencodage
		$message = array();
		$params_att = array();
		foreach($params as $k=>$v){
			if (strncmp($k,"PBX_",4)==0){
				$params_att[$k] = str_replace('"','&#034;', $v);
				$message[] = "$k=".$params_att[$k];
			}
		}
		$message = implode("&",$message);

		// la cle secrete HMAC
		// Si la cle est en ASCII, On la transforme en binaire
		$binKey = pack("H*", $key);

		// La cha�ne sera envoy�e en majuscules, d'o� l'utilisation de strtoupper()
		$hmac = strtoupper(hash_hmac($hash_method, $message, $binKey));

		$params_att['PBX_HMAC'] = $hmac;

		// On cree les hidden du formulaire a envoyer a Paybox System
		// ATTENTION : l'ordre des champs est extremement important, il doit
		// correspondre exactement a l'ordre des champs dans la chaine hachee
		$hidden = array();
		foreach($params_att as $k=>$v){
			$hidden[] = "<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
		}
		$hidden = implode("\n",$hidden);
		return $hidden;
	}

	// sinon methode encodage par binaire
	// depreciee mais permet transition en douceur et iso-fonctionnelle
	$paybox_exec_request = charger_fonction("exec_request","presta/paybox");
	$hidden = $paybox_exec_request($params);
	// modulev2.cgi injecte une balise Form dont on ne veut pas ici
	$hidden = preg_replace(",<form[^>]*>,Uims","",$hidden);
	return $hidden;
}


function paybox_response($response = 'response'){

	$url = parse_url($_SERVER['REQUEST_URI']);
	if (function_exists('openssl_pkey_get_public')){
		// recuperer la signature
		$sign = _request('sign');
		$sign = base64_decode($sign);
		// recuperer les variables
		$vars = $url['query'];
		// enlever la signature des data
		$vars = preg_replace(',&sign=.*$,','',$vars);

		// une variante sans &action=...
		// car l'autoresponse ne la prend pas en compte, mais la response directe la prend en compte
		// $vars1 = preg_replace(',^[^?]*?page=[^&]*&,','',$vars);
		$vars1 = preg_replace(',^[^?]*?action=[^&]*&,','',$vars);
		$vars1 = preg_replace(',^[^?]*?bankp=[^&]*&,','',$vars1);

		// recuperer la cle publique Paybox
		// on utilise find_in_path pour permettre surcharge au cas ou la cle changerait
		$pubkey = "";
		if ($keyfile = find_in_path("presta/paybox/inc/pubkey.pem")){
			lire_fichier($keyfile,$pubkey);
		}
		// verifier la signature avec $vars ou $vars1
		if (!$pubkey
		  OR !$cle = openssl_pkey_get_public($pubkey)
			OR !(openssl_verify( $vars, $sign, $cle ) OR openssl_verify($vars1, $sign, $cle))
		){

			spip_log('call_response : signature invalide: '.var_export($url,true),'paybox');
			return false;
		}
	}
	else {
		if (!_request('sign')){
			spip_log('call_response : reponse sans signature: '.var_export($url,true),'paybox');
			return false;
		}
		// on ne sait pas verifier la signature, on fait comme si elle etait OK (hum)
	}

	parse_str($url['query'],$response);
	unset($response['page']);
	unset($response['sign']);
	
	return $response;
}

function paybox_traite_reponse_transaction($response,$mode = 'paybox') {
	$id_transaction = $response['id_transaction'];
	if (!$row = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction))){
		spip_log($t = "call_response : id_transaction $id_transaction inconnu:".paybox_shell_args($response),$mode);
		// on log ca dans un journal dedie
		spip_log($t,$mode . "_douteux");
		// on mail le webmestre
		$envoyer_mail = charger_fonction('envoyer_mail','inc');
		$envoyer_mail($GLOBALS['meta']['email_webmaster'],"[$mode]Transaction Frauduleuse",$t,"$mode@".$_SERVER['HTTP_HOST']);
		$message = "Une erreur est survenue, les donn&eacute;es re&ccedil;ues de la banque ne sont pas conformes. ";
		$message .= "Votre r&egrave;glement n'a pas &eacute;t&eacute; pris en compte (Ref : $id_transaction)";
		$set = array(
			"mode" => $mode,
			"message"=>$message,
			'statut'=>'echec'
		);
		sql_updateq("spip_transactions",$set,"id_transaction=".intval($id_transaction));
		return array($id_transaction,false);
	}
	// ok, on traite le reglement
	$date=time();
	$date_paiement = sql_format_date(
	date('Y',$date), //annee
	date('m',$date), //mois
	date('d',$date), //jour
	date('H',$date), //Heures
	date('i',$date), //min
	date('s',$date) //sec
	);

	$erreur = paybox_response_code($response['erreur']);
	$authorisation_id = $response['auth'];
	$transaction = $response['trans'];

	if (!$transaction
	 OR !$authorisation_id
//	 OR $authorisation_id=='XXXXXX'
	 OR $erreur!==true){
	 	// regarder si l'annulation n'arrive pas apres un reglement (internaute qui a ouvert 2 fenetres de paiement)
	 	if ($row['reglee']=='oui') return array($id_transaction,true);
	 	// sinon enregistrer l'absence de paiement et l'erreur
		spip_log($t="call_response : transaction $id_transaction refusee :[$erreur]:".paybox_shell_args($response),$mode);
		$set = array(
			"mode" => $mode,
			"statut" => 'echec['.$response['erreur'].']',
			'date_paiement' => $date_paiement
		);
		sql_updateq("spip_transactions",$set,"id_transaction=".intval($id_transaction));
		if ($response['erreur']==3 OR $response['erreur']==6){
			// Erreur paybox, avertir le webmestre
			$envoyer_mail = charger_fonction('envoyer_mail','inc');
			$envoyer_mail($GLOBALS['meta']['email_webmaster'],"[$mode]Transaction Impossible",$t,"$mode@".$_SERVER['HTTP_HOST']);
		}
		$message = "Aucun r&egrave;glement n'a &eacute;t&eacute; r&eacute;alis&eacute;".($erreur===true?"":" ($erreur)");
		sql_updateq("spip_transactions",array("message"=>$message),"id_transaction=".intval($id_transaction));
		return array($id_transaction,false);
	}

	// Ouf, le reglement a ete accepte

	// on verifie que le montant est bon !
	$montant_regle = $response['montant']/100;
	if ($montant_regle!=$row['montant']){
		spip_log($t = "call_response : id_transaction $id_transaction, montant regle $montant_regle!=".$row['montant'].":".paybox_shell_args($response),$mode);
		// on log ca dans un journal dedie
		spip_log($t,$mode . '_reglements_partiels');
	}

	$set = array(
			"autorisation_id"=>"$transaction/$authorisation_id",
			"mode"=>$mode,
			"montant_regle"=>$montant_regle,
			"date_paiement"=>$date_paiement,
			"statut"=>'ok',
			"reglee"=>'oui');

	// si on a envoye un U il faut recuperer les donnees CB et les stocker sur le compte client
	$ppps = "";
	if (isset($response['ppps'])){
		$set['refcb'] = $response['ppps'];
	}

	// si abonnement, stocker les 2 infos importantes : uid et validite
	if (isset($response['abo']) AND $response['abo']){
		$set['abo_uid'] = $response['abo'];
		if (isset($response['valid']) AND $response['valid']){
			$set['validite'] = "20" . substr($response['valid'],0,2) . "-" . substr($response['valid'],2,2);
		}
	}

	// il faudrait stocker le $transaction aussi pour d'eventuels retour vers paybox ?
	sql_updateq("spip_transactions",$set,"id_transaction=".intval($id_transaction));
	spip_log("call_response : id_transaction $id_transaction, reglee",$mode);

	$regler_transaction = charger_fonction('regler_transaction','bank');
	$regler_transaction($id_transaction,"",$row);
	return array($id_transaction,true);
}

function paybox_response_code($code){
	if ($code==0) return true;
	$pre = "";
	if ($code > 100 AND $code <199)
		$pre = 'Autorisation refusee : ';
	$codes = array(
	1 => 'La connexion au centre d\'autorisation a echoue',
	3 => 'Erreur Paybox',
	4 => 'Numero de porteur ou cryptogramme visuel invalide',
	6 => 'Acces refuse ou site/rang/identifiant invalide',
	8 => 'Date de fin de validite incorrecte',
	9 => 'Erreur verification comportementale',
	10 => 'Devise inconnue',
	11 => 'Montant incorrect',
	15 => 'Paiement deja effectue',
	16 => 'Abonne deja existant',
	21 => 'Carte non autorisee',
	29 => 'Carte non conforme',
	30 => 'Temps d\'attente superieur a 15min',
	);
	if (isset($codes[intval($code)]))
		return $pre.$codes[intval($code)];
	return $pre ? $pre : false;
}

?>
