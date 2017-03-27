<?php
namespace ApacheSolrForTypo3\Solr\Tests\Integration\IndexQueue;

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
use ApacheSolrForTypo3\Solr\IndexQueue\Initializer\Page;
use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\IndexQueue\PageIndexer;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use ApacheSolrForTypo3\Solr\Tests\Integration\IntegrationTest;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Testcase for the page indexer
 *
 * @author Timo Schmidt
 */
class PageIndexerTest extends IntegrationTest
{

    /**
     * @var Queue
     */
    protected $indexQueue;

    /**
     * @var PageIndexer
     */
    protected $indexer;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->indexQueue = GeneralUtility::makeInstance(Queue::class);
        $this->indexer = GeneralUtility::makeInstance(PageIndexer::class);

        /** @var $beUser  \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
        $beUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $beUser;

        /** @var $languageService  \TYPO3\CMS\Lang\LanguageService */
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $languageService->csConvObj = GeneralUtility::makeInstance(CharsetConverter::class);
        $GLOBALS['LANG'] = $languageService;
    }

    /**
     * This testcase should check if we can queue an custom record with MM relations and respect the additionalWhere clause.
     *
     * There is following scenario:
     *
     *  [0]
     *  |
     *  ——[20] Shared-Pages (Not root)
     *  |   |
     *  |   ——[24] FirstShared (Not root)
     *  |
     *  ——[ 1] Page (Root)
     *  |
     *  ——[14] Mount Point (to [24] to show contents from)
     *
     * @test
     */
    public function canIndexMountedPage()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        $this->importDataSetFromFixture('can_index_mounted_page.xml');
        $this->initializeAllPageIndexQueues();

        $allItemsToIndex = $this->indexQueue->getAllItems();
        $mountedPageWasIndexed = $this->indexer->index($allItemsToIndex[2]);
        $this->assertTrue($mountedPageWasIndexed, 'Could not index mounted page.');
    }

    /**
     * Initialize page index queue
     *
     * @return void
     */
    protected function initializeAllPageIndexQueues()
    {
        $pageInitializer = GeneralUtility::makeInstance(Page::class);
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        /* @var $siteRepository SiteRepository */
        $sites = $siteRepository->getAvailableSites();

        foreach($sites as $site) {
            $pageInitializer->setIndexingConfigurationName('pages');
            $pageInitializer->setSite($site);
            $pageInitializer->setType('pages');
            $pageInitializer->initialize();
        }
    }
}
