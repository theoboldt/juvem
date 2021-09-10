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
use AppBundle\Entity\ChangeTracking\SupportsChangeTrackingInterface;
use AppBundle\Security\EventVoter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ChangeTrackingButton extends AbstractExtension
{
    
    /**
     * Router used to create the route to details page
     *
     * @var UrlGeneratorInterface
     */
    protected $router;
    
    /**
     * @var EntityChangeRepository
     */
    private $repository;
    
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    
    /**
     * ChangeTrackingButton constructor.
     *
     * @param EntityChangeRepository $repository
     * @param AuthorizationChecker $authorizationChecker
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        EntityChangeRepository $repository, AuthorizationChecker $authorizationChecker, UrlGeneratorInterface $router
    )
    {
        $this->repository           = $repository;
        $this->authorizationChecker = $authorizationChecker;
        $this->router               = $router;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'insertTrackingModal',
                [
                    $this,
                    'insertTrackingModal'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            ),
            new TwigFilter(
                'changeTrackingButton',
                [
                    $this,
                    'changeTrackingButton'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            ),
            new TwigFilter(
                'smallChangeTrackingButton',
                [
                    $this,
                    'smallChangeTrackingButton'
                ],
                [
                    'pre_escape' => 'html',
                    'is_safe'    => ['html']
                ]
            ),
        ];
    }
    
    /**
     * Provide the modal Html
     *
     * @return string
     */
    public function insertTrackingModal(): string
    {
        return <<<HTML
<div class="modal fade" tabindex="-1" role="dialog" id="modalChangeTracking">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Änderungsverlauf <small></small></h4>
      </div>
      <div class="modal-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Zeitpunkt</th>
              <th class="col-class">Subjekt</th>
              <th><abrr title="Operation">O</abrr></th>
              <th>Benutzer</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5" class="loading-text text-center">(Änderungsverlauf wird geladen)</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>
HTML;
    }
    
    /**
     * Generate button for changes in specified size
     *
     * @param SupportsChangeTrackingInterface $entity
     * @param string|null $size
     * @param bool $includeModal If set to true, modal is included. Use this, if this tracking button is the only one
     *                           on the page
     * @return string
     */
    private function changeTrackingButtonWithSize(
        SupportsChangeTrackingInterface $entity, ?string $size, bool $includeModal = true
    ): string
    {
        if (!$this->authorizationChecker->isGranted(EventVoter::READ, $entity)) {
            $changes = 0;
        } else {
            $changes = $this->repository->countAllByEntity($entity);
        }
        switch ($size) {
            case 'xs':
                $btnClass = 'btn btn-default btn-xs';
                $btnTitle = '';
                break;
            default:
                $btnClass = 'btn btn-default';
                if ($changes) {
                    $btnTitle = ' <span class="hidden-xs">' .
                                $changes . '&nbsp;Änderung' . (($changes > 1) ? 'en' : '') .
                                ' <span class="caret-right"></span></span>';
                } else {
                    $btnTitle = '<span class="hidden-xs">Keine Änderungen</span>';
                }
                break;
        }
        
        
        if ($changes === 0) {
            return '<span class="' . $btnClass .
                   ' disabled" title="Änderungsverlauf untersuchen (Keine Änderungen)"><span class="glyphicon glyphicon-road" aria-hidden="true"></span> ' .
                   $btnTitle . '</span>';
        } else {
            
            $url = $this->router->generate(
                'admin_change_overview',
                [
                    'entityId'        => $entity->getId(),
                    'classDescriptor' => EntityChangeRepository::convertClassNameForRoute(get_class($entity))
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            
            $result = '';
            if ($includeModal) {
                $result .= $this->insertTrackingModal();
            }
            $result .= '<button class="' . $btnClass . '"' .
                       ' data-toggle="modal" data-target="#modalChangeTracking"' .
                       ' data-list-url="' . $url . '"' .
                       ' title="Änderungsverlauf untersuchen">' .
                       '<span class="glyphicon glyphicon-road" aria-hidden="true"></span>' . $btnTitle . '</button>';
            return $result;
        }
    }
    
    /**
     * Generate button for changes in default size
     *
     * @param SupportsChangeTrackingInterface $entity
     * @param bool $includeModal If set to true, modal is included. Use this, if this tracking button is the only one
     *                           on the page
     * @return string
     */
    public function changeTrackingButton(SupportsChangeTrackingInterface $entity, bool $includeModal = true): string
    {
        return $this->changeTrackingButtonWithSize($entity, null, $includeModal);
    }
    
    /**
     * Generate button for changes in small size
     *
     * @param SupportsChangeTrackingInterface $entity
     * @param bool $includeModal If set to true, modal is included. Use this, if this tracking button is the only one
     *                           on the page
     * @return string
     */
    public function smallChangeTrackingButton(SupportsChangeTrackingInterface $entity, bool $includeModal = true
    ): string
    {
        return $this->changeTrackingButtonWithSize($entity, 'xs', $includeModal);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'change_tracking_button';
    }
}