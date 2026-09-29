<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Enum\MessageAuthor;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mercure\Authorization;

class ChatManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ConversationRepository $conversationRepository,
        private readonly Authorization $authorization,
    ) {
    }

    public function getOrCreateConversation(Request $request): Conversation
    {
        $session = $request->getSession();
        $conversation = null;

        if ($id = $session->get('conversation_id')) {
            $conversation = $this->conversationRepository->find($id);
        }

        if (!$conversation) {
            $conversation = new Conversation();
            $this->em->persist($conversation);
            $this->em->flush();
            $session->set('conversation_id', $conversation->getId());
        }

        return $conversation;
    }

    public function authorizeSubscriber(Request $request, Conversation $conversation): void
    {
        $this->authorization->setCookie($request, 'chat_'.$conversation->getId());
    }

    public function postVisitorMessage(Conversation $conversation, Request $request, string $content): void
    {
        if ($request->getSession()->get('conversation_id') !== $conversation->getId()) {
            throw new AccessDeniedHttpException();
        }

        $this->postMessage($conversation, MessageAuthor::VISITOR, $content);
    }

    public function postAdminMessage(Conversation $conversation, string $content): void
    {
        $this->postMessage($conversation, MessageAuthor::ADMIN, $content);
    }

    /** @return Conversation[] */
    public function listConversations(): array
    {
        return $this->conversationRepository->findAllOrderedByCreatedAtDesc();
    }

    private function postMessage(Conversation $conversation, MessageAuthor $author, string $content): void
    {
        $content = trim($content);
        if ('' === $content) {
            return;
        }

        $this->em->persist(new Message($conversation, $author, $content));
        $this->em->flush();
    }
}
