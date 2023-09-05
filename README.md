# TP3 Laurent Faggin

# Travail pratique 3 - Travail sur serveur Web  


Capture d'ecran 
-

![visuel](images\capture.png)

Architecture
-

L'architecture de ce projet est la suivante:

Dans cette architecture Nginx sert d' equilibreur de charge entre serveur1 et serveur2.
serveur1 et serveur2 sont chacun connecte a un serveur php, qui lui meme est connecte a mariadb

Nginx englobe serveur1 et serveur2 dans un reseau (frontend).
Chaque serveur a un reseau prive pour php et mariadb (backend1 pour serveur1 et backend2 pour serveur2). 
De cette maniere chaque serveur est independant au niveau de sa connection avec php et mariadb.
```
tp3 ---- proxy Nginx
      |
      |--- serveur1 apache
      |          |
      |          |---php
      |               |---mariadb
      |
      |---  serveur2
                 |
                 |---php2
                      |---mariadb    
```  

 Construction du projet  
 -  

Pour pouvoir utiliser nginx comme equilibreur de charge, nous remplacons le fichier default.conf (celui de nginx) par le notre.
C'est dans ce fichier que nous allons determiner les serveurs aavec lesquels l balance doit etre faite.

On ajoute les lignes upstream pour indiquer vers quels serveurs nginx doit envoyer les requetes. max_fails determine le nombre de requetes que l'on peut envoyer avant que nginx ne considere qu'il y a un probleme.
Dans ce cas la nginx placera ce serveur en panne pendant un certain temps avant de reessayer d'envoyer des requetes. C'est ce principe qui permet de conserver la redondance des informations

```
upstream monsite-servers {
   	server serveur1 max_fails=2;
    	server serveur2 max_fails=2;
    }

server {
    listen 80;
    server_name tp3.com www.tp3.com;

    index index.php; 
    ...
    ...
}
```

Le docker-compose est le fichier de configuration de notre serveur. C'est a l'interieur de celui ci que nous pouvons definir les services necessaires.
```
proxy:
    image: 'nginx:alpine' //image pour le container
    ports:
      - '80:80'  // ports 80 de la machine hote ecoute sur le port 80 du serveur 
                 // entrant
    networks:
      - frontend   // reseau dans lequel le proxy communiquera
    depends_on:     
      - php        // ici on stipule a ngnix qu 'il a besoin de ces services 
      - php2       // avant de demarrer   
    volumes:
      - './nginx/default.conf:/etc/nginx/conf.d/default.conf:ro'  // ici on remplace le fichier default.conf de nginx par le notre. ro place ce fichier en lecture seule, il ne peut pas etre modifier depuis le container
```


Les fichiers Dockerfile sont utilises pour aller chercher l'image pour construire le container (cela permet une isolation par rapport aux autres services, car chacun a sa propre image).
```
# Pour image de php
FROM php:fpm-alpine

RUN apk update; \
    apk upgrade;
# Met a jour le serveur

# Install mysqli
RUN docker-php-ext-install mysqli

```


Tout comme le serveur nginx, serveur1 et serveur2 ont des fichiers de configuration personnalises. Par exemple ici nous specifions au serveur apache de rediriger les demandes vers php2:9000

![proxy_match_pass](images\proxy_pass_match.png)

Pour indiquer a Apache l'endroit a partir duquel il devra chercher les fichiers il faut modifier les lignes suivantes dans le fichier httpd.conf. 

![DocumentRoot](images\DocumentRoot.png)

Les points de montage sont utiles pour la persistance des donnees. Les repertoires etant situes sur la machine hote, ils ne sont pas modifiables depuis le container, ce qui est utile pour la persistance des donnees. C;est ici que sont stockees les personnalisations des containers ainsi que les informations de connection.

```
serveur1:
    build: './serveur1/'
    networks:
      - frontend
      - backend1
    depends_on:
      - php
      - mariadb
    volumes:
      - './serveur1/html:/srv/htdocs'   
// point de montage de serveur1. Les fichiers qui sont dans serveur1/html seront places dans srv/htdocs du container.
```

Une fois que toutes les configurations sont terminees, la commande pour lancer le container est la suivante (il faut se placer a la racine du dossier ou se trouve le fichier docker-compose.yml): 
```
docker compose up -d --build
```  

Lancement  
-


  - **docker compose up**: commande pour lancer les services du fichier docker-compose.yml

  - **d**: permet de placer l'execution du container en arriere plan, on pourra se servir de notre terminal

  - **build**: on oblige la construction l'image a partir des dockerfile.

La commande **docker compose ps** sert a voir tous les containers qui sont en cours d'execution.

![docker compose ps](images\docker_compose_ps.png)


La commande **top** permet de voir les processus en cours d'execution (pour avoir acces aux commandes docker sous windows, il faut que l'application docker desktop soit ouverte).
![top](images\top.png)

Fermeture
-

![docker compose down](images\docker_compose_down.png)

La commande precedente permet de fermer les containers ouverts.

  - **--rmi**: fermeture des images construites localement.

  - **-v**: suppression des volumes.

Liste de commande pour le nettoyage du systeme
-

  - **docker container prune**: supprime toutes les ressources docker non utilisees.
  - **docker container prune**: supprime tous les conteneurs arretes.
  - **docker image prune**: supprime les images non utilisees.
  - **docker network prune**: supprime les reseaux docker non utilises.
  - **docker volume prune**: supprime les volumes orphelins (qui n'a plus de conteneur associe).


