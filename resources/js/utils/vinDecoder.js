/**
 * Decode VIN via NHTSA API (optional validation, frontend only).
 * @param {string} vin
 * @returns {Promise<{valid: boolean, make?: string, vehicle_model?: string, year?: string, error?: string}>}
 */
export async function decodeVin(vin) {
  const cleaned = (vin || '').trim().toUpperCase();

  if (cleaned.length !== 17) {
    return { valid: false, error: 'رقم الشانصي يجب أن يكون 17 حرفاً' };
  }

  try {
    const response = await fetch(
      `https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues/${cleaned}?format=json`
    );
    const data = await response.json();
    const result = data.Results?.[0];

    if (!result) {
      return { valid: false, error: 'لم يتم العثور على بيانات' };
    }

    const errorCode = result.ErrorCode || '';
    const isValid = errorCode === '0' || errorCode.startsWith('0');

    return {
      valid: isValid,
      make: result.Make || '',
      vehicle_model: result.Model || '',
      year: result.ModelYear || '',
      error: isValid ? null : (result.ErrorText || 'الشانصي غير صالح'),
    };
  } catch {
    return { valid: false, error: 'تعذر الاتصال بخدمة التحقق' };
  }
}
