<?php
/**
 * @author Ryan Castle <ryan@hutsix.com.au>
 * @since 2019-01-08
 */

namespace App;

/**
 * Plain text response
 */
class Response extends \Symfony\Component\HttpFoundation\Response
{
    public function __construct(string $content = '', int $status = 200, array $headers = array())
    {
        $headers['Content-Type'] = 'text/plain';
        parent::__construct($content, $status, $headers);
    }
}

