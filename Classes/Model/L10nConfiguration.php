<?php
namespace Localizationteam\L10nmgr\Model;

/***************************************************************
 *  Copyright notice
 *  (c) 2006 Kasper Skårhøj <kasperYYYY@typo3.com>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * l10nConfiguration
 * Capsulate a 10ncfg record.
 * Has factory method to get a relevant AccumulatedInformationsObject
 *
 * @author     Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author     Daniel Pötzinger <ext@aoemedia.de>
 * @package    TYPO3
 * @subpackage tx_l10nmgr
 */
class L10nConfiguration
{

    var $l10ncfg = array();

    /**
     * loads internal array with l10nmgrcfg record
     *
     * @param int $id Id of the cfg record
     *
     * @return void
     **/
    function load($id)
    {
        $this->l10ncfg = BackendUtility::getRecord('tx_l10nmgr_cfg', $id);
    }

    /**
     * checks if configuration is valid
     *
     * @return boolean
     **/
    function isLoaded()
    {
        // array must have values also!
        if (is_array($this->l10ncfg) && (!empty($this->l10ncfg))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * get uid field
     *
     * @return int
     **/
    function getId()
    {
        return $this->getData('uid');
    }

    /**
     * get a field of the current cfgr record
     *
     * @param string $key Key of the field. E.g. title,uid...
     *
     * @return string Value of the field
     **/
    function getData($key)
    {
        return $this->l10ncfg[$key];
    }

    /**
     * Factory method to create AccumulatedInformations Object (e.g. build tree etc...) (Factorys should have all dependencies passed as parameter)
     *
     * @param int $sysLang sys_language_uid
     * @param mixed $overrideStartingPoint optional override startingpoint  TODO!
     *
     * @return L10nAccumulatedInformations
     **/
    function getL10nAccumulatedInformationsObjectForLanguage($sysLang, $overrideStartingPoint = '')
    {

        $l10ncfg = $this->l10ncfg;
        $treeStartingRecord = array();
        // Showing the tree:
        // Initialize starting point of page tree:
        $treeStartingPoint = $l10ncfg['depth'] == -1 ? (int)GeneralUtility::_GET('srcPID') : (int)$l10ncfg['pid'];
        if ($treeStartingPoint > 0) {
            $treeStartingRecord = BackendUtility::getRecordWSOL('pages', $treeStartingPoint);
        }
        $depth = $l10ncfg['depth'];

        // Initialize tree object:
        /** @var $tree PageTreeView */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
        $tree->addField('l18n_cfg');

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $HTML = $iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL)->render();
        $tree->tree[] = array(
            'row' => $treeStartingRecord,
            'HTML' => $HTML
        );
        // Create the tree from starting point:
        if ($depth > 0) {
            $tree->getTree($treeStartingPoint, $depth, '');
        }

        //now create and init accum Info object:
        /** @var $accumObj L10nAccumulatedInformation */
        $accumObj = GeneralUtility::makeInstance(L10nAccumulatedInformation::class, $tree, $l10ncfg, $sysLang);

        return $accumObj;
    }

    function updateFlexFormDiff($sysLang, $flexFormDiffArray)
    {
        $l10ncfg = $this->l10ncfg;
        // Updating diff-data:
        // First, unserialize/initialize:
        $flexFormDiffForAllLanguages = unserialize($l10ncfg['flexformdiff']);
        if (!is_array($flexFormDiffForAllLanguages)) {
            $flexFormDiffForAllLanguages = array();
        }

        // Set the data (
        $flexFormDiffForAllLanguages[$sysLang] = array_merge((array)$flexFormDiffForAllLanguages[$sysLang],
            $flexFormDiffArray);

        // Serialize back and save it to record:
        $l10ncfg['flexformdiff'] = serialize($flexFormDiffForAllLanguages);
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_l10nmgr_cfg', 'uid=' . (int)$l10ncfg['uid'],
            array('flexformdiff' => $l10ncfg['flexformdiff']));
    }
}