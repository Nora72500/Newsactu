<?php

namespace App\Controller;

use DateTime;
use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleFromType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/tableau-de-bord", name="show_dashboard", methods={"GET"})
     */
    public function showDashboard(EntityManagerInterface $entityManager): Response
    {
        #2ème façon de bloquer un accès à un user en f(x) de son ROLE. 
        # (la première se trouve dans 'access control' -> config/packages/security.yaml)
        // Ce bloc de code vous permet de vérifier si le rôle du user est ADMIN, sinon cela lance une
        // une erreur, qui est attrapée dans le catch et cela redirige avec un message dans une partie
        // autorisée pour les différents rôles.
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } catch (AccessDeniedException $exception) {
            $this->addFlash('warning', 'Cette partie du site est réservée aux admins');
            return $this->redirectToRoute('default_home');
        }

        $articles   = $entityManager->getRepository(Article::class)->findBy(['deletedAt'=> null]);
        $categories = $entityManager->getRepository(Category::class)->findAll();


        return $this->render("\admin\show_dashboard.html.twig", [
            'articles'=> $articles,
            'categories'=> $categories,
        ]);
        
    }

    /**
     * @Route("/ajouter-un-article", name="create_article", methods={"GET|POST"})
     */
    public function createArticle(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        
            # 1 - Instanciation
            $article = new Article();
    
            # 2 - Création du formulaire
            $form = $this->createForm(ArticleFromType::class, $article)
                ->handleRequest($request);
    
            if($form->isSubmitted() && $form->isValid()) {
    
                $article->setCreatedAt(new DateTime());
                $article->setUpdatedAt(new DateTime());
    
                # L'alias sera utilisé dans l'url (comme FranceTvInfo) et donc doit être assaini de tout accents et espaces.
                $article->setAlias($slugger->slug($article->getTitle()));
    
                /** @var UploadedFile $photo */
                $photo = $form->get('photo')->getData();
    
                # Si une photo a été uploadée dans le formulaire on va faire le traitement nécessaire à son stockage dans notre projet.
                if($photo) {
                    # Déconstructioon
                    $extension = '.' . $photo->guessExtension();
                    $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
    //                $safeFilename = $article->getAlias();
    
                    # Reconstruction
                    $newFilename = $safeFilename . '_' . uniqid() . $extension;
    
                    try {
                        $photo->move($this->getParameter('uploads_dir'), $newFilename);
                        $article->setPhoto($newFilename);
                    }
                    catch(FileException $exception) {
                        # Code à exécuter en cas d'erreur.
                    }
                } # end if($photo)
    
                    # Ajout d'un auteur à l'article (User récupéré depuis la session)
                    $article->setAuthor($this->getUser());
    
                    $entityManager->persist($article);
                    $entityManager->flush();
    
                    $this->addFlash('success', "L'article est en ligne avec succès !");
                    return $this->redirectToRoute('show_dashboard');
    
            } # end if ($form)
    
            # 3 - Création de la vue
            return $this->render("admin/form/article.html.twig", [
                'form' => $form->createView()
            ]);
        } # end function createArticle
    
        /**
         * @Route("/modifier-un-article/{id}", name="update_article", methods={"GET|POST"})
         */
        public function updateArticle(Article $article, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
        {
            $originalPhoto = $article->getPhoto();
    
            # 2 - Création du formulaire
            $form = $this->createForm(ArticleFromType::class, $article, [
                'photo' => $originalPhoto
            ])->handleRequest($request);
    
            if($form->isSubmitted() && $form->isValid()) {
    
    
                $article->setCreatedAt(new DateTime());
                $article->setUpdatedAt(new DateTime());
    
                # L'alias sera utilisé dans l'url (comme FranceTvInfo) et donc doit être assaini de tout accents et espaces.
                $article->setAlias($slugger->slug($article->getTitle()));
    
                /** @var UploadedFile $photo */
                $photo = $form->get('photo')->getData();
    
                # Si une photo a été uploadée dans le formulaire on va faire le traitement nécessaire à son stockage dans notre projet.
                if($photo) {
    
                    # Déconstructioon
                    $extension = '.' . $photo->guessExtension();
                    $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
    //                $safeFilename = $article->getAlias();
    
                    # Reconstruction
                    $newFilename = $safeFilename . '_' . uniqid() . $extension;
    
                    try {
                        $photo->move($this->getParameter('uploads_dir'), $newFilename);
                        $article->setPhoto($newFilename);
                    }
                    catch(FileException $exception) {
                        # Code à exécuter en cas d'erreur.
                    }
                } else {
                    $article->setPhoto($originalPhoto);
                } // end if photo
    
                # Ajout d'un auteur à l'article (User récupéré depuis la session)
                $article->setAuthor($this->getUser());
    
                $entityManager->persist($article);
                $entityManager->flush();
    
                $this->addFlash('success', "L'article a été modifié avec succès !");
                return $this->redirectToRoute('show_dashboard');
            } # end if ($form)
    
            # 3 - Création de la vue
            return $this->render("admin/form/article.html.twig", [
                'form' => $form->createView(),
                'article' => $article
            ]);
        }# end function updateArticle
    
        /**
         * @Route("/archiver-un-article/{id}", name="soft_delete_article", methods={"GET"})
         */
        public function softDeleteArticle(Article $article, EntityManagerInterface $entityManager): RedirectResponse
        {
            $article->setDeletedAt(new DateTime());
    
            $entityManager->persist($article);
            $entityManager->flush();
    
            $this->addFlash('success', "L'article a bien été archivé.");
            return $this->redirectToRoute('show_dashboard');
        } // en f(x) softDeletedArticle

        /**
         * @Route("/restaurer-un-article/{id}", name="restore_article", methods={"GET"})
         */
        public function restoreArticle(Article $article, EntityManagerInterface $entityManager): RedirectResponse
        {
            $article->setDeletedAt(null);

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', "L'article a bien été restauré");
            return $this->redirectToRoute('show_dashboard');
        }

        /**
        * @Route("/voir-les-articles-archives", name="show_trash", methods={"GET"})
        */       
        public function showTrash(EntityManagerInterface $entityManager): Response
        {
            $archivedArticles = $entityManager->getRepository(Article::class)->findByTrash();

            return $this->render("admin/trash/article_trash.html.twig", [
                'archivedArticles'=>$archivedArticles
            ]);
        }

        /**
         * @Route("/supprimer-un-article/{id}", name="hard_delete_article", methods={"GET"})
         */
        public function hardDeleteArticle(Article $article, EntityManagerInterface $entityManager): RedirectResponse
        {
            // Suppression manuelle de la photo
            $photo = $article->getPhoto();

            if($photo){
                // Fonction native PHP pour supprimer un fichier dans le filesystem (syn. arborescence)
                unlink($this->getParameter('uploads_dir') . "/" . $photo);
            }

            $entityManager->remove($article);
            $entityManager->flush();

            $this->addFlash('success', "L'article a bien été supprimé définitivement");
            return $this->redirectToRoute('show_trash');
        }

    } # end class