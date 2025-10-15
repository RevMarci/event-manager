<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EventController extends AbstractController
{
    #[Route('/event', name: 'create_event', methods: ['POST'])]
    public function createEvent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $capacity = $data['capacity'] ?? null;
        $userId = $data['user_id'] ?? null;

        if (!$name || !$capacity || !$userId) {
            return new JsonResponse(['error' => 'Hiányzó mezők'], 400);
        }

        if ($capacity <= 0) {
            return new JsonResponse(['error' => 'A kapacitásnak nagyobbnak kell lennie 0-nál'], 400);
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Helytelen felhasználó'], 401);
        }

        $event = new Event();
        $event->setName($name)
            ->setCapacity((int) $capacity)
            ->setRegisterCounter(0);

        $em->persist($event);
        $em->flush();

        return new JsonResponse([
            'id' => $event->getId(),
            'name' => $event->getName(),
            'capacity' => $event->getCapacity(),
            'register_counter' => $event->getRegisterCounter()
        ], 201);
    }
}
