<?php

namespace App\Service;
use Symfony\Component\HttpKernel\KernelInterface;

class EventsJsonLoader
{
    private string $jsonPath;
    private array $data;

    public function __construct(KernelInterface $kernel, string $jsonPath = null)
    {
        $this->jsonPath = $jsonPath ?? $kernel->getProjectDir() . '/src/DataFixtures/evenements.json';

        if (!file_exists($this->jsonPath)) {
            throw new \RuntimeException("Fichier JSON introuvable : $this->jsonPath");
        }

        $content  = file_get_contents($this->jsonPath);
        $this->data =json_decode($content, true);
        if ($this->data === null) {
            throw new \RuntimeException("JSON invalide dans : {$this->jsonPath}");
        }

    }

    public function getData(): array
    {
        return $this->data;
    }
}
