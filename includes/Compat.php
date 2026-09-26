<?php

namespace MediaWiki\Extension\HitCounters;

class Compat {

    public static function init(): void {
        self::aliasCoreClasses();
    }

    private static function aliasCoreClasses(): void {

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
