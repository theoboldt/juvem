<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Newsletter;

use AppBundle\Controller\AuthorizationAwareControllerTrait;
use AppBundle\Controller\DoctrineAwareControllerTrait;
use AppBundle\Controller\FlashBagAwareControllerTrait;
use AppBundle\Controller\FormAwareControllerTrait;
use AppBundle\Controller\RenderingControllerTrait;
use AppBundle\Controller\RoutingControllerTrait;
use AppBundle\Manager\NewsletterManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

abstract class AbstractController
{
    use DoctrineAwareControllerTrait, RenderingControllerTrait, AuthorizationAwareControllerTrait, FormAwareControllerTrait, FlashBagAwareControllerTrait, RoutingControllerTrait;
    
    /**
     * feature.newsletter
     *
     * @var bool
     */
    private bool $newsletterFeature;
    
    /**
     * customization.organization_name
     *
     * @var string
     */
    protected string $customizationOrganizationName;
    
    /**
     * fos_user.util.token_generator
     *
     * @var TokenGeneratorInterface
     */
    protected TokenGeneratorInterface $fosTokenGenerator;
    
    /**
     * app.newsletter_manager
     *
     * @var NewsletterManager
     */
    protected NewsletterManager $newsletterManager;
    
    /**
     * doctrine.orm.entity_manager
     *
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $ormManager;
    
    /**
     * security.csrf.token_manager
     *
     * @var CsrfTokenManagerInterface
     */
    protected CsrfTokenManagerInterface $csrfTokenManager;
    
    /**
     * AbstractController constructor.
     *
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     * @param FormFactoryInterface $formFactory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     * @param bool $newsletterFeature
     * @param string $customizationOrganizationName
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param SessionInterface $session
     * @param TokenGeneratorInterface $fosTokenGenerator
     * @param NewsletterManager $newsletterManager
     * @param EntityManagerInterface $ormManager
     */
    public function __construct(
        Environment $twig,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        bool $newsletterFeature,
        string $customizationOrganizationName,
        CsrfTokenManagerInterface $csrfTokenManager,
        SessionInterface $session,
        TokenGeneratorInterface $fosTokenGenerator,
        NewsletterManager $newsletterManager,
        EntityManagerInterface $ormManager
    )
    {
        $this->twig                          = $twig;
        $this->doctrine                      = $doctrine;
        $this->formFactory                   = $formFactory;
        $this->authorizationChecker          = $authorizationChecker;
        $this->tokenStorage                  = $tokenStorage;
        $this->router                        = $router;
        $this->newsletterFeature             = $newsletterFeature;
        $this->fosTokenGenerator             = $fosTokenGenerator;
        $this->newsletterManager             = $newsletterManager;
        $this->ormManager                    = $ormManager;
        $this->csrfTokenManager              = $csrfTokenManager;
        $this->session                       = $session;
        $this->customizationOrganizationName = $customizationOrganizationName;
    }
    
    
    /**
     * Throws an exception if newsletter feature is disabled
     *
     * @return void
     * @throws NotFoundHttpException    If feature is disabled
     */
    public function dieIfNewsletterNotEnabled()
    {
        if (!$this->newsletterFeature) {
            throw new NotFoundHttpException('Newsletter feature is disabled');
        }
    }
}