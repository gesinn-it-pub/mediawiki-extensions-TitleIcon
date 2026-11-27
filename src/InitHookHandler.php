<?php

/*
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace MediaWiki\Extension\TitleIcon;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\TitleParser;

class InitHookHandler implements ParserFirstCallInitHook {
	/** @var IconManager */
	private $iconManager;

	/** @var TitleParser */
	private $titleParser;

	/**
	 * @param IconManager $iconManager
	 * @param TitleParser $titleParser
	 */
	public function __construct( IconManager $iconManager, TitleParser $titleParser ) {
		$this->iconManager = $iconManager;
		$this->titleParser = $titleParser;
	}

	/**
	 * @param Parser $parser
	 * @return bool|void
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'hidetitleicon', [ $this, 'hideTitleIcon' ] );
		$parser->setFunctionHook( 'titleicon_file', [ $this, 'handleFile' ] );
		$parser->setFunctionHook( 'titleicon_ooui', [ $this, 'handleOOUI' ] );
		$parser->setFunctionHook( 'titleicon_unicode', [ $this, 'handleUnicode' ] );
	}

	/**
	 * @param Parser $parser
	 * @param string|null $flag 'page', 'category', 'namespace', or 'all'
	 */
	public function hideTitleIcon( Parser $parser, ?string $flag = null ) {
		$this->iconManager->hideTitleIcon( $parser, $flag );
	}

	/**
	 * @param Parser $parser
	 * @param string|null $icon
	 * @param string|null $link
	 */
	public function handleFile( Parser $parser, ?string $icon = null, ?string $link = null ) {
		$this->iconManager->parseIcons( $parser, Icon::ICON_TYPE_FILE, $icon, $this->linkToPageReference( $link ) );
	}

	/**
	 * @param Parser $parser
	 * @param string|null $icon
	 * @param string|null $link
	 */
	public function handleOOUI( Parser $parser, ?string $icon = null, ?string $link = null ) {
		$this->iconManager->parseIcons( $parser, Icon::ICON_TYPE_OOUI, $icon, $this->linkToPageReference( $link ) );
	}

	/**
	 * @param Parser $parser
	 * @param string|null $icon
	 * @param string|null $link
	 */
	public function handleUnicode( Parser $parser, ?string $icon = null, ?string $link = null ) {
		$this->iconManager->parseIcons(
			$parser,
			Icon::ICON_TYPE_UNICODE,
			$icon,
			$this->linkToPageReference( $link )
		);
	}

	/**
	 * @param string|null $link
	 * @return PageReference|null
	 */
	private function linkToPageReference( ?string $link ) {
		if ( $link ) {
			try {
				$title = $this->titleParser->parseTitle( $link );
				return PageReferenceValue::localReference( $title->getNamespace(), $title->getDBkey() );
			} catch ( MalformedTitleException ) {
				return null;
			}
		}
		return null;
	}
}
