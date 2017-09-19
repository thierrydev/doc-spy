<?php

namespace CoreBundle\Controller;

use CoreBundle\Entity\Item;
use CoreBundle\Entity\Tag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    /**
     * @Route("/tag/{id}", name="tag_show")
     */
    public function showAction($id)
    {
        $itemsRepository = $this->getDoctrine()->getRepository(Item::class);
        $items = $itemsRepository->findByTagId($id);

        $tagsRepository = $this->getDoctrine()->getRepository(Tag::class);
        $tags = $tagsRepository->findAll();

        return $this->render('CoreBundle:Default:index.html.twig', ['items' => $items, 'tags' => $tags]);
    }
}