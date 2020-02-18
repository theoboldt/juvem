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
        ];
    }
    
    /**
     * Generate button for changes
     *
     * @param SupportsChangeTrackingInterface $entity
     * @return string
     */
    public function changeTrackingButton(SupportsChangeTrackingInterface $entity): string
    {
        if ($this->authorizationChecker->isGranted('READ', $entity)) {
            $changes = 0;
        } else {
            $changes = $this->repository->countAllByEntity($entity);
        }
        if ($changes === 0) {
            return '<span class="btn btn-default disabled" title="Änderungsverlauf untersuchen"><span class="glyphicon glyphicon-road"  aria-hidden="true"></span> <span class="hidden-xs">Keine Änderungen</span></span>';
        } else {
            $modalHtml = <<<HTML
<div class="modal fade" tabindex="-1" role="dialog" id="modalChangeTracking">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Änderungsverlauf</h4>
      </div>
      <div class="modal-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Zeitpunkt</th>
              <th>Operation</th>
              <th>Benutzer</th>
              <th>Änderungen</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="4" class="loading-text text-center">(Änderungsverlauf wird geladen)</td></tr>
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
            
            $url = $this->router->generate(
                'admin_change_overview',
                [
                    'entityId'        => $entity->getId(),
                    'classDescriptor' => EntityChangeRepository::convertClassNameForRoute(get_class($entity))
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            return $modalHtml .
                   '<button class="btn btn-default"' .
                   ' data-toggle="modal" data-target="#modalChangeTracking"' .
                   ' data-list-url="' . $url . '"' .
                   ' title="Änderungsverlauf untersuchen">' .
                   '<span class="glyphicon glyphicon-road" aria-hidden="true"></span> <span class="hidden-xs">' .
                   $changes . '&nbsp;Änderung' . (($changes > 1) ? 'en' : '') .
                   '</span></button>';
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'change_tracking_button';
    }
}