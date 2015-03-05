# Paiement bancaire pour SPIP

Ce plugin permet de g�rer les interfaces techniques avec les prestataires bancaires.

Une table des transactions permet de conserver un historique et l'etat de chaque paiement ou demande de paiement.
Le plugin ne fournit pas un processus de paiement complet c�t� front, il ne prend en charge que la partie technique et est utilis� par d'autres plugins comme

* le plugin Souscriptions pour la gestion de dons et adh�sions https://github.com/otetard/spip_souscription
* le plugin Paiement avec formidable http://plugins.spip.net/formidablepaiement
* le plugin Commandes http://plugins.spip.net/commandes

Il peut aussi �tre compl�t� par
* le plugin factures https://github.com/nursit/factures


## Prestataires pris en charge

### Paiements � l'acte

Le plugin permet le paiement � l'acte via les plateformes techniques suivantes :

* CMCIC (C.I.C, Cr�dit Mutuel, O.B.C.)
* Internet+ (prise en charge full PHP sans serveur Tomcat, mais sans support du service technique Internet+)
* Ogone
* Paybox
* Paypal (par formulaire simple ou Paypal Express)
* SIPS (Elysnet, Mercanet, Scellius Sogenactif)
* SystemPay (Banque Populaire CyberPlus, PayZen)

Par ailleurs, il est aussi possible d'utiliser les modes de paiement suivant :

* Ch�que
* Virement

Un mode de paiement "Simulation" permet de tester le workflow de paiement sans prestataire bancaire dans la phase de d�veloppement. Il utilise tout les m�me processus que le paiement par un prestataire en by-passant simplement celui-ci.

### Paiements r�currents

Le plugin permet aussi les paiements mensuels avec les plateformes techniques suivantes :

* Internet+
* Paybox

Un mode de paiement "Simulation" permet de tester le workflow de paiement pendant la phase de developpement.