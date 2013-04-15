Snaphax: a PHP library to use the Snapchat API
==============================================

This library allows you to communicate with Snapchat's servers using their
undocumented HTTP API. It was reverse engineered from the official Android
client (version 1.6)

Warning
-------

I made Snaphax by reverse engineering the app. It may be extremely buggy or
piss off the Snapchat people. Use at your own risk.

How to use
----------

Pretty simple:

```php
	require_once('snaphax/snaphax.php');

	$opts = array();
	$opts['username'] = 'username';
	$opts['password'] = 'password';
	$opts['debug'] = 1; 

	$s = new Snaphax($opts);
	$result = $s->login();
	var_dump($result);
```

Limitations
-----------

Only login (with list of new media) and fetching of image/video snaps is
implemented.  This is obviously a huge failing which I am to correct when I
have more time.

Motivation and development process
----------------------------------

I'm a huge fan of Snapchat, a photo/video sharing app that allows you to set
expiration times on the media you send to your friends. They can't open it
after they've seen it for up to 10 seconds, and if they take a screenshot, the
other party is notified.

I'm stunned and delighted by the fact that a simple
feature like auto-expiration of images can create such a compelling and
challenging service. And it's not just me: everyone I've told about Snapchat
who has used it has loved it, and as of last November more than one billion
snaps had been exchanged using the service.

But I hate closed products, so I set about figuring out how it worked. [Adam
Caudill](http://adamcaudill.com/2012/06/16/snapchat-api-and-security/) wrote an
excellent analysis of their HTTP-based API by using an HTTPS traffic sniffer.
Unfortunately this information now seems out of date. 

I ended up having to fetch the official Android client's app binary (APK),
decompiling the whole thing with a mix of tools (all of them seemed to produce
subtly incorrect output), tracing the control flow a bit, and then puzzling
through the process of creating their dreaded access tokens (called req\_token
in the HTTP calls).

This involved me paging through Fiddler, trying to generate SHA-256 hashes
seemingly at random, tearing my heart out, and weeping openly.

Their system is a bit unusual: it SHA-256 hashes two input values separately,
using a secret key contained in the binary, and then uses a fixed pattern
string to pull bytes from one or the other. The final composition of the two is
used in HTTP requests. Why not just append the values pre-hash? The security
profile would be similar.

Other things about the API that I've discovered so far:

- Speaks JSON over HTTPS, using POST as the verb
- Not made for human consumption; difficult error messaging
- Doesn't seem to support JSONP (i.e., callback parameter in post data is
	ignored)
- Blob (image/video) downloads are encrypted using AES. This code successfully
	decodes them before they are returned by the library. 

The apocalyptic future
----------------------

The TODO list is almost endless at this point:

- API likely to change
- DOCS!!!
- Figure out the /device call - what's this do? also device_id in /login resp
- Syncing (to mark snaps as seen)
- Image/video uploading
- Friend list maintenance
- Port to Javascript (probably via Node + NPM since their API doesn't seem to
	support JSONP)
- Add support for PHP composer
- Test framework

License
-------

MIT

Credits
-------

Made by Thomas Lackner <[@tlack](http://twitter.com/tlack)> with a lot of help
from [@adamcaudill](http://twitter.com/adamcaudill).  And of course none of
this would be possible without the inventiveness of the
[Snapchat](http://snapchat.com) team

