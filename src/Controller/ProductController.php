<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\CheckoutType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Swift_Mailer;
Use Swift_Message;


/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_index');
    }

    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/{id}/addToCart", name="addToCart", methods={"GET","POST"})
     */
    public function addToCart ($id): Response

    {        $Cart = $this->session->get('Cart', []);
        if (isset($Cart[$id]))
        {
            $Cart[$id]['array']++;
        } else {
            $Cart[$id] = array('array' => 1);
        }
        $totaal = 0;
        $this->session->set('Cart', $Cart);
        ($this->session->get('Cart'));
        $cartArray = [];
        foreach ($Cart as $id => $product) {
            $res = $this->getDoctrine()
                ->getRepository(Product::class)
                ->find($id);
            array_push($cartArray, [$id, $product['array'], $res]);
            $totaal = $totaal +($product['array'] * $res->getPrice());
        }
        return $this->render('product/addToCart.html.twig', [
            'product' => $cartArray,
            'totaal' => $totaal
        ]);
    }

    /**
     * @Route("/checkout", name="checkout",methods={"GET","POST"})
     */
    public function checkout (Request $request, Swift_Mailer $mailer): Response

    {
        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        $Cart = $this->session->get('Cart', []);

        if($form->isSubmitted() && $form->isValid()){
//            $name = $form->get('name')->getData();
//            $email = $form->get('email')->getData();

            $message = (new Swift_Message('Factuur'))
                ->setFrom('jspillenaar@gmail.com')
                ->setTo('jspillenaar@gmail.com')
                ->setBody('Welcome to Maitrap!</p>Now your test emails will be <i>safe</i> ' .  . $Cart['name'],  'text/html');
            $mailer->send($message);
            $this->session->clear();
            return $this->redirect('/');
        }

        $Cart = $this->session->get('Cart', []);

        $totaal = 0;
        $this->session->set('Cart', $Cart);
        ($this->session->get('Cart'));
        $cartArray = [];
        foreach ($Cart as $id => $product) {
            $res = $this->getDoctrine()
                ->getRepository(Product::class)
                ->find($id);
            array_push($cartArray, [$id, $product['array'], $res]);
            $totaal = $totaal +($product['array'] * $res->getPrice());
        }
        return $this->render('product/checkout.html.twig', [
            'form' => $form->createView(),
            'product' => $cartArray,
            'totaal' => $totaal
        ]);
    }
}
