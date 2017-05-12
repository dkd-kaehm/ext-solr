<?php
namespace ApacheSolrForTypo3\Solr\Controller\Backend\Component;

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

use ApacheSolrForTypo3\Solr\Mvc\Backend\Component\Menu\CoreSelectorInterface;
use ApacheSolrForTypo3\Solr\Mvc\Backend\Component\Menu\CoreSelectorTrait;
use ApacheSolrForTypo3\Solr\Mvc\Backend\Component\Menu\SiteSelectorInterface;
use ApacheSolrForTypo3\Solr\Mvc\Backend\Component\Menu\SiteSelectorTrait;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class BackendComponentController is responsible for switching the state of backend components.
 *
 * This uses the actions from trait. To register new one add the action in ext_tables.php and add to used trait list in this controller.
 */
class BackendComponentController extends ActionController implements SiteSelectorInterface, CoreSelectorInterface
{
    use SiteSelectorTrait, CoreSelectorTrait;
}
