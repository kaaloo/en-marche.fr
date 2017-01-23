<?php

namespace AppBundle\Geocoder\Subscriber;

use AppBundle\Committee\CommitteeCreatedEvent;
use AppBundle\Committee\CommitteeEvents;
use AppBundle\Geocoder\Exception\GeocodingException;
use AppBundle\Geocoder\GeocodableInterface;
use AppBundle\Geocoder\GeocoderInterface;
use AppBundle\Membership\AdherentAccountActivationCompletedEvent;
use AppBundle\Membership\AdherentEvents;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityAddressGeocodingSubscriber implements EventSubscriberInterface
{
    private $geocoder;
    private $manager;

    public function __construct(GeocoderInterface $geocoder, ObjectManager $manager)
    {
        $this->geocoder = $geocoder;
        $this->manager = $manager;
    }

    public function onAdherentAccountActivationCompleted(AdherentAccountActivationCompletedEvent $event)
    {
        $this->updateGeocodableEntity($event->getAdherent());
    }

    public function onCommitteeCreated(CommitteeCreatedEvent $event)
    {
        $this->updateGeocodableEntity($event->getCommittee());
    }

    private function updateGeocodableEntity(GeocodableInterface $geocodable)
    {
        if ($coordinates = $this->geocode($geocodable->getGeocodableAddress())) {
            $geocodable->updateCoordinates($coordinates);
            $this->manager->flush();
        }
    }

    private function geocode(string $address)
    {
        try {
            return $this->geocoder->geocode($address);
        } catch (GeocodingException $e) {
            // do nothing when an exception arises
            return null;
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            AdherentEvents::ACTIVATION_COMPLETED => ['onAdherentAccountActivationCompleted', -256],
            CommitteeEvents::CREATED => ['onCommitteeCreated', -256],
        ];
    }
}
