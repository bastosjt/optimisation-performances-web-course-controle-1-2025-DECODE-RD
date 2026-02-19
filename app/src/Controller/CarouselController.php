<?php

namespace App\Controller;

use App\Repository\GalaxyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CarouselController extends AbstractController
{
    #[Route('/carousel', name: 'app_carousel')]
    public function index(GalaxyRepository $galaxyRepository, CacheInterface $cache): Response
    {
        $cachePage = 'carousel_page';
        $htmlContent = $cache->get($cachePage, function (ItemInterface $item) use ($galaxyRepository) {
            $item->expiresAfter(300);
            
            $carousel = $galaxyRepository->findAllWithFiles();
            return $this->renderView('carousel/index.html.twig', [
                'carousel' => $carousel
            ]);
        });
        
        $response = new Response($htmlContent);
        $response->headers->set('Cache-Control', 'public, max-age=300');
        
        return $response;
    }
}