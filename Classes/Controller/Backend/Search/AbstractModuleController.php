<?php
namespace ApacheSolrForTypo3\Solr\Controller\Backend\Search;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2017 dkd Internet Service GmbH <solr-support@dkd.de>
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
use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\Utility\StringUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Abstract Module
 *
 * @property BackendTemplateView $view
 */
abstract class AbstractModuleController extends ActionController
{
    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var \ApacheSolrForTypo3\Solr\Utility\StringUtility
     */
    protected $stringUtility;

    /**
     * In the pagetree selected page UID
     *
     * @var int
     */
    protected $selectedPageUID;

    /**
     * @var Site
     */
    protected $selectedSite;

    /**
     * Method to pass a StringUtil object.
     * Use to overwrite injected object in unit test context.
     *
     * @param \ApacheSolrForTypo3\Solr\Utility\StringUtility $stringUtility
     */
    public function injectStringHelper(StringUtility $stringUtility)
    {
        $this->stringUtility = $stringUtility;
    }

    /**
     * Initializes the controller and sets needed vars.
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $this->selectedPageUID = (int)GeneralUtility::_GP('id');

        if ($this->selectedPageUID < 1) {
            return;
        }
        /* @var SiteRepository $siteRepository */
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $this->selectedSite = $siteRepository->getSiteByPageId($this->selectedPageUID);
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        if ($view instanceof NotFoundView || $this->selectedPageUID < 1) {
            return;
        }
        $permissionClause = $GLOBALS['BE_USER']->getPagePermsClause(1);
        $pageRecord = BackendUtility::readPageAccess($this->selectedSite->getRootPageId(), $permissionClause);
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);
    }
}
