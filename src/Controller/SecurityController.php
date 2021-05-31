<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/api")
 */
class SecurityController extends AbstractController
{
    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {
        $values = json_decode($request->getContent());
        if(isset($values->email,$values->password)) {
            $user = new User();
            $user->setEmail($values->email);
            $user->setPassword($passwordEncoder->encodePassword($user, $values->password));
            $user->setRoles("ROLE_USER");
            $entityManager->persist($user);
            $entityManager->flush();

            $data = [
                'status' => 201,
                'message' => 'L\'utilisateur a bien été créé'
            ];

            return new JsonResponse($data, 201);
        }
        $data = [
            'status' => 500,
            'message' => 'Merci de renseigner un email et un mot de passe'
        ];
        return new JsonResponse($data, 500);
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $user = $userRepository->findOneBy([
            'email'=>$request->get('email'),
        ]);

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ]);
        }
        $payload = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify("+5 minutes")->getTimestamp(),
        ];


        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        return $this->json([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
        ]);
    }

    /**
     * @Route("/list", name="list", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function list(UserRepository $userRepository, NormalizerInterface $normalizer)
    {
        $users = $userRepository->findAll();
        $result = $normalizer->normalize($users, null, [
            'groups' => 'user:read'
        ]);
        $json = json_encode($result);
        return new Response($json, 200, [
            "content-type" => "application/json"
        ]);
    }
}
