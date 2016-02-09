<?php

namespace AppBundle\Twig\Extension;

class ModalDialog extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'modalDialog',
                array($this,
                      'modalDialog'
                ),
                array('pre_escape'        => 'html',
                      'is_safe'           => array('html'),
                      'needs_environment' => true
                )
            ),
        );

    }

    /**
     * Create html for a bootstrap glyph
     *
     * @param \Twig_Environment $twig
     * @return string Html bootstrap glyph snippet
     * @internal param string $glyph Glyph name
     */
    public function modalDialog(\Twig_Environment $twig, $title)
    {
        $data = array('modalId'    => 'xxx',
                      'modalTitle' => $title,
                      'modalYes'   => 1,
                      'modalNo'    => 2
        );

        return $twig->render('modal/dialog.html.twig', $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'modal_dialog';
    }
}
