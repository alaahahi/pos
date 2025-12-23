# ⚙️ إعداد Queue Worker للمزامنة في الخلفية

## 📋 نظرة عامة

المزامنة تعمل في الخلفية باستخدام Laravel Queue Jobs. يجب تشغيل Queue Worker لمعالجة Jobs.

---

## ✅ التحقق من الإعدادات

### 1. في `.env`

```env
QUEUE_CONNECTION=database  # ✅ مهم: يجب أن يكون database وليس sync
```

### 2. في `config/queue.php`

```php
'default' => env('QUEUE_CONNECTION', 'database'),  // ✅ تم التعديل
```

---

## 🚀 تشغيل Queue Worker

### على Windows (Local):

#### الطريقة 1: استخدام Script

```bash
start-queue-worker.bat
```

#### الطريقة 2: يدوياً

```bash
php artisan queue:work database --queue=sync
```

---

### على Linux/Mac (Local):

#### الطريقة 1: استخدام Script

```bash
chmod +x start-queue-worker.sh
./start-queue-worker.sh
```

#### الطريقة 2: يدوياً

```bash
php artisan queue:work database --queue=sync
```

#### الطريقة 3: في الخلفية

```bash
nohup php artisan queue:work database --queue=sync > storage/logs/queue-worker.log 2>&1 &
```

---

### على السيرفر (Production):

#### استخدام Supervisor (موصى به)

**1. إنشاء ملف Supervisor**

```bash
sudo nano /etc/supervisor/conf.d/pos-queue-worker.conf
```

**2. إضافة التكوين**

```ini
[program:pos-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --queue=sync --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**3. تحديث Supervisor**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pos-queue-worker:*
```

**4. التحقق من الحالة**

```bash
sudo supervisorctl status
```

---

## 🔍 التحقق من Queue Worker

### 1. التحقق من العملية

```bash
# Linux/Mac
ps aux | grep "queue:work"

# Windows
tasklist | findstr "php"
```

### 2. التحقق من Logs

```bash
tail -f storage/logs/laravel.log | grep "background sync"
```

### 3. التحقق من Jobs في Queue

```bash
php artisan tinker
```

```php
DB::table('jobs')->count();  // عدد Jobs في الانتظار
```

---

## ⚠️ ملاحظات مهمة

1. **Queue Worker يجب أن يعمل دائماً**:
   - على السيرفر: استخدام Supervisor
   - على العميل: يمكن تشغيله يدوياً أو في الخلفية

2. **إذا توقف Queue Worker**:
   - Jobs ستبقى في `jobs` table
   - يجب إعادة تشغيل Queue Worker

3. **Failed Jobs**:
   ```bash
   php artisan queue:failed  # عرض Failed Jobs
   php artisan queue:retry all  # إعادة محاولة
   ```

---

## 🔄 إعادة تشغيل Queue Worker

### بعد تحديث الكود:

```bash
# إيقاف Queue Worker
# (Ctrl+C إذا كان يعمل في Terminal)

# تنظيف Cache
php artisan config:clear
php artisan cache:clear

# إعادة التشغيل
php artisan queue:work database --queue=sync
```

---

## 📊 Monitoring

### 1. عدد Jobs في Queue

```bash
php artisan tinker
```

```php
DB::table('jobs')->count();
```

### 2. Failed Jobs

```bash
php artisan queue:failed
```

### 3. Logs

```bash
tail -f storage/logs/queue-worker.log
```

---

## ✅ Checklist

- [ ] ✅ `QUEUE_CONNECTION=database` في `.env`
- [ ] ✅ `config/queue.php` محدث
- [ ] ✅ Queue Worker يعمل
- [ ] ✅ Supervisor مُعد (على السيرفر)
- [ ] ✅ Logs تعمل

---

**تاريخ الإنشاء**: 2025-12-23
**الحالة**: ✅ جاهز للاستخدام

