# Architecture

## Option C

WP Seed Events adopte l'Option C : un plugin autonome avec intégration optionnelle
à WP Seed Content Kit.

Cette option protège le domaine évènement contre deux risques :

- dépendre d'un autre plugin pour fonctionner ;
- transformer trop tôt le projet en système d'affichage généraliste.

## Principe directeur

Le plugin doit d'abord être bon dans son métier :

- décrire un évènement ;
- représenter ses dates et occurrences ;
- gérer ses lieux ;
- exposer des informations publiques simples ;
- fournir des sorties utiles comme QR Code et ICS.

Le rendu public minimal doit exister pour garantir l'autonomie du plugin, mais il
ne doit pas devenir un moteur de templates avancé.

## Frontières fonctionnelles

WP Seed Events est responsable de :

- la structure métier des évènements ;
- les occurrences et leur statut temporel ;
- les lieux ;
- les médias rattachés au domaine évènement ;
- les données nécessaires au QR Code ;
- les données nécessaires à l'ICS ;
- un affichage public minimal et stable.

WP Seed Events n'est pas responsable de :

- composer des layouts complexes ;
- piloter Divi ;
- fournir un builder ;
- remplacer WP Seed Content Kit ;
- imposer WP Seed Content Kit.

## Relation avec WP Seed Content Kit

WP Seed Content Kit pourra devenir une extension de confort :

- détection optionnelle ;
- adaptation possible de contenus ;
- passerelles futures si elles sont justifiées.

Cette relation ne devra jamais empêcher WP Seed Events de fonctionner seul.

## Règles anti-dérive

- Aucun code WordPress avant validation du cadrage.
- Aucun fichier `wp-seed-events.php` pendant la phase actuelle.
- Aucune dépendance Composer ou npm sans décision explicite.
- Aucun couplage obligatoire à WP Seed Content Kit.
- Aucun ajout Divi tant que le coeur évènement n'est pas stabilisé.
- Aucun moteur de templates avancé dans le coeur V1.

