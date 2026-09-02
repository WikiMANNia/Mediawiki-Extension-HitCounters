<?php

namespace MediaWiki\Extension\HitCounters;

class Compat {

    public static function init(): void {
        self::aliasCoreClasses();
    }

    private static function aliasCoreClasses(): void {

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
