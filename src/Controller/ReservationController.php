<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/reservations', name: 'app_reservation_')]
class ReservationController extends AbstractController
{
    #[Route('/event/{id}', name: 'create', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function create(
        Event $event,
        Request $request,
        ReservationRepository $reservationRepository,
        ValidatorInterface $validator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($event->getAvailableSeats() <= 0) {
            $this->addFlash('error', 'Sorry, this event is fully booked.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);
        $reservation->setUser($this->getUser());
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $email = trim($request->request->get('email', ''));
            $phone = trim($request->request->get('phone', ''));

            $reservation->setName($name);
            $reservation->setEmail($email);
            $reservation->setPhone($phone ?: null);

            $violations = $validator->validate($reservation);
            if (count($violations) === 0) {
                $reservationRepository->save($reservation);
                return $this->redirectToRoute('app_reservation_confirm', ['id' => $reservation->getId()]);
            }

            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
        }

        return $this->render('reservation/create.html.twig', [
            'event' => $event,
            'errors' => $errors,
        ]);
    }

    #[Route('/confirm/{id}', name: 'confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirm(Reservation $reservation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Ensure users can only see their own reservation confirmations
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reservation/confirm.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/my', name: 'my_reservations', methods: ['GET'])]
    public function myReservations(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('reservation/my_reservations.html.twig', [
            'reservations' => $user->getReservations(),
        ]);
    }
}
