# Simple Mass Email

Extension EspoCRM qui permet de rédiger un email de masse directement dans la vue de création ou de modification d'un Mass Email, puis de l'envoyer maintenant ou à une date choisie.

## Fonctionnalités

- **Entrée dans la barre latérale** : l'installation ajoute « Nouvel email groupé » juste après « Emails » dans la liste d'onglets du système. Elle ouvre directement le formulaire de création. Le lien n'est affiché qu'aux utilisateurs qui ont accès aux emails groupés (droits du rôle sur les Campagnes).
- **Envoyer maintenant ou planifier** : deux boutons, dans le formulaire comme sur la fiche.
  - « Envoyer maintenant » : l'envoi démarre dans la minute.
  - « Planifier… » : une fenêtre demande la date et l'heure, par défaut le lendemain à 9 h.
  - La fiche affiche « Planifié · date », « Envoi en cours » ou « Envoi échelonné », et propose selon l'état : annuler la planification, mettre en pause ou reprendre.
  - Le statut et la date de début ne se modifient plus à la main.
- **Envoi par un compte personnel, avec limite** : le champ « Compte SMTP » propose aussi les comptes email personnels de l'utilisateur. L'envoi part alors de ce compte, par lots, sans dépasser la limite choisie (45 par défaut) sur 24 heures. Sur 200 contacts avec une limite de 45, l'envoi s'étale sur 5 jours. La fiche de l'email groupé affiche la progression et le prochain envoi possible.
- **Vue unique** en deux colonnes, dans des blocs à fond blanc :
  - à gauche, un bloc avec le nom, le statut (affiché seulement), le SMTP, l'expéditeur (adresse et nom) et la réponse (adresse et nom) sur deux colonnes ; un second bloc avec le choix du template, l'objet et le corps de l'email ;
  - à droite, un bloc « Paramètres » avec la campagne, les listes cibles, les listes exclues et les options d'envoi, puis les blocs « Email de test » et « Placeholders ».
- **Template existant** : le champ « Email Template » permet de charger un template existant, dont l'objet et le corps sont repris dans le formulaire. Un template partagé n'est jamais modifié : si l'objet ou le corps est changé, la sauvegarde crée une copie privée et la lie au Mass Email.
- **Infobulles** : les icônes d'aide suivent la configuration des champs (`tooltip`) définie dans l'administration, comme dans la vue native.
- **Envoi de test** : la fenêtre native d'EspoCRM permet de choisir les destinataires parmi les utilisateurs, contacts, prospects et comptes. Avec un compte personnel, le test part de ce compte et compte dans sa limite.
- **Brouillon** : un nouveau Mass Email est créé en brouillon ; il ne part que par l'un des deux boutons.
- **Campagne** : un rôle qui ne peut modifier que ses propres campagnes doit en choisir une, et seules les siennes lui sont proposées. Sans campagne, un email groupé n'appartient à personne et EspoCRM en refuse la création à ce rôle (« No 'edit' access »).
- **Édition** : le bouton « Modifier » ouvre la vue d'édition de l'extension, avec l'objet et le corps rechargés.

## Fonctionnement technique

- Le module `SimpleMassEmail` surcharge les vues `MassEmail` natives via `metadata/clientDefs/MassEmail.json` :
  - `recordViews.edit` → `simple-mass-email:views/mass-email/record/edit` (formulaire deux colonnes).
  - `recordViews.detail` → `simple-mass-email:views/mass-email/record/detail` (boutons d'envoi selon l'état, édition inline désactivée).
- L'objet et le corps sont stockés dans un EmailTemplate interne, marqué `oneOff`, assigné à son auteur et lié au Mass Email par `emailTemplateId`. Il est créé à la première sauvegarde puis mis à jour. L'installation assigne à leur auteur ceux créés sans utilisateur assigné avant la 1.2.1.
- Si le Mass Email est lié à un template existant non `oneOff`, ce template n'est jamais modifié : une copie `oneOff` est créée à la sauvegarde seulement si l'objet ou le corps a changé.
- Les styles sont dans `css/simple-mass-email.css`, déclaré via `metadata/app/client.json` → `cssList`.
- Le module est livré en bundle : `lib/init.js` est déclaré dans `metadata/app/client.json` → `scriptList`. Sans lui, EspoCRM en production cherche les vues dans `lib/transpiled/` (absent du ZIP) et `#MassEmail/create` reste sur la page précédente (corrigé en 1.1.0). En mode développeur, `scriptList` n'est pas lu : les vues viennent des sources synchronisées.
- `scripts/AfterInstall.php` ajoute l'entrée de barre latérale à `tabList` (lien `#MassEmail/create`, `aclScope` = `MassEmail`, qui suit les droits sur les Campagnes) : après « Emails », sinon avant le séparateur « Plus », sinon à la fin. Le paramètre `simpleMassEmailTabAdded` mémorise l'ajout, pour qu'une mise à jour ne remette pas un lien que l'administrateur a déplacé ou retiré. `scripts/AfterUninstall.php` retire le lien et ce paramètre.

