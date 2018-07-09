<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Filter;
use AppBundle\Form\FilterType;

/**
 * Filter controller.
 *
 * @Route("/filter")
 */
class FilterController extends Controller
{

    /**
     * Lists all Filter entities.
     *
     * @Route("/", name="filter")
     * @Method("GET")
     * @Template("Filter/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Filter')->findBy(array(), array('tag' => 'ASC'));

        $entity = new Filter();
        if($request->get('q')) {
            $entity->setQuery($request->get('q'));
        }
        $form = $this->createCreateForm($entity);

        return array(
            'entities' => $entities,
            'form'   => $form->createView(),
        );
    }
    /**
     * Creates a new Filter entity.
     *
     * @Route("/", name="filter_create")
     * @Method("POST")
     * @Template("Filter/new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Filter();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->addFlash('success', sprintf("Le filtre a été ajouté"));

            return $this->redirectToRoute('filter');
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Filter entity.
     *
     * @param Filter $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Filter $entity)
    {
        $form = $this->createForm(new FilterType(), $entity, array(
            'action' => $this->generateUrl('filter_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Ajouter'));

        return $form;
    }


    /**
     * Displays a form to edit an existing Filter entity.
     *
     * @Route("/{id}/edit", name="filter_edit")
     * @Method("GET")
     * @Template("Filter/edit.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Filter')->findBy(array(), array('tag' => 'ASC'));

        $entity = $em->getRepository('AppBundle:Filter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Filter entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entities'      => $entities,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Filter entity.
    *
    * @param Filter $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Filter $entity)
    {
        $form = $this->createForm(new FilterType(), $entity, array(
            'action' => $this->generateUrl('filter_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Modifier'));

        return $form;
    }
    /**
     * Edits an existing Filter entity.
     *
     * @Route("/{id}", name="filter_update")
     * @Method("PUT")
     * @Template("Filter/edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Filter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Filter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('filter'));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Filter entity.
     *
     * @Route("/{id}", name="filter_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Filter')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Filter entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('filter'));
    }

    /**
     * Creates a form to delete a Filter entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('filter_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @Route("/{id}/execute", name="filter_execute")
     * @Method("GET")
     * @Template("Filter/execute.html.twig")
     */
    public function executeAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Filter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Filter entity.');
        }

        $form = $this->createCreateForm($entity);

        $this->get('app.manager.main')->executeFilter($entity, new \Symfony\Component\Console\Output\ConsoleOutput(), true);

        $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->computeChangeSets();

        $entities = $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->getScheduledEntityUpdates();

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'entities' => $entities,
        );
    }
}
