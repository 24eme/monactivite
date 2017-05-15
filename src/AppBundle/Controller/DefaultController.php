<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\ActivityTagAddType;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="timeline")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $nbDays = $request->get('nb', 10);
        $dateFrom = new \DateTime($request->get('date', $request->get('date', date('Y-m-d H:i:s'))));
        $query = $request->get('q', null);
        $duration = $request->get('duration', 6);
        $dateTo = null;
        if($request->get('dateTo')) {
            $dateTo = new \DateTime($request->get('dateTo'));
        }
        if(!$dateTo){
            $dateTo = clone $dateFrom;
            $dateTo->modify("-".$duration." month");
        }

        $tags = $em->getRepository('AppBundle:Tag')->findAll();

        $stats = $em->getRepository('AppBundle:Activity')->countDatesByInterval($dateFrom, $dateTo, $query);
        $total = 0;
        foreach($stats as $key => $stat) {
            $total += $stat['total'];
        }
        $statsMax = $total/count($stats) * 3;

        $tagAddForm = $this->createForm(ActivityTagAddType::class, array(), array('action' => $this->generateUrl('activity_tag'),'method' => 'POST'));

        return $this->render('default/index.html.twig', array('query' => $request->get('q'), 'dateFrom' => $dateFrom->format('Y-m-d  H:i:s'), 'stats' => $stats, 'statsMax' => $statsMax, 'nbDays' => $nbDays, 'tags' => $tags, 'duration' => $duration, 'tagAddForm' => $tagAddForm->createView()));
    }

    /**
     * @Route("/update", name="update")
     */
    public function updateAction()
    {

        return $this->render('default/update.html.twig');
    }

    /**
     * @Route("/execute-update", name="execute_update")
     */
    public function executeUpdateAction()
    {
        $mm = $this->get('app.manager.main');

        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        $mm->executeAll($output);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContent($output->fetch());

        return $response;
    }

}
