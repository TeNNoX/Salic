# SaLi CMS a.k.a. 'SaLiC'
**Sa**ssy **Li**ttle **C**MS - a simple lightweight PHP CMS

### Status:
This is at the moment in active developement, but I am not sure if the result will ever be a completely usable software for non-developers, like it could be.

It is **not ready to be installed**, yet.

.

## The philosophy
I want this CMS to be the following:
- **simplistic**
- **fast** (I'm developing on a Raspberry Pi)
- as **easy and straight-forward** as possible for content editors (That's why ContentTools is perfect for it)
- working without extra software (only PHP and composer libraries)

It is designed mostly for simple websites (navigation and content). But will also be flexible for mildly complex pages.
I will probably also add possibilities to extend it with custom controllers/widgets/page elements.

But mainly it is designed for people like me, who know web developement, but don't want to be called every time the client wants to change something on the page. ;)

.

## Technical details
It is the result of throwing **[ContentTools](http://getcontenttools.com/)** and **[Twig](http://twig.sensiolabs.org/)** together, dreaming, coding, and finally applying some magic.
(The part with the magic is still in the future.)

It runs without any databse or extra software other than PHP (and a small .htaccess UrlRewrite).
Most configuration stuff can be done via the Backend UI (which is very simple to use), or via the *config.ini* and *pages.json*.
All data is stored in **data/**.
All custom templates are stored in **templates/** (salic has a few of it's own)
