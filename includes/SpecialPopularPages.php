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
use Linker;
use QueryPage;
use Skin;
use Title;

class SpecialPopularPages extends QueryPage {

	private $mMsgToken;

	public function __construct( $name = 'PopularPages' ) {
		parent::__construct( $name );

		$user = $this->getUser();
		$enableAddPageId     = $user->getBoolOption( 'hitcounters-pageid' );
		$enableAddTextLength = $user->getBoolOption( 'hitcounters-textlength' );

		$this->mMsgToken = 'hitcounters-nviews';
		$this->mMsgToken .= $enableAddTextLength ? '-nlength' : '';
		$this->mMsgToken .= $enableAddPageId ? '-id' : '';
	}

	public function isExpensive() {
		return false;
	}

	public function isSyndicated() {
		return false;
	}

	public function getQueryInfo() {
		return DBConnect::getQueryInfo();
	}

	/**
	 * @param Skin $skin
	 * @param \stdClass $result Result row
	 * @return string
	 *
	 * Suppressed because we can't choose the params
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function formatResult( $skin, $result ) {

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

		$link = Linker::linkKnown( $title );

		return $this->getLanguage()->specialList(
			$link,
			$this->msg( $this->mMsgToken )
				->numParams( $result->value )
				->numParams( $result->length )
				->numParams( $title->getArticleID() )
		);
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
