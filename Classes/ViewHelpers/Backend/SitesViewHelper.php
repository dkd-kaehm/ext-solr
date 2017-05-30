<?php
namespace ApacheSolrForTypo3\Solr\ViewHelpers\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Timo Schmidt <timo.schmidt@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Make site information available in the rendering scope of this ViewHelper
 *
 * = Examples =
 *
 * <solr:backend.sites>
 *       <f:if condition="{hasSites}">
 *           <f:then>We have a site configured</f:then>
 *      </f:if>
 * </solr:backend.sites>
 */
class SitesViewHelper extends AbstractSolrBackendViewHelper
{
    /**
     * @var \ApacheSolrForTypo3\Solr\Mvc\Backend\Service\ModuleDataStorageService
     * @inject
     */
    protected $moduleDataStorageService;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @return mixed
     */
    public function render()
    {
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        $availableSites = $siteRepository->getAvailableSites();
        $currentSite = $this->moduleDataStorageService->loadModuleData()->getSite();
        $hasSites = is_array($availableSites) && count($availableSites) > 0;

        $this->templateVariableContainer->add('availableSites', $availableSites);
        $this->templateVariableContainer->add('currentSite', $currentSite);
        $this->templateVariableContainer->add('hasSites', $hasSites);

        $output = $this->renderChildren();

        $this->templateVariableContainer->remove('hasSites');
        $this->templateVariableContainer->remove('currentSite');
        $this->templateVariableContainer->remove('availableSites');

        return $output;
    }
}
