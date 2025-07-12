# Module UpdatePagec

Module Dolibarr pour lancer les mises à jour Pagecran depuis l'interface web.

## Description

Ce module permet de lancer le script de mise à jour `dolibarr_pagec_proxmox.sh` directement depuis l'interface d'administration de Dolibarr.

## Installation

1. Copier le dossier `updatepagec` dans `htdocs/custom/`
2. Activer le module dans Dolibarr : Configuration > Modules/Applications
3. Aller dans Configuration > UpdatePagec pour lancer les mises à jour

## Utilisation

- Accéder à Configuration > UpdatePagec
- Cliquer sur "Lancer la mise à jour"
- Le script `dolibarr_pagec_proxmox.sh` sera exécuté
- Les résultats s'affichent dans l'interface

## Fichiers

- `core/modules/modUpdatePagec.class.php` - Classe principale du module
- `admin/update.php` - Interface d'administration
- `langs/fr_FR/updatepagec.lang` - Traductions françaises

## Version

1.0 - Version simplifiée 