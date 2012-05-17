<?php 
	require_once __DIR__.'/../hashMan.php';

	header('Content-type: text/plain');

	$ok = 0;
	$hasher = new \TakPHPLib\Hash\hashMan(8, false);

	$correct = 'test12345';
	$hash = $hasher->hashData($correct);

	echo 'Hash: ' . $hash . "\n";

	$check = $hasher->checkData($correct, $hash);
	if ($check) ++$ok;
	echo "Check correct: '" . $check . "' (should be 1 or true)\n";

	$wrong = 'test12346';
	$check = $hasher->checkData($wrong, $hash);
	if (!$check) ++$ok;
	echo "Check wrong: '" . $check . "' (should be 0 or '' or false)\n";

	unset($hasher);

	$hasher = new \TakPHPLib\Hash\hashMan(8, true);
	$hash = $hasher->hashData($correct);

	echo 'Hash: ' . $hash . "\n";

	$check = $hasher->checkData($correct, $hash);
	if ($check) ++$ok;
	echo "Check correct: '" . $check . "' (should be 1 or true)\n";

	$check = $hasher->checkData($wrong, $hash);
	if (!$check) ++$ok;
	echo "Check wrong: '" . $check . "' (should be 0 or '' or false)\n";

	$hash = '$P$9IQRaTwmfeRo7ud9Fh4E2PdI0S3r.L0';

	echo 'Hash: ' . $hash . "\n";

	$check = $hasher->checkData($correct, $hash);
	if ($check) ++$ok;
	echo "Check correct: '" . $check . "' (should be 1 or true)\n";

	$check = $hasher->checkData($wrong, $hash);
	if (!$check) ++$ok;
	echo "Check wrong: '" . $check . "' (should be 0 or '' or false)\n";

	if ($ok == 6)
		echo "All tests have PASSED\n";
	else
		echo "Some tests have FAILED\n";