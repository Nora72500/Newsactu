<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RenderController extends AbstractController
{
    /**
     * @Route("/render", name="app_render")
     */
    public function index(): Response
    {
        return $this->render('render/index.html.twig', [
            'controller_name' => 'RenderController',
        ]);
    }
}
