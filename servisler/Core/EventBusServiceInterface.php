<?php
namespace ProSiparis\Core;

interface EventBusServiceInterface
{
    public function publish(string $eventName, array $payload): void;
}
