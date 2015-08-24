# Plugin Bank v3 pour SPIP <small>Paiement bancaire</small>

Ce plugin permet de g�rer les interfaces techniques avec les prestataires bancaires.

Une table des transactions permet de conserver un historique et l'etat de chaque paiement ou demande de paiement.
Le plugin ne fournit pas un processus de paiement complet c�t� front, il ne prend en charge que la partie technique et est utilis� par d'autres plugins comme

* le plugin Souscriptions pour la gestion de dons et adh�sions https://github.com/otetard/spip_souscription
* le plugin Paiement avec formidable http://plugins.spip.net/formidablepaiement
* le plugin Commandes http://plugins.spip.net/commandes

Il peut aussi �tre compl�t� par

* le plugin factures https://github.com/nursit/factures


**Nouveaut�s de la version 3 du plugin **

* N�cessite SPIP 3.0+, compatible SPIP 3.1
* Refonte de la configuration : 
    * on peut avoir plusieurs modules du m�me prestataire technique avec des param�tres diff�rents
    * possibilit� de configurer l'ordre de pr�sentation des modes de paiement
    * possibilit� de configurer les CB propos�es pour les prestataires par CB qui le permettent (tous sauf SIPS)
    * conervation de la cl� de test et passage en test/production par case a cocher
* Ajout du prestataire PayZen qui supporte le paiement par SEPA pour les paiements uniques et les paiements r�currents
* Evolution de l'API abonnements, prise en charge des r�curences plus complexes (N1 paiements initiaux, N2 paiements suivants)
* Ajout d'un statut "attente" sur les transactions pour le paiement par ch�que, virement et SEPA, et ajout d'une page de retour bank_retour_attente pour le retour sur ces transactions
* Prise en charge de PDT pour paypal




## Prestataires pris en charge

La configuration permet d'ajouter/supprimer/ordonner les prestataires bancaires que l'on souhaite utiliser.
Il est possible d'avoir plusieurs configurations pour le meme prestataire technique.

Le paiement par SEPA est pris en charge via PayZen.

### Paiements � l'acte

Le plugin permet le paiement � l'acte via les plateformes techniques suivantes :

* CMCIC (C.I.C, Cr�dit Mutuel, O.B.C.)
* Internet+ (prise en charge full PHP sans serveur Tomcat, mais sans support du service technique Internet+)
* Ogone
* Paybox
* Paypal (par formulaire simple ou Paypal Express)
* SIPS (Elysnet, Mercanet, Scellius Sogenactif)
* SystemPay (Banque Populaire CyberPlus, O.S.B., SystemPay et SP Plus)
* PayZen

Par ailleurs, il est aussi possible d'utiliser les modes de paiement suivant :

* Ch�que
* Virement

Un mode de paiement "Simulation" permet de tester le workflow de paiement sans prestataire bancaire dans la phase de d�veloppement.
Il utilise tout les m�me processus que le paiement par un prestataire en by-passant simplement celui-ci.

### Paiements r�currents

Le plugin permet aussi les paiements mensuels avec les plateformes techniques suivantes :

* Internet+
* Paybox
* PayZen

Un mode de paiement "Simulation" permet de tester le workflow de paiement pendant la phase de developpement. 

Les documentations (pdf) des diff�rentes plateformes sont centralis�es � cette adresse : http://www.nursit.com/doc_presta_bank .

