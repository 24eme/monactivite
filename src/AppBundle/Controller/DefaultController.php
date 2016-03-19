<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="timeline")
     */
    public function indexAction(Request $request)
    {
        $nbDays = $request->get('nb', 10);
        $dateFrom = $request->get('date', date('Y-m-d'));
        $query = $request->get('q', null);

        return $this->render('default/index.html.twig', array('query' => $request->get('q'), 'dateFrom' => $dateFrom, 'nbDays' => $nbDays));
    }

    /**
     * @Route("/update", name="update")
     */
    public function updateAction()
    {

        return $this->render('default/update.html.twig');
    }

}
