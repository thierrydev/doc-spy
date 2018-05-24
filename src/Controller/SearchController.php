<?php

namespace App\Controller;

use App\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends Controller
{
    /**
     * @Route("/search/{page}", name="search", requirements={"page"="\d+"})
     * @param Request $request
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $page = 1)
    {
        $phrase = $request->get('q');

        $itemRepository = $this->getDoctrine()->getRepository(Item::class);
        $items = $itemRepository->findPaginatedByPhrase($phrase, $page, Item::LIMIT);

        $maxPages = ceil($items->count() / Item::LIMIT);

        return $this->render('search/index.html.twig', [
            'items' => $items,
            'phrase' => $phrase,
            'maxPages' => $maxPages,
            'page' => $page,
        ]);
    }

    /**
     * Action for rendering search form
     */
    public function form()
    {
        $phrase = Request::createFromGlobals()->get('q');
        return $this->render('parts/search.html.twig', ['phrase' => $phrase]);
    }
}