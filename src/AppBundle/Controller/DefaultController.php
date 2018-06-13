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

        $nbDays = $request->get('nbDays', 10);
        $dateFrom = new \DateTime($request->get('dateFrom', $request->get('dateFrom', date('Y-m-d'))));
        $query = $request->get('q', null);
        $dateTo = null;
        if($request->get('dateTo')) {
            $dateTo = new \DateTime($request->get('dateTo'));
        } else {
            $dateTo = clone $dateFrom;
            $dateTo->modify("-6 month");
        }

        $tags = $em->getRepository('AppBundle:Tag')->findAll();
        $tagAddForm = $this->createForm(ActivityTagAddType::class, array(), array('action' => $this->generateUrl('activity_tag'),'method' => 'POST'));

        /*$stats = $em->getRepository('AppBundle:Activity')->countDatesByInterval($dateFrom, $dateTo, $query);
        $total = 0;
        foreach($stats as $key => $stat) {
            $total += $stat['total'];
        }
        $statsMax = $total/count($stats) * 3;
        */


        return $this->render('default/index.html.twig',
            array('query' => $query,
                  'dateFrom' => $dateFrom->format('Y-m-d'),
                  'dateTo' => $dateTo->format('Y-m-d'),
                  'nbDays' => $nbDays,
                  'tags' => $tags,
                  'tagAddForm' => $tagAddForm->createView()
            )
        );
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

    /**
     * @Route("/config", name="config")
     */
    public function configAction()
    {
        $configExporter = $this->get('app.config.exporter');

        return $this->render('default/config.html.twig', array('config' => $configExporter->getYaml()));
    }

}
