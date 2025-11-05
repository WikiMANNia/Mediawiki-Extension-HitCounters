<?php
/**
 * Hooks for HitCounters extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\HitCounters;

use MediaWiki\Hook\GetMagicVariableIDsHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\ParserGetVariableValueSwitchHook;
use MediaWiki\Hook\SkinAddFooterLinksHook;
use MediaWiki\Hook\SpecialStatsAddExtraHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\Page\Hook\PageViewUpdatesHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;

use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\SiteStats\SiteStats;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserOptionsLookup;
use InvalidArgumentException;
use Skin;
use WikiPage;

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
class Hook implements LoadExtensionSchemaUpdatesHook {

	/**
	 * https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 * https://doc.wikimedia.org/mediawiki-core/master/php/classDatabaseUpdater.html
	 *
	 * @param DatabaseUpdater $updater
	 * @throws InvalidArgumentException
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {

		$dbType = $updater->getDB()->getType();

		if ( !in_array( $dbType, [ 'mysql', 'postgres', 'sqlite' ] ) ) {
			throw new InvalidArgumentException( "HitCounters extension does not currently support $dbType database." );
		}

		HCUpdater::getDBUpdates( $updater );
	}
}

class Hooks implements
	GetMagicVariableIDsHook,
	GetPreferencesHook,
	PageViewUpdatesHook,
	ParserFirstCallInitHook,
	ParserGetVariableValueSwitchHook,
	SkinAddFooterLinksHook,
	SpecialStatsAddExtraHook
{
	private GlobalVarConfig $config;
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
		$this->config = $config;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->enabledCounters = !$config->get( 'DisableCounters' );
		$this->enabledCountersAtTheFooter = $config->get( 'EnableCountersAtTheFooter' );
		$this->updateFreq = $config->get( 'HitcounterUpdateFreq' );
	}

	/**
	 * @param User $user User whose preferences are being modified
	 * @param array &$preferences Preferences description array, to be fed to an HTMLForm object
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onGetPreferences( $user, &$preferences ) {

		$preferences_key = 'hitcounters-exempt';
		$preferences_default = $this->userOptionsLookup->getOption( $user, $preferences_key );
		$preferences[$preferences_key] = [
			'type' => 'toggle',
			'label-message' => 'hitcounters-exempt-label',
			'default' => $preferences_default,
			'section' => 'hitcounters'
		];
		$preferences_key = 'hitcounters-pageid';
		$preferences_default = $this->userOptionsLookup->getOption( $user, $preferences_key );
		$preferences[$preferences_key] = [
			'type' => 'toggle',
			'label-message' => 'hitcounters-pageid-label',
			'default' => $preferences_default,
			'section' => 'hitcounters'
		];
		$preferences_key = 'hitcounters-textlength';
		$preferences_default = $this->userOptionsLookup->getOption( $user, $preferences_key );
		$preferences[$preferences_key] = [
			'type' => 'toggle',
			'label-message' => 'hitcounters-textlength-label',
			'default' => $preferences_default,
			'section' => 'hitcounters'
		];
		$preferences_key = 'hitcounters-numberofmostviewedpages';
		$preferences_default = $this->userOptionsLookup->getOption( $user, $preferences_key );
		$preferences[$preferences_key] = [
			'type' => 'int',
			'help-message' => 'hitcounters-numberofmostviewedpages-help',
			'label-message' => 'hitcounters-numberofmostviewedpages-label',
			'maxLength' => 4,
			'default' => $preferences_default,
			'section' => 'hitcounters'
		];
	}

	/**
	 * @param array &$extraStats
	 * @param IContextSource $context
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSpecialStatsAddExtra( &$extraStats, $context ) {

		$user = $context->getUser();
		$numberofmostviewedpages = $this->userOptionsLookup->getIntOption( $user, 'hitcounters-numberofmostviewedpages' );
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
		$options = [];
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
				if ( empty( $title ) ) {
					// skip on 'null'
					$key   = $title->getPrefixedText();
					$link  = $linkRenderer->makeLink( $title );

					if ( $title instanceof Title ) {
						$most_viewed_pages_array[ $key ]['number'] = $row->value;
						$most_viewed_pages_array[ $key ]['name']   = $link;
					}
				}
			}
			$res->free();

			$extraStats['hitcounters-statistics-mostpopular'] = $most_viewed_pages_array;
		}

		return true;
	}

	/**
	 * @return array
	 */
	protected static function getMagicWords() {

		$key = 'MediaWiki\Extension\HitCounters\HitCounters';

		return [
			'numberofviews'     => [ $key, 'numberOfViews' ],
			'numberofpageviews' => [ $key, 'numberOfPageViews' ]
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
	 * @param Parser $parser
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
	): bool {

		foreach ( self::getMagicWords() as $magicWord => $processingFunction ) {
			if ( $magicWord === $magicWordId ) {
				if ( $this->enabledCounters ) {
					$ret = $variableCache[$magicWordId] = $parser->getTargetLanguage()->formatNum(
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
		if ( $this->enabledCounters && $wikipage->exists() ) {
			if (
				!$user->isAllowed( 'bot' ) &&
				!$user->isAllowed( 'sysop' ) &&
				!$this->userOptionsLookup->getBoolOption( $user, 'hitcounters-exempt' )
			) {
				DeferredUpdates::addUpdate( new ViewCountUpdate( $wikipage->getId(), $this->updateFreq ) );
			}
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
	): bool {
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
		string $prefix
	) {
		$vars->setLazyLoadVar( $prefix . '_VIEWS', 'page-views', [ 'title' => $title ] );
	}
}
