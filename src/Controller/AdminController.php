<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin', name: 'app_admin_')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly SluggerInterface $slugger
    ) {}

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(EventRepository $eventRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/events/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function newEvent(Request $request, EventRepository $eventRepository): Response
    {
        $event = new Event();
        $errors = [];

        if ($request->isMethod('POST')) {
            [$event, $errors] = $this->handleEventForm($request, $event);

            if (empty($errors)) {
                $eventRepository->save($event);
                $this->addFlash('success', 'Event created successfully.');
                return $this->redirectToRoute('app_admin_dashboard');
            }
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => $event,
            'errors' => $errors,
            'action' => 'Create',
        ]);
    }

    #[Route('/events/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editEvent(Event $event, Request $request, EventRepository $eventRepository): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            [$event, $errors] = $this->handleEventForm($request, $event);

            if (empty($errors)) {
                $eventRepository->save($event);
                $this->addFlash('success', 'Event updated successfully.');
                return $this->redirectToRoute('app_admin_dashboard');
            }
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => $event,
            'errors' => $errors,
            'action' => 'Edit',
        ]);
    }

    #[Route('/events/{id}/delete', name: 'event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteEvent(Event $event, Request $request, EventRepository $eventRepository): Response
    {
        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            // Delete image file if exists
            if ($event->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $event->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $eventRepository->remove($event);
            $this->addFlash('success', 'Event deleted.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/events/{id}/reservations', name: 'event_reservations', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function eventReservations(Event $event): Response
    {
        return $this->render('admin/event_reservations.html.twig', [
            'event' => $event,
            'reservations' => $event->getReservations(),
        ]);
    }

    #[Route('/reservations/{id}/delete', name: 'reservation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteReservation(
        \App\Entity\Reservation $reservation,
        Request $request,
        ReservationRepository $reservationRepository
    ): Response {
        $eventId = $reservation->getEvent()?->getId();

        if ($this->isCsrfTokenValid('delete_res_' . $reservation->getId(), $request->request->get('_token'))) {
            $reservationRepository->remove($reservation);
            $this->addFlash('success', 'Reservation deleted.');
        }

        return $this->redirectToRoute('app_admin_event_reservations', ['id' => $eventId]);
    }

    private function handleEventForm(Request $request, Event $event): array
    {
        $errors = [];

        $title = trim($request->request->get('title', ''));
        $description = trim($request->request->get('description', ''));
        $dateStr = $request->request->get('date', '');
        $location = trim($request->request->get('location', ''));
        $seats = (int) $request->request->get('seats', 0);

        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($description)) $errors[] = 'Description is required.';
        if (empty($location)) $errors[] = 'Location is required.';
        if ($seats <= 0) $errors[] = 'Seats must be a positive number.';

        $date = null;
        if (!empty($dateStr)) {
            try {
                $date = new \DateTime($dateStr);
            } catch (\Exception) {
                $errors[] = 'Invalid date format.';
            }
        } else {
            $errors[] = 'Date is required.';
        }

        if (empty($errors)) {
            $event->setTitle($title);
            $event->setDescription($description);
            $event->setDate($date);
            $event->setLocation($location);
            $event->setSeats($seats);

            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
                    $imageFile->move($uploadsDir, $newFilename);
                    $event->setImage($newFilename);
                } catch (FileException) {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }

        return [$event, $errors];
    }
}
