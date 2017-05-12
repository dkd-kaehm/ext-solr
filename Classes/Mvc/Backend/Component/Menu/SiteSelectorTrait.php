<?php
namespace ApacheSolrForTypo3\Solr\Mvc\Backend\Component\Menu;

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

use ApacheSolrForTypo3\Solr\Mvc\Backend\Component\Exception\InvalidViewObjectNameException;
use ApacheSolrForTypo3\Solr\Site;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder as ExtbaseUriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

/**
 * Class MenuSiteSelectorTrait
 *
 * Can be used in module controller to generate site selector menu in backends docheader.
 * Just use this trait and call generateSiteSelectorMenu() in initializeView() method of your controller.
 * See example usage in CoreOptimi
 *
 * Properties from ActionController, which are used in this trait:
 * @property BackendTemplateView $view
 * @property ExtbaseUriBuilder $uriBuilder
 * @property ControllerContext $controllerContext
 * @property Request $request
 *
 * Methods from ActionController, which are used in this trait:
 * @method void redirect($actionName, $controllerName = null, $extensionName = null, array $arguments = null, $pageUid = null, $delay = 0, $statusCode = 303)
 * @method void redirectToUri($uri, $delay = 0, $statusCode = 303)
 * @method void addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
 */
trait SiteSelectorTrait
{
    /**
     * @var \ApacheSolrForTypo3\Solr\Mvc\Backend\Service\ModuleDataStorageService
     * @inject
     */
    protected $moduleDataStorageService = null;

    /**
     * @var \ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository
     * @inject
     */
    protected $siteRepository = null;

    /**
     * @var Menu
     */
    protected $siteSelectorMenu = null;

    /**
     * @var Site
     */
    protected $selectedSite;

    /**
     * Generates the menu with available sites.
     *
     * @param string|null $uriToRedirectTo
     * @throws InvalidViewObjectNameException
     */
    public function generateSiteSelectorMenu(string $uriToRedirectTo = null)
    {
        if ($this->view instanceof NotFoundView) {
            $this->initializeSelectedSite();
            return;
        }
        if (!$this->view instanceof BackendTemplateView) {
            throw new InvalidViewObjectNameException(vsprintf(
                'The controller "%s" must use BackendTemplateView to be able to generate site selector menu for backends docheader.
                Please set `protected $defaultViewObjectName = BackendTemplateView::class;` field in your controller.',
                [static::class]
                ), 1493800239);
        }
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        /* @var BackendUriBuilder $backendUriBuilder */
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);

        if (!isset($uriToRedirectTo)) {
            // uri to current(referrer) controller and action
            $uriToRedirectTo = $this->uriBuilder->reset()->uriFor();
        }

        $this->initializeSelectedSite();

        $this->siteSelectorMenu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $this->siteSelectorMenu->setIdentifier('component_site_selector_menu');
        //@todo: Fix broken CSS and set label properly: $siteSelectorMenu->setLabel('Select Site:');
        $sites = $this->siteRepository->getAvailableSites();
        foreach ($sites as $site) {
            $menuItem = $this->siteSelectorMenu->makeMenuItem();
            $menuItem->setTitle($site->getLabel());

            $uri = $backendUriBuilder->buildUriFromModule(
                'SolrSsearch',
                ['tx_solr_solrssearch' => [
                    'action' => 'switchSite',
                    'controller' => 'Backend\\Component\\BackendComponent',
                    'rootPageId' => $site->getRootPageId(),
                    'uriToRedirectTo' => $uriToRedirectTo
                ]]
            );
            $menuItem->setHref($uri);

            if ($site->getRootPageId() == $this->selectedSite->getRootPageId()) {
                $menuItem->setActive(true);
            }
            $this->siteSelectorMenu->addMenuItem($menuItem);
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($this->siteSelectorMenu);
    }

    /**
     * Fetches the selected Site from module storage.
     *
     * Note: This method checks the consistency of the selected Site and chooses the first available one and sets(persists)
     *       as selected in module data.
     *
     * Uses the site from the module data persistent state if present, otherwise uses and persists first available.
     */
    protected function initializeSelectedSite()
    {
        $moduleData = $this->moduleDataStorageService->loadModuleData();
        $currentSite = $moduleData->getSite();
        $this->selectedSite = $currentSite;

        if (!$currentSite instanceof Site || !$currentSite->getSolrConfiguration()->getEnabled()) {
            $this->selectedSite = $this->siteRepository->getFirstAvailableSite();
            $moduleData->setSite($this->selectedSite);
            $this->moduleDataStorageService->persistModuleData($moduleData);
        }
        if ($currentSite instanceof Site && !$currentSite->getSolrConfiguration()->getEnabled()) { // Site was set previously and is gone since last call.
            $this->selectedSite = $this->siteRepository->getFirstAvailableSite();
            $message = LocalizationUtility::translate('siteselector_switched_to_first_available_site', 'solr',
                [$currentSite->getLabel(), $this->selectedSite->getLabel()]
            );
            $this->addFlashMessage($message, '', AbstractMessage::NOTICE);
        }
    }

    /**
     * Switches used site and checks if selected core is available and switches it if needed.
     *
     * @param int $rootPageId
     * @param string $uriToRedirectTo
     */
    public function switchSiteAction(int $rootPageId, string $uriToRedirectTo)
    {
        $moduleData = $this->moduleDataStorageService->loadModuleData();
        $site = $this->siteRepository->getSiteByRootPageId($rootPageId);
        $moduleData->setSite($site);
        $this->moduleDataStorageService->persistModuleData($moduleData);

        $message = LocalizationUtility::translate('siteselector_switched_successfully', 'solr', [$site->getLabel()]);
        $this->addFlashMessage($message);
        $this->redirectToUri($uriToRedirectTo);
    }
}
