<?php

namespace MediaWiki\Extension\HitCounters;

class Compat {

    public static function init(): void {
        self::aliasCoreClasses();
    }

    private static function aliasCoreClasses(): void {

		if ( class_exists( \Html::class ) && /* < 1.40 */
			!class_exists( 'MediaWiki\\Html\\Html', false ) ) {
			class_alias(
				\Html::class,
				'MediaWiki\\Html\\Html'
			);
		}
		if ( class_exists( \Linker::class ) && /* < 1.40 */
			!class_exists( 'MediaWiki\\Linker\\Linker', false ) ) {
			class_alias(
				\Linker::class,
				'MediaWiki\\Linker\\Linker'
			);
		}
		if ( class_exists( \Title::class ) && /* < 1.40 */
			!class_exists( 'MediaWiki\\Title\\Title', false ) ) {
			class_alias(
				\Title::class,
				'MediaWiki\\Title\\Title'
			);
		}
		if ( class_exists( \QueryPage::class ) && /* < 1.41 */
			!class_exists( 'MediaWiki\\SpecialPage\\QueryPage', false ) ) {
			class_alias(
				\QueryPage::class,
				'MediaWiki\\SpecialPage\\QueryPage'
			);
		}
		if ( class_exists( \SpecialPage::class ) && /* < 1.41 */
			!class_exists( 'MediaWiki\\SpecialPage\\SpecialPage', false ) ) {
			class_alias(
				\SpecialPage::class,
				'MediaWiki\\SpecialPage\\SpecialPage'
			);
		}
		if ( class_exists( \RequestContext::class ) && /* < 1.42 */
			!class_exists( 'MediaWiki\\Context\\RequestContext', false ) ) {
			class_alias(
				\RequestContext::class,
				'MediaWiki\\Context\\RequestContext'
			);
		}
		if ( class_exists( \DeferredUpdates::class ) && /* < 1.42 */
			!class_exists( 'MediaWiki\\Deferred\\DeferredUpdates', false ) ) {
			class_alias(
				\DeferredUpdates::class,
				'MediaWiki\\Deferred\\DeferredUpdates'
			);
		}
		if ( class_exists( \Parser::class ) && /* < 1.42 */
			!class_exists( 'MediaWiki\\Parser\\Parser', false ) ) {
			class_alias(
				\Parser::class,
				'MediaWiki\\Parser\\Parser'
			);
		}
		if ( class_exists( \SiteStats::class ) && /* < 1.42 */
			!class_exists( 'MediaWiki\\SiteStats\\SiteStats', false ) ) {
			class_alias(
				\SiteStats::class,
				'MediaWiki\\SiteStats\\SiteStats'
			);
		}
		if ( class_exists( \PPFrame::class ) && /* < 1.43 */
			!class_exists( 'MediaWiki\\Parser\\PPFrame', false ) ) {
			class_alias(
				\PPFrame::class,
				'MediaWiki\\Parser\\PPFrame'
			);
		}
		if ( class_exists( \BagOStuff::class ) && /* < 1.43 */
			!class_exists( 'MediaWiki\\ObjectCache\\BagOStuff', false ) ) {
			class_alias(
				\BagOStuff::class,
				'MediaWiki\\ObjectCache\\BagOStuff'
			);
		}
		if ( class_exists( \Skin::class ) && /* < 1.44 */
			!class_exists( 'MediaWiki\\Skin\\Skin', false ) ) {
			class_alias(
				\Skin::class,
				'MediaWiki\\Skin\\Skin'
			);
		}
		if ( class_exists( \WikiPage::class ) && /* < 1.44 */
			!class_exists( 'MediaWiki\\Page\\WikiPage', false ) ) {
			class_alias(
				\WikiPage::class,
				'MediaWiki\\Page\\WikiPage'
			);
		}
	}
}
