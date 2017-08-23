## IZY web crawler

# installing dependancies

`composer install`

this will download all dependancies (monolog logging library)

# configuring before the execution:

check the conf/config.php for all configurable options available. clarifications:

`$config['write_http_response_to_file'] = TRUE;` - to log the response's http headers etc
`$config['write_http_bodies_to_file'] = FALSE;` - to log the response's http page

`$config['enable_crawl_max_recursion_depth'] = TRUE;` - set to FALSE if you want to guarantee full scan of the site.
`$config['max_recursion_depth'] = 5;` - setting previous parameter to TRUE, with this one you can configure the depth of recursions to be executed.

About recursive search: when crawling a page, crawler will discover URLs, and for each of them will recursively fetch a new page, discovering new links. By setting a maximum depth, you can control how deep the crawler can go.

`$config['http_use_random_http_agent'] = FALSE;` - use a random user agent on each http request
`$config['use_http_authentication'] = FALSE;` - if the server needs username/password (on apache level, not to be confused with user login to a website)
`$config['http_username'] = NULL;` - if use_http_authentication is set to TRUE
`$config['http_password'] = NULL;`- if use_http_authentication is set to TRUE

`$config['http_allow_followlocation'] = TRUE;` - not tested. is meant to allow curl to follow a redirect if a URL is redirecting us to another URL.
`$config['allow_discovered_urls_from_different_domain'] = FALSE;` - if a page contains links from another server/domain, dont crawl that domain. Setting to TRUE not yet supported.

`$config['discovered_urls_report_file'] = 'discovered_urls.txt';` - file to write the results in json encoded format.


# running the crawler against a site:

edit the wrapper.php, and add the domain you want to run the crawler against.
run the crawler by:

`php wrapper.php`

check the log generated, and the report file under report folder.

