# CKEditor : VideoEmbed Plugin
Intégrer facilement des vidéos de Youtube, Vimeo et Dailymotion dans votre éditeur CKEditor à partir d'une simple adresse.
#### CKEditor version : 4.5
#### Support Language : Français, English

## Installation
`config.extraPlugins = 'videoembed';`

## Utilisation
Entrer simplement l'adresse d'une vidéo dans le champ, exemple :https://www.youtube.com/watch?v=EOIvnRUa3ik. Le Plugin se chargera d'apadter le code automatiquement si la vidéo vient de youtube, dailymotion ou vimeo.

## Video Responsive
Un peu de CSS pour rendre les vidéos en iframe responsive. Par défaut la class de la div container est *.videoEmbed* vous pouvez personnaliser ce nom de classe au moment de l'intégration de la vidéo dans le champ spécifique. Voici un exemple de code SASS assez simple pour rendre vos vidéos responsives dans la majorité des cas.
```sass
.videoEmbed {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 */
    padding-top: 25px;
    height: 0;
    
    iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
}
```
## URLs reconnues
- Youtube (full & tiny) & Youtube playlist
- Dailymotion (full & tiny) & Dailymotion Jukebox
- Vimeo (full only)

Ne pas mettre d'url "embed" sous peine d'avoir une erreur au moment la lecture. Exemple : https://www.youtube.com/embed/NN68RqtRmjM
