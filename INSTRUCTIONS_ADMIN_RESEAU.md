# Installation Dolibarr Modifié

## Commande d'installation :

```bash
bash -c "$(curl -fsSL https://raw.githubusercontent.com/Pagecran/dolibarr/Pagec/dolibarr_pagec_proxmox.sh)"
```

## Différence :
Installe Dolibarr avec mes modifications personnalisées au lieu de la version officielle.

## Avantages :
- **Installation ET mise à jour** dans un seul script
- **Détection automatique** : installation fraîche ou mise à jour
- **Sauvegarde/restauration** automatique de la configuration
- **Simple** : un seul script pour tout

## Accès :
- URL : http://[IP_SERVEUR]/install/
- Base : dolibarr / dolibarr / Dolibarr2024!

## Mise à jour :
Le script détecte automatiquement s'il s'agit d'une installation ou d'une mise à jour.
Pour mettre à jour, relancer simplement la même commande.

## Remarques importantes :
- Le script configure le mot de passe root et l'utilisateur Dolibarr avec : `Dolibarr2024!`
- Lors de l'installation via l'interface web, saisir ce mot de passe pour root ET pour l'utilisateur Dolibarr.
- Le script doit être lancé en root sur le serveur cible (Proxmox VE Shell ou SSH).
- Le script installe directement sur le système (pas en conteneur).
- La configuration et les documents sont sauvegardés automatiquement lors des mises à jour. 