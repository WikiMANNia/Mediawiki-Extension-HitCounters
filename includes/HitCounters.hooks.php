<?php
namespace HitCounters;

use AbuseFilterVariableHolder;
use CoreParserFunctions;
use DatabaseUpdater;
use DeferredUpdates;
use IContextSource;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Parser;
use PPFrame;
use RequestContext;
use SiteStats;
use SkinTemplate;
use Title;
use User;
use ViewCountUpdate;
use WikiPage;

/**
 * PHPMD will warn us about these things here but since they're hooks,
 * we really don't have much choice.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Hooks {

	/**
	 * @param User $user
	 * @param array &$preferences
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
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

	public static function onLoadExtensionSchemaUpdates(
		DatabaseUpdater $updater
	) {
		HCUpdater::getDBUpdates( $updater );
	}

	public static function onSpecialStatsAddExtra(
		array &$extraStats, IContextSource $statsPage
	) {
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
		$conf = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$user = RequestContext::getMain()->getUser();
		$param = DBConnect::getQueryInfo();
		$options['ORDER BY'] = [ 'page_counter DESC' ];
		$options['LIMIT'] = $conf->getIntOption( $user, 'hitcounters-numberofmostviewedpages', 50 );
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

	public static function onMagicWordwgVariableIDs( array &$variableIDs ) {
		$variableIDs = array_merge( $variableIDs, array_keys( self::getMagicWords() ) );
	}

	public static function onParserFirstCallInit( Parser $parser ) {
		foreach ( self::getMagicWords() as $magicWord => $processingFunction ) {
			$parser->setFunctionHook( $magicWord, $processingFunction,
				Parser::SFH_OBJECT_ARGS );
		}
		return true;
	}

	public static function onParserGetVariableValueSwitch( Parser $parser,
		array &$cache, $magicWordId, &$ret, PPFrame $frame ) {
		$conf = MediaWikiServices::getInstance()->getMainConfig();

		foreach ( self::getMagicWords() as $magicWord => $processingFunction ) {
			if ( $magicWord === $magicWordId ) {
				if ( !$conf->get( "DisableCounters" ) ) {
					$ret = $cache[$magicWordId] = CoreParserFunctions::formatRaw(
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

	public static function onPageViewUpdates( WikiPage $wikipage, User $user ) {
		$conf = MediaWikiServices::getInstance()->getMainConfig();

		// Don't update page view counters on views from bot users (bug 14044)
		if (
			!$conf->get( "DisableCounters" ) &&
			!$user->isAllowed( 'bot' ) &&
			$wikipage->exists()
		) {
			DeferredUpdates::addUpdate( new ViewCountUpdate( $wikipage->getId() ) );
		}
	}

	/**
	 * Hook: SkinAddFooterLinks
	 * @param Skin $skin
	 * @param string $key the current key for the current group (row) of footer links.
	 *   e.g. `info` or `places`.
	 * @param array &$footerLinks an empty array that can be populated with new links.
	 *   keys should be strings and will be used for generating the ID of the footer item
	 *   and value should be an HTML string.
	 */
	public static function onSkinAddFooterLinks(
		SkinTemplate $skin,
		string $key,
		array &$footerLinks
	) {
		if ( $key !== 'info' ) {
			return;
		}

		$conf = MediaWikiServices::getInstance()->getMainConfig();

		if ( !$conf->get( "DisableCounters" ) && $conf->get( "EnableCountersAtTheFooter" ) ) {

			$viewcount = HitCounters::getCount( $skin->getTitle() );

			if ( $viewcount ) {
				wfDebugLog(
					"HitCounters",
					"Got viewcount=$viewcount and putting in page"
				);
				$msg = 'hitcounters-viewcount';
				if ( $conf->get( "EnableAddTextLength" ) ) {
					$msg .= '-len';
				}
				$charactercount = $skin->getTitle()->getLength();
				$viewcountMsg = $skin->msg( $msg )
					->numParams( $viewcount )
					->numParams( $charactercount )->parse();

				// Set up the footer
				$footerLinks['viewcount'] = $viewcountMsg;
			}
		}
	}

	/**
	 * Tells AbuseFilter about our variables
	 * @param array &$builderValues
	 * @return void
	 */
	public static function onAbuseFilterBuilder( array &$builderValues ) {
		$builderValues['vars']['page_views'] = 'page-views';
		$builderValues['vars']['moved_from_views'] = 'movedfrom-views';
		$builderValues['vars']['moved_to_views'] = 'movedto-views';
	}

	/**
	 * Old, deprecated syntax
	 * @param array &$deprecatedVars
	 * @return void
	 */
	public static function onAbuseFilterDeprecatedVariables( array &$deprecatedVars ) {
		$deprecatedVars['article_views'] = 'page_views';
	}

	/**
	 * Lazy-loads the article_views variable
	 * @param AbuseFilterVariableHolder $vars
	 * @param Title $title
	 * @param string $prefix
	 * @return void
	 */
	public static function onAbuseFilterGenerateTitleVars(
		AbuseFilterVariableHolder $vars,
		Title $title,
		$prefix
	) {
		$vars->setLazyLoadVar( $prefix . '_VIEWS', 'page-views', [ 'title' => $title ] );
	}

	/**
	 * Computes the article_views variables
	 * @param string $method
	 * @param AbuseFilterVariableHolder $vars
	 * @param array $parameters
	 * @param null &$result
	 * @return bool
	 */
	public static function onAbuseFilterComputeVariable( $method, $vars, $parameters, &$result ) {
		// Both methods are needed because they're saved in the DB and are necessary for old entries
		if ( $method === 'article-views' || $method === 'page-views' ) {
			$result = HitCounters::getCount( $parameters['title'] );
			return false;
		} else {
			return true;
		}
	}
}
