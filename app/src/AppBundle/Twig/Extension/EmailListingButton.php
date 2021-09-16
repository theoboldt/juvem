<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Twig\Extension;


use AppBundle\Entity\ChangeTracking\EntityChangeRepository;
use AppBundle\Mail\SupportsRelatedEmailsInterface;
use AppBundle\Security\EmailVoter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EmailListingButton extends AbstractExtension
{
    
    /**
     * Router used to create the route to details page
     *
     * @var UrlGeneratorInterface
     */
    protected $router;
    
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    
    /**
     * EmailListingButton constructor.
     *
     * @param UrlGeneratorInterface $router
     * @param AuthorizationChecker $authorizationChecker
     */
    public function __construct(UrlGeneratorInterface $router, AuthorizationChecker $authorizationChecker)
    {
        $this->router               = $router;
        $this->authorizationChecker = $authorizationChecker;
    }
    
    
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'emailListingModal',
                [
                    $this,
                    'emailListingModal'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            ),
            new TwigFilter(
                'emailListingButton',
                [
                    $this,
                    'emailListingButton'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            )
        ];
    }
    
    /**
     * Provide the modal Html
     *
     * @return string
     */
    public function emailListingModal(): string
    {
        if (!$this->authorizationChecker->isGranted(EmailVoter::READ_EMAIL)) {
            return '';
        }
        
        return <<<HTML
<div class="modal fade" tabindex="-1" role="dialog" id="modalEmailListing">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">E-Mails</h4>
      </div>
      <div class="modal-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Beteilligte</th>
              <th>Datum</th>
              <th class="col-class">Betreff</th>
              <th>Verzeichnis</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5" class="loading-text text-center">(E-Mails werden abgerufen)</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <small style="float: left;">E-Mails, die vor August 2021 versendet wurden werden u.U. nicht angezeigt.</small>
        <button type="button" class="btn btn-default btn-refresh" data-title="Aktualisieren" data-toggle="tooltip"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>
HTML;
    }
    
    /**
     * Generate button for changes in default size
     *
     * @param SupportsRelatedEmailsInterface $entity
     * @param bool $includeModal If set to true, modal is included. Use this, if this tracking button is the only one
     *                           on the page
     * @return string
     */
    public function emailListingButton(SupportsRelatedEmailsInterface $entity, bool $includeModal = true): string
    {
        return $this->emailListingButtonWithSize($entity, null, $includeModal);
    }
    
    /**
     * Generate button for changes in specified size
     *
     * @param SupportsRelatedEmailsInterface $entity
     * @param string|null $size
     * @param bool $includeModal If set to true, modal is included. Use this, if this tracking button is the only one
     *                           on the page
     * @return string
     */
    public function emailListingButtonWithSize(
        SupportsRelatedEmailsInterface $entity, ?string $size, bool $includeModal = true
    ): string
    {
        if (!$this->authorizationChecker->isGranted(EmailVoter::READ_EMAIL, $entity)) {
            return '';
        }
        
        $btnClass = $size ? 'btn-' . $size : '';
        
        
        $url = $this->router->generate(
            'admin_email_related_list',
            [
                'entityId'        => $entity->getId(),
                'classDescriptor' => EntityChangeRepository::convertClassNameForRoute(get_class($entity))
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $result = '';
        if ($includeModal) {
            $result .= $this->emailListingModal();
        }
        $result .= '<button class="btn-email-listing btn btn-default ' . $btnClass . '"' .
                   ' data-target="#modalEmailListing"' .
                   ' data-url="' . $url . '"' .
                   ' data-type="' . get_class($entity) . '"' .
                   ' data-id="' . $entity->getId() . '"' .
                   ' title="Zugehörige E-Mails auflisten">' .
                   '<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span><span class="hidden-xs"> E-Mails </span><span class="caret-right"></span></button>';
        return $result;
    }
}