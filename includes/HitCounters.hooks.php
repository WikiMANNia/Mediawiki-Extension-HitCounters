<?php
namespace HitCounters;

use AbuseFilterVariableHolder;
use CoreParserFunctions;
use DatabaseUpdater;
use DeferredUpdates;
use IContextSource;
use Parser;
use PPFrame;
use RequestContext;
use QuickTemplate;
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
	public static function onGetPreferences( $user, &$preferences ) {
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
			'label-message' => 'hitcounters-numberofmostviewedpages-label',
			'maxLength' => 4,
			'default' => 50,
			'section' => 'hitcounters',
		];
		return true;
	}

	public static function onLoadExtensionSchemaUpdates(
		DatabaseUpdater $updater
	) {
		HCUpdater::getDBUpdates( $updater );
	}

	public static function onSpecialStatsAddExtra(
		array &$extraStats, IContextSource $statsPage
	) {
		$totalEdits = SiteStats::edits() ? SiteStats::edits() : 0;
		$totalViews = HitCounters::views() ? HitCounters::views() : 0;
		$extraStats['hitcounters-statistics-header-views']
			['hitcounters-statistics-views-total'] = $totalViews;
		$extraStats['hitcounters-statistics-header-views']
			['hitcounters-statistics-views-peredit'] =
				( $totalEdits > 0 )
				? sprintf( '%.2f', $totalViews / $totalEdits )
				: 0;

		$dbr = DBConnect::getReadingConnect();
		$user = RequestContext::getMain()->getUser();
		$param = DBConnect::getQueryInfo();
		$options['ORDER BY'] = [ 'page_counter DESC' ];
		$options['LIMIT'] = $user->getIntOption( 'hitcounters-numberofmostviewedpages' );
		$res = $dbr->select(
			$param['tables'], $param['fields'], [], __METHOD__,
			$options, $param['join_conds']
		);

		$most_viewed_pages_array = [];
		if ( $res->numRows() > 0 ) {
			foreach ( $res as $row ) {
				$title = Title::makeTitleSafe( $row->namespace, $row->title );
				$key   = $title->getPrefixedText();
				$link  = \Linker::linkKnown( $title );

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
		global $wgDisableCounters;

		foreach ( self::getMagicWords() as $magicWord => $processingFunction ) {
			if ( $magicWord === $magicWordId ) {
				if ( !$wgDisableCounters ) {
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
		global $wgDisableCounters;

		// Don't update page view counters on views from bot users (bug 14044)
		if (
			!$wgDisableCounters &&
			!$user->isAllowed( 'bot' ) &&
			$wikipage->exists()
		) {
			DeferredUpdates::addUpdate( new ViewCountUpdate( $wikipage->getId() ) );
		}
	}

	/**
	 * Hook: SkinTemplateOutputPageBeforeExec
	 * @param SkinTemplate $skin
	 * @param QuickTemplate $tpl
	 */
	public static function onSkinTemplateOutputPageBeforeExec(
		SkinTemplate &$skin,
		QuickTemplate &$tpl
	) {
		global $wgDisableCounters, $wgEnableCountersAtTheFooter, $wgEnableAddTextLength;

		/* Without this check two lines are added to the page. */
		static $called = false;
		if ( $called ) {
			return;
		}
		$called = true;

		if ( !$wgDisableCounters && $wgEnableCountersAtTheFooter ) {

			$footer = $tpl->get( 'footerlinks' );
			if ( isset( $footer['info'] ) && is_array( $footer['info'] ) ) {
				// 'viewcount' goes after 'lastmod', we'll just assume
				// 'viewcount' is the 0th item
				array_splice( $footer['info'], 1, 0, 'viewcount' );
				$tpl->set( 'footerlinks', $footer );
			}

			$viewcount = HitCounters::getCount( $skin->getTitle() );

			if ( $viewcount ) {
				wfDebugLog(
					"HitCounters",
					"Got viewcount=$viewcount and putting in page"
				);
				$msg = 'hitcounters-viewcount';
				if ( $wgEnableAddTextLength ) {
					$msg .= '-len';
				}
				$charactercount = $skin->getTitle()->getLength();
				$tpl->set( 'viewcount',
					$skin->msg( $msg )
						->numParams( $viewcount )
						->numParams( $charactercount )->parse() );
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
