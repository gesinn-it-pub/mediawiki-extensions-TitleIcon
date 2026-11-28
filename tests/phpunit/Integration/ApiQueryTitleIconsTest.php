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

use ApiTestCase;
use MediaWiki\Extension\TitleIcon\Icon;

/**
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\TitleIcon\ApiQueryTitleIcons
 */
class ApiQueryTitleIconsTest extends ApiTestCase {
	private function queryIcons( string $titles ): array {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query',
			'prop' => 'titleicons',
			'titles' => $titles,
		] );
		return $result['query']['pages'];
	}

	public function testReturnsIconsOfEachPage(): void {
		$this->insertPage( 'Api Page One', '{{#titleicon_unicode:A}}' );
		$this->insertPage( 'Api Page Two', '{{#titleicon_unicode:B}}{{#titleicon_ooui:wikiText.svg}}' );
		$codec = $this->getServiceContainer()->getJsonCodec();

		$icons = [];
		foreach ( $this->queryIcons( 'Api Page One|Api Page Two' ) as $page ) {
			$icons[$page['title']] = array_map(
				static fn ( Icon $icon ) => $icon->getIcon(),
				array_values( (array)$codec->deserialize( $page['titleicons'] ) )
			);
		}

		$this->assertSame( [ 'A' ], $icons['Api Page One'] );
		$this->assertSame( [ 'B', 'wikiText.svg' ], $icons['Api Page Two'] );
	}

	public function testPageWithoutIconsGetsEmptyList(): void {
		$this->insertPage( 'Api Plain Page', 'no icon here' );

		$pages = array_values( $this->queryIcons( 'Api Plain Page' ) );

		$this->assertCount( 1, $pages );
		$this->assertArrayHasKey( 'titleicons', $pages[0] );
		$icons = $this->getServiceContainer()->getJsonCodec()->deserialize( $pages[0]['titleicons'] );
		$this->assertSame( [], (array)$icons );
	}

	public function testMissingPageIsIgnored(): void {
		$pages = array_values( $this->queryIcons( 'Api Page That Does Not Exist' ) );

		$this->assertArrayNotHasKey( 'titleicons', $pages[0] );
	}
}
