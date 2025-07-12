## **1. Qu'est-ce qu'OpenID Connect ?**

### **Avantages par rapport aux autres méthodes**
- **Standard moderne** (basé sur OAuth 2.0)
- **Plus simple** que SAML
- **Support natif** par de nombreux fournisseurs (Azure AD, Google, Keycloak, etc.)
- **Sécurité renforcée** avec JWT tokens
- **Flexibilité** pour différents types d'applications

---

## **2. Configuration OpenID Connect dans Dolibarr**

### **A. Activer le module**
1. Aller dans **Configuration > Modules/Applications**
2. Activer le module **"OpenID Connect"**
3. Aller dans **Configuration > OpenID Connect**

### **B. Configuration de base**
```
Provider : [Azure AD, Google, Keycloak, etc.]
Client ID : [votre-client-id]
Client Secret : [votre-client-secret]
Discovery URL : [URL de découverte du fournisseur]
Redirect URI : https://votre-domaine.com/dolibarr/oidc/callback
```

---

## **3. Configuration avec Azure AD (recommandé)**

### **A. Créer l'application dans Azure AD**
1. Aller sur https://portal.azure.com
2. **Azure Active Directory > Inscriptions d'applications > Nouvelle inscription**
3. Nom : "Dolibarr SSO"
4. URI de redirection : `https://votre-domaine.com/dolibarr/oidc/callback`
5. Noter le **Client ID** et **Tenant ID**

### **B. Configurer les permissions**
- **Autorisations déléguées** : `openid`, `profile`, `email`
- **Autorisations d'application** : si nécessaire

### **C. Configuration dans Dolibarr**
```
Provider : Azure AD
Client ID : [votre-client-id]
Client Secret : [générer dans Azure AD]
Tenant ID : [votre-tenant-id]
Discovery URL : https://login.microsoftonline.com/[tenant-id]/.well-known/openid_configuration
```

---
