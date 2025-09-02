<?php
switch (http_response_code()) {
	case 100: return 'Continue';
	case 101: return 'Switching Protocols';
	case 102: return 'Processing'; // WebDAV; RFC 2518
	case 103: return 'Early Hints'; // RFC 8297
	case 200: return 'OK';
	case 201: return 'Created';
	case 202: return 'Accepted';
	case 203: return 'Non-Authoritative Information'; // since HTTP/1.1
	case 204: return 'No Content';
	case 205: return 'Reset Content';
	case 206: return 'Partial Content'; // RFC 7233
	case 207: return 'Multi-Status'; // WebDAV; RFC 4918
	case 208: return 'Already Reported'; // WebDAV; RFC 5842
	case 226: return 'IM Used'; // RFC 3229
	case 300: return 'Multiple Choices';
	case 301: return 'Moved Permanently';
	case 302: return 'Found'; // Previously "Moved temporarily"
	case 303: return 'See Other'; // since HTTP/1.1
	case 304: return 'Not Modified'; // RFC 7232
	case 305: return 'Use Proxy'; // since HTTP/1.1
	case 306: return 'Switch Proxy';
	case 307: return 'Temporary Redirect'; // since HTTP/1.1
	case 308: return 'Permanent Redirect'; // RFC 7538
	case 400: return 'Bad Request';
	case 401: return 'Unauthorized'; // RFC 7235
	case 402: return 'Payment Required';
	case 403: return 'Forbidden';
	case 404: return 'Not Found';
	case 405: return 'Method Not Allowed';
	case 406: return 'Not Acceptable';
	case 407: return 'Proxy Authentication Required'; // RFC 7235
	case 408: return 'Request Timeout';
	case 409: return 'Conflict';
	case 410: return 'Gone';
	case 411: return 'Length Required';
	case 412: return 'Precondition Failed'; // RFC 7232
	case 413: return 'Payload Too Large'; // RFC 7231
	case 414: return 'URI Too Long'; // RFC 7231
	case 415: return 'Unsupported Media Type'; // RFC 7231
	case 416: return 'Range Not Satisfiable'; // RFC 7233
	case 417: return 'Expectation Failed';
	case 418: return 'I\'m a teapot'; // RFC 2324; RFC 7168
	case 421: return 'Misdirected Request'; // RFC 7540
	case 422: return 'Unprocessable Entity'; // WebDAV; RFC 4918
	case 423: return 'Locked'; // WebDAV; RFC 4918
	case 424: return 'Failed Dependency'; // WebDAV; RFC 4918
	case 425: return 'Too Early'; // RFC 8470
	case 426: return 'Upgrade Required';
	case 428: return 'Precondition Required'; // RFC 6585
	case 429: return 'Too Many Requests'; // RFC 6585
	case 431: return 'Request Header Fields Too Large'; // RFC 6585
	case 451: return 'Unavailable For Legal Reasons'; // RFC 7725
	case 500: return 'Internal Server Error';
	case 501: return 'Not Implemented';
	case 502: return 'Bad Gateway';
	case 503: return 'Service Unavailable';
	case 504: return 'Gateway Timeout';
	case 505: return 'HTTP Version Not Supported';
	case 506: return 'Variant Also Negotiates'; // RFC 2295
	case 507: return 'Insufficient Storage'; // WebDAV; RFC 4918
	case 508: return 'Loop Detected'; // WebDAV; RFC 5842
	case 510: return 'Not Extended'; // RFC 2774
	case 511: return 'Network Authentication Required'; // RFC 6585
}
return http_response_code();

/*
Content from http://en.wikipedia.org/wiki/List_of_HTTP_status_codes

You may also want a list of unofficial codes:

	case 103: return 'Checkpoint';
	case 218: return 'This is fine'; // Apache Web Server
	case 419: return 'Page Expired'; // Laravel Framework
	case 420: return 'Method Failure'; // Spring Framework
	case 420: return 'Enhance Your Calm'; // Twitter
	case 430: return 'Request Header Fields Too Large'; // Shopify
	case 450: return 'Blocked by Windows Parental Controls'; // Microsoft
	case 498: return 'Invalid Token'; // Esri
	case 499: return 'Token Required'; // Esri
	case 509: return 'Bandwidth Limit Exceeded'; // Apache Web Server/cPanel
	case 526: return 'Invalid SSL Certificate'; // Cloudflare and Cloud Foundry's gorouter
	case 529: return 'Site is overloaded'; // Qualys in the SSLLabs
	case 530: return 'Site is frozen'; // Pantheon web platform
	case 598: return 'Network read timeout error'; // Informal convention
	case 440: return 'Login Time-out'; // IIS
	case 449: return 'Retry With'; // IIS
	case 451: return 'Redirect'; // IIS
	case 444: return 'No Response'; // nginx
	case 494: return 'Request header too large'; // nginx
	case 495: return 'SSL Certificate Error'; // nginx
	case 496: return 'SSL Certificate Required'; // nginx
	case 497: return 'HTTP Request Sent to HTTPS Port'; // nginx
	case 499: return 'Client Closed Request'; // nginx
	case 520: return 'Web Server Returned an Unknown Error'; // Cloudflare
	case 521: return 'Web Server Is Down'; // Cloudflare
	case 522: return 'Connection Timed Out'; // Cloudflare
	case 523: return 'Origin Is Unreachable'; // Cloudflare
	case 524: return 'A Timeout Occurred'; // Cloudflare
	case 525: return 'SSL Handshake Failed'; // Cloudflare
	case 526: return 'Invalid SSL Certificate'; // Cloudflare
	case 527: return 'Railgun Error'; // Cloudflare
 */