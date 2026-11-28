<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$IP = getenv( 'MW_INSTALL_PATH' ) !== false
	? str_replace( '\\', '/', getenv( 'MW_INSTALL_PATH' ) )
	: '../..';

// Semantic MediaWiki is optional at runtime. Its classes are only read for type information.
$cfg['directory_list'][] = $IP . '/extensions/SemanticMediaWiki';
$cfg['exclude_analysis_directory_list'][] = $IP . '/extensions/SemanticMediaWiki';

return $cfg;
