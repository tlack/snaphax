<?
	/*
	SnapHax: a library for communicating with Snaphax	
	Implements a subset of the Snaphax API
	
	(c) Copyright 2012 Thomas Lackner <lackner@gmail.com>

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.
	*/
	
	$SNAPHAX_DEFAULT_OPTIONS = array(
		'blob_enc_key' => 'M02cnQ51Ji97vwT4',
		'debug' => false,
		'pattern' => '0001110111101110001111010101111011010001001110011000110001000110',
		'secret' => 'iEk21fuwZApXlz93750dmW22pw389dPwOk',
		'static_token' => 'm198sOkJEn37DjqZ32lpRu76xmw288xSQ9',
		'url' => 'https://feelinsonice.appspot.com',
		'user_agent' => 'Snaphax 4.0.1 (iPad; iPhone OS 6.0; en_US)'
	);

	if (!function_exists('curl_init')) {
		  throw new Exception('Snaphax needs the CURL PHP extension.');
	}
	if (!function_exists('json_decode')) {
		  throw new Exception('Snaphax needs the JSON PHP extension.');
	}

	class SnaphaxApi {
		function SnaphaxApi($options) {
			$this->options = $options;
		}

		private function debug($text) {
			if ($this->options['debug'])
				echo "SNAPHAX DEBUG: $text\n";
		}

		public function blob($snap_id, $username, $auth_token) {
			$un = urlencode($username);
			$ts = time();
			$url = "/ph/blob";
			$result = $this->postToEndpoint($url, array(
				'id' => $snap_id,
				'timestamp' => $ts, 
				'username' => $username,
			), $auth_token, $ts, 0);
			$this->debug('blob result: ' . $result);
			$result_decoded = mcrypt_decrypt('rijndael-128', $this->options['blob_enc_key'], $result, 'ecb');
			$this->debug('decoded: ' . $result_decoded);
			if ($result_decoded[0] == chr(0xFF) &&
					$result_decoded[1] == chr(0xD8)) {		
				return $result_decoded;
			} else
				return false;
		}

		public function postToEndpoint($endpoint, $post_data, $param1, $param2, $json=1) {
			$ch = curl_init();

			// set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $this->options['url'].$endpoint);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch,CURLOPT_USERAGENT, $this->options['user_agent']);

			$post_data['req_token'] = $this->hash($param1, $param2);
			curl_setopt($ch,CURLOPT_POST, count($post_data));
			curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post_data));
			$this->debug('POST params: ' . json_encode($post_data));
			$result = curl_exec($ch);
			$this->debug($result);

			// close connection
			curl_close($ch);

			if ($json)
				return json_decode($result, true);
			else
				return $result;
		}

		function hash($param1, $param2) {
			$this->debug("p1: $param1");
			$this->debug("p2: $param2");

			$s1 = $this->options['secret'] . $param1;
			$this->debug("s1: $s1");
			$s2 = $param2 . $this->options['secret'];
			$this->debug("s2: $s2");

			$hash = hash_init('sha256');
			hash_update($hash, $s1);
			$s3 = hash_final($hash, false);
			$this->debug("s3: $s3");

			$hash = hash_init('sha256');
			hash_update($hash, $s2);
			$s4 = hash_final($hash, false);
			$this->debug("s4: $s4");

			$out = '';
			for ($i = 0; $i < strlen($this->options['pattern']); $i++) {
				$c = $this->options['pattern'][$i];
				if ($c == '0')
					$out .= $s3[$i];
				else
					$out .= $s4[$i];
			}
			$this->debug("out: $out");
			return $out;
		}
	}

	class Snaphax {
		const STATUS_NEW = 1;

		function Snaphax($options) {
			global $SNAPHAX_DEFAULT_OPTIONS;

			$this->options = array_merge($SNAPHAX_DEFAULT_OPTIONS, $options);
			$this->api = new SnaphaxApi($this->options);
			$this->auth_token = false;
		}
		function login() {
			$ts = time();
			$out = $this->api->postToEndpoint(
				'/ph/login',
				array(
					'username' => $this->options['username'],
					'password' => $this->options['password'],
					'timestamp' => $ts
				),
				$this->options['static_token'], 
				$ts
			);
			if (is_array($out) &&
					!empty($out['auth_token'])) {
				$this->auth_token = $out['auth_token'];
			}
			return $out;
		}
		function fetch($id) {
			if (!$this->auth_token) {
				throw new Exception('no auth token');
			}
			$blob = $this->api->blob($id, 
				$this->options['username'], 
				$this->auth_token);
			return $blob;
		}
	}

