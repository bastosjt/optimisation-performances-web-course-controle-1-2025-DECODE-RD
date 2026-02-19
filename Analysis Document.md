# Analysis document 

## 1- intro
### why performance is important ?

La performance est un aspect très important :

- Permet de diminuer le Largest Contentful Paint
- De maintenir un utilisateur sur le site tout au long de sa navigation
- sinon taux de rebond élevé par rapport au LCP par rapport à un site non performant
c'est à dire que plus le site charge/est lent, plus l'utilisateur va quitter la page

- C'est aussi un levier économique 
exemple : une personne a économisé 500k$ à son entreprise seulement en otpimisant
exemple : renault a optimisé son application web et cela a enchainé une augmentation du chiffre d'affaire

- Cela réduire l'empreinte carbone
- serveur -> utilise éléctricité -> plus c'est gourmand et non otpimisé -> plus on utilisé d'éléctricité plus on paye


## 2- hypothesis
### what is wrong with the application

Lorsqu'on charge l'application web sans regarder le code, je remarque plusieurs choses :

- le site mets trop de temps à charger
- Trop de requêtes côté serveur qui ralentissent le chargement

- les images mettent trop de temps à charger
- les images se chargent d'une façon aléatoire :
    - aucune priorité sur le chargement des images (pas de preload)
        - des images se trouvant plus bas que les autres ont chargés 
    - elles sont sans doute volumineuses

- Pas de lazy loading visible ou alors ne fonctionne pas

Côté code :

- Le requête BDD dans le fichier `CarouselController.php` est relativement long comparé à ce qui est affiché sur son html
- Dans les fichiers twig : `aucun preload()` est présent, il n'y a pas de balises `<picture>` pour les images ni de `lazy-loading`
- Il n'y a pas de cache présent, le site doit tout charger à chaque refresh

## 3- tests and measurements
### what metrics confirm your hypothesis

#### Le terminal docker :

Elle affiche les méthodes GET de l'application web avec l'heure

- [Thu Feb 19 09:52:06 2026] 172.20.0.1:38252 Accepted
- [Thu Feb 19 09:52:16 2026] 172.20.0.1:38252 [200]: GET /carousel

- [Thu Feb 19 09:55:35 2026] 172.20.0.1:40986 Accepted
- [Thu Feb 19 09:55:37 2026] 172.20.0.1:40976 [200]: GET /assets/img 2944a001-a1be-439a-9d2e-7ebd8b9d7c56-eMKT4wZ.JPG
- [Thu Feb 19 09:55:37 2026] 172.20.0.1:40976 Closing

L'application web à mis 3 minutes à charger l'intégralité de ses assets (images)

#### Profiler Symfony :

Elle affiche plusieurs options de debug avec des données, parmis eux :

- Panel Time (Performance) : 
Le temps total d'execution : 8082ms
Utilisation maximale de la mémoire : 6.00MiB

Les deux fichiers qui ont mis plus de temps à charger sont :

carousel/index.html.twig 2976.3 ms / 4 MiB
base.html.twig 2964.8 ms / 4 MiB
Voir captures d'écrans `/analysisImages/PerformancesProfiler.png` et `/analysisImages/PerformancesProfiler2.png`

- Panel SQL (Doctrine) :
Requêtes SQL en total : 164 
Temps de requête : 104.90ms

Pour 21 sections, 164 requêtes SQL est énorme
→ 7-8 requêtes par section

#### Dossier img dans assets contenant les images uniquement :

- Dossier img: 917Mo pour 230 images
- Certaines images vont jusqu'à 10Mo
- Format: mélange JPG et Webp (majorité sont JPG)
- Pas de dimensions fixes sur les images


### what tools you will use to measure/test

- Inspection F12 :

Aucune balises `preload()` et propriétés `lazy-loading` visible sur le html 

- Inspection F12 + onglet Network :

Le graphique indique presque 310,000 ms de chargement
La majorité du cache est du texte, très peu d'images
Pas de Cache-control visibles dans Network
Pas de page caching 
Voir capture d'écran `/analysisImages/NetworkF12.png`

- Inspection F12 + Performance :

Largest Contentful Paint (LCP):
10.68 s 

Time to first byte 7,397 ms
Resource load delay 21 ms
Resource load duration 3,167 ms
Element render delay 92 ms

Cumulative Layout Shift (CLS):
0.01
Voir capture d'écran `/analysisImages/PerformancesF12.png`

- Inspection F12 + lighthouse :

Aucun résultat : ERROR!
Voir capture d'écran `/analysisImages/LightouseF12.png`

## 4- solutions
### what immediate programming solutions could fix the application

#### Optimisation des requêtes BDD :

- Création d'une méthode dans `GalaxyRepository.php`
(explique ce qu'elle fait)

- Adaptation au conroller `CarouselController.php`
(explique ce qu'elle fait)

- Résulats :

Avant :

Panel SQL (Doctrine) :
Temps de requête : 104.90 ms

Après :

Panel SQL (Doctrine) :
Temps de requête : 34.70 ms

Voir capture d'écran `/analysisImages/OneQuery.png`

L'optimisation de la requête a diminué son temps de requête

#### Ajout du cache 

Ajout du Cache-Control: public, max-age=300 sur la réponse HTTP du CarouselController
Cette configuration permet au navigateur de mettre en cache la page pendant 5 minutes

Voir capture d'écran `/analysisImages/CacheControl.png`

On peut voir que dans le profiler, l'onglet BDD n'existe pas, la page s'est chargée, donc le cache est bien mis en place et fonctionne

#### Optimisation des images 

Comme le nom des images et leurs extensions est enregistré en BDD, j'ai optimisé les images JPG les plus volumineuses

Le dossier img dans assets est dpassé de 917Mo à 144Mo

#### Ajout du preload et lazy loading

Ajout du preload sur la première image et lazy loading sur tout le reste des images

Les images chargent plus rapidement, surtout celles de la première section

## 5- conclusion
### new measurements to confirm your solutions

#### Profiler :

- Dans l'onglet performance, le temps d'execution est diminué et la mémoire maximale également
- On voit aussi que l'utilisation des fichirs twig n'est plus aussi gourmande qu'avant

Voir capture d'écran `/analysisImages/ResultatPerfProfiler.png`
Voir capture d'écran `/analysisImages/ResultatPerfProfiler2.png`

- Le cache est bien mis en place car le doctrine affiche aucune informations et le site est chargé
Voir capture d'écran `/analysisImages/ResultatsDoctrineProfiler.png`

#### Inspection F12

-- Dans l'onglet performances, Largest Contentful Paint (LCP) est passé à 0.14 s

### what could be done in the future to improve the performances again

Dans un futur proche, il faudra otpimiser le INP car il est mauvais 
Passer toutes les images JPG à webp
Mise en place d'un CDN pour les assets
Indexation des requêtes BDD
Dimensions fixes sur images (pour le CLS)