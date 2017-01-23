<?php

namespace AppBundle\Membership;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\AdherentActivationToken;
use AppBundle\Mailjet\MailjetService;
use AppBundle\Mailjet\Message\AdherentAccountConfirmationMessage;
use AppBundle\Repository\AdherentRepository;
use AppBundle\Repository\CommitteeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdherentAccountActivationHandler
{
    private $dispatcher;
    private $adherentRepository;
    private $committeeRepository;
    private $manager;
    private $mailjet;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        AdherentRepository $adherentRepository,
        CommitteeRepository $committeeRepository,
        ObjectManager $manager,
        MailjetService $mailjet
    ) {
        $this->dispatcher = $dispatcher;
        $this->adherentRepository = $adherentRepository;
        $this->committeeRepository = $committeeRepository;
        $this->manager = $manager;
        $this->mailjet = $mailjet;
    }

    public function handle(Adherent $adherent, AdherentActivationToken $token)
    {
        $adherent->activate($token);
        $this->manager->flush();

        $this->dispatch(
            AdherentEvents::ACTIVATION_COMPLETED,
            new AdherentAccountActivationCompletedEvent($adherent, $token)
        );

        $this->mailjet->sendMessage(AdherentAccountConfirmationMessage::createFromAdherent(
            $adherent,
            $this->adherentRepository->countActiveAdherents(),
            $this->committeeRepository->countApprovedCommittees()
        ));
    }

    private function dispatch(string $eventName, AdherentEvent $event)
    {
        if ($this->dispatcher->hasListeners($eventName)) {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }
}
