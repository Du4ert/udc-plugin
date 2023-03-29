<?php

/**
 * @file plugins/pubIds/udc/UDCPubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UDCPubIdPlugin
 * @ingroup plugins_pubIds_udc
 *
 * @brief udc plugin class
 */

import('classes.plugins.PubIdPlugin');

class UDCPubIdPlugin extends PubIdPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled($mainContextId)) {
			HookRegistry::register('CitationStyleLanguage::citation', array($this, 'getCitationData'));
			HookRegistry::register('Publication::getProperties::summaryProperties', array($this, 'modifyObjectProperties'));
			HookRegistry::register('Publication::getProperties::fullProperties', array($this, 'modifyObjectProperties'));
			// HookRegistry::register('Publication::validate', array($this, 'validatePublicationUdc'));
			HookRegistry::register('Publication::getProperties::values', array($this, 'modifyObjectPropertyValues'));
			HookRegistry::register('Form::config::before', array($this, 'addPublicationFormFields'));
			HookRegistry::register('Form::config::before', array($this, 'addPublishFormNotice'));
			HookRegistry::register('TemplateManager::display', [$this, 'loadUdcFieldComponent']);
		}
		return $success;
	}

	//
	// Implement template methods from Plugin.
	//
	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.udc.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.udc.description');
	}

	/**
	 * @copydoc PKPPubIdPlugin::instantiateSettingsForm()
	 */
	function instantiateSettingsForm($contextId) {
		return false;
	}

		/**
	 * @copydoc PKPPubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('udcSuffix');
	}

		/**
	 * @copydoc PKPPubIdPlugin::getPrefixFieldName()
	 */
	function getPrefixFieldName() {
		return false;
	}
	

		/**
	 * @copydoc PKPPubIdPlugin::getLinkActions()
	 */
	function getLinkActions($pubObject) {
		$linkActions = array();

		return $linkActions;
	}

	/**
	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @copydoc PKPPubIdPlugin::constructPubId()
	 */
	function constructPubId($pubIdPrefix='', $pubIdSuffix, $contextId) {
		// return $pubIdSuffix;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'udc';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'UDC';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'UDC';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($contextId, $pubId) {
		return '';
	}

	function addJavaScript($name, $script, $args = []) {

		$args = array_merge(
			[
				'priority' => STYLE_SEQUENCE_NORMAL,
				'contexts' => ['backend'],
				'inline'   => false,
			],
			$args
		);

		$args['contexts'] = (array) $args['contexts'];
		foreach($args['contexts'] as $context) {
			$this->_javaScripts[$context][$args['priority']][$name] = [
				'script' => $script,
				'inline' => $args['inline'],
			];
		}
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplateResource('udcSuffixEdit.tpl');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdAssignFile()
	 */
	function getPubIdAssignFile() {
		return $this->getTemplateResource('udcAssign.tpl');
	}


	/**
	 * @copydoc PKPPubIdPlugin::getAssignFormFieldName()
	 */
	function getAssignFormFieldName() {
		return 'assignUdc';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixFieldName()
	 */
	function getSuffixFieldName() {
		return 'udcSuffix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixPatternsFieldNames()
	 */
	function getSuffixPatternsFieldNames() {
		return  false;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::udc');
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function isObjectTypeEnabled($pubObjectType, $contextId) {
		return (boolean) $this->getSetting($contextId, "enable" . $pubObjectType . "udc");
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function getNotUniqueErrorMsg() {
		return __('plugins.pubIds.udc.editor.udcCustomIdentifierNotUnique');
	}

	/**
	 * @copydoc PKPPubIdPlugin::validatePubId()
	 */
	function validatePubId($pubId) {
		return true;
	}

	/*
	 * Public methods
	 */
	/**
	 * Add udc to citation data used by the CitationStyleLanguage plugin
	 *
	 * @see CitationStyleLanguagePlugin::getCitation()
	 * @param $hookname string
	 * @param $args array
	 * @return false
	 */
	public function getCitationData($hookname, $args) {
		$citationData = $args[0];
		$article = $args[2];
		$issue = $args[3];
		$journal = $args[4];

		if ($issue && $issue->getPublished()) {
			$pubId = $article->getStoredPubId($this->getPubIdType());
		} else {
			$pubId = $this->getPubId($article);
		}

		if (!$pubId) {
			return;
		}

		$citationData->udc = $pubId;
	}


	/*
	 * Private methods
	 */
	
	/**
	 * Validate a publication's udc against the plugin's settings
	 *
	 * @param $hookName string
	 * @param $args array
	 */
	public function validatePublicationUdc($hookName, $args) {
		$errors =& $args[0];
		$action = $args[1];
		$props =& $args[2];

		if (empty($props['pub-id::udc'])) {
			return;
		}

		if ($action === VALIDATE_ACTION_ADD) {
			$submission = Services::get('submission')->get($props['submissionId']);
		} else {
			$publication = Services::get('publication')->get($props['id']);
			$submission = Services::get('submission')->get($publication->getData('submissionId'));
		}

		$contextId = $submission->getData('contextId');

		$udcErrors = [];
		if (!$this->validatePubId($props['pub-id::udc'])) {
			$udcErrors[] = __('plugins.pubIds.udc.editor.udcCustomIdentifierDontMatchPreg');
		}
		
		if (!$this->checkDuplicate($props['pub-id::udc'], 'Publication', $submission->getId(), $contextId)) {
			$udcErrors[] = $this->getNotUniqueErrorMsg();
		}
		if (!empty($udcErrors)) {
			$errors['pub-id::udc'] = $udcErrors;
		}
	}

	/**
	 * Add udc to submission, issue or galley properties
	 *
	 * @param $hookName string <Object>::getProperties::summaryProperties or
	 *  <Object>::getProperties::fullProperties
	 * @param $args array [
	 * 		@option $props array Existing properties
	 * 		@option $object Submission|Issue|Galley
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return array
	 */
	public function modifyObjectProperties($hookName, $args) {
		$props =& $args[0];

		$props[] = 'pub-id::udc';
	}

	/**
	 * Add udc submission value
	 *
	 * @param $hookName string <Object>::getProperties::values
	 * @param $args array [
	 * 		@option $values array Key/value store of property values
	 * 		@option $object Submission|Issue|Galley
	 * 		@option $props array Requested properties
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return array
	 */
	public function modifyObjectPropertyValues($hookName, $args) {
	}

	/**
	 * Show udc during final publish step
	 *
	 * @param $hookName string Form::config::before
	 * @param $form FormComponent The form object
	 */
	public function addPublishFormNotice($hookName, $form) {
	}

		/**
	 * Add udc fields to the publication identifiers form
	 *
	 * @param $hookName string Form::config::before
	 * @param $form FormComponent The form object
	 */
	public function addPublicationFormFields($hookName, $form) {

		if ($form->id !== 'publicationIdentifiers') {
			return;
		}

		// Add a text field to enter the udc if no pattern exists
			$form->addField(new \PKP\components\forms\FieldText('pub-id::udc', [
				'label' => __('plugins.pubIds.udc.editor.udc'),
				'description' => __('plugins.pubIds.udc.editor.udc.description'),
				'value' => $form->publication->getData('pub-id::udc'),
			]));
	}

     /*
     /* @param string $hookName
     /* @param array $args
     */
    public function loadUdcFieldComponent($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'workflow/workflow.tpl') {
            return;
        }

        $templateMgr->addStyleSheet(
            'purl-field-component',
            '
				.pkpFormField--purl__input {
					display: inline-block;
				}

				.pkpFormField--purl__button {
					margin-left: 0.25rem;
					height: 2.5rem; // Match input height
				}
			',
            [
                'contexts' => 'backend',
                'inline' => true,
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }
	
}