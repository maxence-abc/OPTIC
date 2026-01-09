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

        try {
            $date = new \DateTime($dateStr);
        } catch (\Throwable) {
            return $this->json(['slots' => []], 400);
        }

        /** @var Service|null $service */
        $service = $em->getRepository(Service::class)->find($serviceId);
        if (!$service || !$service->getEstablishment()) {
            return $this->json(['slots' => []], 404);
        }

        $establishment = $service->getEstablishment();

        // Sécurité équipement (optionnel)
        $equipement = null;
        if ($equipementId > 0) {
            /** @var Equipement|null $equipement */
            $equipement = $em->getRepository(Equipement::class)->find($equipementId);
            if ($equipement && $equipement->getEstablishment()?->getId() !== $establishment->getId()) {
                $equipement = null;
            }
        }

        // Sécurité pro (optionnel, pour le mode manuel)
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

        // --- IMPORTANT ---
        // Pour éviter "The selected choice is invalid" avec un ChoiceType rempli en AJAX :
        // On doit reconstruire les choices côté serveur au moment du POST.
        $availableSlotsForForm = [];

        // Récupération via POST (soumission) ou GET (préremplissage)
        $posted = $request->request->all('appointment');

        $selectedServiceId = $posted['service'] ?? $request->query->get('service') ?? null;
        $selectedDate = $posted['date'] ?? $request->query->get('date') ?? null;
        $selectedEquipementId = $posted['equipement'] ?? null; // peut être "" ou un id

        // Pré-remplissage entité appointment (service/date)
        if ($selectedServiceId) {
            $service = $entityManager->getRepository(Service::class)->find($selectedServiceId);
            if ($service) {
                $appointment->setService($service);
            }
        }

        if ($selectedDate) {
            try {
                $appointment->setDate(new \DateTime($selectedDate));
            } catch (\Throwable) {
                // ignore
            }
        }

        // Si service+date dispo, on calcule les slots serveur (mêmes règles que /slots)
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

            // Symfony ChoiceType attend un tableau label => value
            // ex: ['08:00' => '08:00', '09:05' => '09:05']
            if (!empty($slots)) {
                $availableSlotsForForm = array_combine($slots, $slots) ?: [];
            }
        }

        // Création du formulaire avec slots "serveur" (vide en GET sans service/date, rempli en POST)
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
            $selectedTime = $form->get('startTime')->getData(); // ex: "14:30"

            if (!$selectedDateObj || !$selectedTime) {
                $this->addFlash('error', 'Veuillez sélectionner une date et un créneau horaire.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj?->format('Y-m-d'),
                ]);
            }

            // (optionnel mais robuste) : revalider que la valeur fait partie des slots serveur
            // (ça protège contre un POST "forgé")
            $validTimes = array_values($availableSlotsForForm);
            if (!in_array($selectedTime, $validTimes, true)) {
                $this->addFlash('error', 'Ce créneau n’est plus disponible. Merci de choisir un autre horaire.');
                return $this->redirectToRoute('app_appointment_new', [
                    'service' => $service->getId(),
                    'date' => $selectedDateObj->format('Y-m-d'),
                ]);
            }

            // Fusion date + heure
            [$hour, $minute] = explode(':', $selectedTime);
            $startTime = (clone $selectedDateObj)->setTime((int) $hour, (int) $minute);

            $duration = (int) $service->getDuration();
            $buffer = (int) ($service->getBufferTime() ?? 0);
            $endTime = (clone $startTime)->modify("+{$duration} minutes")->modify("+{$buffer} minutes");

            $appointment->setStartTime($startTime);
            $appointment->setEndTime($endTime);
            $appointment->setStatus('pending');
            $appointment->setCreatedAt(new \DateTimeImmutable());

            // --- MULTI-PRO (AUTO) ---
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
                        // collision => essayer un autre pro
                        $entityManager->clear();

                        // Recréer un Appointment propre
                        $appointment = new Appointment();
                        $appointment->setClient($this->getUser());
                        $appointment->setService($service);
                        $appointment->setDate($selectedDateObj);
                        $appointment->setStartTime($startTime);
                        $appointment->setEndTime($endTime);
                        $appointment->setStatus('pending');
                        $appointment->setCreatedAt(new \DateTimeImmutable());

                        // conserver l'équipement si fourni
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
     * Générateur AJAX de créneaux (multi-pro + équipement optionnel)
     *
     * - Si $professionalId est fourni => slots pour ce pro uniquement
     * - Sinon => slot dispo si au moins un pro est libre
     * - Si $equipementId est fourni => slot dispo seulement si équipement libre
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

        // Pros
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

            // Check équipement si sélectionné
            if ($equipementId) {
                if ($repo->hasOverlapForEquipment($equipementId, $date, $slotStart, $slotEndBlocking)) {
                    $current->modify("+{$stepMinutes} minutes");
                    continue;
                }
            }

            // Check pro: soit pro spécifique, soit "au moins un pro libre"
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
