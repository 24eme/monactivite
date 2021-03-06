<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Form\ActivityTagAddType;
use AppBundle\Form\ActivityTagDeleteType;
use AppBundle\Entity\Activity;

/**
 * Filter controller.
 *
 * @Route("/activity")
 */
class ActivityController extends Controller
{
    /**
     * @Route("/list", name="activity_list")
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $am = $this->get('app.manager.activity');
        $repo = $em->getRepository('AppBundle:Activity');

        $nbDays = $request->get('nbDays');
        $viewMode = $request->get('viewMode');
        $dateFrom = new \DateTime($request->get('dateFrom'));
        $dateTo = new \DateTime($request->get('dateTo'));
        if($request->get('dateFromQuery')) {
            $dateFromQuery = new \DateTime($request->get('dateFromQuery'));
        } else {
            $dateFromQuery = clone $dateFrom;
        }
        $query = $request->get('q');

        $activities = $repo->findByDatesIntervalByDays($dateFromQuery, $dateTo, $query, $nbDays,$viewMode);
        $activitiesByDates = $am->createView($activities, $viewMode);

        $dateNext = null;
        if(count($activitiesByDates) > 0) {
            end($activitiesByDates);
            $dateNext = new \DateTime(key($activitiesByDates));
            $dateNext->modify("-1 day");
            $dateNext = $dateNext->format('Y-m-d');
        }

        return $this->render('Activity/list.html.twig',
            array(
                'activitiesByDates' => $activitiesByDates,
                'dateNext' => $dateNext,
                'query' => $query,
                'dateTo' => $dateTo->format('Y-m-d'),
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'nbDays' => $nbDays,
                'viewMode' => \AppBundle\Config\ConfigApp::getInstance()->getViewMode(),
            )
        );
    }

    /**
     * @Route("/view/{id}", name="activity_view")
     * @ParamConverter("activity", class="AppBundle:Activity")
     */
    public function viewAction(Request $request, Activity $activity)
    {
        return $this->render('Activity/view.html.twig', array('activity' => $activity, 'query' => $request->get('query', null), 'dateTo' => $request->get('dateTo', null), 'dateFrom' => $request->get('dateFrom', null), 'nbDays' => $request->get('nbDays', null)));
    }

    /**
     * @Route("/tag_add", name="activity_tag_add")
     * @Method("POST")
     */
    public function tagAddAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ActivityTagAddType::class, array('method' => 'POST'));

        $form->handleRequest($request);

        if (!$form->isValid()) {
            return new Response();
        }

        $data = $form->getData();

        $activity = $em->getRepository('AppBundle:Activity')->find($data['activity_id']);
        $tag = $em->getRepository('AppBundle:Tag')->find($data['tag_id']);

        $activity->addTag($tag);
        $em->flush();

        return new Response();
    }

    /**
     * @Route("/tag_delete", name="activity_tag_delete")
     * @Method("POST")
     */
    public function tagDeleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ActivityTagDeleteType::class);

        $form->handleRequest($request);

        if (!$form->isValid()) {

            return new Response();
        }

        $data = $form->getData();

        $activity = $em->getRepository('AppBundle:Activity')->find($data['activity_id']);
        $tag = $em->getRepository('AppBundle:Tag')->find($data['tag_id']);

        $activity->removeTag($tag);
        $em->flush();

        return new Response();
    }
}
