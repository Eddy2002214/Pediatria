<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveService
{
    protected ?Client $client = null;

    protected ?Drive $service = null;

    protected ?string $rootFolderId;

    protected ?string $templatesFolderId;

    public function __construct()
    {
        $this->rootFolderId = env('GOOGLE_DRIVE_FOLDER_ID');
        $this->templatesFolderId = env('GOOGLE_DRIVE_TEMPLATES_FOLDER_ID');
    }

    /**
     * Check if Google Drive is properly configured.
     */
    public function isConfigured(): bool
    {
        $credPath = $this->getCredentialsPath();

        return ! empty($this->rootFolderId)
            && file_exists($credPath)
            && env('GOOGLE_DRIVE_ENABLED', true);
    }

    /**
     * Get path to service account credentials.
     */
    public function getCredentialsPath(): string
    {
        $envPath = env('GOOGLE_APPLICATION_CREDENTIALS', 'storage/app/google-credentials.json');

        if (file_exists($envPath)) {
            return $envPath;
        }

        return base_path($envPath);
    }

    /**
     * Get or initialize the Google Drive API service.
     */
    public function getDriveService(): Drive
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $client = new Client;
        $client->setApplicationName('Pediatria Japones');
        $client->setScopes([Drive::DRIVE]);
        $client->setAuthConfig($this->getCredentialsPath());
        $client->setAccessType('offline');

        $this->client = $client;
        $this->service = new Drive($client);

        return $this->service;
    }

    /**
     * Find a folder by name inside a parent folder, or create it if not found.
     *
     * @return array{id: string, name: string, webViewLink: ?string}
     */
    public function findOrCreateFolder(string $folderName, string $parentFolderId): array
    {
        $drive = $this->getDriveService();

        // Search for existing folder
        $escapedName = str_replace("'", "\\'", $folderName);
        $query = "mimeType = 'application/vnd.google-apps.folder' and name = '{$escapedName}' and '{$parentFolderId}' in parents and trashed = false";

        $response = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name, webViewLink)',
        ]);

        $files = $response->getFiles();
        if (! empty($files)) {
            $folder = $files[0];

            return [
                'id' => $folder->getId(),
                'name' => $folder->getName(),
                'webViewLink' => $folder->getWebViewLink(),
            ];
        }

        // Create folder
        $fileMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId],
        ]);

        $folder = $drive->files->create($fileMetadata, [
            'fields' => 'id, name, webViewLink',
        ]);

        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'webViewLink' => $folder->getWebViewLink(),
        ];
    }

    /**
     * List all template files inside the templates folder.
     *
     * @return array<int, array{id: string, name: string, mimeType: string}>
     */
    public function getTemplateFiles(): array
    {
        if (empty($this->templatesFolderId)) {
            return [];
        }

        $drive = $this->getDriveService();
        $query = "'{$this->templatesFolderId}' in parents and trashed = false and mimeType != 'application/vnd.google-apps.folder'";

        $response = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name, mimeType)',
        ]);

        $templates = [];
        foreach ($response->getFiles() as $file) {
            $templates[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
            ];
        }

        return $templates;
    }

    /**
     * Register a new patient in Google Drive:
     * 1. Finds/creates date folder (YYYY-MM-DD) inside Pediatria
     * 2. Finds/creates patient folder inside date folder
     * 3. Clones the 3 template documents into patient folder
     * 4. Returns folder and file links
     *
     * @return array{
     *     success: bool,
     *     paciente: string,
     *     fecha: string,
     *     carpeta_id: string,
     *     carpeta_url: ?string,
     *     archivos: array<int, array{id: string, nombre: string, webViewLink: ?string, webContentLink: ?string}>
     * }
     */
    public function ingresarPacienteDrive(string $nombrePaciente, ?string $fecha = null): array
    {
        if (! $this->isConfigured()) {
            throw new \Exception('Google Drive no está configurado en el sistema.');
        }

        $drive = $this->getDriveService();
        $fecha = $fecha ?: now()->format('Y-m-d');

        // 1. Ensure date folder exists inside PEDIATRIA
        $dateFolder = $this->findOrCreateFolder($fecha, $this->rootFolderId);

        // 2. Ensure patient folder exists inside date folder
        $patientFolder = $this->findOrCreateFolder($nombrePaciente, $dateFolder['id']);

        // 3. Get templates from PLANTILLAS folder
        $templates = $this->getTemplateFiles();
        $archivosClonados = [];

        foreach ($templates as $template) {
            $extension = pathinfo($template['name'], PATHINFO_EXTENSION);
            $nombreBase = pathinfo($template['name'], PATHINFO_FILENAME);
            $nuevoNombre = $extension ? "{$nombreBase} - {$nombrePaciente}.{$extension}" : "{$nombreBase} - {$nombrePaciente}";

            // Check if document copy already exists for this patient to avoid duplicate copies
            $escapedDocName = str_replace("'", "\\'", $nuevoNombre);
            $existingQuery = "'{$patientFolder['id']}' in parents and name = '{$escapedDocName}' and trashed = false";
            $existingResp = $drive->files->listFiles([
                'q' => $existingQuery,
                'spaces' => 'drive',
                'fields' => 'files(id, name, webViewLink, webContentLink)',
            ]);

            $existingFiles = $existingResp->getFiles();
            if (! empty($existingFiles)) {
                $doc = $existingFiles[0];
                $archivosClonados[] = [
                    'id' => $doc->getId(),
                    'nombre' => $doc->getName(),
                    'webViewLink' => $doc->getWebViewLink(),
                    'webContentLink' => $doc->getWebContentLink(),
                ];

                continue;
            }

            // Copy file to patient folder
            $copiedFileMetadata = new DriveFile([
                'name' => $nuevoNombre,
                'parents' => [$patientFolder['id']],
            ]);

            $copiedFile = $drive->files->copy($template['id'], $copiedFileMetadata, [
                'fields' => 'id, name, webViewLink, webContentLink',
            ]);

            $archivosClonados[] = [
                'id' => $copiedFile->getId(),
                'nombre' => $copiedFile->getName(),
                'webViewLink' => $copiedFile->getWebViewLink(),
                'webContentLink' => $copiedFile->getWebContentLink(),
            ];
        }

        return [
            'success' => true,
            'paciente' => $nombrePaciente,
            'fecha' => $fecha,
            'carpeta_id' => $patientFolder['id'],
            'carpeta_url' => $patientFolder['webViewLink'],
            'archivos' => $archivosClonados,
        ];
    }

    /**
     * List all patients and dates from Google Drive.
     *
     * @return array<int, array{
     *     fecha: string,
     *     paciente: string,
     *     carpeta_id: string,
     *     carpeta_url: ?string,
     *     archivos: array<int, array{id: string, nombre: string, webViewLink: ?string}>
     * }>
     */
    public function obtenerPacientesDrive(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $drive = $this->getDriveService();

        // 1. List date folders inside PEDIATRIA
        $queryDates = "'{$this->rootFolderId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        $dateFoldersResp = $drive->files->listFiles([
            'q' => $queryDates,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);

        $pacientes = [];

        foreach ($dateFoldersResp->getFiles() as $dateFolder) {
            $fecha = $dateFolder->getName();

            // Ignore non-date folders (like PLANTILLAS)
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                continue;
            }

            // 2. List patient folders inside date folder
            $queryPatients = "'{$dateFolder->getId()}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $patientFoldersResp = $drive->files->listFiles([
                'q' => $queryPatients,
                'spaces' => 'drive',
                'fields' => 'files(id, name, webViewLink, modifiedTime)',
            ]);

            foreach ($patientFoldersResp->getFiles() as $pFolder) {
                // 3. List documents in patient folder
                $queryDocs = "'{$pFolder->getId()}' in parents and trashed = false and mimeType != 'application/vnd.google-apps.folder'";
                $docsResp = $drive->files->listFiles([
                    'q' => $queryDocs,
                    'spaces' => 'drive',
                    'fields' => 'files(id, name, webViewLink, webContentLink, size)',
                ]);

                $archivos = [];
                foreach ($docsResp->getFiles() as $doc) {
                    $archivos[] = [
                        'id' => $doc->getId(),
                        'nombre' => $doc->getName(),
                        'webViewLink' => $doc->getWebViewLink(),
                        'webContentLink' => $doc->getWebContentLink(),
                    ];
                }

                $pacientes[] = [
                    'fecha' => $fecha,
                    'paciente' => $pFolder->getName(),
                    'carpeta_id' => $pFolder->getId(),
                    'carpeta_url' => $pFolder->getWebViewLink(),
                    'archivos' => $archivos,
                    'modified_time' => $pFolder->getModifiedTime(),
                ];
            }
        }

        return $pacientes;
    }
}
