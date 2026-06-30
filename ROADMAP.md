# Roadmap

## V1 - Coeur autonome minimal

Objectif : disposer d'un plugin WordPress autonome qui couvre le socle métier
évènement sans dépendance externe.

Axes envisagés :

- modèle évènement ;
- modèle occurrence ;
- modèle lieu ;
- statut temporel ;
- rendu public minimal ;
- base QR Code ;
- base ICS ;
- administration minimale ;
- validation des frontières avec WP Seed Content Kit.

V1 ne doit pas inclure de moteur de templates avancé, de logique Divi avancée ou
de constructeur d'affichage.

## V2 - Enrichissement métier

Objectif : renforcer la qualité du domaine évènement après stabilisation du socle.

Axes possibles :

- occurrences plus riches ;
- gestion plus fine des lieux ;
- médias mieux structurés ;
- ICS plus complet ;
- QR Code plus exploitable ;
- règles de statut temporel plus précises ;
- premières passerelles optionnelles avec WP Seed Content Kit si elles sont utiles.

Les intégrations devront rester optionnelles.

## V3 - Extensions contrôlées

Objectif : ouvrir le projet à des usages plus avancés sans casser l'autonomie du
coeur.

Axes possibles :

- intégrations optionnelles plus nombreuses ;
- outils d'import ou d'export ;
- personnalisation encadrée du rendu ;
- compatibilité avec des workflows éditoriaux plus complexes ;
- éventuels connecteurs vers des systèmes tiers.

V3 ne doit pas transformer le plugin en builder généraliste.

## Supervision

Chaque version doit commencer par une note de cadrage et se terminer par une
validation humaine.

Avant toute implémentation :

- vérifier le périmètre ;
- documenter les décisions ;
- identifier les dérives possibles ;
- confirmer les fichiers à créer ;
- refuser les dépendances non justifiées.

Les commits, tags et publications doivent rester des actions validées
explicitement.

