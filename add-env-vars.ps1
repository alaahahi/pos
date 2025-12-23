# إضافة متغيرات API Sync إلى .env
$envFile = ".env"

if (Test-Path $envFile) {
    $content = Get-Content $envFile -Raw
    
    # إضافة SYNC_VIA_API إذا لم يكن موجوداً
    if ($content -notmatch "SYNC_VIA_API") {
        Add-Content $envFile "`nSYNC_VIA_API=true"
        Write-Host "✅ تم إضافة SYNC_VIA_API=true"
    } else {
        Write-Host "⚠️  SYNC_VIA_API موجود بالفعل"
    }
    
    # إضافة SYNC_API_TOKEN إذا لم يكن موجوداً
    if ($content -notmatch "SYNC_API_TOKEN") {
        Add-Content $envFile "`nSYNC_API_TOKEN=your-api-token-here"
        Write-Host "✅ تم إضافة SYNC_API_TOKEN=your-api-token-here"
        Write-Host "⚠️  يجب تحديث SYNC_API_TOKEN بالـ token الصحيح من السيرفر"
    } else {
        Write-Host "⚠️  SYNC_API_TOKEN موجود بالفعل"
    }
    
    # إضافة SYNC_API_TIMEOUT إذا لم يكن موجوداً
    if ($content -notmatch "SYNC_API_TIMEOUT") {
        Add-Content $envFile "`nSYNC_API_TIMEOUT=30"
        Write-Host "✅ تم إضافة SYNC_API_TIMEOUT=30"
    } else {
        Write-Host "⚠️  SYNC_API_TIMEOUT موجود بالفعل"
    }
    
    Write-Host "`n✅ تم تحديث .env بنجاح"
} else {
    Write-Host "❌ ملف .env غير موجود"
}

