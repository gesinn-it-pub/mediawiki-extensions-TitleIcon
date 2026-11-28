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

use MediaWiki\Extension\TitleIcon\Icon;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * Rendering of file icons for a file that is registered in the database.
 * There is no thumbnail on disk, so MediaWiki renders its error placeholder, but the icon code still runs through.
 *
 * @group Database
 * @covers \MediaWiki\Extension\TitleIcon\IconManager
 */
class FileIconHtmlTest extends MediaWikiIntegrationTestCase {
	private const NBSP = "\u{00A0}";

	private function registerFile( string $name ): void {
		$file = $this->getServiceContainer()->getRepoGroup()->getLocalRepo()
			->newFile( Title::makeTitle( NS_FILE, $name ) );
		$status = $file->recordUpload3( '', 'test upload', 'test', $this->getTestSysop()->getUser(), [
			'size' => 1000,
			'width' => 100,
			'height' => 100,
			'bits' => 8,
			'mime' => 'image/png',
			'media_type' => MEDIATYPE_BITMAP,
			'sha1' => 'sha1abc',
			'fileExists' => true,
			'metadata' => [],
			'minor_mime' => 'png',
			'major_mime' => 'image',
		] );
		$this->assertStatusGood( $status );
	}

	private function getHtml( Icon $icon ): string {
		return $this->getServiceContainer()->getService( 'TitleIcon:IconManager' )->getHTML( [ $icon ] );
	}

	public static function provideSources(): array {
		return [
			'source as page reference' => [ PageReferenceValue::localReference( NS_MAIN, 'Test Page' ) ],
			'source as title' => [ Title::makeTitle( NS_MAIN, 'Test Page' ) ],
		];
	}

	/**
	 * @dataProvider provideSources
	 */
	public function testExistingFileIsRendered( $source ): void {
		$this->registerFile( 'Registered.png' );

		$html = $this->getHtml( new Icon( $source, 'Registered.png', Icon::ICON_TYPE_FILE ) );

		$this->assertStringContainsString( 'File:Registered.png', $html );
		$this->assertStringEndsWith( self::NBSP, $html, 'icons are followed by a no-break space' );
	}

	public function testLinkTargetOfTheIconIsAccepted(): void {
		$this->registerFile( 'Registered.png' );
		$icon = new Icon(
			PageReferenceValue::localReference( NS_MAIN, 'Test Page' ),
			'Registered.png',
			Icon::ICON_TYPE_FILE,
			PageReferenceValue::localReference( NS_HELP, 'Target' )
		);

		$this->assertStringEndsWith( self::NBSP, $this->getHtml( $icon ) );
	}
}
