# Guide IA : Créer et publier une release du plugin sur GitHub

Ce guide explique comment un agent IA peut créer une release du plugin WordPress et la publier sur GitHub.

## 1. Préparation
- Vérifier que le code du plugin est à jour et testé.
- Mettre à jour le numéro de version dans le fichier principal du plugin (ex: `gerfaut-companion.php`).
- Mettre à jour le changelog ou le fichier `README.md` si nécessaire.

## 2. Création de l'archive
- Se placer dans le dossier du plugin :
  ```bash
  cd /chemin/vers/gerfaut-companion-plugin
  ```
- Créer une archive zip du plugin :
  ```bash
  zip -r gerfaut-companion-x.y.z.zip . -x '*.git*' '*node_modules*' '*tests*'
  ```
  Remplacer `x.y.z` par la version.

## 3. Commit et tag Git
- Ajouter et committer les changements :
  ```bash
  git add .
  git commit -m "Release vX.Y.Z"
  ```
- Créer un tag :
  ```bash
  git tag vX.Y.Z
  git push origin main --tags
  ```

## 4. Création de la release GitHub
- Utiliser GitHub CLI (gh) :
  ```bash
  gh release create vX.Y.Z gerfaut-companion-x.y.z.zip --title "vX.Y.Z" --notes "Description des changements"
  ```
- Ou créer la release manuellement sur l'interface GitHub, en important l'archive zip.

## 5. Vérification
- Vérifier que la release apparaît sur la page GitHub Releases.
- Télécharger l'archive pour tester l'installation du plugin.

---

**Résumé rapide :**
1. Mettre à jour version et changelog
2. Zipper le plugin
3. Commit + tag git
4. Créer la release GitHub avec l'archive
5. Vérifier la publication

Important :
Ne créee pas de fichiers de documentation du processus ou de résumé en fichier MD.