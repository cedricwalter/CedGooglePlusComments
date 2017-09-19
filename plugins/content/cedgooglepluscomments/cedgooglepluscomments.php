<?php
/**
 * @package     CedGooglePlusComments
 * @subpackage  com_cedgooglepluscomments
 *
 * @copyright   Copyright (C) 2013-2017 galaxiis.com All rights reserved.
 * @license     The author and holder of the copyright of the software is Cédric Walter. The licensor and as such issuer of the license and bearer of the
 *              worldwide exclusive usage rights including the rights to reproduce, distribute and make the software available to the public
 *              in any form is Galaxiis.com
 *              see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class PlgContentCedGooglePlusComments extends JPlugin
{
    /**
     * @var    String  base update url, to decide whether to process the event or not
     * @since  2.5
     */
    private $baseUrl = 'https://www.galaxiis.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=6&dummy=extension.xml';

    /**
     * @var    String  your extension identifier, to retrieve its params
     * @since  2.5
     */
    private $extension = 'plg_content_cedgooglepluscomments';


    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        //Do not run in admin area and non HTML  (rss, json, error)
        $app = JFactory::getApplication();
        if ($app->isClient('administrator') || JFactory::getDocument()->getType() !== 'html')
        {
            return;
        }

	    // Return if we don't have a valid article id
	    if (!isset($row->id) || !(int) $row->id)
	    {
		    return;
	    }

        $print = JFactory::getApplication()->input->get('print') == 1;
        $isActive = JFactory::getApplication()->input->getWord('view') == 'article' && $context == 'com_content.article' && !$print;
        if ($isActive) {
            $this->addWidget($row, true);
        }
    }

    public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
    {
        //Do not run in admin area
        $app = JFactory::getApplication();
        if ($app->isClient('administrator')) {
            return;
        }

        if ($this->params->get('counter', 1)) {
            $isActive = JFactory::getApplication()->input->getWord('view') != 'article' && ($context == 'com_content.featured' || $context == 'com_content.category');
            if ($isActive) {
                return $this->addWidget($row, false);
            }
        }
    }

    /**
     * Handle adding credentials to package download request
     * Joomla! 2: version 2.5.19+ or Joomla! 3: version 3.2.3+
     * @param   string  $url        url from which package is going to be downloaded
     * @param   array   $headers    headers to be sent along the download request (key => value format)
     *
     * @return  boolean true if credentials have been added to request or not our business, false otherwise (credentials not set by user)
     *
     * @since   2.5
     */
    public function onInstallerBeforePackageDownload(&$url, &$headers)
    {
        // are we trying to update our extension?
        if (strpos($url, $this->baseUrl) !== 0)
        {
            return true;
        }

        $downloadId = $this->params->get('downloadid', '');

        // bind credentials to request by appending it to the download url
        if (preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $downloadId))
        {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'dlid=' . $downloadId;
        }

        return true;
    }

    private function addWidget(&$row, $view)
    {
//        $categories = $this->getCategories($row);
//        $categories = $this->getChildCategories($categories);
//        $menus = $this->getMenuIds();

        if ($this->isActiveInCategory($row->catid) == false) {
            return;
        }

        $document = JFactory::getDocument();
        $lang = JFactory::getLanguage();
        $document->addScript('https://apis.google.com/js/plusone.js">{lang: "' . substr($lang->getTag(), 0, 2) . '"}');

        $profileId = $this->params->get('profileid');
        if ($profileId != "") {
            $document->addCustomTag('<link rel="author" href="https://plus.google.com/' . $profileId . '/">');
        }

        $uri = JUri::getInstance();
        if ($view == 'article') {
            $output = '<!-- Copyright (C) 2013-2017 galaxiis.com All rights reserved. -->';
            $output .= '
                        <a id="g-comments"></a>
                        <div class="g-comments"
                        data-href="' . $uri->toString() . '"
                        data-width="' . $this->params->get('width', 500) . '"
                        data-first_party_property="BLOGGER"
                        data-view_type="FILTERED_POSTMOD">
                    </div>';
            $row->text .= $output;
        } else {
            require_once(JPATH_ROOT . '/components/com_content/helpers/route.php');
            $link = $uri->toString(array('scheme', 'host')) . JRoute::_(ContentHelperRoute::getArticleRoute($row->id, $row->catid));

            $icon = "";
            if ($this->params->get('showIcon', 1)) {
                $document->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

                $icon = '<span class="fa '.$this->params->get('icon', 'fa-comment').' pull-left fa-border"></span>';
            }

            $output = '<a href="'.$link.'#g-comments">'.$icon.'<div class="g-commentcount" data-href="' . $link . '"></div></a>';

            return $output;
        }

    }


	public function isActiveInCategory($categoryId)
	{
		$categoryMode       = intval($this->params->get('categoryMode', 0));
		$selectedCategories = $this->params->get('includedCatIds');

		if ($categoryMode == 0)
		{
			return true;
		}

		if ($categoryMode == 1)
		{
			if ($selectedCategories == null)
			{
				return false;
			}

			return $this->isSelectedInCategory($selectedCategories, $categoryId);
		}

		return !$this->isSelectedInCategory($selectedCategories, $categoryId);
	}

	private function isSelectedInCategory($selectedCategories, $categoryId) {
		$match = false;
		if (is_array($selectedCategories))
		{
			foreach ($selectedCategories as $category)
			{
				if ($category === "")
				{ // all category is in the list
					return true;
				}
				if (strcmp(trim($category), $categoryId) == 0)
				{
					$match = true;
				}
			}
		}
		return $match;
	}

}
