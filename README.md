# SaLi CMS a.k.a. 'SaLiC'
**Sa**ssy **Li**ttle **C**MS - a simple lightweight PHP CMS

# MOVED TO >[GITLAB](https://gitlab.com/tennoxlab/Salic)<
## It's way cooler, has CI and more.

.  

.  

~~This is at the moment in active developement, but I am not sure if the result will ever be a complete, usable software for non-developers - although I would love to get to that point.~~

~~It is **not ready for use**, yet.~~

.

## The Philosophy
I want this CMS to be the following:
- **simplistic**
- **fast** (Mine is running on a Raspberry Pi)
- as **easy and straight-forward** as possible for content editors (That's why ContentTools is perfect for it)
- working without extra software (only PHP + included libraries)
- **flexible** (you can freely choose what features to use in the template, nothing is static)

It is designed mostly for simple websites (navigation and content). But will also be flexible for mildly complex pages.
I will probably also add possibilities to extend it with custom controllers/widgets.

But it is mainly designed like this:
After setting up the website, **editors can perform Additions/Modifications of content mostly without a web developer**, and **without much introduction or learning required**.

.

## The Status
### Implemented features:
- Basic functionality
- Edit mode
- **Multi-language support**
- nice URLs (via mod_rewrite)
- subpages
- Template blocks + subblocks
- **Adaptive images (via srcset, automatically generated)**

### Planned/WIP:
- Configuration Backend
- generation of sitemap.xml
- automatic HTML/CSS/JS compression (optional of course)
- Image uploads in ContentTools
- Use browser caching

.

## Technical details
It is the result of throwing **[ContentTools](http://getcontenttools.com/)** and **[Twig](http://twig.sensiolabs.org/)** together, dreaming, coding, and finally applying some magic.
(The part with the magic is still in the future.)

It runs without any database or extra software other than Apache and PHP (and a few RewriteRules).
Most stuff will be configurable via the Backend UI (which is very simple to use), or *via some JSON files*.

### Directory structure
- **cache/** - Stores eg. generated images
- **salic/** - Most salic-related stuff is in here
- **site/** - Everything specific to this website
  - **data/** - The content, stored in a page-based directory structure
  - **static/** - CSS, JS, images, fonts...
  - **template/** - The template files
    - **blocks/** - Block templates
    - ***blocks.json*** - Configuration of blocks
  - ***general.json*** - General settings
  - ***templates.json*** - Define templates, fields, blocks
  - ***[navigation.json]*** - You can also handle that yourself
  - ***[languages.json]*** - If the site is available in multiple languages
- ***.htaccess*** - Holds a few RewriteRules, which can be transfered to your apache config
