# Utiliser une image officielle PHP avec le serveur web Apache intégré
FROM php:8.2-apache

# Copier tous les fichiers de ton projet (situés dans ton dossier local) 
# directement dans le dossier web du conteneur Apache
COPY . /var/www/html/

# Exposer le port 80 (le port standard sur lequel Apache écoute à l'intérieur du conteneur)
EXPOSE 80