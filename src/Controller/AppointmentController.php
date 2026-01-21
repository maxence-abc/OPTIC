<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Equipement;
use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    /**
     * Parse une date venant du front.
     * Supporte:
     *  - YYYY-MM-DD (input type="date")
     *  - DD/MM/YYYY (format FR)
     */
    private function parseDateFromRequest(?string $dateStr): ?\DateTime
    {
        if (!$dateStr) {
            return null;
        }

        $dateStr = trim($dateStr);

        $d = \DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($d instanceof \DateTime) {
            $d->setTime(0, 0, 0);
            return $d;
        }

        $d = \DateTime::createFromFormat('d/m/Y', $dateStr);
        if ($d instanceof \DateTime) {
            $d->setTime(0, 0, 0);
            return $d;
        }

        try {
            $d = new \DateTime($dateStr);
            $d->setTime(0, 0, 0);
            return $d;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Endpoint AJAX: renvoie les créneaux disponibles selon service/date/équipement
     * et, si fourni, selon un professionnel précis.
     *
     * GET /appointment/slots?service=1&date=2026-01-05&equipement=2&professional=10
     */
    #[Route('/slots', name: 'app_appointment_slots', methods: ['GET'])]
    public function slots(Request $request, EntityManagerInterface $em, AppointmentRepository $repo): JsonResponse
    {
        $serviceId = $request->query->getInt('service');
        $dateStr = $request->query->get('date');
        $equipementId = $request->query->getInt('equipement', 0);
        $professionalId = $request->query->getInt('professional', 0);

        if (!$serviceId || !$dateStr) {
            return $this->json(['slots' => []]);
        }

        $date = $this->parseDateFromRequest(is_string($dateStr) ? $dateStr : null);
        if (!$date) {
            return $this->json(['slots' => []], 400);
        }

        /** @var Service|null $service */
        $service = $em->getRepository(Service::class)->find($serviceId);
        if (!$service || !$service->getEstablishment()) {
            return $this->json(['slots' => []], 404);
        }

        $establishment = $service->getEstablishment();

        $equipement = null;
        if ($equipementId > 0) {
            /** @var Equipement|null $equipement */
            $equipement = $em->getRepository(Equipement::class)->find($equipementId);
            if ($equipement && $equipement->getEstablishment()?->getId() !== $establishment->getId()) {
                $equipement = null;
            }
        }

        $professional = null;
        if ($professionalId > 0) {
            /** @var User|null $professional */
            $professional = $em->getRepository(User::class)->find($professionalId);
            if ($professional && $professional->getEstablishment()?->getId() !== $establishment->getId()) {
                $professional = null;
            }
        }

        $slots = $this->generateAvailableSlotsAjax(
            $service,
            $date,
            $repo,
            $em,
            $equipement?->getId(),
            $professional?->getId()
        );

        return $this->json(['slots' => $slots]);
    }

    #[Route('/new', name: 'app_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AppointmentRepository $appointmentRepository): Response
    {
        $appointment = new Appointment();
        $availableSlotsForForm = [];

        $posted = $request->request->all('appointment');

        $selectedServiceId = $posted['service'] ?? $request->query->get('service') ?? null;
        $selectedDate = $posted['date'] ?? $request->query->get('date') ?? null;
        $selectedEquipementId = $posted['equipement'] ?? null;

        if ($selectedServiceId) {
            $service = $entityManager->getRepository(Service::class)->find($selectedServiceId);
            if ($service) {
                $appointment->setService($service);
            }
        }

        if ($selectedDate) {
            $d = $this->parseDateFromRequest(is_string($selectedDate) ? $selectedDate : null);
            if ($d) {
                $appointment->setDate($d);
            }
        }

        if ($appointment->getService() && $appointment->getDate()) {
            $equipementId = null;
            if (!empty($selectedEquipementId)) {
                $equipementId = (int) $selectedEquipementId;
            }

            $slots = $this->generateAvailableSlotsAjax(
                $appointment->getService(),
                $appointment->getDate(),
                $appointmentRepository,
                $entityManager,
                $equipementId ?: null,
                null
            );

            if (!empty($slots)) {
                $availableSlotsForForm = array_combine($slots, $slots) ?: [];
            }
        }

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'available_slots' => $availableSlotsForForm,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointment->setClient($this->getUser());

            $service = $appointment->getService();
            $establishment = $service?->getEstablishment();

            if (!$service || !$establishment) {
                $this->addFlash('error', 'Service ou établissement invalide.');
                return $this->redirectToRoute('app_appointment_new');
            }

            $selectedDateObj = $appointment->getDate();
            $selectedTime = $form->get('startTime')->getData();

            if (!$selectedDateObj || !$selectedTime) {
                $this->addFlash('error', 'Veuillez sélectionner une date et un créneau horaire.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj?->format('Y-m-d'),
                ]);
            }

            $validTimes = array_values($availableSlotsForForm);
            if (!in_array($selectedTime, $validTimes, true)) {
                $this->addFlash('error', 'Ce créneau n’est plus disponible. Merci de choisir un autre horaire.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj->format('Y-m-d'),
                ]);
            }

            [$hour, $minute] = explode(':', $selectedTime);
            $startTime = (clone $selectedDateObj)->setTime((int) $hour, (int) $minute);

            $duration = (int) $service->getDuration();
            $buffer = (int) ($service->getBufferTime() ?? 0);
            $endTime = (clone $startTime)->modify("+{$duration} minutes")->modify("+{$buffer} minutes");

            $appointment->setStartTime($startTime);
            $appointment->setEndTime($endTime);
            $appointment->setStatus('pending');
            $appointment->setCreatedAt(new \DateTimeImmutable());

            $proIds = $appointmentRepository->findProfessionalIdsForEstablishment($establishment->getId());
            if (!$proIds && $establishment->getOwner()) {
                $proIds = [$establishment->getOwner()->getId()];
            }

            if (!$proIds) {
                $this->addFlash('error', 'Aucun professionnel n’est disponible pour cet établissement.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj->format('Y-m-d'),
                ]);
            }

            $saved = false;

            foreach ($proIds as $proId) {
                if ($appointmentRepository->hasOverlapForProfessional($proId, $selectedDateObj, $startTime, $endTime)) {
                    continue;
                }

                $professional = $entityManager->getRepository(User::class)->find($proId);
                if (!$professional) {
                    continue;
                }

                $appointment->setProfessional($professional);

                try {
                    $entityManager->persist($appointment);
                    $entityManager->flush();
                    $saved = true;
                    break;
                } catch (DriverException $e) {
                    if ($e->getSQLState() === '23P01') {
                        $entityManager->clear();

                        $appointment = new Appointment();
                        $appointment->setClient($this->getUser());
                        $appointment->setService($service);
                        $appointment->setDate($selectedDateObj);
                        $appointment->setStartTime($startTime);
                        $appointment->setEndTime($endTime);
                        $appointment->setStatus('pending');
                        $appointment->setCreatedAt(new \DateTimeImmutable());

                        $equipId = !empty($selectedEquipementId) ? (int) $selectedEquipementId : 0;
                        if ($equipId > 0) {
                            $equip = $entityManager->getRepository(Equipement::class)->find($equipId);
                            if ($equip) {
                                $appointment->setEquipement($equip);
                            }
                        }

                        continue;
                    }

                    throw $e;
                }
            }

            if (!$saved) {
                $this->addFlash('error', 'Ce créneau n’est plus disponible. Merci de choisir un autre horaire.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj->format('Y-m-d'),
                ]);
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
        $form = $this->createForm(AppointmentType::class, $appointment, [
            'available_slots' => [],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    /**
     * Annuler une réservation (client propriétaire, uniquement si à venir).
     */
    #[Route('/{id}/cancel', name: 'app_appointment_cancel', methods: ['POST'])]
    public function cancel(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($appointment->getClient()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas annuler cette réservation.");
        }

        if (!$this->isCsrfTokenValid('cancel'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_account', ['tab' => 'reservations']);
        }

        // Vérifie que c'est à venir
        $now = new \DateTimeImmutable();

        $date = $appointment->getDate();
        $start = $appointment->getStartTime();

        if (!$date || !$start) {
            $this->addFlash('error', "Réservation invalide.");
            return $this->redirectToRoute('app_account', ['tab' => 'reservations']);
        }

        $apptDate = \DateTimeImmutable::createFromMutable($date);
        $apptStart = \DateTimeImmutable::createFromMutable($start);

        $apptDateTime = $apptDate->setTime(
            (int) $apptStart->format('H'),
            (int) $apptStart->format('i'),
            (int) $apptStart->format('s')
        );

        if ($apptDateTime < $now) {
            $this->addFlash('error', "Impossible d'annuler une réservation passée.");
            return $this->redirectToRoute('app_account', ['tab' => 'reservations']);
        }

        if ($appointment->getStatus() === 'cancelled') {
            $this->addFlash('info', "Cette réservation est déjà annulée.");
            return $this->redirectToRoute('app_account', ['tab' => 'reservations']);
        }

        $appointment->setStatus('cancelled');
        $entityManager->flush();

        $this->addFlash('success', 'Réservation annulée.');
        return $this->redirectToRoute('app_account', ['tab' => 'reservations']);
    }

    #[Route('/{id}', name: 'app_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Rendez-vous supprimé avec succès.');
        }

        return $this->redirectToRoute('app_appointment_index');
    }

    /**
     * Générateur AJAX de créneaux (multi-pro + équipement optionnel)
     */
    private function generateAvailableSlotsAjax(
        Service $service,
        \DateTime $date,
        AppointmentRepository $repo,
        EntityManagerInterface $em,
        ?int $equipementId = null,
        ?int $professionalId = null
    ): array {
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
            (int) $openingHour->getOpenTime()->format('H'),
            (int) $openingHour->getOpenTime()->format('i')
        );

        $close = (clone $date)->setTime(
            (int) $openingHour->getCloseTime()->format('H'),
            (int) $openingHour->getCloseTime()->format('i')
        );

        $duration = (int) $service->getDuration();
        $buffer = (int) ($service->getBufferTime() ?? 0);
        $stepMinutes = max(1, $duration + $buffer);

        $proIds = [];
        if ($professionalId) {
            $proIds = [$professionalId];
        } else {
            $proIds = $repo->findProfessionalIdsForEstablishment($establishment->getId());
            if (!$proIds && $establishment->getOwner()) {
                $proIds = [$establishment->getOwner()->getId()];
            }
        }

        if (!$proIds) {
            return [];
        }

        $slots = [];
        $current = clone $open;

        while ($current < $close) {
            $slotStart = clone $current;
            $slotEndBlocking = (clone $slotStart)->modify("+{$duration} minutes")->modify("+{$buffer} minutes");

            if ($slotEndBlocking > $close) {
                break;
            }

            if ($equipementId) {
                if ($repo->hasOverlapForEquipment($equipementId, $date, $slotStart, $slotEndBlocking)) {
                    $current->modify("+{$stepMinutes} minutes");
                    continue;
                }
            }

            $hasAnyPro = false;
            foreach ($proIds as $pid) {
                if (!$repo->hasOverlapForProfessional($pid, $date, $slotStart, $slotEndBlocking)) {
                    $hasAnyPro = true;
                    break;
                }
            }

            if ($hasAnyPro) {
                $slots[] = $slotStart->format('H:i');
            }

            $current->modify("+{$stepMinutes} minutes");
        }

        return $slots;
    }
}
