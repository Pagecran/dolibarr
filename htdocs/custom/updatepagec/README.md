# Module de mise à jour Pagecran pour Dolibarr

## Description
Ce module permet de lancer les mises à jour de Dolibarr depuis l'interface web en utilisant le script personnalisé Pagecran.

## Installation

1. Copier le dossier `updatepagec` dans `htdocs/custom/`
2. Aller dans **Configuration > Modules/Applications**
3. Chercher "UpdatePagec" et l'activer
4. Aller dans **Configuration > Mise à jour Pagecran**

## Utilisation

1. Aller dans **Configuration > Mise à jour Pagecran**
2. Cliquer sur "Lancer la mise à jour"
3. Consulter les logs pour suivre le progrès

## Sécurité

- Seuls les administrateurs peuvent utiliser ce module
- Toutes les actions sont loggées
- Vérifications préalables avant lancement

## Logs

Les logs sont stockés dans `DOL_DATA_ROOT/updatepagec.log`

## Support

Pour toute question, contacter l'équipe Pagecran.
