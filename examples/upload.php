<?
	// upload snaps from the command line

	require('../snaphax.php');

	function main() {
		global $argv;

		if (count($argv) != 5) {
			die("$argv[0]: usage $argv[0] [user] [pass] [fname] [recipients]\n");
		}

		$fdata = file_get_contents($argv[3]);
		if (!$fdata) die("could not read $argv[3]");

		$opts = array();
		$opts['username'] = $argv[1];
		$opts['password'] = $argv[2];
		$opts['debug'] = 1; 

		$s = new Snaphax($opts);
		$result = $s->login();
		var_dump($result);
		$result = $s->upload($fdata, SnapHax::MEDIA_IMAGE, array($argv[4]));
		var_dump($result);
	}

	main();
