<?php
/**
 * اجراکننده‌ی تست‌های افزونه‌ی TS Comments Overview.
 *
 * اجرا:  php tests/run.php
 *
 * @package TS_Comments_Overview
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures.php';

$files = glob( __DIR__ . '/*.test.php' );
sort( $files );

foreach ( $files as $file ) {
	$name = basename( $file );
	echo "\n▶ " . $name . "\n";

	++$GLOBALS['ts_co_test_count'];

	try {
		require $file;
	} catch ( Throwable $error ) {
		$GLOBALS['ts_co_failures'][] = $name . ': ' . $error->getMessage();
		echo '  ✗ استثنا: ' . $error->getMessage() . "\n";
	}
}

$failures   = $GLOBALS['ts_co_failures'];
$assertions = (int) $GLOBALS['ts_co_assertions'];

echo "\n" . str_repeat( '-', 72 ) . "\n";
printf(
	"%d فایل تست، %d ادعا، %d خطا\n",
	count( $files ),
	$assertions,
	count( $failures )
);

if ( empty( $failures ) ) {
	echo "نتیجه: همه‌ی تست‌ها پاس شدند ✅\n";
	exit( 0 );
}

echo "نتیجه: تست‌ها شکست خوردند ❌\n";
foreach ( $failures as $failure ) {
	echo ' - ' . $failure . "\n";
}

exit( 1 );
