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

use MediaWiki\Extension\TitleIcon\IconManager;
use MediaWiki\Title\TitleValue;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\TitleIcon\IconManager::getFileTooltip
 */
class IconManagerFileTooltipTest extends MediaWikiUnitTestCase {
	public static function provideTooltips(): array {
		return [
			'plain file name' => [ 'Logo.png', 'Logo' ],
			'only the last extension is removed' => [ 'Logo.v2.png', 'Logo.v2' ],
			'name without extension' => [ 'Logo', 'Logo' ],
			'underscores are shown as spaces' => [ 'My_Logo.png', 'My Logo' ],
		];
	}

	/**
	 * @dataProvider provideTooltips
	 */
	public function testGetFileTooltip( string $dbKey, string $expected ): void {
		$this->assertSame( $expected, IconManager::getFileTooltip( new TitleValue( NS_FILE, $dbKey ) ) );
	}
}
