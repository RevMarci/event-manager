<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegistrationController extends AbstractController
{
    #[Route('/registration/{event_id}', name: 'register_event', methods: ['POST'])]
    public function registerEvent($event_id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            return new JsonResponse(['error' => 'Hiányzó felhasználó'], 400);
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Felhasználó nem található'], 401);
        }

        $event = $em->getRepository(Event::class)->find($event_id);
        if (!$event) {
            return new JsonResponse(['error' => 'Esemény nem található'], 404);
        }

        $existing = $em->getRepository(Registration::class)->findOneBy([
            'user' => $user,
            'event' => $event
        ]);

        if ($existing) {
            return new JsonResponse(['error' => 'Már regisztráltál erre az eseményre'], 400);
        }

        $event->incrementRegisterCounter();

        $registration = new Registration();
        $registration->setUser($user)
            ->setEvent($event)
            ->setName($user->getName())
            ->setRank($event->getRegisterCounter())
            ->setSuccess($event->getRegisterCounter() <= $event->getCapacity());

        $em->persist($registration);
        $em->flush();

        if ($registration->isSuccess()) {
            $message = 'Sikeres jelentkezés';
        } else {
            $message = 'Várólistás vagy, sorszámod: ' . $registration->getRank();
        }

        return new JsonResponse([
            'message' => $message,
            'registration' => [
                'id' => $registration->getId(),
                'rank' => $registration->getRank(),
                'success' => $registration->isSuccess()
            ]
        ], 201);
    }
}
