<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\ActivityTagAddType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="timeline")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $nbDays = $request->get('nb', 10);
        $dateFrom = $request->get('date', date('Y-m-d'));
        $query = $request->get('q', null);

        $tags = $em->getRepository('AppBundle:Tag')->findAll();

        $tagAddForm = $this->createForm(ActivityTagAddType::class, array(), array('action' => $this->generateUrl('activity_tag'),'method' => 'POST'));

        return $this->render('default/index.html.twig', array('query' => $request->get('q'), 'dateFrom' => $dateFrom, 'nbDays' => $nbDays, 'tags' => $tags, 'tagAddForm' => $tagAddForm->createView()));
    }

    /**
     * @Route("/update", name="update")
     */
    public function updateAction()
    {

        return $this->render('default/update.html.twig');
    }

}
