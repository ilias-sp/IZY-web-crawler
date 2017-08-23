<?php

class IZY_crawler {

    private $config;

    private $logger;

    private $discovered_urls = array();

    private $initial_url;
    private $initial_domain;

    private $http_requests_counter = 0;

    private $execution_start;

    //
    //
    //

    public function __construct($config, $logger) {

        $this->config = $config;
        $this->logger = $logger;

    }

    public function main($url, $depth = 100) {

        $this->execution_start = date('U');
        $this->logger->info( __FUNCTION__ . ' - START, url = ' . $url);

        $this->initial_url = $url;
        $this->_extract_domain($url);

        // $this->discovered_urls[] = $url;

        $this->_crawl_page($url, $this->config['max_recursion_depth']);

        $this->logger->info( __FUNCTION__ . ' - END');
        $this->logger->info( __FUNCTION__ . ' - Total http requests executed = ' . $this->http_requests_counter);
        $this->logger->info( __FUNCTION__ . ' - Discovered urls count = ' . count($this->discovered_urls)) ;
        $this->logger->info( __FUNCTION__ . ' - Discovered urls = ', $this->discovered_urls);
        $this->logger->info( __FUNCTION__ . ' - Total run time = ' . (date('U') - $this->execution_start) . ' seconds.');
        
    }

    private function _crawl_page($url, $depth) {

        $url = $this->_sanitize_url($url);

        if ($depth === 0 && $this->config['enable_crawl_max_recursion_depth'] === TRUE ) {
        
            $this->logger->info( __FUNCTION__ . ' - Discarding URL: ' . $url . ', due to depth exhaustion.');
            return FALSE;

        }

        if ( array_search($url, $this->discovered_urls) !== FALSE ) {
            
            $this->logger->info( __FUNCTION__ . ' - Discarding URL: ' . $url . ', has already been discovered.');
            return FALSE;
                
        }

        $this->discovered_urls[] = $url;
        $this->_write_discovered_urls_to_file();

        //

        $this->logger->info( __FUNCTION__ . ' - Fetching URL: ' . $url . ', current depth = ' . $depth);
        $web_response = $this->_fetch_url_page($url);

        if ($web_response[0] === 200) {

            $dom = new DOMDocument('1.0');

            @$dom->loadHTML($web_response[1]);

            $links = $dom->getElementsByTagName('a');

            foreach ($links as $element) {

                $href = $element->getAttribute('href');

                if (0 !== strpos($href, 'http')) {

                    if (0 === strpos($href, '/')) {

                        $parsed_url = parse_url($url);

                        $href = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $href;

                    }
                    else {
                        
                        // if href is not of format: #xxx, append to current url. else ignore the $url:
                        
                        if ($this->_validate_urls_path($href) === TRUE) {

                            $href = $url . '/' . $href;

                        }
                        else {

                            GOTO ENDOFANCHORPROCESSING;

                        }

                    }

                }
                //
                // check if link is from the same domain, and if not allowed by config, dont process it:
                // 
                if ($this->config['allow_discovered_urls_from_different_domain'] === FALSE) {

                    if ($this->_validate_urls_domain($href) === TRUE) {

                        $this->_crawl_page($href, $depth - 1);

                    }
                    else {
                        
                        $this->logger->info( __FUNCTION__ . ' - discovered URL = ' . $href . ' was discarded, different domain.');

                    }
                    
                }
                else {
                    
                    $this->_crawl_page($href, $depth - 1);

                }

                ENDOFANCHORPROCESSING:
            }

        }

        return FALSE;

    }

    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------------------------

    private function _extract_domain($url) {

        $parsed_url = parse_url($url);

        if ($parsed_url === FALSE) {

            $this->logger->error( __FUNCTION__ . ' - Error occured while analyzing the target URL: ' . $url);
            throw new \Exception("Error occured while analyzing the target URL: " . $url);

        }

        $this->initial_domain = mb_strtolower($parsed_url['host']);

        $this->logger->error( __FUNCTION__ . ' - Set the initial_domain to: ' . $this->initial_domain);

        return TRUE;
        
    }

    private function _fetch_url_page($url) {
    
        usleep(250000);
        
        $ch = curl_init();

        if ($this->config['http_use_random_http_agent'] === TRUE) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_select_random_user_agent());
        }
        
        if ($this->config['http_allow_followlocation'] === TRUE) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }      
        
        if ($this->config['use_http_authentication'] === TRUE) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->config['http_username'] . ":" . $this->config['http_password']);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $web_response = curl_exec($ch);
        $http_ret_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->http_requests_counter++;

        if ($http_ret_code === 200) {
            $this->logger->info(__FUNCTION__ . ' - http response code = ' . $http_ret_code);
        }
        else {
            $this->logger->error(__FUNCTION__ . ' - http response code = ' . $http_ret_code);
        }

        
        if ($this->config['write_http_response_to_file'] === TRUE) {
            $this->logger->debug(__FUNCTION__ . ' - curl_getinfo = ' , curl_getinfo($ch));
        }

        if ($this->config['write_http_bodies_to_file'] === TRUE) {
            $this->logger->debug(__FUNCTION__ . ' - ' . $web_response);  
        }        
        
        return array($http_ret_code, $web_response);
    }
    
    private function _select_random_user_agent() {

        // from: https://udger.com/resources/ua-list
        //

        $user_agents_array = array(
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:48.0) Gecko/20100101 Firefox/48.0', 
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.38 Safari/537.36',
            'Mozilla/5.0 (IE 11.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C; rv:11.0) like Gecko',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/600.3.10 (KHTML, like Gecko) Version/8.0.3 Safari/600.3.10',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36 OPR/32.0.1948.25',
            'Mozilla/5.0 (IE 11.0; Windows NT 6.3; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko',
            'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)',
            'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1467.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.0) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.792.0 Safari/535.1',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0'
            );

        $rand_keys = array_rand($user_agents_array);

        return $user_agents_array[$rand_keys];

    }

    private function _sanitize_url($url) {

        $url = preg_replace('/#$/', '', $url);
        $url = preg_replace('/[\/]+$/', '/', $url);

        return $url;
    }

    private function _write_discovered_urls_to_file() {

        if (file_put_contents('report/' . $this->config['discovered_urls_report_file'], json_encode($this->discovered_urls)) === FALSE)
        {
            $this->logger->error(__FUNCTION__ . ' - could not write discovered URLs to file: ' . 'report/' . $this->config['discovered_urls_report_file']);  
            throw new \Exception("Error occured while flushing discovered URLs to file: " . 'report/' . $this->config['discovered_urls_report_file']);
        }
        //
        return TRUE;
    }

    private function _validate_urls_domain($href) {

        $parsed_url = parse_url($href);

        if ($parsed_url === FALSE) {

            $this->logger->error( __FUNCTION__ . ' - Error occured while analyzing the eligible for discovery URL: ' . $href);

            return FALSE;

        }

        return (mb_strtolower($parsed_url['host']) === $this->initial_domain ) ? TRUE : FALSE;

    }

    private function _validate_urls_path($path) {

        if (preg_match('/#/', $path) === 1) {

            $this->logger->info( __FUNCTION__ . ' - Path: ' . $path . ' contains invalid character #.');

            return FALSE;

        }

        return TRUE;

    }



}
