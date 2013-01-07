<?
	// try to generate req_tokens from the command line

	require('../snaphax.php');

	function main() {
		global $argv;

		if (count($argv) != 5) {
			die("$argv[0]: usage $argv[0] [user] [pass] [param1] [param2]\n");
		}

		$opts = array();
		$opts['username'] = $argv[1];
		$opts['password'] = $argv[2];
		$opts['debug'] = 1; 

		$s = new Snaphax($opts);
		$req_token = $s->reqToken($argv[3], $argv[4]);
		echo "req_token for $argv[3] $argv[4]:\n";
		var_dump($req_token);
		$req_token = $s->reqToken($argv[4], $argv[3]);
		echo "req_token for $argv[4] $argv[3]:\n";
		var_dump($req_token);
	}

	main();
