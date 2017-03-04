<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Source;
use AppBundle\Entity\Activity;
use AppBundle\Form\SourceType;
use AppBundle\Form\SourceAddType;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Source controller.
 *
 * @Route("/source")
 */
class SourceController extends Controller
{

    /**
     * Lists all Source entities.
     *
     * @Route("/", name="source")
     * @Template("Source/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Source();
        $form = $this->createForm(new SourceAddType(), $entity, array(
            'action' => $this->generateUrl('source'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Tester'));

        $entities = $em->getRepository('AppBundle:Source')->findAll();

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return array(
                'entities' => $entities,
                'form'   => $form->createView(),
            );
        }

        return $this->redirectToRoute('source_create', array("source" => $entity->getSource()));
    }
    /**
     * Creates a new Source entity.
     *
     * @Route("/creation", name="source_create")
     * @Template("Source/create.html.twig")
     */
    public function createAction(Request $request)
    {
        $am = $this->get('app.manager.activity');
        $entity = new Source();
        $entity->setSource($request->get('source', null));



        $importer = $this->get('app.manager.importer')->search($entity);

        if($importer) {
            $entity->setImporter($importer->getName());
        }

        $form = $this->createCreateForm($entity);

        $form->handleRequest($request);

        if(!$importer) {
            $importer = $this->get('app.manager.importer')->search($entity);
        }

        if($form->isValid() && $form->getClickedButton()->getName() == 'add') {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('success', sprintf("La source donnée \"%s\" a été ajouté", $entity->getSourceProtected()));
            return $this->redirectToRoute('source');
        }

        if($importer) {
            $importer->run($entity, new NullOutput(), true, false, 100);
        }

        $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->computeChangeSets();
        $activities = array();
        $insertions = $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->getScheduledEntityInsertions();
        foreach($insertions as $insertion) {
            if($insertion instanceof Activity) {
                $activities[] = $insertion;
            }
        }

        return array(
            'entity' => $entity,
            'activitiesByDates' => $am->createView($activities),
            'form'   => $form->createView(),
            'importer' => $importer
        );
    }

    /**
     * Creates a form to create a Source entity.
     *
     * @param Source $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Source $entity)
    {
        $form = $this->createForm(new SourceType(), $entity, array(
            'action' => $this->generateUrl('source_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Relancer le test'));
        $form->add('add', 'submit', array('label' => 'Ajouter'));

        return $form;
    }

    /**
     * Displays a form to create a new Source entity.
     *
     * @Route("/new", name="source_new")
     * @Method("GET")
     * @Template("Source/new.html.twig")
     */
    public function newAction()
    {
        $entity = new Source();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Source entity.
     *
     * @Route("/{id}", name="source_show")
     * @Method("GET")
     * @Template("Source/show.html.twig")
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Source')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Source entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Source entity.
     *
     * @Route("/{id}/edit", name="source_edit")
     * @Method("GET")
     * @Template("Source/edit.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Source')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Source entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Source entity.
    *
    * @param Source $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Source $entity)
    {
        $form = $this->createForm(new SourceType(), $entity, array(
            'action' => $this->generateUrl('source_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Source entity.
     *
     * @Route("/{id}", name="source_update")
     * @Method("PUT")
     * @Template("Source/edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Source')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Source entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('source_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Source entity.
     *
     * @Route("/{id}", name="source_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Source')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Source entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('source'));
    }

    /**
     * Creates a form to delete a Source entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('source_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * @Route("/{id}/execute", name="source_execute")
     * @Method("GET")
     * @Template("Source/execute.html.twig")
     */
    public function executeAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Source')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Source entity.');
        }

        $form = $this->createCreateForm($entity);

        $this->get('app.manager.main')->executeSource($entity, new \Symfony\Component\Console\Output\ConsoleOutput(), true);

        $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->computeChangeSets();

        $entities = $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->getScheduledEntityInsertions();

        $activities = array();
        foreach($entities as $activity) {
            if(!$activity instanceof \AppBundle\Entity\Activity) {
                continue;
            }
            $activities[] = $activity;
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'entities' => $activities,
        );
    }

}
