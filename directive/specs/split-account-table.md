# Spécification Technique : Découpage de la table Account

## Objectif
Alléger la table `Account` pour résoudre l'erreur MySQL `Row size too large (> 8126)` et améliorer les performances en déplaçant les champs dépréciés et les métadonnées volumineuses vers une entité dédiée.

## Entité Cible : `AccountDetails`
- **Type** : Entité personnalisée EspoCRM.
- **Relation** : `hasOne` (Account -> AccountDetails), `belongsTo` (AccountDetails -> Account).

## Champs à migrer
Tous les champs marqués `[DÉPRÉCIÉ - utiliser Contact]` dans `Account.json` ainsi que les champs de gestion interne volumineux.

### Liste des champs :
- **Dirigeant** : `dirigeantNom`, `dirigeantPrenom`, `dirigeantFonction`, `dirigeantTelephone`, `dirigeantEmail`, `dirigeantNotes`.
- **Secrétaire CSE** : `secretaireNom`, `secretairePrenom`, `secretaireTelDirect`, `secretaireTelPerso`, `secretaireEmailPro`, `secretaireEmailPerso`.
- **Trésorier CSE** : `tresorierNom`, `tresorierPrenom`, `tresorierTelDirect`, `tresorierTelPerso`, `tresorierEmailPro`, `tresorierEmailPerso`.
- **Secrétaire Adjoint** : `secretaireAdjointNom`, `secretaireAdjointPrenom`, `secretaireAdjointTel`, `secretaireAdjointEmail`.
- **Syndicat (Détaillé)** : `responsableSyndicalNom`, `responsableSyndicalPrenom`, `responsableSyndicalFonction`, `syndicatTelDirect`, `syndicatTelPerso`, `syndicatEmailPro`, `syndicatEmailPerso`, `notesSyndicat`.

## Structure de la nouvelle entité (`AccountDetails`)
```json
{
    "fields": {
        "account": { "type": "link" },
        "dirigeantNom": { "type": "text" },
        ...
    },
    "links": {
        "account": { "type": "belongsTo", "entity": "Account" }
    }
}
```

## Plan de Migration
1. **Création** : Définir `AccountDetails` dans `custom/Espo/Custom/Resources/metadata/entityDefs/`.
2. **Migration (Script)** : Développer un script PHP (`ConsoleCommand`) pour :
    - Créer une instance `AccountDetails` pour chaque `Account` existant.
    - Copier les valeurs des champs dépréciés de `Account` vers `AccountDetails`.
    - Sauvegarder les instances.
3. **Suppression** : Une fois la migration validée, retirer les champs de `Account.json` et exécuter `php rebuild.php`.
4. **UI** : Mettre à jour les vues (`views/`) pour afficher les données depuis `AccountDetails` si nécessaire.
