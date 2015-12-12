<?php
namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class EventController extends Controller
{

    /**
     * @Route("/event/new")
     */
    public function newAction(Request $request)
    {
        dump(\Locale::getDefault());

        // create a task and give it some dummy data for this example
        $event = new Event();
        $event->setTitle('Title');
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));
        
        $form = $this->createFormBuilder($event)
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add('startDate', DateType::class, array('label' => 'Beginn'))
            ->add('endDate', DateType::class, array('label' => 'Ende'))
            ->add('save', SubmitType::class, array('label' => 'Veranstaltung erstellen'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('event/list');
        }

        return $this->render('event/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}