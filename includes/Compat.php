<?php

namespace MediaWiki\Extension\HitCounters;

class Compat {

    public static function init(): void {
        self::aliasCoreClasses();
    }

    private static function aliasCoreClasses(): void {

		if ( class_exists( \Html::class ) && /* < 1.40 */
			!class_exists( \MediaWiki\Html\Html::class, false ) ) {
			class_alias(
				\Html::class,
				\MediaWiki\Html\Html::class
			);
		}
		if ( class_exists( \Linker::class ) && /* < 1.40 */
			!class_exists( \MediaWiki\Linker\Linker::class, false ) ) {
			class_alias(
				\Linker::class,
				\MediaWikiLinker\Linker::class
			);
		}
		if ( class_exists( \Title::class ) && /* < 1.40 */
			!class_exists( \MediaWiki\Title\Title::class, false ) ) {
			class_alias(
				\Title::class,
				\MediaWikiTitle\Title::class
			);
		}
		if ( class_exists( \SpecialPage::class ) && /* < 1.41 */
			!class_exists( \MediaWiki\SpecialPage\SpecialPage::class, false ) ) {
			class_alias(
				\SpecialPage::class,
				\MediaWikiSpecialPage\SpecialPage::class
			);
		}
		if ( class_exists( \DeferredUpdates::class ) && /* < 1.42 */
			!class_exists( \MediaWiki\Deferred\DeferredUpdates::class, false ) ) {
			class_alias(
				\DeferredUpdates::class,
				\MediaWikiDeferred\DeferredUpdates::class
			);
		}
		if ( class_exists( \Parser::class ) && /* < 1.42 */
			!class_exists( \MediaWiki\Parser\Parser::class, false ) ) {
			class_alias(
				\Parser::class,
				\MediaWikiParser\Parser::class
			);
		}
		if ( class_exists( \DatabaseUpdater::class ) && /* < 1.42 */
			!class_exists( \MediaWiki\Installer\DatabaseUpdater::class, false ) ) {
			class_alias(
				\DatabaseUpdater::class,
				\MediaWikiInstaller\DatabaseUpdater::class
			);
		}
		if ( class_exists( \Language::class ) && /* < 1.43 */
			!class_exists( \MediaWiki\Language\Language::class, false ) ) {
			class_alias(
				\Language::class,
				\MediaWikiLanguage\Language::class
			);
		}
		if ( class_exists( \PPFrame::class ) && /* < 1.43 */
			!class_exists( \MediaWiki\Parser\PPFrame::class, false ) ) {
			class_alias(
				\PPFrame::class,
				\MediaWikiParser\PPFrame::class
			);
		}
		if ( class_exists( \BagOStuff::class ) && /* < 1.43 */
			!class_exists( \MediaWiki\ObjectCache\BagOStuff::class, false ) ) {
			class_alias(
				\BagOStuff::class,
				\MediaWikiObjectCache\BagOStuff::class
			);
		}
		if ( class_exists( \Skin::class ) && /* < 1.44 */
			!class_exists( \MediaWiki\Skin\Skin::class, false ) ) {
			class_alias(
				\Skin::class,
				\MediaWiki\Skin\Skin::class
			);
		}
		if ( class_exists( \WikiPage::class ) && /* < 1.44 */
			!class_exists( \MediaWiki\Page\WikiPage::class, false ) ) {
			class_alias(
				\WikiPage::class,
				\MediaWikiPage\WikiPage::class
			);
		}
		if ( class_exists( \UserOptionsLookup::class ) && /* < 1.45 */
			!class_exists( \MediaWiki\User\UserOptionsLookup::class, false ) ) {
			class_alias(
				\UserOptionsLookup::class,
				\MediaWikiUser\UserOptionsLookup::class
			);
		}
		if ( class_exists( \Maintenance::class ) && /* < 1.45 */
			!class_exists( \MediaWiki\Maintenance\Maintenance::class, false ) ) {
			class_alias(
				\Maintenance::class,
				\MediaWikiMaintenance\Maintenance::class
			);
		}
    }
}
