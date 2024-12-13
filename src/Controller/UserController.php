<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $JWTManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->JWTManager = $JWTManager;
    }

    #[Route('/api/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['message' => 'Email et mot de passe sont requis'], 400);
        }
        $user = new User();
        $user->setEmail($data['email']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setName($data['name'] ?? null);
        $user->setPhoneNumber($data['phoneNumber'] ?? null);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName()
        ], 201);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['message' => 'Email et mot de passe sont requis'], 400);
        }
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['message' => 'erreur lors de la connexion'], 404);
        }

        $token = $this->JWTManager->create($user);
        return new JsonResponse(['bearer' => $token]);
    }

    #[Route('/api/update', name: 'update', methods: ['put'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        if (empty($data['email'])) {
            return new JsonResponse(['message' => 'email invalide'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        };
        $entityManager->flush();

        return new JsonResponse([
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phoneNumber' => $user->getPhoneNumber()
        ], 201);
    }

    #[Route('/api/delete/{email}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, EntityManagerInterface $entityManager,  Security $security, string $email): JsonResponse
    {

        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'votre acces ne vous permet pas de delete.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $userToDelete = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$userToDelete) {
            return new JsonResponse(['error' => 'aucun utilisateur trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($userToDelete);
        $entityManager->flush();

        return new JsonResponse(['message' => "utilisateur supprimer avec succès"], 201);
    }

    #[Route('/api/showAll', name: 'showAllUser', methods: ['get'])]
    public function showAll(EntityManagerInterface $entityManager,  Security $security,): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'votre acces ne vous permet pas de voir la liste des utilisateurs.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $users = $entityManager->getRepository(User::class)->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'phoneNumber' => $user->getPhoneNumber()
            ];
        }

        return new JsonResponse($data, 200);
    }

    #[Route('/api/show/{email}', name: 'showByEmail', methods: ['get'])]
    public function showByEmail(Request $request, EntityManagerInterface $entityManager,  Security $security, string $email): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'votre acces ne vous permet pas de voir les informations d\'un utilisateur.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'aucun utilisateur trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }
        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phoneNumber' => $user->getPhoneNumber()
        ], 200);
    }
}
