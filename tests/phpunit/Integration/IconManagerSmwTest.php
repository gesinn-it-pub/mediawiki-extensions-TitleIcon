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

namespace MediaWiki\Extension\TitleIcon\Test;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiIntegrationTestCase;

/**
 * Icons and hide flags that come from the Semantic MediaWiki properties.
 *
 * @group Database
 * @covers \MediaWiki\Extension\TitleIcon\SMWInterface
 * @covers \MediaWiki\Extension\TitleIcon\IconManager
 */
class IconManagerSmwTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
			$this->markTestSkipped( 'Semantic MediaWiki is not installed' );
		}
		// both properties hold plain text
		$this->insertPage( 'Property:Title Icon', '[[Has type::Text]]', SMW_NS_PROPERTY );
		$this->insertPage( 'Property:Hide Title Icon', '[[Has type::Text]]', SMW_NS_PROPERTY );
	}

	private function iconNames( string $pageText ): array {
		$title = $this->insertPage( 'Smw Test Page', $pageText )['title'];
		$this->getServiceContainer()->resetServiceForTesting( 'TitleIcon:IconManager' );
		$manager = $this->getServiceContainer()->getService( 'TitleIcon:IconManager' );

		return array_map( static fn ( $icon ) => $icon->getIcon(), $manager->getIcons( $title ) );
	}

	public function testIconFromProperty(): void {
		$this->assertSame( [ 'File:Smw icon.png' ], $this->iconNames( '[[Title Icon::Smw icon.png]]' ) );
	}

	public function testSeveralIconsFromProperty(): void {
		$this->assertSame(
			[ 'File:One.png', 'File:Two.png' ],
			$this->iconNames( '[[Title Icon::One.png]][[Title Icon::Two.png]]' )
		);
	}

	public function testPropertyIconIsAddedToParserFunctionIcon(): void {
		$this->assertSame(
			[ 'File:Parser.png', 'File:Smw icon.png' ],
			$this->iconNames( '{{#titleicon_file:Parser.png}}[[Title Icon::Smw icon.png]]' )
		);
	}

	public function testHideAllFromProperty(): void {
		$this->assertSame(
			[],
			$this->iconNames( '[[Title Icon::Smw icon.png]]{{#titleicon_file:Parser.png}}[[Hide Title Icon::all]]' )
		);
	}

	public function testHidePageFromProperty(): void {
		$this->assertSame(
			[],
			$this->iconNames( '[[Title Icon::Smw icon.png]]{{#titleicon_file:Parser.png}}[[Hide Title Icon::page]]' )
		);
	}

	public function testUnknownHideValueIsIgnored(): void {
		$this->assertSame(
			[ 'File:Smw icon.png' ],
			$this->iconNames( '[[Title Icon::Smw icon.png]][[Hide Title Icon::bogus]]' )
		);
	}
}
