Snaphax: a PHP library to use the Snapchat API
==============================================

This library allows you to communicate with Snapchat's servers using their
undocumented HTTP API. It was reverse engineered from the official Android
client (version 1.6)

Warning
-------

I made this by reverse engineering the app. It may be extremely buggy or piss
off the Snapchat people. 

Limitations
-----------

Only login (with list of new media) and fetching of images is implemented.
This is obviously a huge failing which I am to correct when I have more time.

Motivation
----------

I'm a huge fan of Snapchat. I'm stunned and delighted by the fact that a simple
feature like auto-expiration of images can create such a compelling and
challenging service. And it's not just me: everyone I've told about Snapchat
who has used it has loved it. 

But I hate closed APIs, so I set about figuring out how it worked. [Adam
Caudill](http://adamcaudill.com/2012/06/16/snapchat-api-and-security/) wrote an
excellent analysis of their HTTP-based API by using an HTTPS traffic sniffer.
Unfortunately this information now seems out of date. 

I ended up having to fetch the official Android client's app binary (APK),
decompiling the whole thing with a mix of tools (all of them seemed to produce
subtly incorrect output), and then puzzling through the process of creating
their dreaded access tokens (called req\_token in the HTTP calls).

Their system is a bit unusual: it AES-256 hashes the two input values
separately, using a secret key contained in the binary, and then uses a fixed
pattern string to pull bytes from one or the other. The final composition of
the two is used in HTTP requests.

Other things about the API that I've discovered so far:

- Speaks JSON over HTTPS, using POST as the verb
- Not made for human consumption; difficult error messaging
- Doesn't seem to support JSONP (i.e., callback parameter in post data is
	ignored)

How to use
----------

Pretty simple:

```
	require_once('snaphax/snaphax.php');

	$opts = array();
	$opts['username'] = 'username';
	$opts['password'] = 'password';
	$opts['debug'] = 1; // CHANGE THIS; major spewage

	$s = new Snaphax($opts);
	$result = $s->login();
	var_dump($result);
```

The apocalyptic future
----------------------

The TODO list is almost endless at this point:

- Syncing (to mark snaps as seen)
- Video fetching
- Image/video posting
- Friend list maintenance
- Port to Javascript (probably via Node + NPM since their API doesn't seem to
	support JSONP)
- Add support for PHP composer

Author
------

Made by [@tlack](http://twitter.com/tlack) with a lot of help from 
[@adamcaudill](http://twitter.com/adamcaudill)

