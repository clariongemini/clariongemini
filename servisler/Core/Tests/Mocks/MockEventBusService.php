<?php
namespace ProSiparis\Core\Tests\Mocks;

use ProSiparis\Core\EventBusServiceInterface;

class MockEventBusService implements EventBusServiceInterface
{
    private array $publishedEvents = [];

    public function publish(string $eventName, array $payload): void
    {
        $this->publishedEvents[] = ['name' => $eventName, 'payload' => $payload];
    }

    public function getPublishedEventCount(): int
    {
        return count($this->publishedEvents);
    }

    public function getLastPublishedEventName(): ?string
    {
        return end($this->publishedEvents)['name'] ?? null;
    }

    public function getLastPublishedEventPayload(): ?array
    {
        return end($this->publishedEvents)['payload'] ?? null;
    }
}
