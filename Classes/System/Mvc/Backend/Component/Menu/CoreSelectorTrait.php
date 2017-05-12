<?php
namespace ApacheSolrForTypo3\Solr\System\Mvc\Backend\Component\Menu;

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
use ApacheSolrForTypo3\Solr\System\Mvc\Backend\Component\Exception\InvalidComponentCombinationException;
use ApacheSolrForTypo3\Solr\System\Mvc\Backend\Component\Exception\InvalidViewObjectNameException;
use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\SolrService as SolrCoreConnection;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * CoreSelectorTrait for generating core selector menu in backends docHeader by selected site.
 *
 * Properties from ActionController, which are used in this trait:
 * @property BackendTemplateView $view
 * @property UriBuilder $uriBuilder
 * @property ControllerContext $controllerContext
 *
 * Methods from ActionController, which are used in this trait:
 * @method void redirect($actionName, $controllerName = null, $extensionName = null, array $arguments = null, $pageUid = null, $delay = 0, $statusCode = 303)
 * @method void redirectToUri($uri, $delay = 0, $statusCode = 303)
 * @method void addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
 *
 * Internal Properties from other Components.
 * @property Site $selectedSite
 */
trait CoreSelectorTrait
{
    /**
     * @var SolrCoreConnection
     */
    protected $selectedSolrCoreConnection;

    /**
     * @var Menu
     */
    protected $coreSelectorMenu = null;

    /**
     * @var \ApacheSolrForTypo3\Solr\ConnectionManager
     * @inject
     */
    protected $solrConnectionManager = null;

    /**
     * @var \ApacheSolrForTypo3\Solr\System\Mvc\Backend\Service\ModuleDataStorageService
     * @inject
     */
    protected $moduleDataStorageService = null;

    /**
     * @param string|null $uriToRedirectTo
     * @throws InvalidComponentCombinationException
     * @throws InvalidViewObjectNameException
     */
    public function generateCoreSelectorMenuUsingSiteSelector(string $uriToRedirectTo = null)
    {
        if ($this->view instanceof NotFoundView) {
            $this->initializeSelectedSolrCoreConnection();
            return;
        }
        if (!$this->selectedSite instanceof Site) {
            throw new InvalidComponentCombinationException(vsprintf(
                'The method "%s" must be called after "%s::generateCoreSelectorMenuUsingSiteSelector()". Please use "%s" in "%s" and call "generateSiteSelectorMenu()" method as first.',
                [__METHOD__, SiteSelectorTrait::class, SiteSelectorTrait::class, static::class]), 1493805259);
        }
        $this->generateCoreSelectorMenu($this->selectedSite, $uriToRedirectTo);
    }

    /**
     * Generates selector menu in backends doc header using selected page from page tree.
     *
     * @param string|null $uriToRedirectTo
     */
    public function generateCoreSelectorMenuUsingPageTree(string $uriToRedirectTo = null)
    {
        $selectedPageUID = (int)GeneralUtility::_GP('id');
        if ($selectedPageUID < 1) {
            return;
        }
        /* @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        $this->selectedSite = $siteRepository->getSiteByPageId($selectedPageUID);

        if ($this->view instanceof NotFoundView) {
            $this->initializeSelectedSolrCoreConnection();
            return;
        }

        $this->generateCoreSelectorMenu($this->selectedSite, $uriToRedirectTo);
    }

    /**
     * Generates Core selector Menu for given Site.
     *
     * @param Site $site
     * @param string|null $uriToRedirectTo
     * @throws InvalidViewObjectNameException
     */
    protected function generateCoreSelectorMenu(Site $site, string $uriToRedirectTo = null)
    {
        if (!$this->view instanceof BackendTemplateView) {
            throw new InvalidViewObjectNameException(vsprintf(
                'The controller "%s" must use BackendTemplateView to be able to generate menu for backends docheader. \
                Please set `protected $defaultViewObjectName = BackendTemplateView::class;` field in your controller.',
                [static::class]), 1493804179);
        }
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $this->coreSelectorMenu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $this->coreSelectorMenu->setIdentifier('component_core_selector_menu');

        /* @var BackendUriBuilder $backendUriBuilder */
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);

        if (!isset($uriToRedirectTo)) {
            $uriToRedirectTo = $this->uriBuilder->reset()->uriFor();
        }

        $this->initializeSelectedSolrCoreConnection();
        $cores = $this->solrConnectionManager->getConnectionsBySite($site);
        foreach ($cores as $core) {
            $menuItem = $this->coreSelectorMenu->makeMenuItem();
            $menuItem->setTitle($core->getPath());
            $uri = $this->uriBuilder->reset()->uriFor('switchCore',
                [
                    'corePath' => $core->getPath(),
                    'uriToRedirectTo' => $uriToRedirectTo
                ]
            );
            $menuItem->setHref($uri);

            if ($core->getPath() == $this->selectedSolrCoreConnection->getPath()) {
                $menuItem->setActive(true);
            }
            $this->coreSelectorMenu->addMenuItem($menuItem);
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($this->coreSelectorMenu);
    }

    /**
     * Switches used core.
     *
     * Note: Does not check availability of core in site. All this stuff is done in the generation step.
     *
     * @param string $corePath
     * @param string $uriToRedirectTo
     */
    public function switchCoreAction(string $corePath, string $uriToRedirectTo)
    {
        $moduleData = $this->moduleDataStorageService->loadModuleData();
        $moduleData->setCore($corePath);

        $this->moduleDataStorageService->persistModuleData($moduleData);
        $message = LocalizationUtility::translate('coreselector_switched_successfully', 'solr', [$corePath]);
        $this->addFlashMessage($message);
        $this->redirectToUri($uriToRedirectTo);
    }

    /**
     * Initializes the solr core connection considerately to the components state.
     * Uses and persists default core connection if persisted core in Site does not exist.
     *
     */
    private function initializeSelectedSolrCoreConnection()
    {
        $moduleData = $this->moduleDataStorageService->loadModuleData();

        $solrCoreConnections = $this->solrConnectionManager->getConnectionsBySite($this->selectedSite);
        $currentSolrCorePath = $moduleData->getCore();
        if (empty($currentSolrCorePath)) {
            $this->initializeFirstAvailableSolrCoreConnection($solrCoreConnections, $moduleData);
        }
        foreach ($solrCoreConnections as $solrCoreConnection) {
            if ($solrCoreConnection->getPath() == $currentSolrCorePath) {
                $this->selectedSolrCoreConnection = $solrCoreConnection;
            }
        }
        if (!$this->selectedSolrCoreConnection instanceof SolrCoreConnection && count($solrCoreConnections) > 0) {
            $this->initializeFirstAvailableSolrCoreConnection($solrCoreConnections, $moduleData);
            $message = LocalizationUtility::translate('coreselector_switched_to_default_core', 'solr', [$currentSolrCorePath, $this->selectedSite->getLabel(), $this->selectedSolrCoreConnection->getPath()]);
            $this->addFlashMessage($message, '', AbstractMessage::NOTICE);
        }
    }

    /**
     * @param SolrCoreConnection[] $solrCoreConnections
     */
    private function initializeFirstAvailableSolrCoreConnection(array $solrCoreConnections, $moduleData)
    {
        $this->selectedSolrCoreConnection = $solrCoreConnections[0];
        $moduleData->setCore($this->selectedSolrCoreConnection->getPath());
        $this->moduleDataStorageService->persistModuleData($moduleData);
    }
}
