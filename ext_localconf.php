<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}


if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup'] = unserialize($_EXTCONF);

    // Globally registered fluid namespace
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['flux'] = ['FluidTYPO3\\Flux\\ViewHelpers'];

    // FormEngine integration between TYPO3 forms and Flux Providers
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\FluidTYPO3\Flux\Backend\FormEngine\ProviderProcessor::class] = array(
		'depends' => array(
			\TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class
		),
		'before' => array(
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class
		)
	);

    // PreviewView class name (expecting needed changes on TYPO3 8.6+)
    \FluidTYPO3\Flux\Utility\CompatibilityRegistry::register(
        \FluidTYPO3\Flux\Service\ContentService::LANGUAGE_SOURCE_FIELD,
        array(
            '7.6.0' => 't3_origuid',
            '8.6.0' => 'l10n_source'
        )
    );

    // We need For Localization in TYPO3 7.6 a modified Version see comments in LegacyLocalizationRepository
    \FluidTYPO3\Flux\Utility\CompatibilityRegistry::register(
        \FluidTYPO3\Flux\Backend\Domain\Repository\LocalizationRepository::class,
        [
            '7.6.0' => \FluidTYPO3\Flux\Backend\Domain\Repository\LegacyLocalizationRepository::class,
            '8.6.0' => \FluidTYPO3\Flux\Backend\Domain\Repository\LocalizationRepository::class
        ]
    );

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('FluidTYPO3.Flux', 'API', array('Flux' => 'renderChildContent'), array());

    // Various hooks needed to operate Flux
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing']['flux'] = \FluidTYPO3\Flux\Backend\DynamicFlexForm::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \FluidTYPO3\Flux\Backend\TceMain::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \FluidTYPO3\Flux\Backend\TceMain::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = \FluidTYPO3\Flux\Backend\TceMain::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \FluidTYPO3\Flux\Backend\TceMain::class . '->clearCacheCommand';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['flux'] = \FluidTYPO3\Flux\Backend\Preview::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']['flux'] = \FluidTYPO3\Flux\Hooks\WizardItemsHookSubscriber::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']['flux'] =  \FluidTYPO3\Flux\Hooks\ContentIconHookSubscriber::class . '->addSubIcon';


    // The following is a dual registration of the same TCA-manipulating hook; the reason for registering it twice for two
    // different hooks is that extTablesInclusion-PostProcessing does not get executed in FE, resulting in errors due to
    // features provided by this hook subscriber not being loaded.
    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']['flux'] = \FluidTYPO3\Flux\Backend\TableConfigurationPostProcessor::class;
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'][] = \FluidTYPO3\Flux\Backend\TableConfigurationPostProcessor::class . '->includeStaticTypoScriptHook';
    }

	if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['listNestedContent']) && !(boolean)$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['listNestedContent']) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable']['flux'] = \FluidTYPO3\Flux\Hooks\RecordListGetTableHookSubscriber::class;
	}

	if (TRUE === class_exists(\FluidTYPO3\Flux\Core::class)) {

		// native Outlets, replaceable by short name in subsequent registerOutlet() calls by adding second argument (string, name of type)
		\FluidTYPO3\Flux\Core::registerOutlet('standard');

		// native Pipes, replaceable by short name in subsequent registerPipe() calls by adding second argument (string, name of type)
		\FluidTYPO3\Flux\Core::registerPipe('standard');
		\FluidTYPO3\Flux\Core::registerPipe('controller');
		\FluidTYPO3\Flux\Core::registerPipe('email');
		\FluidTYPO3\Flux\Core::registerPipe('flashMessage');
		\FluidTYPO3\Flux\Core::registerPipe('typeConverter');
	}

	/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
	$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
	$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class, \FluidTYPO3\Flux\Configuration\ConfigurationManager::class);
	unset($extbaseObjectContainer);

}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['flux'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['flux'] = array(
		'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
		'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
		'groups' => array('system'),
		'options' => [
			'defaultLifetime' => 2592000,
		],
	);
}
