<?php
/**
 * Hooks for HitCounters extension
 *
 * @file
 * @ingroup Extensions
 */

namespace HitCounters;

use MediaWiki\Hook\GetMagicVariableIDsHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\Page\Hook\PageViewUpdatesHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\ParserGetVariableValueSwitchHook;
use MediaWiki\Hook\SkinAddFooterLinksHook;
use MediaWiki\Hook\SpecialStatsAddExtraHook;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;

use CoreParserFunctions;
use DeferredUpdates;
use GlobalVarConfig;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserOptionsLookup;
use Parser;
use SiteStats;
use Skin;
use Title;
use ViewCountUpdate;

/**
 * PHPMD will warn us about these things here but since they're hooks,
 * we really don't have much choice.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
 */
class Hooks implements
	GetMagicVariableIDsHook,
	GetPreferencesHook,
	LoadExtensionSchemaUpdatesHook,
	PageViewUpdatesHook,
	ParserFirstCallInitHook,
	ParserGetVariableValueSwitchHook,
	SkinAddFooterLinksHook,
	SpecialStatsAddExtraHook
{

	private UserOptionsLookup $userOptionsLookup;
	private bool $enabledCounters;
	private bool $enabledCountersAtTheFooter;
	private int $updateFreq;

	/**
	 * @param GlobalVarConfig $config
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		GlobalVarConfig $config,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->userOptionsLookup = $userOptionsLookup;
		$this->enabledCounters = !$config->get( "DisableCounters" );
		$this->enabledCountersAtTheFooter = $config->get( "EnableCountersAtTheFooter" );
		$this->updateFreq = $config->get( "HitcounterUpdateFreq" );
	}

	/**
	 * @param User $user User whose preferences are being modified
	 * @param array &$preferences Preferences description array, to be fed to an HTMLForm object
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onGetPreferences( $user, &$preferences ) {

		$preferences['hitcounters-exempt'] = [
			'type' => 'toggle',
			'label-message' => 'hitcounters-exempt-label',
			'section' => 'hitcounters',
		];
		$preferences['hitcounters-pageid'] = [
			'type' => 'toggle',
			'label-message' => 'hitcounters-pageid-label',
			'section' => 'hitcounters',
		];
		$preferences['hitcounters-textlength'] = [
			'type' => 'toggle',
			'label-message' => 'hitcounters-textlength-label',
			'section' => 'hitcounters',
		];
		$preferences['hitcounters-numberofmostviewedpages'] = [
			'type' => 'int',
			'help-message' => 'hitcounters-numberofmostviewedpages-help',
			'label-message' => 'hitcounters-numberofmostviewedpages-label',
			'maxLength' => 4,
			'default' => 50,
			'section' => 'hitcounters',
		];
	}

	/**
	 * https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 * https://doc.wikimedia.org/mediawiki-core/master/php/classDatabaseUpdater.html
	 *
	 * @param DatabaseUpdater $updater DatabaseUpdater subclass
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {

		HCUpdater::getDBUpdates( $updater );
	}

	/**
	 * @param array &$extraStats Array to save the new stats
	 *   	$extraStats['<name of statistic>'] => <value>;
	 *   <value> can be an array with the keys "name" and "number":
	 *   "name" is the HTML to be displayed in the name column
	 *   "number" is the number to be displayed.
	 *   or, <value> can be the number to be displayed and <name> is the
	 *   message key to use in the name column,
	 * @param IContextSource $context IContextSource object
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSpecialStatsAddExtra( &$extraStats, $context ) {

		$user = $context->getUser();
		$numberofmostviewedpages = $this->userOptionsLookup->getIntOption( $user, 'hitcounters-numberofmostviewedpages', 50 );
		if ( $numberofmostviewedpages < 0 ) {
			$numberofmostviewedpages = 0;
		}

		$totalEdits = SiteStats::edits() ?? 0;
		$totalViews = HitCounters::views() ?? 0;

		$extraStats['hitcounters-statistics-header-views']
			['hitcounters-statistics-views-total'] = $totalViews;
		$extraStats['hitcounters-statistics-header-views']
			['hitcounters-statistics-views-peredit'] =
				( $totalEdits > 0 )
				? sprintf( '%.2f', $totalViews / $totalEdits )
				: 0;

		$dbr = DBConnect::getReadingConnect();
		$param = DBConnect::getQueryInfo();
		$options['ORDER BY'] = [ 'page_counter DESC' ];
		$options['LIMIT'] = $numberofmostviewedpages;
		$res = $dbr->select(
			$param['tables'], $param['fields'], [], __METHOD__,
			$options, $param['join_conds']
		);

		$most_viewed_pages_array = [];
		if ( $res->numRows() > 0 ) {

			$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

			foreach ( $res as $row ) {
				$title = Title::makeTitleSafe( $row->namespace, $row->title );
				$key   = $title->getPrefixedText();
				$link  = $linkRenderer->makeLink( $title );

				if ( $title instanceof Title ) {
					$most_viewed_pages_array[ $key ]['number'] = $row->value;
					$most_viewed_pages_array[ $key ]['name']   = $link;
				}
			}
			$res->free();

			$extraStats['hitcounters-statistics-mostpopular'] = $most_viewed_pages_array;
		}

		return true;
	}

	protected static function getMagicWords() {
		return [
			'numberofviews'     => [ 'HitCounters\HitCounters', 'numberOfViews' ],
			'numberofpageviews' => [ 'HitCounters\HitCounters', 'numberOfPageViews' ]
		];
	}

	/**
	 * Use this hook to modify the list of magic variables.
	 * Magic variables are localized with the magic word system,
	 * and this hook is called by MagicWordFactory.
	 *
	 * @param string[] &$variableIDs array of magic word identifiers
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onGetMagicVariableIDs( &$variableIDs ) {
		$variableIDs = array_merge( $variableIDs, array_keys( self::getMagicWords() ) );
	}

	/**
	 * This hook is called when the parser initialises for the first time.
	 *
	 * @param Parser $parser Parser object being initialised
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onParserFirstCallInit( $parser ) {
		foreach ( self::getMagicWords() as $magicWord => $processingFunction ) {
			$parser->setFunctionHook( $magicWord, $processingFunction,
				Parser::SFH_OBJECT_ARGS );
		}
		return true;
	}

	/**
	 * This hook is called when the parser needs the value of a
	 * custom magic word.
	 *
	 * @param Parser $parser
	 * @param array &$variableCache Array to cache the value; when you return
	 *   $variableCache[$magicWordId] should be the same as $ret
	 * @param string $magicWordId Index of the magic word (hook should not mutate it!)
	 * @param string &$ret Value of the magic word (the hook should set it)
	 * @param PPFrame $frame PPFrame object to use for expanding any template variables
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onParserGetVariableValueSwitch(
		$parser,
		&$variableCache,
		$magicWordId,
		&$ret,
		$frame
	) {

		foreach ( self::getMagicWords() as $magicWord => $processingFunction ) {
			if ( $magicWord === $magicWordId ) {
				if ( $this->enabledCounters ) {
					$ret = $variableCache[$magicWordId] = CoreParserFunctions::formatRaw(
						call_user_func( $processingFunction, $parser, $frame, null ),
						null,
						$parser->getTargetLanguage()
					);
					return true;
				} else {
					wfDebugLog( 'HitCounters', 'Counters are disabled!' );
				}
			}
		}
		return true;
	}

	/**
	 * Use this hook to make database (or other) changes after a
	 * page view is seen by MediaWiki.  Note this does not capture views made
	 * via external caches such as Squid.
	 *
	 * @param WikiPage $wikipage Page being viewed
	 * @param User $user User who is viewing
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onPageViewUpdates( $wikipage, $user ) {

		// Don't update page view counters on views from bot users (bug 14044)
		if (
			$this->enabledCounters &&
			!$user->isAllowed( 'bot' ) &&
			!$user->isAllowed( 'sysop' ) &&
			!$this->userOptionsLookup->getBoolOption( $user, 'hitcounters-exempt' ) &&
			$wikipage->exists()
		) {
			DeferredUpdates::addUpdate( new ViewCountUpdate( $wikipage->getId(), $this->updateFreq ) );
		}
	}

	/**
	 * This hook is called when generating the code used to display the
	 * footer.
	 *
	 * @param Skin $skin
	 * @param string $key the current key for the current group (row) of footer links.
	 *   e.g. `info` or `places`.
	 * @param array &$footerItems an empty array that can be populated with new links.
	 *   keys should be strings and will be used for generating the ID of the footer item
	 *   and value should be an HTML string.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerItems ) {

		if ( $key !== 'info' ) {
			return;
		}

		if ( $this->enabledCounters && $this->enabledCountersAtTheFooter ) {

			$viewcount = HitCounters::getCount( $skin->getTitle() );

			if ( $viewcount ) {
				wfDebugLog(
					"HitCounters",
					"Got viewcount=$viewcount and putting in page"
				);
				$enableAddTextLength = MediaWikiServices::getInstance()->getUserOptionsLookup()->getBoolOption( $this->getUser(), 'hitcounters-textlength' );
				$msg = 'hitcounters-viewcount';
				if ( $enableAddTextLength ) {
					$msg .= '-len';
				}
				$charactercount = $skin->getTitle()->getLength();
				$viewcountMsg = $skin->msg( $msg )
					->numParams( $viewcount )
					->numParams( $charactercount )->parse();

				// Set up the footer
				$footerItems['viewcount'] = $viewcountMsg;
			}
		}
	}

	/**
	 * Hook runner for the `AbuseFilter-builder` hook
	 *
	 * @param array &$realValues Builder values
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_builder( array &$realValues ) {
		$realValues['vars']['page_views'] = 'page-views';
		$realValues['vars']['moved_from_views'] = 'movedfrom-views';
		$realValues['vars']['moved_to_views'] = 'movedto-views';
	}

	/**
	 * Hook runner for the AbuseFilter-computeVariable` hook
	 *
	 * Like AbuseFilter-interceptVariable but called if the requested method wasn't found.
	 * Return true to indicate that the method is known to the hook and was computed successful.
	 *
	 * @param string $method Method to generate the variable
	 * @param VariableHolder $vars
	 * @param array $parameters Parameters with data to compute the value
	 * @param ?string &$result Result of the computation
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_computeVariable(
		string $method,
		VariableHolder $vars,
		array $parameters,
		?string &$result
	) {
		// Both methods are needed because they're saved in the DB and are necessary for old entries
		if ( $method === 'article-views' || $method === 'page-views' ) {
			$result = HitCounters::getCount( $parameters['title'] );
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Hook runner for the `AbuseFilter-deprecatedVariables` hook
	 *
	 * @param array &$deprecatedVariables deprecated variables, syntax: [ 'old_name' => 'new_name' ]
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_deprecatedVariables( array &$deprecatedVariables ) {
		$deprecatedVariables['article_views'] = 'page_views';
	}

	/**
	 * Hook runner for the `AbuseFilter-generateTitleVars` hook
	 *
	 * Allows altering the variables generated for a title
	 *
	 * @param VariableHolder $vars
	 * @param Title $title
	 * @param string $prefix Variable name prefix
	 * @param ?RecentChange $rc If the variables should be generated for an RC entry,
	 *     this is the entry. Null if it's for the current action being filtered.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_generateTitleVars(
		VariableHolder $vars,
		Title $title,
		string $prefix,
		?RecentChange $rc
	) {
		$vars->setLazyLoadVar( $prefix . '_VIEWS', 'page-views', [ 'title' => $title ] );
	}
}
