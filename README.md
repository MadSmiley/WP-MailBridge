# MailBridge - WordPress Email Template Manager

**MailBridge** est un plugin WordPress qui centralise la gestion des e-mails transactionnels personnalisés.

## Objectif

Permettre aux développeurs et administrateurs de :

- **Définir** depuis l'interface admin des modèles d'e-mails (nom, objet, contenu, langue, plugin associé)
- **Inclure des variables dynamiques** (ex. `{{nom_client}}`, `{{date_commande}}`) dans le contenu
- **Envoyer ces e-mails depuis n'importe quel plugin ou thème**, via une simple fonction PHP

## Installation

1. Téléchargez le plugin dans `wp-content/plugins/wp-mail-bridge/`
2. Activez le plugin depuis l'admin WordPress
3. Accédez au menu **MailBridge** dans l'admin

## Utilisation pour les Administrateurs

### Gestion des Templates

1. **Aller dans MailBridge > Templates**
2. **Créer un nouveau template** :
   - Nom du template : "Email de bienvenue"
   - Slug : `welcome_email` (utilisé dans le code)
   - Sujet : `Bienvenue {{user_name}} !`
   - Contenu : HTML avec variables `{{variable_name}}`
   - Langue : Sélectionnez la langue
   - Status : Actif/Inactif

### Variables Disponibles

Dans vos templates, vous pouvez utiliser :

- Variables personnalisées définies par les développeurs
- Variables système automatiques :
  - `{{site_name}}` - Nom du site
  - `{{site_url}}` - URL du site
  - `{{site_description}}` - Description du site
  - `{{current_date}}` - Date actuelle
  - `{{current_time}}` - Heure actuelle

### Types d'Email

La page **MailBridge > Email Types** affiche tous les types d'emails enregistrés par les plugins et thèmes, avec leurs variables disponibles.

### Logs

Consultez l'historique de tous les emails envoyés dans **MailBridge > Logs**.

## Utilisation pour les Développeurs

### Envoyer un Email

```php
// Exemple simple
mailbridge_send('welcome_email', array(
    'to' => 'user@example.com',
    'user_name' => 'Jean Dupont',
    'user_email' => 'user@example.com',
));

// Avec spécification de langue
mailbridge_send('welcome_email', array(
    'user_name' => 'Jean Dupont',
    'user_email' => 'user@example.com',
), 'user@example.com', 'fr');
```

### Enregistrer un Type d'Email

Permettez aux administrateurs de personnaliser vos emails :

```php
add_action('mailbridge_register_email_types', function() {
    mailbridge_register_email_type('order_confirmation', array(
        'name' => 'Confirmation de commande',
        'description' => 'Email envoyé après une commande',
        'variables' => array(
            'customer_name' => 'Nom du client',
            'order_number' => 'Numéro de commande',
            'order_date' => 'Date de commande',
            'order_total' => 'Montant total',
            'order_items' => 'Liste des articles',
        ),
        'plugin' => 'Mon Plugin E-commerce',
        'default_subject' => 'Commande #{{order_number}} confirmée',
        'default_content' => '
            <h1>Merci {{customer_name}} !</h1>
            <p>Votre commande #{{order_number}} a été confirmée.</p>
            <p>Date : {{order_date}}</p>
            <p>Total : {{order_total}}</p>
            <div>{{order_items}}</div>
        ',
    ));
});
```

### Fonctions API Disponibles

#### `mailbridge_send($template_slug, $variables, $to, $language)`

Envoie un email en utilisant un template.

**Paramètres :**
- `$template_slug` (string) : Identifiant du template
- `$variables` (array) : Variables à remplacer
- `$to` (string, optionnel) : Email du destinataire (peut être dans `$variables['to']`)
- `$language` (string, optionnel) : Code langue (par défaut : langue du site)

**Retour :** `true` en cas de succès, `false` en cas d'échec

#### `mailbridge_register_email_type($id, $args)`

Enregistre un type d'email.

**Paramètres :**
- `$id` (string) : Identifiant unique
- `$args` (array) :
  - `name` : Nom d'affichage
  - `description` : Description
  - `variables` : Tableau des variables disponibles
  - `plugin` : Nom du plugin/module
  - `default_subject` : Sujet par défaut
  - `default_content` : Contenu par défaut

#### `mailbridge_get_email_types()`

Récupère tous les types d'emails enregistrés.

**Retour :** Array des types d'emails

## Exemple Complet

### Dans votre plugin

```php
// Enregistrer le type d'email
add_action('mailbridge_register_email_types', function() {
    mailbridge_register_email_type('membership_renewal', array(
        'name' => 'Renouvellement d\'adhésion',
        'description' => 'Email de rappel de renouvellement',
        'variables' => array(
            'member_name' => 'Nom du membre',
            'expiry_date' => 'Date d\'expiration',
            'renewal_url' => 'URL de renouvellement',
        ),
        'plugin' => 'Mon Plugin Adhésions',
        'default_subject' => 'Votre adhésion expire bientôt',
        'default_content' => '<p>Bonjour {{member_name}},</p><p>Votre adhésion expire le {{expiry_date}}.</p><p><a href="{{renewal_url}}">Renouveler maintenant</a></p>',
    ));
});

// Envoyer l'email
function send_renewal_reminder($member) {
    mailbridge_send('membership_renewal', array(
        'to' => $member->email,
        'member_name' => $member->name,
        'expiry_date' => date('d/m/Y', strtotime($member->expiry_date)),
        'renewal_url' => home_url('/renew?member=' . $member->id),
    ));
}
```

## Structure de la Base de Données

### Table `wp_mailbridge_templates`

Stocke les templates d'emails créés par les administrateurs.

### Table `wp_mailbridge_email_types`

Registre des types d'emails déclarés par les plugins.

### Table `wp_mailbridge_logs`

Historique des emails envoyés.

## Fonctionnalités

- ✅ Interface d'administration complète
- ✅ Éditeur WYSIWYG pour le contenu
- ✅ Système de variables dynamiques
- ✅ Support multilingue
- ✅ Logging des emails envoyés
- ✅ API simple pour développeurs
- ✅ Registre des types d'emails
- ✅ Templates par défaut
- ✅ Validation des variables

## Support et Contribution

- **Auteur** : MadSmiley
- **GitHub** : [https://github.com/MadSmiley/WP-MailBridge](https://github.com/MadSmiley/WP-MailBridge)
- **LinkedIn** : [https://www.linkedin.com/in/germain-belacel/](https://www.linkedin.com/in/germain-belacel/)

## Licence

GPL v2 or later

## Changelog

### Version 1.0.0
- Version initiale
- Gestion des templates d'emails
- Système de variables
- Support multilingue
- API développeur
- Logging des emails
