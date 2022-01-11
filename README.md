# sendinblue
Plugin sendinblue for [magixcms](https://www.magix-cms.com)

sendinblue est un outil d’email marketing qui vous permettra de gérer vos listes de diffusion, de concevoir des newsletters professionnels.
Le plugin intègre le système d'inscription a la newsletter de sendinblue avec un block formulaire ainsi que le visuel des inscriptions dans chaque langue.

## Installation
* Décompresser l'archive dans le dossier "plugins" de magix cms
* Connectez-vous dans l'administration de votre site internet
* Cliquer sur l'onglet plugins du menu déroulant pour sélectionner sendinblue.
* Une fois dans le plugin, laisser faire l'auto installation
* Il ne reste que la configuration du plugin pour correspondre avec vos données.

### Ajouter dans layout.tpl la ligne suivante :

```smarty
{block name="main:after"}
    {include file="sendinblue/form/sendinblue.tpl"}
{/block}
````