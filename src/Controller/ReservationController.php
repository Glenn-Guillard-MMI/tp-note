<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservationController extends AbstractController
{


    #[Route('/api/reservation', name: 'create_reservation', methods: ['POST'])]
    public function reservation(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'votre acces ne vous permet pas de delete.'], JsonResponse::HTTP_FORBIDDEN);
        }
        $data = json_decode($request->getContent(), true);
        if (empty($data['date']) || empty($data['eventName'])) {
            return new JsonResponse(['error' => 'Date et nom d\'événement sont requis'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $reservation = new Reservation();
        $date = new \DateTime($data['date']);
        $reservation->setDate($date);
        $timeSlot = new \DateTime($data['timeSlot']);
        $reservation->setTimeSlot($timeSlot);
        $eventName = $data['eventName'];
        $reservation->setEventName($eventName);
        $entityManager->persist($reservation);
        $entityManager->flush();
        return new JsonResponse([
            'id' => $reservation->getId(),
            'date' => $reservation->getDate()->format('Y-m-d H:i:s'),
            'timeSlot' => $reservation->getTimeSlot()->format('Y-m-d H:i:s'),
            'eventName' => $eventName,
        ]);
    }
    #[Route('/api/addReservation/{email}/{reservationId}', name: 'addReservation', methods: ['post'])]
    public function addReservation(
        string $email,
        int $reservationId,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_NOT_FOUND);
        }
        $reservation = $reservationRepository->find($reservationId);

        if (!$reservation) {
            return new JsonResponse(['error' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user->addReservation($reservation);
        $entityManager->persist($reservation);
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Reservation ajouter'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/removeReservation/{email}/{reservationId}', name: 'addReservation', methods: ['put'])]
    public function removeReservation(
        string $email,
        int $reservationId,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_NOT_FOUND);
        }
        $reservation = $reservationRepository->find($reservationId);
        if (!$reservation) {
            return new JsonResponse(['error' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }
        $user->removeReservation($reservation);
        $entityManager->persist($user);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Reservation supprimée'], JsonResponse::HTTP_OK);
    }
    #[Route('/api/getAllReservation', name: 'getAllReservation', methods: ['get'])]
    public function getAllReservation(ReservationRepository $reservationRepository): JsonResponse
    {
        $reservations = $reservationRepository->findAll();
        $data = [];
        foreach ($reservations as $reservation) {
            $data[] = [
                'id' => $reservation->getId(),
                'date' => $reservation->getDate()->format('Y-m-d H:i:s'),
                'timeSlot' => $reservation->getTimeSlot()->format('H:i:s'),
                'eventName' => $reservation->getEventName(),
            ];
        }
        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/getReservationById/{id}', name: 'getReservationById', methods: ['get'])]
    public function getReservationById(ReservationRepository $reservationRepository, Int $id): JsonResponse
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['error' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }
        return new JsonResponse([
            'id' => $reservation->getId(),
            'date' => $reservation->getDate()->format('Y-m-d H:i:s'),
            'timeSlot' => $reservation->getTimeSlot()->format('H:i:s'),
            'eventName' => $reservation->getEventName(),
        ], 200);
    }
    #[Route('/api/updateReservation/{id}', name: 'updateReservation', methods: ['put'])]
    public function updateReservation(Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, Int $id,  Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'votre acces ne vous permet pas de delete.'], JsonResponse::HTTP_FORBIDDEN);
        }
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['error' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }
        $data =      $data = json_decode($request->getContent(), true);
        if (isset($data['date'])) {
            $date = new \DateTime($data['date']);
            $reservation->setDate($date);
        }
        if (isset($data['timeSlot'])) {
            $timeSlot = new \DateTime($data['timeSlot']);
            $reservation->setTimeSlot($timeSlot);
        }
        if (isset($data['eventName'])) {
            $eventName = $data['eventName'];
            $reservation->setEventName($eventName);
        }
        $entityManager->flush();
        return new JsonResponse([
            'id' => $reservation->getId(),
            'date' => $reservation->getDate()->format('Y-m-d H:i:s'),
            'timeSlot' => $reservation->getTimeSlot()->format('H:i:s'),
            'eventName' => $reservation->getEventName(),
        ], 201);
    }


    #[Route('/api/deleteReservationById/{id}', name: 'deleteReservationById', methods: ['DELETE'])]
    public function deleteReservationById(Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, Int $id,  Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User non identifier'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'votre acces ne vous permet pas de delete.'], JsonResponse::HTTP_FORBIDDEN);
        }
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['error' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }
        $entityManager->remove($reservation);
        $entityManager->flush();
        return new JsonResponse([
            'message' => 'Reservation delete'
        ], 201);
    }
}
