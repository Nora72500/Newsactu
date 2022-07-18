<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
 * @Route("Admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/tableau de bord", name="show_dashbord", methods={"GET"})
     */
    public function showDashboard(EntityManagerInterface $entityManager): Response
    {
        return $this->render("admin/show_dashboard.html.twig");
    }

    public function (Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger)



    /**
     * @Route("/admin", name="app_admin")
     */
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
