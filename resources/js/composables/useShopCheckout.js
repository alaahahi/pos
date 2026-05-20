import { ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrf) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
}
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export function useShopCheckout(apiItems, clearCart) {
  const toast = useToast();
  const pricing = ref(null);
  const pricingLoading = ref(false);
  const couponCode = ref('');
  const phone = ref('');
  const notes = ref('');
  const phoneError = ref('');
  const submitting = ref(false);

  const recalculate = async () => {
    if (!apiItems.value.length) {
      pricing.value = null;
      return;
    }
    pricingLoading.value = true;
    try {
      const { data } = await axios.post('/api/shop/calculate', {
        items: apiItems.value,
        coupon_code: couponCode.value || undefined,
      });
      pricing.value = data;
    } catch {
      toast.error('تعذر حساب السلة');
    } finally {
      pricingLoading.value = false;
    }
  };

  watch(apiItems, recalculate, { deep: true });

  const validatePhone = () => {
    const digits = phone.value.replace(/\D/g, '');
    if (digits.length < 10 || digits.length > 15) {
      phoneError.value = 'أدخل رقم هاتف صالح (10–15 رقم)';
      return false;
    }
    phoneError.value = '';
    return true;
  };

  const applyCoupon = async () => {
    await recalculate();
    if (pricing.value?.coupon_discount > 0) {
      toast.success('تم تطبيق الكود');
    } else if (couponCode.value) {
      toast.warning('الكود غير صالح أو لم يتحقق الحد الأدنى');
    }
  };

  const submitOrder = async () => {
    if (!validatePhone()) return false;
    submitting.value = true;
    try {
      const { data } = await axios.post('/api/shop/orders', {
        customer_phone: phone.value,
        customer_notes: notes.value || null,
        coupon_code: couponCode.value || null,
        items: apiItems.value,
      });
      if (data.success) {
        toast.success(`تم تسجيل الطلب ${data.order_number}`);
        clearCart();
        pricing.value = null;
        couponCode.value = '';
        phone.value = '';
        notes.value = '';
        if (data.whatsapp_url) window.open(data.whatsapp_url, '_blank');
        return true;
      }
    } catch (e) {
      toast.error(e.response?.data?.message || 'فشل إرسال الطلب');
    } finally {
      submitting.value = false;
    }
    return false;
  };

  return {
    pricing,
    pricingLoading,
    couponCode,
    phone,
    notes,
    phoneError,
    submitting,
    recalculate,
    validatePhone,
    applyCoupon,
    submitOrder,
  };
}
