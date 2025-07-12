## **Solutions officielles Microsoft**

### 1. **SMTP AUTH avec authentification moderne**
Microsoft recommande d'utiliser **OAuth2** au lieu de l'authentification par mot de passe simple pour les applications tierces.

### 2. **Configuration SMTP avec authentification moderne**
- Utiliser un **mot de passe d'application** (si double authentification activée)
- Ou configurer **OAuth2** pour l'authentification

### 3. **Relais SMTP authentifié**
Microsoft permet de configurer des relais SMTP pour les applications tierces.

---

## **Pourquoi ça ne marche pas actuellement**

Le problème vient probablement de :
- **Authentification par mot de passe simple** (Microsoft limite cela pour les applications tierces)
- **Absence de mot de passe d'application** (si double authentification activée)
- **IP d'envoi différente** de celle de Microsoft (ton poste vs serveurs Microsoft)

---

## **Solutions à tester**

### **Solution 1 : Mot de passe d'application**
1. Va sur https://account.microsoft.com/security
2. Active la double authentification si pas déjà fait
3. Génère un "mot de passe d'application" pour Dolibarr
4. Utilise ce mot de passe dans la configuration SMTP de Dolibarr

### **Solution 2 : Relais SMTP Microsoft**
1. Dans l'admin Microsoft 365, va dans "Flux de messagerie" > "Connecteurs"
2. Configure un connecteur SMTP pour autoriser l'envoi depuis ton serveur/IP

### **Solution 3 : Utiliser l'API Microsoft Graph**
- Plus complexe mais solution "officielle" pour les applications tierces