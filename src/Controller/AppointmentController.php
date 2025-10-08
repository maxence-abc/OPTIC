<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Entity\OpeningHour;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appointment')]
final class AppointmentController extends AbstractController
{
    #[Route(name: 'app_appointment_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        return $this->render('appointment/index.html.twig', [
            'appointments' => $appointmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AppointmentRepository $appointmentRepository): Response
    {
        $appointment = new Appointment();

        // Récupération du service et de la date depuis GET ou POST
        $selectedServiceId = $request->query->get('service') ?? $request->request->get('appointment')['service'] ?? null;
        $selectedDate = $request->query->get('date') ?? $request->request->get('appointment')['date'] ?? null;

        $availableSlots = [];

        // Si un service et une date sont sélectionnés → on calcule les créneaux
        if ($selectedServiceId && $selectedDate) {
            $service = $entityManager->getRepository(Service::class)->find($selectedServiceId);

            if ($service) {
                $availableSlots = $this->generateAvailableSlots($service, new \DateTime($selectedDate), $appointmentRepository, $entityManager);
                // On pré-remplit l'objet appointment pour garder les valeurs dans le formulaire
                $appointment->setService($service);
                $appointment->setDate(new \DateTime($selectedDate));
            }
        }

        // Création du formulaire avec les créneaux disponibles
        $form = $this->createForm(AppointmentType::class, $appointment, [
            'available_slots' => $availableSlots,
        ]);
        $form->handleRequest($request);

        // Si soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $appointment->setClient($this->getUser());

            $service = $appointment->getService();
            $establishment = $service?->getEstablishment();

            if (!$establishment || !$establishment->getOwner()) {
                $this->addFlash('error', 'Aucun professionnel n’est associé à ce service.');
                return $this->redirectToRoute('app_appointment_new');
            }

            $appointment->setProfessional($establishment->getOwner());

            // On récupère la date et l'heure choisies
            $selectedDate = $appointment->getDate(); // c’est déjà un DateTime
            $selectedTime = $form->get('startTime')->getData(); // ex: "14:30"

            // Fusionne la date du rendez-vous et l'heure choisie
            list($hour, $minute) = explode(':', $selectedTime);
            $startTime = (clone $selectedDate)->setTime((int)$hour, (int)$minute);

            $duration = $service->getDuration();
            $buffer = $service->getBufferTime() ?? 0;

            // Calcul automatique de l’heure de fin
            $endTime = (clone $startTime)->modify("+{$duration} minutes")->modify("+{$buffer} minutes");

            $appointment->setStartTime($startTime);
            $appointment->setEndTime($endTime);
            $appointment->setStatus('pending');
            $appointment->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($appointment);
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous créé avec succès ✅');
            return $this->redirectToRoute('app_appointment_index');
        }

        return $this->render('appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le rendez-vous a été mis à jour ✅');
            return $this->redirectToRoute('app_appointment_index');
        }

        return $this->render('appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous supprimé avec succès 🗑️');
        }

        return $this->redirectToRoute('app_appointment_index');
    }

    /**
     * Génère tous les créneaux disponibles pour un service donné et une date donnée
     */
    private function generateAvailableSlots(Service $service, \DateTime $date, AppointmentRepository $repo, EntityManagerInterface $em): array
    {
        $establishment = $service->getEstablishment();
        if (!$establishment) {
            return [];
        }

        $dayOfWeek = $date->format('l');
        $openingHour = $em->getRepository(OpeningHour::class)->findOneBy([
            'establishment' => $establishment,
            'dayOfWeek' => $dayOfWeek,
        ]);

        if (!$openingHour) {
            return [];
        }

        $open = (clone $date)->setTime(
            (int)$openingHour->getOpenTime()->format('H'),
            (int)$openingHour->getOpenTime()->format('i')
        );
        $close = (clone $date)->setTime(
            (int)$openingHour->getCloseTime()->format('H'),
            (int)$openingHour->getCloseTime()->format('i')
        );

        $duration = $service->getDuration();
        $buffer = $service->getBufferTime() ?? 0;
        $step = $duration + $buffer;

        $slots = [];
        $current = clone $open;

        while ($current < $close) {
            $end = (clone $current)->modify("+{$duration} minutes");

            if ($end > $close) break;

            $existing = $repo->findOneBy([
                'service' => $service,
                'date' => $date,
                'startTime' => $current,
            ]);

            if (!$existing) {
                $slots[$current->format('H:i')] = $current->format('H:i');
            }

            $current->modify("+{$step} minutes");
        }

        return $slots;
    }
}
