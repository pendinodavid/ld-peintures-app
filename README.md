# Localisation Famille

Petite application web pour partager sa position avec sa famille **en toute transparence** :
chaque personne choisit d'activer le partage, voit qui partage, et peut l'arrêter à tout moment.
Rien n'est envoyé tant qu'on n'a pas appuyé sur « Je partage ma position ».

- Carte en temps réel (OpenStreetMap)
- Un simple **code de famille** (un mot secret commun) regroupe les proches
- Distance et « vu il y a X min » pour chacun
- Fonctionne sur téléphone et ordinateur
- **Aucune base de données à installer** : juste du PHP (parfait pour Hostinger)

---

## 1. Mettre l'appli en ligne sur Hostinger

1. Connecte-toi à **Hostinger** → **hPanel** → ton site → **Gestionnaire de fichiers**.
2. Ouvre le dossier **`public_html`** de ton site.
3. Envoie dedans **tout le contenu** du dossier `public_html` de ce projet :
   - `index.html`
   - `api.php`
   - le dossier `data` (avec son `.htaccess`)
4. Vérifie que ton site est bien en **HTTPS** (cadenas 🔒). Le GPS ne marche **que** en HTTPS.
   Sur Hostinger : **hPanel → SSL** → activer le certificat gratuit s'il ne l'est pas déjà.
5. C'est tout. Ouvre ton site (ex. `https://ton-domaine.fr`) sur ton téléphone.

> Le dossier `data/` doit rester **accessible en écriture** par le serveur (c'est le cas par défaut
> sur Hostinger). C'est là que les positions sont stockées, puis effacées automatiquement après 24 h.

---

## 2. Utiliser l'appli

1. Chaque membre ouvre le site, entre **son prénom** et le **même code de famille**
   (par exemple `famille-dupont`). Choisissez un code que vous êtes seuls à connaître.
2. On appuie sur **« Je partage ma position »** → le téléphone demande l'autorisation GPS → **Autoriser**.
3. Tout le monde se voit sur la carte. Le bouton **partager** (en haut) copie un lien d'invitation
   qui contient déjà le code.
4. Pour ne plus être vu : on remet l'interrupteur sur **Désactivé**, ou on appuie sur **Quitter**.

---

## 3. Vie privée

- On ne peut **pas** localiser quelqu'un à son insu : chacun doit activer le partage sur son propre téléphone.
- Seules les personnes qui connaissent le **code de famille** voient les positions.
- Le code n'est jamais stocké en clair sur le serveur (il est transformé en empreinte).
- Les positions s'effacent toutes seules après 24 h sans mise à jour.
- Change de code de famille à tout moment pour « repartir à zéro ».

---

## Fichiers

```
public_html/
  index.html   ← l'application (ce que voient les gens)
  api.php      ← le mini-serveur qui partage les positions
  data/        ← positions stockées ici (protégé, non versionné)
    .htaccess  ← interdit l'accès web direct aux données
```
