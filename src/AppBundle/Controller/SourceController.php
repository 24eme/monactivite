<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Source;
use AppBundle\Entity\Activity;
use AppBundle\Form\SourceType;
use AppBundle\Form\SourceAddType;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * @Route("/source")
 */
class SourceController extends Controller
{

    /**
     * @Route("/", name="source")
     * @Template("Source/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $sources = $em->getRepository('AppBundle:Source')->findAll();

        return array(
            'sources' => $sources,
            'importers' => $this->get('app.manager.importer')->getImporters(),
        );
    }

    /**
     * @Route("/creation/{type}", name="source_create")
     * @Template("Source/create.html.twig")
     */
    public function createAction(Request $request, $type)
    {
        $source = new Source();
        $source->setImporter($type);

        return $this->editAction($request, $source);
    }

    /**
     * @Route("/edit/{id}", name="source_edit")
     * @ParamConverter("source", class="AppBundle:Source")
     * @Template("Source/form.html.twig")
     */
    public function editAction(Request $request, Source $source)
    {
        $importer = $this->get('app.manager.importer')->get($source->getImporter());
        $isCreation = !$source->getId();

        $form = $this->createForm(SourceType::class, $source->getParameters(), array(
            'action' => $isCreation ? $this->generateUrl('source_create', array('type' => $source->getImporter())) : $this->generateUrl('source_edit', array('id' => $source->getId())),
            'method' => 'POST',
            'importer' => $importer,
        ));

        if(!$request->isMethod(Request::METHOD_POST)) {
            return array(
                'form'   => $form->createView(),
                'importer' => $importer,
                'isCreation' => $isCreation,
            );
        }

        $importer->updateParameters($source, $request->get($form->getName()));
        $form->submit(array_merge($request->get($form->getName()), $source->getParameters()));

        if(!$form->isValid()) {
            return array(
                'form'   => $form->createView(),
                'importer' => $importer,
                'isCreation' => $isCreation,
            );
        }

        try {
            $importer->check($source);
        } catch(\Exception $e) {

            return array(
                'form'   => $form->createView(),
                'importer' => $importer,
                'checkError' => $e->getMessage(),
                'isCreation' => $isCreation,
            );
        }

        if($request->get('action') != "save") {
            $importer->run($source, new NullOutput(), true, false, 100);
            $am = $this->get('app.manager.activity');
            $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->computeChangeSets();
            $activities = array();
            $insertions = $this->get('doctrine.orm.entity_manager')->getUnitOfWork()->getScheduledEntityInsertions();
            foreach($insertions as $insertion) {
                if($insertion instanceof Activity) {
                    $activities[] = $insertion;
                }
            }

            return array(
                'form'   => $form->createView(),
                'importer' => $importer,
                'activitiesByDates' => $am->createView($activities),
                'isCreation' => $isCreation,
            );
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($source);
        $em->flush();

        $this->addFlash('success', sprintf("La source donnée \"%s\" a été %s", $source->getTitle(), $isCreation ? "ajoutée" : "modifiée"));

        return $this->redirectToRoute('source');
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

}
