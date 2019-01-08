<?php
/**
 * @author Ryan Castle <ryan@hutsix.com.au>
 * @since 2019-01-08
 */

namespace App\Controller;

use App\Response;
use Rollerworks\Component\Version\Version;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("")
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("", name="index")
     */
    public function index(): Response
    {
        return new Response("Usage:\ncurl -s bump.run/patch/1.0.0\ncurl -s bump.run/patch -d @VERSION -o VERSION");
    }
    /**
     * @Route("/{stability}/{version}", name="bump")
     * @Route("/{stability}", name="bump_post")
     * @param Request $request
     * @param string $stability
     * @param string $version
     * @return Response
     */
    public function bump(Request $request, string $stability, string $version = null): Response
    {
        $version = $version ?: (string)$request->getContent() ?: '0.0.0';
        try {
            $current = Version::fromString($version);

            return new Response($current->getNextIncreaseOf($stability));
        } catch (\Exception $exception) {
            return new Response($version, 500, ['X-Exception' => $exception->getMessage()]);
        }
    }
}
