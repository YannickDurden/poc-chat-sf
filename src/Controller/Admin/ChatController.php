<?php

namespace App\Controller\Admin;

use App\Entity\Conversation;
use App\Service\ChatManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/chat')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatManager $chatManager,
    ) {
    }

    #[Route('', name: 'admin_chat_list')]
    public function list(): Response
    {
        return $this->render('admin/chat_list.html.twig', [
            'conversations' => $this->chatManager->listConversations(),
        ]);
    }

    #[Route('/{id}', name: 'admin_chat_show')]
    public function show(Conversation $conversation, Request $request): Response
    {
        $this->chatManager->authorizeSubscriber($request, $conversation);

        return $this->render('admin/chat_show.html.twig', [
            'conversation' => $conversation,
            'messages' => $conversation->getMessages(),
        ]);
    }

    #[Route('/{id}/message', name: 'admin_chat_message', methods: ['POST'])]
    public function message(Conversation $conversation, Request $request): Response
    {
        $this->chatManager->postAdminMessage($conversation, (string) $request->request->get('content'));

        return $this->redirectToRoute('admin_chat_show', ['id' => $conversation->getId()]);
    }
}
