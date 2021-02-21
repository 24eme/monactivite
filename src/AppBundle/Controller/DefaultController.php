<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\ActivityTagAddType;
use AppBundle\Form\ActivityTagDeleteType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="timeline")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $nbDays = $request->get('nbDays', 31);
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
        $tagAddForm = $this->createForm(ActivityTagAddType::class, array(), array('action' => $this->generateUrl('activity_tag_add')));
        $tagRemoveForm = $this->createForm(ActivityTagDeleteType::class, array(), array('action' => $this->generateUrl('activity_tag_delete')));

        //$stats = $em->getRepository('AppBundle:Activity')->countDatesByInterval($dateFrom, $dateTo, $query);
        /*$total = 0;
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
                  'tagAddForm' => $tagAddForm->createView(),
                  'tagRemoveForm' => $tagRemoveForm->createView(),
                  'viewMode' => \AppBundle\Config\ConfigApp::getInstance()->getViewMode(),
            )
        );
    }

    /**
     * @Route("/export", name="export")
     */
    public function exportAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $dateFrom = new \DateTime($request->get('dateFrom'));
        $dateTo = new \DateTime($request->get('dateTo'));
        $query = $request->get('q', null);

        $activities = $em->getRepository('AppBundle:Activity')->findByDatesIntervalByDays($dateFrom, $dateTo, $query);

        $response = new StreamedResponse(function() use ($activities, $em) {
            echo "date;tags;titre;attributs;description\n";
            foreach($activities as $activity) {
                echo $activity->toCSV()."\n";
            }
        }, 200, array(
          'Content-Type' => 'text/csv',
          'Content-Disposition' => 'attachment; filename=export.csv',
        ));

        return $response;
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
        set_time_limit(180);

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
