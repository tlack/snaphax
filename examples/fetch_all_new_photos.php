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
		// $opts['debug'] = 1; uncomment if having trouble

		$s = new Snaphax($opts);
		$result = $s->login();
		var_dump($result);
		if (empty($result) || empty($result['snaps'])) {
			echo "no snaps";
			exit;
		}

		foreach ($result['snaps'] as $snap) {
			if ($snap['st'] == SnapHax::STATUS_NEW) {
				echo "fetching $snap[id]\n";
				$blob_data = $s->fetch($snap['id']);
				if ($blob_data) {
					if ($snap['m'] == SnapHax::MEDIA_IMAGE)
						$ext = '.jpg';
					else
						$ext = '.mp4';
					file_put_contents($snap['sn'].$snap['id'].$ext, $blob_data);
				}
			}
		}
	}

	main();

