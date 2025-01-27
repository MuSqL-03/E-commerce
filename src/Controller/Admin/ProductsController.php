<?php

namespace App\Controller\Admin;

use App\Entity\Images;
use App\Entity\Products;
use App\Form\ProductsFormType;
use App\Repository\ProductsRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/produits', name: 'admin_products_')]

class ProductsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ProductsRepository $productsRepository): Response
    {
        $produits = $productsRepository->findAll();
        return $this->render('admin/products/index.html.twig', compact('produits'));    }

    #[Route('/ajout', name: 'add')]
    public function add(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, PictureService $pictureService ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        // on crée u nouveau produit
        $product = new Products();




        // on va crée un formulair
        $productForm = $this->createForm(ProductsFormType::class, $product);

        // on traite la requet la formulaire
        $productForm->handleRequest($request);

        // on vérifie si le formulaire est soumis et valide
        if($productForm->isSubmitted() && $productForm->isValid()){


            // on va récupere les images
            $images = $productForm->get('images')->getData();
            
            foreach($images as $image){
                // on définit le dossier de distination
                $folder = 'products';
                
                // on appelle le service d'ajout

                $fichier = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($fichier);
                $product->addImage($img);
            }






            // on génère le slug
            $slug = $slugger->slug($product->getName());

           $product->setSlug($slug);

           // on arrondit le prix
           $prix = $product->getPrice() * 100;
           $product->setPrice($prix);

           // on stock 

           $em->persist($product);
           $em->flush();

           $this->addFlash('success', 'Produit ajouté avec success');

           // on redirige

           return $this->redirectToRoute('admin_products_index');
        }



        return $this->render('admin/products/add.html.twig', [
            'productForm' => $productForm->createView()
        ]);

      // return $this->renderForm('admin/products/add.html.twig', 
      // compact('productForm')); 
    }


     #[Route('/edittion/{id}', name: 'edit')]
       public function edit(Products $product,Request $request, EntityManagerInterface $em, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        // on vérifie si l'utilisateur peut éditer avec le voter
        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);

        // on divise le prix par 100
        $prix = $product->getPrice() / 100;
        $product->setPrice($prix);
   


        // on va crée un formulair
        $productForm = $this->createForm(ProductsFormType::class, $product);

       // on traite la requet la formulaire
       $productForm->handleRequest($request);

       // on vérifie si le formulaire est soumis et valide
       if($productForm->isSubmitted() && $productForm->isValid()){

            // on va récupere les images
            $images = $productForm->get('images')->getData();
            
            foreach($images as $image){
                // on définit le dossier de distination
                $folder = 'products';
                
                // on appelle le service d'ajout

                $fichier = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($fichier);
                $product->addImage($img);
            }

      // on génère le slug
      $slug = $slugger->slug($product->getName());

      $product->setSlug($slug);

      // on arrondit le prix
     $prix = $product->getPrice() * 100;
     $product->setPrice($prix);

     // on stock 

     $em->persist($product);
     $em->flush();

      $this->addFlash('success', 'Produit Modifié avec success');

     // on redirige

     return $this->redirectToRoute('admin_products_index');
     }



      return $this->render('admin/products/edit.html.twig', [
     'productForm' => $productForm->createView(),
     'button_label' => 'Modifier',
     'product' => $product
     ]);

      // return $this->renderForm('admin/products/add.html.twig', 
      // compact('productForm')); 
     }    

 #[Route('/suppression/{id}', name: 'delete')]
 public function delete(Products $products): Response
    {
        // on vérifie si l'utilisateur peut supprimer avec le voter
        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $products);
        return $this->render('admin/products/index.html.twig');
    }

    #[Route('/suppression/image/{id}', name: 'delete_image', methods: ['DELETE'] )]
 public function deleteImage(Images $image, Request $request, EntityManagerInterface $em, PictureService $pictureService): JsonResponse
    {
     

       // on récupère le contenu de la raquéte
       $data = json_decode($request->getContent(), true);

       if($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])){
           // Le token csrf est valide
           // On récupère le nom de l'image
           $nom = $image->getName();

           if($pictureService->delete($nom, 'products', 300, 300)){
               // On supprime l'image de la base de données
               $em->remove($image);
               $em->flush();

               return new JsonResponse(['success' => true], 200);
           }
           // La suppression a échoué
           return new JsonResponse(['error' => 'Erreur de suppression'], 400);
       }

        return new JsonResponse(['error' => 'Token invalide'], 400);
    }
}

