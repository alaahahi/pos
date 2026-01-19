<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting database backup job');
            
            $timestamp = now()->format('Y-m-d_His');
            $backupDir = storage_path('app/backups');
            
            // إنشاء مجلد النسخ الاحتياطية إذا لم يكن موجوداً
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // مسار قاعدة البيانات المحلية (SQLite)
            $sqlitePath = database_path('sync.sqlite');
            
            if (!file_exists($sqlitePath)) {
                Log::warning('SQLite database not found for backup', ['path' => $sqlitePath]);
                return;
            }
            
            // إنشاء اسم ملف النسخة الاحتياطية
            $backupFileName = "backup_local_db_{$timestamp}.sqlite";
            $backupFilePath = $backupDir . '/' . $backupFileName;
            
            // نسخ قاعدة البيانات
            if (copy($sqlitePath, $backupFilePath)) {
                Log::info('Database backup created successfully', [
                    'file' => $backupFileName,
                    'size' => $this->formatBytes(filesize($backupFilePath))
                ]);
                
                // ضغط النسخة الاحتياطية
                $zipFileName = "backup_local_db_{$timestamp}.zip";
                $zipFilePath = $backupDir . '/' . $zipFileName;
                
                $zip = new ZipArchive();
                if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                    $zip->addFile($backupFilePath, $backupFileName);
                    $zip->close();
                    
                    // حذف النسخة غير المضغوطة
                    unlink($backupFilePath);
                    
                    Log::info('Database backup compressed successfully', [
                        'zip_file' => $zipFileName,
                        'size' => $this->formatBytes(filesize($zipFilePath))
                    ]);
                }
                
                // حذف النسخ الاحتياطية القديمة (أكثر من 30 يوم)
                $this->deleteOldBackups($backupDir, 30);
                
            } else {
                Log::error('Failed to create database backup');
            }
            
        } catch (\Exception $e) {
            Log::error('Database backup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * حذف النسخ الاحتياطية القديمة
     */
    protected function deleteOldBackups(string $backupDir, int $days): void
    {
        $files = glob($backupDir . '/backup_local_db_*.zip');
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                // إذا كان الملف أقدم من X يوم
                if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            Log::info("Deleted {$deleted} old backup file(s)");
        }
    }
    
    /**
     * تنسيق حجم الملف
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
