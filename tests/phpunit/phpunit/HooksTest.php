<?php

namespace MediaWiki\Extension\HitCounters;

use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

// Class aliases for multi-version compatibility.
// These need to be in global scope so phan can pick up on them,
// and before any use statements that make use of the namespaced names.
if ( version_compare( MW_VERSION, '1.44', '<' ) ) {
	if ( !class_exists('MediaWiki\Skin\Skin') )  class_alias( '\Skin', '\MediaWiki\Skin\Skin' );
}

/**
 * @coversDefaultClass HitCounters\Hooks
 */
class HooksTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::onSkinAddFooterLinks
	 */
	public function testOnSkinAddFooterLinksDisabled() {
		global $wgDisableCounters;

		$wgDisableCounters = true;
		$skinMock = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$footerItems = [];
		Hooks::onSkinAddFooterLinks( $skinMock, "", $footerItems );

		$this->assertSame( [], $footerItems, "footerItems is un-changed (empty array)" );
	}

	/**
	 * @covers ::onSkinAddFooterLinks
	 */
	public function testOnSkinAddFooterLinksNotDisabledSpecialPage() {
		global $wgDisableCounters, $wgTitle;

		$wgTitle = Title::newFromText( "Special:Version" );

		$wgDisableCounters = false;
		$skinMock = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$footerItems = [];
		Hooks::onSkinAddFooterLinks( $skinMock, "", $footerItems );

		$this->assertSame( [], $footerItems, "Do not count views for special page" );
	}
}
