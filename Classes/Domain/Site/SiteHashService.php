<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace ApacheSolrForTypo3\Solr\Domain\Site;

use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SiteHashService
 *
 * Responsible to provide sitehash related service methods.
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class SiteHashService
{
    /**
     * Resolves magic keywords in allowed sites configuration.
     * Supported keywords:
     *   __solr_current_site - The domain of the site the query has been started from
     *   __current_site - Same as __solr_current_site
     *   __all - Adds all domains as allowed sites
     *   * - Means all sites are allowed, same as no siteHash
     *
     * @param int $pageId A page ID that is then resolved to the site it belongs to
     * @param string|null $allowedSitesConfiguration TypoScript setting for allowed sites
     * @return string List of allowed sites/domains, magic keywords resolved
     * @throws DBALDriverException
     * @throws Throwable
     */
    public function getAllowedSitesForPageIdAndAllowedSitesConfiguration(
        int $pageId,
        ?string $allowedSitesConfiguration = ''
    ): string {
        if ($allowedSitesConfiguration === '__all') {
            return  $this->getDomainListOfAllSites();
        }
        if ($allowedSitesConfiguration === '*') {
            return '*';
        }
        // we thread empty allowed site configurations as __solr_current_site since this is the default behaviour
        $allowedSitesConfiguration = empty($allowedSitesConfiguration) ? '__solr_current_site' : $allowedSitesConfiguration;
        return $this->getDomainByPageIdAndReplaceMarkers($pageId, $allowedSitesConfiguration);
    }

    /**
     * Gets the site hash for a domain
     *
     * @param string $domain Domain to calculate the site hash for.
     * @return string site hash for $domain
     */
    public function getSiteHashForDomain(string $domain): string
    {
        static $siteHashes = [];
        if (isset($siteHashes[$domain])) {
            return $siteHashes[$domain];
        }

        $siteHashes[$domain] = sha1($domain . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . 'tx_solr');
        return $siteHashes[$domain];
    }

    /**
     * Returns a comma separated list of all domains from all sites.
     *
     * @return string
     * @throws DBALDriverException
     * @throws Throwable
     */
    protected function getDomainListOfAllSites(): string
    {
        $sites = $this->getAvailableSites();
        $domains = [];
        foreach ($sites as $site) {
            $domains[] = $site->getDomain();
        }

        return implode(',', $domains);
    }

    /**
     * Retrieves the domain of the site that belongs to the passed pageId and replaces their markers __solr_current_site
     * and __current_site.
     *
     * @param int $pageId
     * @param string $allowedSitesConfiguration
     * @return string
     */
    protected function getDomainByPageIdAndReplaceMarkers(int $pageId, string $allowedSitesConfiguration): string
    {
        $domainOfPage = $this->getSiteByPageId($pageId)->getDomain();
        $allowedSites = str_replace(['__solr_current_site', '__current_site'], $domainOfPage, $allowedSitesConfiguration);
        return (string)$allowedSites;
    }

    /**
     * @return Site[]
     * @throws DBALDriverException
     * @throws Throwable
     */
    protected function getAvailableSites(): array
    {
        return $this->getSiteRepository()->getAvailableSites();
    }

    /**
     * @param int $pageId
     * @return SiteInterface
     */
    protected function getSiteByPageId(int $pageId): SiteInterface
    {
        return $this->getSiteRepository()->getSiteByPageId($pageId);
    }

    /**
     * Get a reference to SiteRepository
     *
     * @return SiteRepository
     */
    protected function getSiteRepository(): SiteRepository
    {
        return GeneralUtility::makeInstance(SiteRepository::class);
    }
}
