<?php

namespace App\Controller;

use App\Entity\Annonces;
use App\Entity\Images;
use App\Form\AnnoncesType;
use App\Repository\AnnoncesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/annonces")
 */
class AnnoncesController extends AbstractController
{
    /**
     * @Route("/", name="annonces_index", methods={"GET"})
     */
    public function index(AnnoncesRepository $annoncesRepository): Response
    {
        return $this->render('annonces/index.html.twig', [
            'annonces' => $annoncesRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="annonces_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $annonce = new Annonces();
        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // on recupere les images transmises
            $images = $form->get('images')->getData();

            // on boucle sur les images

            foreach($images as $image){
                // on genere un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                //on copie le fichier dans le dosier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                // on stocke l'image dans la base de données ( son nom)
                $img = new Images();
                $img->setName($fichier);
                $annonce->addImage($img);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($annonce);
            $entityManager->flush();

            return $this->redirectToRoute('annonces_index');
        }

        return $this->render('annonces/new.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="annonces_show", methods={"GET"})
     */
    public function show(Annonces $annonce): Response
    {
        return $this->render('annonces/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="annonces_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Annonces $annonce): Response
    {
        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

             // on recupere les images transmises
             $images = $form->get('images')->getData();

             // on boucle sur les images
 
             foreach($images as $image){
                 // on genere un nouveau nom de fichier
                 $fichier = md5(uniqid()) . '.' . $image->guessExtension();
 
                 //on copie le fichier dans le dosier uploads
                 $image->move(
                     $this->getParameter('images_directory'),
                     $fichier
                 );
 
                 // on stocke l'image dans la base de données ( son nom)
                 $img = new Images();
                 $img->setName($fichier);
                 $annonce->addImage($img);
                } 

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('annonces_index');
        }

        return $this->render('annonces/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="annonces_delete", methods={"POST"})
     */
    public function delete(Request $request, Annonces $annonce): Response
    {
        if ($this->isCsrfTokenValid('delete'.$annonce->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($annonce);
            $entityManager->flush();
        }

        return $this->redirectToRoute('annonces_index');
    }

    /**
     * @Route("/supprime/image/{id}" ,name="annonces_delete_image", methods={"DELETE"})
     */
    public function deleteImage(Images $image, Request $request)
    {
        $data = json_decode($request->getContent(), true);
        //on verifie si le token est valide
        if($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token']))
        {   //on recupère le nom de l'image
            $nom = $image->getName();
            // on supprime le fichier
            unlink($this->getParameter('image_directory').'/'.$nom);

            // on supprime l'entrée de la base
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            //on répond en json
            return new JsonResponse(['success' => 1]);
        } else {
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }

}
