/**
 * نظام Offline بسيط جداً للعقود
 * فقط localStorage - بدون أي تعقيدات
 */

import { ref, onMounted } from 'vue';
import axios from 'axios';

export function useSimpleOffline() {
    const isOnline = ref(navigator.onLine);
    const pendingCount = ref(0);

    /**
     * حفظ عقد
     */
    const saveContract = async (contractData) => {
        // 🧪 وضع الاختبار: غيّر هذا السطر للاختبار
        const FORCE_OFFLINE_TEST = false; // غيّر إلى true لاختبار offline محلياً
        
        // إذا online، أرسل مباشرة
        if (navigator.onLine && !FORCE_OFFLINE_TEST) {
            try {
                const response = await axios.post('/api/addCarContract', contractData);
                return { success: true, online: true, data: response.data };
            } catch (error) {
                // فشل، احفظ offline
                return saveOffline(contractData);
            }
        }
        
        // offline
        return saveOffline(contractData);
    };

    /**
     * حفظ offline
     */
    const saveOffline = (contractData) => {
        const contracts = getContracts();
        contracts.push({
            ...contractData,
            _id: `offline_${Date.now()}`,
            _offline: true,
            _timestamp: new Date().toISOString()
        });
        
        localStorage.setItem('offline_contracts', JSON.stringify(contracts));
        updateCount();
        
        console.log('💾 تم الحفظ offline:', contracts.length);
        return { success: true, online: false };
    };

    /**
     * الحصول على العقود
     */
    const getContracts = () => {
        try {
            return JSON.parse(localStorage.getItem('offline_contracts') || '[]');
        } catch {
            return [];
        }
    };

    /**
     * مزامنة الكل
     */
    const syncAll = async () => {
        const contracts = getContracts();
        
        if (contracts.length === 0) {
            console.log('✅ لا يوجد عقود للمزامنة');
            return 0;
        }
        
        console.log('🔄 بدء مزامنة', contracts.length, 'عقد...');
        let synced = 0;
        let failed = 0;

        for (const contract of contracts) {
            try {
                await axios.post('/api/addCarContract', contract);
                console.log('✅ تمت مزامنة:', contract._id);
                synced++;
                // تأخير صغير بين الطلبات
                await new Promise(resolve => setTimeout(resolve, 500));
            } catch (error) {
                console.error('❌ فشل:', contract._id, error);
                failed++;
            }
        }

        // مسح المزامنة الناجحة فقط
        if (synced > 0) {
            // إزالة العقود التي تمت مزامنتها
            const remaining = contracts.slice(synced);
            localStorage.setItem('offline_contracts', JSON.stringify(remaining));
            updateCount();
            console.log(`✅ تمت مزامنة ${synced} عقد، بقي ${remaining.length}`);
        }

        return synced;
    };

    /**
     * تحديث العداد
     */
    const updateCount = () => {
        pendingCount.value = getContracts().length;
    };

    /**
     * مراقبة الاتصال
     */
    const handleOnline = () => {
        console.log('🌐 عاد الاتصال بالإنترنت');
        isOnline.value = true;
        // انتظر قليلاً ثم زامن
        setTimeout(() => {
            syncAll();
        }, 2000);
    };

    const handleOffline = () => {
        console.log('📡 انقطع الاتصال بالإنترنت');
        isOnline.value = false;
    };

    onMounted(() => {
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        updateCount();
        
        // تحقق من وجود عقود معلقة عند التحميل
        const pending = getContracts().length;
        if (pending > 0 && navigator.onLine) {
            console.log(`⏳ يوجد ${pending} عقد معلق، جاري المزامنة...`);
            setTimeout(() => syncAll(), 3000);
        }
    });

    return {
        isOnline,
        pendingCount,
        saveContract,
        getContracts,
        syncAll
    };
}

