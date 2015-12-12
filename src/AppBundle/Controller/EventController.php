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
        $task = new Event();
        $task->setTitle('Title');
        $task->setStartDate(new \DateTime('today'));
        $task->setEndDate(new \DateTime('tomorrow'));
        
        $form = $this->createFormBuilder($task)
            ->add('title', TextType::class)
            ->add('startDate', DateType::class)
            ->add('endDate', DateType::class)
            ->add('save', SubmitType::class, array('label' => 'Create Event'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('task_success');
        }

        return $this->render('event/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}