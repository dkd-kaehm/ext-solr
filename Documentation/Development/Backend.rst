
##########################
Developing Backend Modules
##########################

******************
Backend Components
******************

EXT:solr provides UI components for backend modules. Some components hold (GUI)state and some not, but all components calling actions(changing the extension and/or GUI state!),
and then redirecting to the actions within the component was used(referrer also) or to the defined by callers action uri, if that is required by UX.

Below are all available components listed and their responsibility.

SiteSelector
============

Renders menu in backends doc header with available Sites and changes the Site by clicking on option in drop down menu.

* Provides following methods, which must be called inside the `initializeView(...)` method in your controller:
  * `generateSiteSelectorMenu()`
* Provides following fully initialized properties in utilizing action controller:
  * `$selectedSite from type \ApacheSolrForTypo3\Solr\Site`

CoreSelector
============

Renders menu in backends doc header with available Solr cores for selected Site and changes the solr core by clicking on option in drop down menu.

* Provides following methods, which must be called inside the `initializeView(...)` method in your controller:
  * `generateCoreSelectorMenuUsingSiteSelector()`
    * Use this method together with SiteSelectorMenu component.
  * `generateCoreSelectorMenuUsingPageTree()`
    * Use this method if you are using original page tree from CMS.
* Provides following fully initialized properties in utilizing action controller:
  * `$selectedSolrCoreConnection from type \ApacheSolrForTypo3\Solr\SolrService`
