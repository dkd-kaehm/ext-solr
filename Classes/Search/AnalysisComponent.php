<?php

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

namespace ApacheSolrForTypo3\Solr\Search;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\QueryBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Analysis search component
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class AnalysisComponent extends AbstractComponent implements QueryAware
{

    /**
     * Solr query
     *
     * @var Query
     */
    protected $query;

    /**
     * QueryBuilder
     *
     * @var QueryBuilder|object
     */
    protected $queryBuilder;

    /**
     * AccessComponent constructor.
     * @param QueryBuilder|null
     */
    public function __construct(QueryBuilder $queryBuilder = null)
    {
        $this->queryBuilder = $queryBuilder ?? GeneralUtility::makeInstance(QueryBuilder::class);
    }

    /**
     * Initializes the search component.
     */
    public function initializeSearchComponent()
    {
        if ($this->searchConfiguration['results.']['showDocumentScoreAnalysis']) {
            $this->queryBuilder->startFrom($this->query)->useDebug(true);
        }
    }

    /**
     * Provides the extension component with an instance of the current query.
     *
     * @param Query $query Current query
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;
    }
}
