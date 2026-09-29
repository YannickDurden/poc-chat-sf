<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Service\ChatManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatManager $chatManager,
    ) {
    }

    #[Route('/chat', name: 'app_chat')]
    public function index(Request $request): Response
    {
        $conversation = $this->chatManager->getOrCreateConversation($request);
        $this->chatManager->authorizeSubscriber($request, $conversation);

        return $this->render('chat/index.html.twig', [
            'conversation' => $conversation,
            'messages' => $conversation->getMessages(),
        ]);
    }

    #[Route('/chat/{id}/message', name: 'app_chat_message', methods: ['POST'])]
    public function message(Conversation $conversation, Request $request): Response
    {
        $this->chatManager->postVisitorMessage($conversation, $request, (string) $request->request->get('content'));

        return $this->redirectToRoute('app_chat');
    }
}
