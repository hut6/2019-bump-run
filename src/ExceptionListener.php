<?php
/**
 * @author Ryan Castle <ryan@hutsix.com.au>
 * @since 2019-01-08
 */

namespace App;


use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $event->setResponse(new Response($event->getException()->getMessage(), 500));
    }
}
