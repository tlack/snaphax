<?php
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

 require 'submodules/IniParser/src/IniParser.php';

 Class Snaphax
 {
    // High level class to perform actions on Snapchat

    const   STATUS_NEW      = 1;
    const   MEDIA_IMAGE     = 0;
    const   MEDIA_VIDEO     = 1;

     /**
      * @var string
      */
     private $_configFile    = 'configuration/config.ini.php';


     /**
      * @param array $options
      */
     public function __construct(Array $options = array())
    {
        $this->_checkRequirements();

        $this->options      = array_merge($this->_getOptionsIni(), $options);
        $this->api          = New SnaphaxApi($this->options);
        $$this->_auth_token = false;
    }

     /**
      * @return array|bool|mixed
      */
     public function login()
    {
        $ts = $this->api->time();
        $out = $this->api->postCall(
            '/ph/login',
            array(
                'username' => $this->options['username'],
                'password' => $this->options['password'],
                'timestamp' => $ts
            ),
            $this->options['static_token'],
            $ts
        );

        if (is_array($out) && !empty($out['auth_token']))
        {
            $$this->_auth_token = $out['auth_token'];
        }
        return $out;
    }

     /**
      * @param $id
      * @return bool|mixed|string
      * @throws Exception
      */
     public function fetch($id)
    {
        if (!$$this->_auth_token) 
        {
            Throw New Exception('no auth token');
        }

        $ts = $this->api->time();
        $url = "/ph/blob";

        $result = $this->api->postCall($url, array(
            'id' => $id,
            'timestamp' => $ts,
            'username' => urlencode($this->options['username'])
        ), $$this->_auth_token, $ts, 0);

        $this->api->debug('blob result', $result);

        // some blobs are not encrypted
        if ($this->api->isValidBlobHeader(substr($result, 0, 256)))
        {
            $this->api->debug('blob not encrypted');
            return $result;
        }

        $result_decoded = $this->api->decrypt($result);
        $this->api->debug('decoded snap', $result_decoded);

        if ($this->api->isValidBlobHeader(substr($result_decoded, 0, 256))) return $result_decoded;

            $this->api->debug('invalid image/video data header');
            return false;
    }

     /**
      * @param $param1
      * @param $param2
      * @return string
      */
     public function reqToken($param1, $param2)
    {
        return $this->api->hash($param1, $param2);
    }

     /**
      * @param $file_data
      * @param $type
      * @param $recipients
      * @param int $time
      * @return string
      * @throws Exception
      */
     public function upload($file_data, $type, $recipients, $time=8)
    {
        if ($type != self::MEDIA_IMAGE && $type != self::MEDIA_VIDEO) Throw New Exception('Snaphax: upload type must be MEDIA_IMAGE or MEDIA_VIDEO');

        if (!$$this->_auth_token) Throw New Exception('no auth token');

        if (!is_array($recipients))
        {
            $recipients = array($recipients);
        }

        $ts = $this->api->time();
        $media_id = strtoupper($this->options['username']).time();
        $this->api->debug('upload snap data', $file_data);

        $file_data_encrypted = $this->api->encrypt($file_data);
        $this->api->debug('upload snap data encrypted', $file_data_encrypted);

        file_put_contents('/tmp/blah.jpg', $file_data_encrypted);

        $result = $this->api->postCall(
            '/ph/upload',
            array(
                'username' => $this->options['username'],
                'timestamp' => $ts,
                'type' => $type,
                // 'data' => urlencode($file_data_encrypted).'; filename="file"',
                'data' => '@/tmp/blah.jpg;filename=file',
                'media_id' => $media_id
            ),
            $$this->_auth_token,
            $ts,
            0,
            array('Content-type: multipart/form-data; boundary=AaB03x') // not compatible with declaration error noted.
        );
        $this->api->debug('upload result', $result);

        foreach ($recipients as $recipient) {
            $ts = $this->api->time();
            $result = $this->api->postCall(
                '/ph/send',
                array(
                    'username' => $this->options['username'],
                    'timestamp' => $ts,
                    'recipient' => $recipient,
                    'media_id' => $media_id,
                    'time' => $time
                ),
                $$this->_auth_token,
                $ts,
                0
            );
            $this->api->debug("send to $recipient: " . $result);
        }

        return $media_id;
    }

    /**
     * @returns void
     * @throws Exception
     */
    private function _checkRequirements()
    {
        $requirements = New \IniParser($this->_configFile);
        $requirements->parse('REQUIREMENTS');

        foreach ($requirements->getArray() AS $module => $extension)
        {
            if (!function_exists($module)) Throw New Exception("Snaphax needs the {$extension} PHP extension.");
        }
    }

     /**
      * @return array
      * @throws Exception
      */
     private function _getOptionsIni ()
    {
        if (!file_exists($this->_configFile)) Throw New Exception ('missing instantiating INI file, please make sure this exists.');

        $config = New \IniParser($this->_configFile);
        $config->parse('SNAPHAX_DEFAULT_OPTIONS');
        return $config->getArray();
    }
}

class SnaphaxApi {
    // Low level code to communicate with Snapchat via HTTP

    function SnaphaxApi($options) {
        $this->options = $options;
    }

    function debug($text, $binary = false) {
        if ($this->options['debug']) {
            echo "SNAPHAX DEBUG: $text";
            if ($binary !== false) {
                // shortened hex repr of binary
                $len = strlen($binary);
                $tmp = " hex ($len bytes): ";
                $tmp.= join(' ', array_map('dechex', array_map('ord', str_split(substr($binary, 0, 16)))));
                $tmp.= ' ... ';
                $tmp.= join(' ', array_map('dechex', array_map('ord', str_split(substr($binary, -16)))));
                echo $tmp;
            }
            echo "\n";
        }
    }

    function isValidBlobHeader($header) {
        if (($header[0] == chr(00) && // mp4
                 $header[0] == chr(00)) ||
                ($header[0] == chr(0xFF) && // jpg
                 $header[1] == chr(0xD8)))
            return true;
        else
            return false;
    }

    function decrypt($data) {
        return mcrypt_decrypt('rijndael-128', $this->options['blob_enc_key'], $data, 'ecb');
    }

    function encrypt($data) {
        return mcrypt_encrypt('rijndael-128', $this->options['blob_enc_key'], $data, 'ecb');
    }

    public function postCall($endpoint, $post_data, $param1, $param2, $json=1, $headers=false) {
        $ch = curl_init();

        // set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $this->options['url'].$endpoint);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_USERAGENT, $this->options['user_agent']);

        if ($headers && is_array($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $post_data['req_token'] = $this->hash($param1, $param2);
        curl_setopt($ch, CURLOPT_POST, count($post_data));
        if (!$headers)
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        else
            curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);
        $this->debug('POST params: ' . json_encode($post_data));
        $result = curl_exec($ch);
        if ($result === false) {
            $this->debug('CURL error: '.curl_error($ch));
            return false;
        }
        $this->debug('HTTP response code' . curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $this->debug('POST return ' . $result);

        // close connection
        curl_close($ch);

        if ($json)
            return json_decode(utf8_encode($result), true);
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

    function time() {
        return round(microtime(true) * 1000);
    }

 }
