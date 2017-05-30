<?php
namespace ApacheSolrForTypo3\Solr\ViewHelpers\Backend\Menu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2015 Ingo Renner <ingo@typo3.org>
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
use ApacheSolrForTypo3\Solr\ViewHelpers\AbstractSolrTagBasedViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Site selector menu view helper
 *
 */
class SiteSelectorMenuViewHelper extends AbstractSolrTagBasedViewHelper
{

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var \ApacheSolrForTypo3\Solr\Mvc\Backend\Service\ModuleDataStorageService
     * @inject
     */
    protected $moduleDataStorageService;

    /**
     * Initialize the arguments.
     *
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerTagAttribute('name', 'string',
            'Name of the select field');
        $this->registerUniversalTagAttributes();
    }

    public function render()
    {
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        $this->tag->addAttribute('onchange',
            'jumpToUrl(document.URL + \'&tx_solr_tools_solradministration[action]=setSite&tx_solr_tools_solradministration[site]=\'+this.options[this.selectedIndex].value,this);');

        $sites = $siteRepository->getAvailableSites();
        $currentSite = $this->moduleDataStorageService->loadModuleData()->getSite();

        $options = '';
        foreach ($sites as $site) {
            $selectedAttribute = '';
            if ($site == $currentSite) {
                $selectedAttribute = ' selected="selected"';
            }

            $options .= '<option value="' . htmlspecialchars($site->getRootPageId()) . '"' . $selectedAttribute . '>'
                . htmlspecialchars($site->getLabel())
                . '</option>';
        }

        $this->tag->setContent($options);

        return $this->tag->render();
    }
}
