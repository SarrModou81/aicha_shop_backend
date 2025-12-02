<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--compress : Compress the backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $fileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = storage_path('app/backups');

        // Créer le dossier si nécessaire
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullPath = $backupPath . '/' . $fileName;

        // Commande mysqldump
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($database),
            escapeshellarg($fullPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Backup failed!');
            return 1;
        }

        // Compresser si demandé
        if ($this->option('compress')) {
            $this->info('Compressing backup...');
            $compressedFile = $fullPath . '.gz';
            exec("gzip {$fullPath}", $output, $returnCode);

            if ($returnCode === 0) {
                $fileName .= '.gz';
                $fullPath = $compressedFile;
                $this->info('Backup compressed successfully!');
            }
        }

        $fileSize = filesize($fullPath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        $this->info("Backup completed successfully!");
        $this->info("File: {$fileName}");
        $this->info("Size: {$fileSizeMB} MB");
        $this->info("Path: {$fullPath}");

        // Nettoyer les anciennes sauvegardes (garder les 30 dernières)
        $this->cleanOldBackups($backupPath);

        return 0;
    }

    /**
     * Clean old backups
     */
    protected function cleanOldBackups($backupPath)
    {
        $files = glob($backupPath . '/backup_*.sql*');

        if (count($files) > 30) {
            // Trier par date de modification
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Supprimer les plus anciens
            $filesToDelete = array_slice($files, 0, count($files) - 30);

            foreach ($filesToDelete as $file) {
                unlink($file);
                $this->info("Deleted old backup: " . basename($file));
            }
        }
    }
}
