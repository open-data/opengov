#
# robots.txt
#
# This file is to prevent the crawling and indexing of certain parts
# of your site by web crawlers and spiders run by sites like Yahoo!
# and Google. By telling these "robots" where not to go on your site,
# you save bandwidth and server resources.
#
# This file will be ignored unless it is at the root of your host:
# Used:    http://example.com/robots.txt
# Ignored: http://example.com/site/robots.txt
#
# For more information about the robots.txt standard, see:
# http://www.robotstxt.org/robotstxt.html

User-agent: *
Crawl-delay: 20
# Directories
Disallow: /core/
Disallow: /libraries/
Disallow: /modules/
Disallow: /profiles/
Disallow: /scripts/
Disallow: /sites/
Disallow: /themes/
# Files
Disallow: /README.md
Disallow: /README.txt
Disallow: /update.php
Disallow: /web.config
# Paths (clean URLs)
Disallow: /admin/
Disallow: /en/admin/
Disallow: /fr/admin/
Disallow: /comment/reply/
Disallow: /en/comment/reply/
Disallow: /fr/comment/reply/
Disallow: /filter/tips
Disallow: /en/filter/tips
Disallow: /fr/filter/tips
Disallow: /node/add/
Disallow: /en/node/add/
Disallow: /fr/node/add/
Disallow: /search/
Disallow: /en/search/
Disallow: /fr/search/
Disallow: /search/ati*
Disallow: /en/search/ati*
Disallow: /fr/search/ati*
Disallow: /user/register
Disallow: /en/user/register
Disallow: /fr/user/register
Disallow: /user/password
Disallow: /en/user/password
Disallow: /fr/user/password
Disallow: /user/login
Disallow: /en/user/login
Disallow: /fr/user/login
Disallow: /user/logout
Disallow: /en/user/logout
Disallow: /fr/user/logout
Disallow: /media/oembed
Disallow: /en/media/oembed
Disallow: /fr/media/oembed
Disallow: /*/media/oembed
# Paths (no clean URLs)
Disallow: /index.php/admin/
Disallow: /?q=admin/
Disallow: /index.php/comment/reply/
Disallow: /?q=comment/reply/
Disallow: /index.php/filter/tips
Disallow: /?q=filter/tips/
Disallow: /index.php/node/add/
Disallow: /?q=node/add/
Disallow: /index.php/search/
Disallow: /?q=search/
Disallow: /index.php/user/password
Disallow: /?q=user/password/
Disallow: /index.php/user/register
Disallow: /?q=user/register/
Disallow: /index.php/user/login
Disallow: /?q=user/login/
Disallow: /index.php/user/logout
Disallow: /?q=user/logout/
Disallow: /index.php/media/oembed
Disallow: /index.php/*/media/oembed

# Extras
# no access for table sorting paths or any paths that have parameters.
Disallow: /*?sort*
Disallow: /*&sort*
Disallow: /*?solrsort*
Disallow: /*&solrsort*

# no access to profile listings
Disallow: /profile
Disallow: /profile/*
Disallow: /?q=profile
Disallow: /?q=profile/*
