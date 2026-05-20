<template>
  <div>
    <label v-if="label" :for="id" class="shop-label">{{ label }}<span v-if="required" class="text-red-500"> *</span></label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :aria-invalid="!!error"
      :aria-describedby="error ? `${id}-error` : undefined"
      class="shop-input"
      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500/20': error }"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" :id="`${id}-error`" class="mt-1 text-xs text-red-600" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  id: { type: String, required: true },
  label: String,
  modelValue: [String, Number],
  type: { type: String, default: 'text' },
  placeholder: String,
  error: String,
  hint: String,
  required: Boolean,
  disabled: Boolean,
});
defineEmits(['update:modelValue']);
</script>
