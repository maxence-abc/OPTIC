<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\OpeningHour;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Exception\DriverException;
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
        $selectedServiceId = $request->query->get('service')
            ?? ($request->request->all('appointment')['service'] ?? null);

        $selectedDate = $request->query->get('date')
            ?? ($request->request->all('appointment')['date'] ?? null);

        $availableSlots = [];

        // Si un service et une date sont sélectionnés → on calcule les créneaux
        if ($selectedServiceId && $selectedDate) {
            $service = $entityManager->getRepository(Service::class)->find($selectedServiceId);

            if ($service) {
                $appointment->setService($service);
                $appointment->setDate(new \DateTime($selectedDate));

                // On calcule les créneaux disponibles (avec overlaps + statuts bloquants + pro/équipement)
                $availableSlots = $this->generateAvailableSlots(
                    $service,
                    new \DateTime($selectedDate),
                    $appointmentRepository,
                    $entityManager
                );
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

            if (!$service || !$establishment || !$establishment->getOwner()) {
                $this->addFlash('error', 'Aucun professionnel n’est associé à ce service.');
                return $this->redirectToRoute('app_appointment_new');
            }

            // Aujourd’hui: 1 pro = owner. Demain: on pourra choisir un pro parmi plusieurs.
            $appointment->setProfessional($establishment->getOwner());

            // On récupère la date et l'heure choisies
            $selectedDateObj = $appointment->getDate(); // DateTime
            $selectedTime = $form->get('startTime')->getData(); // ex: "14:30"

            if (!$selectedDateObj || !$selectedTime) {
                $this->addFlash('error', 'Veuillez sélectionner une date et un créneau horaire.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj?->format('Y-m-d'),
                ]);
            }

            // Fusion date + heure
            [$hour, $minute] = explode(':', $selectedTime);
            $startTime = (clone $selectedDateObj)->setTime((int)$hour, (int)$minute);

            $duration = (int) $service->getDuration();
            $buffer = (int) ($service->getBufferTime() ?? 0);

            // Calcul heure de fin bloquante : durée + buffer
            $endTime = (clone $startTime)->modify("+{$duration} minutes")->modify("+{$buffer} minutes");

            $appointment->setStartTime($startTime);
            $appointment->setEndTime($endTime);
            $appointment->setStatus('pending');
            $appointment->setCreatedAt(new \DateTimeImmutable());

            // Persist + flush avec gestion collision PostgreSQL (23P01)
            try {
                $entityManager->persist($appointment);
                $entityManager->flush();
            } catch (DriverException $e) {
                // PostgreSQL: 23P01 = exclusion_violation
                if ($e->getSQLState() === '23P01') {
                    $this->addFlash('error', 'Ce créneau vient d’être réservé. Merci de choisir un autre horaire.');
                    return $this->redirectToRoute('app_appointment_new', [
                        'service' => $service->getId(),
                        'date' => $selectedDateObj->format('Y-m-d'),
                    ]);
                }
                throw $e;
            }

            $this->addFlash('success', 'Rendez-vous créé avec succès.');
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
        // Attention : ton edit ne recalcule pas les créneaux. Pour l’instant on garde simple.
        $form = $this->createForm(AppointmentType::class, $appointment, [
            'available_slots' => [], // évite d’afficher un select vide incohérent
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Si tu permets de changer startTime via edit, il faudrait refaire la même logique que new()
            // et catcher 23P01. Pour l'instant, on flush tel quel.
            try {
                $entityManager->flush();
            } catch (DriverException $e) {
                if ($e->getSQLState() === '23P01') {
                    $this->addFlash('error', 'Conflit de planning : ce créneau est déjà pris.');
                    return $this->redirectToRoute('app_appointment_edit', ['id' => $appointment->getId()]);
                }
                throw $e;
            }

            $this->addFlash('success', 'Le rendez-vous a été mis à jour.');
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
            $this->addFlash('success', 'Rendez-vous supprimé avec succès.');
        }

        return $this->redirectToRoute('app_appointment_index');
    }

    /**
     * Génère les créneaux disponibles pour un service + date.
     * Règles:
     * - respecte les horaires d’ouverture
     * - step = duration + buffer
     * - bloque si overlap avec un RDV "pending/confirmed" du même professionnel
     * - si équipement choisi plus tard, on ne peut pas filtrer ici sur un équipement précis.
     *   Cependant, si un équipement est déjà set sur l’objet (pré-rempli), on le prend en compte.
     */
    private function generateAvailableSlots(
        Service $service,
        \DateTime $date,
        AppointmentRepository $repo,
        EntityManagerInterface $em
    ): array {
        $establishment = $service->getEstablishment();
        if (!$establishment || !$establishment->getOwner()) {
            return [];
        }

        $professional = $establishment->getOwner();
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

        $duration = (int) $service->getDuration();
        $buffer = (int) ($service->getBufferTime() ?? 0);

        // Pas de step à 0
        $stepMinutes = max(1, $duration + $buffer);

        $slots = [];
        $current = clone $open;

        // On calcule l’intervalle bloquant : duration + buffer
        while ($current < $close) {
            $slotStart = clone $current;
            $slotEndBlocking = (clone $slotStart)->modify("+{$duration} minutes")->modify("+{$buffer} minutes");

            // Si l'intervalle dépasse la fermeture, on stop
            if ($slotEndBlocking > $close) {
                break;
            }

            // Overlap check (pro + statut)
            $hasConflict = $repo->hasOverlapForProfessional(
                $professional->getId(),
                $date,
                $slotStart,
                $slotEndBlocking
            );

            if (!$hasConflict) {
                $slots[$slotStart->format('H:i')] = $slotStart->format('H:i');
            }

            $current->modify("+{$stepMinutes} minutes");
        }

        return $slots;
    }
}