### Envoi

- Envoyer ou planifier enregistre le statut `Pending`, avec la date choisie ou, pour « maintenant », une date vide. Le hook `SendingStatus` remplace ce statut, à chaque sauvegarde (API comprise) :
  - par `Sending` (« Envoi en cours ») pour le SMTP système ou un compte de groupe ;
  - par `Throttled` (« Envoi échelonné ») pour un compte personnel.
  Une date vide devient l'heure de la sauvegarde.
- Ces deux statuts tiennent l'email groupé à l'écart du job natif `ProcessMassEmail` (qui passe toutes les 20 minutes). Le job `SimpleMassEmailSendPersonal`, planifié chaque minute à l'installation (au lieu de toutes les 10 minutes jusqu'en 1.2), les envoie à leur date. Les deux jobs n'envoient donc jamais le même email groupé. Si l'extension est désinstallée, ces emails restent inertes.
- Le message reste construit par le `SendingProcessor` natif : lien et en-têtes de désinscription, suivi de campagne, journal, limite horaire `massEmailMaxPerHourCount`.
- « Mettre en pause » et « Annuler la planification » repassent l'email en brouillon ; la file déjà créée est conservée, et un nouvel envoi reprend avec les destinataires restants.
- La date de début n'est plus obligatoire, puisque « Envoyer maintenant » la fixe.

### Envoi par un compte personnel

- Le message part avec le SMTP du compte. Le lien de désinscription est imposé même si `massEmailDisableMandatoryOptOutLink` est activé, et les campagnes informatives, qui n'en ont pas, sont refusées.
- La limite s'applique **par compte**, tous emails groupés et tests confondus, sur 24 heures glissantes : elle tient quelle que soit la façon dont le fournisseur compte ses journées. Les envois sont notés dans l'entité interne `SimpleMassEmailSend`, conservée même si l'email groupé est supprimé.
- Avant chaque passage, les contacts désinscrits d'une liste cible depuis la création de la file sont retirés de l'envoi.
- Si le serveur SMTP refuse un même email à chaque tentative (3 par défaut), ou si le compte n'est plus utilisable, l'envoi passe en « Échec » et le propriétaire du compte reçoit une notification. « Reprendre l'envoi » repart avec les contacts restants, sans renvoi aux contacts déjà servis.
- **Droits** : seul le propriétaire du compte peut l'utiliser ou modifier l'email groupé qui s'en sert, administrateurs compris. Toute personne ayant le droit de modifier l'email groupé peut l'arrêter en le repassant en brouillon. La mise à jour de masse ignore ces emails groupés.

## Prérequis

- Le cron d'EspoCRM doit tourner chaque minute, comme le recommande EspoCRM : c'est lui qui lance les envois.
- Un SMTP système doit être configuré dans EspoCRM (Administration → Outbound Emails), sauf pour un envoi par compte personnel. Sans SMTP, l'envoi échoue et l'erreur n'est visible que dans les journaux (`data/logs/espo-*.log`).
- Pour un compte personnel : le compte doit être actif, avec son SMTP configuré, et le rôle de l'utilisateur doit autoriser les comptes email personnels.
- La sauvegarde crée ou met à jour un EmailTemplate, assigné à son auteur : les rôles non administrateurs doivent pouvoir créer des EmailTemplate et lire et modifier les leurs. Testé avec un rôle limité à ses propres modèles et un droit d'assignation « tous » ou « équipe ».

## Limites

- Compte personnel : les emails d'un lot partent à la suite, lors d'un même passage du cron, et n'apparaissent pas dans le dossier « Envoyés » de la boîte mail. Les emails écrits à côté ne sont pas décomptés : gardez une marge sous la limite du fournisseur.
- La liste des destinataires est figée au premier envoi. Les contacts ajoutés ensuite aux listes cibles ne sont pas inclus.
- Désinstaller l'extension pendant un envoi le laisse en suspens : les emails groupés « Envoi en cours », « Envoi échelonné » ou planifiés ne partent plus.
- L'entrée de barre latérale n'est ajoutée qu'à la liste d'onglets du système. Les utilisateurs qui ont leur propre liste (Préférences) peuvent l'ajouter eux-mêmes : onglet de type URL vers `#MassEmail/create`.
- Le lien suit le droit de lecture sur les Campagnes. Un utilisateur qui peut lire les campagnes sans pouvoir les modifier voit donc le lien, mais EspoCRM refuse la création.
- Les options absentes du Mass Email natif ne sont pas ajoutées : « une seule fois / ne pas renvoyer aux contacts déjà ciblés », catégorie, utilisateur assigné et équipes.
