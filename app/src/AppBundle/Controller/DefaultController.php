<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Flash;
use AppBundle\Http\Annotation\CloseSessionEarly;
use AppBundle\ResponseHelper;
use AppBundle\Sitemap\Page;
use AppBundle\Sitemap\PageFactory;
use AppBundle\Twig\GlobalCustomization;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class DefaultController
{
    use DoctrineAwareControllerTrait, FlashBagAwareControllerTrait, AuthorizationAwareControllerTrait, RenderingControllerTrait, RoutingControllerTrait;
    
    /**
     * %customization.theme_color%
     *
     * @var string|null
     */
    private ?string $customizationThemeColor;
    
    /**
     * %kernel.root_dir%
     *
     * @var string
     */
    private string $kernelRootDir;
    
    /**
     * app.twig_global_customization
     *
     * @var GlobalCustomization
     */
    private GlobalCustomization $twigGlobalCustomization;
    
    /**
     * markdown.parser
     *
     * @var MarkdownParserInterface
     */
    private MarkdownParserInterface $markdownParser;
    
    /**
     * AdminMultipleController constructor.
     *
     * @param string|null $customizationThemeColor
     * @param string $kernelRootDir
     * @param Environment $twig
     * @param RouterInterface $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param ManagerRegistry $doctrine
     * @param SessionInterface $session
     * @param GlobalCustomization $twigGlobalCustomization
     * @param MarkdownParserInterface $markdownParser
     */
    public function __construct(
        ?string $customizationThemeColor,
        string $kernelRootDir,
        Environment $twig,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        SessionInterface $session,
        GlobalCustomization $twigGlobalCustomization,
        MarkdownParserInterface $markdownParser
    )
    {
        $this->customizationThemeColor = $customizationThemeColor;
        $this->kernelRootDir           = $kernelRootDir;
        $this->twig                    = $twig;
        $this->router                  = $router;
        $this->authorizationChecker    = $authorizationChecker;
        $this->tokenStorage            = $tokenStorage;
        $this->doctrine                = $doctrine;
        $this->session                 = $session;
        $this->twigGlobalCustomization = $twigGlobalCustomization;
        $this->markdownParser          = $markdownParser;
    }
    
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $repositoryFlash = $this->getDoctrine()->getRepository(Flash::class);
        $flashList       = $repositoryFlash->findValid();
        /** @var Flash $flash */
        foreach ($flashList as $flash) {
            $this->addFlash(
                $flash->getType(),
                $this->markdownParser->transformMarkdown($flash->getMessage())
            );
        }

        $repositoryEvent = $this->getDoctrine()->getRepository(Event::class);
        $eventList       = $repositoryEvent->findAllWithCounts();

        $user           = $this->getUser();
        $participations = [];
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        $activeCount = 0;
        /** @var Event $event */
        foreach ($eventList as $event) {
            if ($event->isActive()) {
                ++$activeCount;
            }
        }

        $customization = $this->twigGlobalCustomization;
        $description = sprintf('Überblick über alle Veranstaltungen von %s. ', $customization->organizationName());
        switch ($activeCount ) {
            case 0:
                $description .= 'Anmeldungen können hier abgegeben werden.';
                break;
            case 1:
                $description .= 'Anmeldungen können derzeit für eine Veranstaltung abgegeben werden.';
                break;
            default:
                $description .= 'Anmeldungen können derzeit für '.$activeCount.' Veranstaltungen abgegeben werden.';
                break;
        }

        return $this->render(
            'default/index.html.twig',
            [
                'events'          => $eventList,
                'pageDescription' => $description,
                'participations'  => $participations
            ]
        );
    }

    /**
     * @CloseSessionEarly
     * @Route("/heartbeat", name="heartbeat")
     */
    public function heartbeatAction()
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @CloseSessionEarly
     * @Route("/ads.txt")
     * @Route("/css/all.css.map")
     * @Route("/js/all.js.map")
     * @Route("/css/all.min.css.map")
     * @Route("/js/all.min.js.map")
     * @Route("/js/ekko-lightbox.js.map")
     * @Route("/robots.txt")
     * @Route("/favicon.(ico|png|gif)")
     * @Route("/apple-touch-icon.png")
     * @Route("apple-touch-icon-{a}x{b}{c}.png", requirements={"a" = "\d+", "b" = "\d+", "c" = "-precomposed|"})
     * @Route("/safari-pinned-tab.svg")
     * @Route("/HNAP1")
     */
    public function ressourceUnavailableAction()
    {
        return new Response(null, Response::HTTP_GONE);
    }

    /**
     * @CloseSessionEarly
     * @Route("/{url2}",
     * requirements={"url2" = "(?:test|old|wp\/|blog|wordpress|wp-content|wp-includes|wp-admin|utility\/convert)(?:.*)$"})
     * @Route("/wp-login.php")
     * @Route("/jm-ajax/upload_file/")
     * @Route("/js/")
     */
    public function honeyPotQualifiedAction()
    {
        return new Response(null, Response::HTTP_GONE);
    }

    /**
     * @CloseSessionEarly
     * @Route("/apple-app-site-association")
     * @Route("/.well-known/apple-app-site-association")
     */
    public function appleUniversalLinksAction()
    {
        return new JsonResponse(['applinks' => ['apps' => [], 'details' => []]]);
    }

    /**
     * @CloseSessionEarly
     * @Route("")
     * @Route("/new")
     * @Route("/v2")
     * @Route("/v1")
     * @Route("/wp1")
     * @Route("/temp")
     * @Route("/tmp")
     * @Route("/home")
     * @Route("/demo")
     * @Route("/backup")
     * @Route("/site")
     * @Route("/main")
     * @Route("/Old")
     * @Route("/m")
     * @Route("/mobile")
     * @Route("/index.php")
     */
    public function redirectToHomeAction()
    {
        return $this->redirectToRoute('homepage');
    }

    /**
     * @CloseSessionEarly
     * @Route("/login_check", methods={"GET"})
     */
    public function loginCheckFallbackAction()
    {
        return $this->redirectToRoute('homepage');
    }
    /**
     * @CloseSessionEarly
     * @Route("/crossdomain.xml")
     * @Route("/clientaccesspolicy.xml")
     * @Route("/.well-known/assetlinks.json")
     * @Route("/asset-manifest.json")
     */
    public function unsupportedAction()
    {
        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * License redirect
     *
     * @CloseSessionEarly
     * @Route("/LICENSE")
     * @Route("/license")
     * @Route("/license.md")
     * @Route("/.env")
     * @return Response
     */
    public function licenseRedirectAction(): Response
    {
        return $this->redirectToRoute('app_license');
    }
    
    /**
     * License text action
     *
     * @CloseSessionEarly
     * @Route("/license.txt", name="app_license")
     * @return Response
     */
    public function licenseAction(): Response
    {
        return $this->provideTextFileContentIfExists($this->kernelRootDir . '/../LICENSE');
    }
    
    /**
     * Readme redirect
     *
     * @CloseSessionEarly
     * @Route("/README")
     * @Route("/readme.txt")
     * @Route("/README.md")
     * @return Response
     */
    public function readmeRedirectAction(): Response
    {
        return $this->redirectToRoute('app_license');
    }
    
    /**
     * Readme text action
     *
     * @CloseSessionEarly
     * @Route("/readme.md", name="app_license")
     * @return Response
     */
    public function readmeAction(): Response
    {
        return $this->provideTextFileContentIfExists($this->kernelRootDir . '/../README.md');
    }
    
    /**
     * Create a http response for transmitted file or not found
     *
     * @param string $path
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     */
    private function provideTextFileContentIfExists(string $path)
    {
        if (file_exists($path) && is_readable($path)) {
            $response = new BinaryFileResponse($path);
            
            ResponseHelper::configureAttachment(
                $response,
                basename($path),
                'text/plain'
            );
            return $response;
        } else {
            throw new NotFoundHttpException('File not found');
        }
    }

    /**
     * @CloseSessionEarly
     * @Route("/contribute.json")
     */
    public function contributeAction()
    {
        return new JsonResponse(
            [
                'name'        => 'Juvem',
                'description' => 'A symfony based web application to manage events, participants, employees and newsletters',
                'repository'  => [
                    'url'     => 'https://github.com/theoboldt/juvem.git',
                    'license' => 'MIT'
                ],
                'participate' => [
                    'home' => 'https://github.com/theoboldt/juvem',
                    'irc' => '',
                    'irc-contacts' => []
                ],
                'keywords'    => [
                    'PHP',
                    'Symfony',
                    'Twitter Bootstrap',
                    'jQuery'
                ],
                'bugs'        => [
                    'list'   => 'https://github.com/theoboldt/juvem/issues',
                    'report' => 'https://github.com/theoboldt/juvem/issues/new'
                ],
                'urls'        => [
                    'prod' => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]
        );
    }


    /**
     * @CloseSessionEarly
     * @Route("/sitemap.xml")
     */
    public function sitemapAction()
    {
        $configDir   = $this->kernelRootDir . '/config/';
        $router      = $this->router;
        $pageFactory = new PageFactory($router);

        $eventRepository   = $this->getDoctrine()->getRepository(Event::class);
        $eventLastModified = $eventRepository->lastModified();

        $pages = [
            $pageFactory->createForPath(
                $configDir . 'conditions-of-travel-content.html.twig', 'conditions_of_travel', 0.2,
                Page::CHANGEFREQ_MONTHLY
            ),
        ];

        /** @var Event $event */
        foreach ($eventRepository->findAllOrderedByTitle(true) as $event) {
            $eid  = $event->getEid();
            $page = $pageFactory->create('event_public_detail', ['eid' => $eid], 0.8, $event->getModifiedAt());
            if ($event->getImageFilename()) {
                $page->addImage(
                    $pageFactory->generateRoute('event_image', ['eid' => $eid, 'width' => 545, 'height' => 545])
                );
            }
            if ($event->isActive()) {
                $page = $pageFactory->create('event_public_participate', ['eid' => $eid], 0.8, $event->getModifiedAt());
            }
            $pages[] = $page;
        }
        
        $response = new Response(
            '',
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/xml'
            ]
        );

        return $this->render(
            'sitemap.xml.twig',
            [
                'pages'           => $pages,
                'lastModHomePage' => $eventLastModified,
            ],
            $response
        );
    }
    
    /**
     * @CloseSessionEarly
     * @Route("/manifest.json")
     */
    public function manifestAction()
    {
        $webDir = $this->kernelRootDir . '/../web';
        $icons  = [];
        foreach (new \DirectoryIterator($webDir) as $fileinfo) {
            $filename = $fileinfo->getFilename();
            if (!$fileinfo->isDot() && preg_match('/android-chrome-(\d+)x(\d+).png/', $filename, $details)) {
                $icons[] = [
                    'src'  => '/' . $filename,
                    'size' => $details[1] . 'x' . $details[1],
                    'type' => 'image/png',
                ];
            }
        }
        
        $manifest = [
            'name'      => $this->twigGlobalCustomization->organizationName(),
            'icons'     => $icons,
            'start_url' => '/',
            'display'   => 'browser',
        ];
        $color    = $this->customizationThemeColor;
        if ($color) {
            $manifest['theme_color'] = $color;
        }
        
        return new JsonResponse($manifest);
    }
}
