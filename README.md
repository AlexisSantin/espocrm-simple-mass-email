# Simple Mass Email

Extension EspoCRM qui permet de rédiger et d'envoyer un email de masse directement dans la vue de création ou de modification d'un Mass Email.

## Fonctionnalités

- **Entrée dans la barre latérale** : l'installation ajoute « Nouvel email groupé » juste après « Emails » dans la liste d'onglets du système. Elle ouvre directement le formulaire de création. Le lien n'est affiché qu'aux utilisateurs qui ont accès aux emails groupés (droits du rôle sur les Campagnes).
- **Vue unique** en deux colonnes, dans des blocs à fond blanc :
  - à gauche, un bloc avec le nom, le statut, le SMTP, l'expéditeur (adresse et nom) et la réponse (adresse et nom) sur deux colonnes ; un second bloc avec le choix du template, l'objet et le corps de l'email ;
  - à droite, un bloc « Parameters » avec la campagne, les listes cibles, les listes exclues, la date de début et les options d'envoi, puis les blocs « Test email » et « Placeholders ».
- **Template existant** : le champ « Email Template » permet de charger un template existant, dont l'objet et le corps sont repris dans le formulaire. Un template partagé n'est jamais modifié : si l'objet ou le corps est changé, la sauvegarde crée une copie privée et la lie au Mass Email.
- **Infobulles** : les icônes d'aide suivent la configuration des champs (`tooltip`) définie dans l'administration, comme dans la vue native.
- **Envoi de test** : le bouton « Send test email » envoie le test à l'adresse de l'utilisateur connecté.
- **Statut Draft** : un nouveau Mass Email est créé en brouillon, pour ne pas être envoyé par le cron avant validation.
- **Édition** : le bouton « Modifier » ouvre la vue d'édition de l'extension, avec l'objet et le corps rechargés.

## Fonctionnement technique

- Le module `SimpleMassEmail` surcharge les vues `MassEmail` natives via `metadata/clientDefs/MassEmail.json` :
  - `recordViews.edit` → `simple-mass-email:views/mass-email/record/edit` (formulaire deux colonnes).
  - `recordViews.detail` → `simple-mass-email:views/mass-email/record/detail` (désactive l'édition inline, pour passer par la vue d'édition).
- L'objet et le corps sont stockés dans un EmailTemplate interne, marqué `oneOff`, lié au Mass Email par `emailTemplateId`. Il est créé à la première sauvegarde puis mis à jour.
- Si le Mass Email est lié à un template existant non `oneOff`, ce template n'est jamais modifié : une copie `oneOff` est créée à la sauvegarde seulement si l'objet ou le corps a changé.
- Les styles sont dans `css/simple-mass-email.css`, déclaré via `metadata/app/client.json` → `cssList`.
- Le module est livré en bundle : `lib/init.js` est déclaré dans `metadata/app/client.json` → `scriptList`. Sans lui, EspoCRM en production cherche les vues dans `lib/transpiled/` (absent du ZIP) et `#MassEmail/create` reste sur la page précédente (corrigé en 1.1.0). En mode développeur, `scriptList` n'est pas lu : les vues viennent des sources synchronisées.
- `scripts/AfterInstall.php` ajoute l'entrée de barre latérale à `tabList` (lien `#MassEmail/create`, `aclScope` = `MassEmail`, qui suit les droits sur les Campagnes) : après « Emails », sinon avant le séparateur « Plus », sinon à la fin. Le paramètre `simpleMassEmailTabAdded` mémorise l'ajout, pour qu'une mise à jour ne remette pas un lien que l'administrateur a déplacé ou retiré. `scripts/AfterUninstall.php` retire le lien et ce paramètre.

## Prérequis

- Un SMTP système doit être configuré dans EspoCRM (Administration → Outbound Emails). Sans SMTP, l'envoi échoue et l'erreur n'est visible que dans les journaux (`data/logs/espo-*.log`).
- La sauvegarde crée ou met à jour un EmailTemplate : les rôles non administrateurs doivent donc aussi avoir les droits sur les EmailTemplate. Ce point n'a pas encore été testé avec un rôle restreint.
- L'envoi effectif est assuré par le cron `ProcessMassEmail`.

## Limites

- L'entrée de barre latérale n'est ajoutée qu'à la liste d'onglets du système. Les utilisateurs qui ont leur propre liste (Préférences) peuvent l'ajouter eux-mêmes : onglet de type URL vers `#MassEmail/create`.
- Le lien suit le droit de lecture sur les Campagnes. Un utilisateur qui peut lire les campagnes sans pouvoir les modifier voit donc le lien, mais EspoCRM refuse la création.

- Le message « Envoi du test lancé » confirme seulement que la demande a été acceptée par le serveur, pas que l'email est parti. Vérifier la file d'attente ou les journaux.
- Les options absentes du Mass Email natif ne sont pas ajoutées : « une seule fois / ne pas renvoyer aux contacts déjà ciblés », catégorie, utilisateur assigné et équipes.

