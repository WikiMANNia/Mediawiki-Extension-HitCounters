<?php
/**
 * Implements Special:PopularPages
 * A special page that list most viewed pages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 */

namespace MediaWiki\Extension\HitCounters;

use Html;
use Language;
use Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use QueryPage;
use Skin;
use Title;

class SpecialPopularPages extends QueryPage {

	private Language $mContentLanguage;
	private LinkRenderer $mLinkRenderer;
	private string $mMsgToken;

	/**
	 * @param string $name
	 */
	public function __construct( $name = 'PopularPages' ) {
		parent::__construct( $name );

		$this->mContentLanguage = MediaWikiServices::getInstance()->getContentLanguage();
		$this->mLinkRenderer = $this->getLinkRenderer();

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$user = $this->getUser();
		$enableAddPageId     = $userOptionsLookup->getBoolOption( $user, 'hitcounters-pageid' );
		$enableAddTextLength = $userOptionsLookup->getBoolOption( $user, 'hitcounters-textlength' );

		$this->mMsgToken = 'hitcounters-pop-page-line';
		$this->mMsgToken .= $enableAddTextLength ? '-len' : '';
		$this->mMsgToken .= $enableAddPageId ? '-id' : '';
	}

	public function isExpensive() {
		return true;
	}

	public function isSyndicated() {
		return false;
	}

	/**
	 * @return array|null
	 */
	public function getQueryInfo() {
		return DBConnect::getQueryInfo();
	}

	/**
	 * @param Skin $skin
	 * @param stdClass $result Result row
	 * @return string|bool String or false to skip
	 *
	 * Suppressed because we can't choose the params
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function formatResult( $skin, $result ) {

		$title = Title::makeTitleSafe( $result->namespace, $result->title );

		if ( !$title ) {
			return Html::element(
				'span',
				[ 'class' => 'mw-invalidtitle' ],
				Linker::getInvalidTitleDescription(
					$this->getContext(),
					$result->namespace,
					$result->title )
			);
		}

		$link = $this->mLinkRenderer->makeKnownLink(
			$title,
			MediaWikiServices::getInstance()->
				getLanguageConverterFactory()->
				getLanguageConverter()->
				convert( $title->getPrefixedText() )
		);

		return $this->getLanguage()->specialList(
			$link,
			$this->msg( $this->mMsgToken )
				->numParams( $result->value )
				->numParams( $result->length )
				->numParams( $title->getArticleID() )
		);
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'wiki';
	}
}
