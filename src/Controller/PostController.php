<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{
    private $em;

    /**
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    #[Route('/', name: 'index')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $posts = $this->em->getRepository(Post::class)->findAllPosts();

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $url = str_replace(" ", "-", $form->get('title')->getData());

            if ( $file ) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception('Ups there is a problem with your file');
                }

                $post->setFile($newFilename);
            }

            $post->setUrl($url);
            $user = $this->em->getRepository(User::class)->find(1);
            $post->setUser($user);
            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts
        ]);
    }


    #[Route('/post/details/{id}', name: 'postDetails')]
    public function postDetails(Post $post) {

        return $this->render('post/post-details.html.twig', ['post' => $post]);
    }
}
