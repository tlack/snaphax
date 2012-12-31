<?
	/*
	Snaphax example: fetch all your new photo snaps

	Run interactively like:

	$ fetch_all_new_photos.php [username] [password]
	*/

	require_once('../snaphax.php');

	function main() {
		global $argv;

		if (count($argv) != 3) {
			die("$argv[0]: usage $argv[0] [user] [pass]\n");
		}

		$opts = array();
		$opts['username'] = $argv[1];
		$opts['password'] = $argv[2];

		$s = new Snaphax($opts);
		$result = $s->login();
		var_dump($result);
		foreach ($result['snaps'] as $snap) {
			var_dump($snap['st']);
			if ($snap['st'] == SnapHax::STATUS_NEW) {
				echo "fetching $snap[id]\n";
				$blob_data = $s->fetch($snap['id']);
				if ($blob_data)
					file_put_contents($snap['id'].'.jpg', $blob_data);
			}
		}
	}

	main();

