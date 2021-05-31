<?php

namespace App\Controller;

use App\Entity\transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/transaction")
 */
class TransactionController extends AbstractController
{
    /**
     * @Route("/add")
     * @IsGranted("ROLE_USER")
     */
    public function add(EntityManagerInterface $em)
    {
        $transaction = new Transaction();
        $transaction->setName('Transaction 1');
        $transaction->setPrice(150);
        $em->persist($transaction);
        $em->flush();

        $data = [
            'status' => 201,
            'message' => 'La transaction a bien été créée'
        ];

        return new JsonResponse($data, 201);
    }
}