/**
 * Upload Helper Utility
 * Builds clean FormData payloads for multipart/form-data requests
 * accommodating file uploads, null values, and boolean flags.
 */

export function buildFormData(data = {}, files = {}) {
  const formData = new FormData();

  // Append regular data fields
  Object.keys(data).forEach((key) => {
    const value = data[key];
    if (value === null || value === undefined) {
      // Omit or send empty string depending on backend expectations
      return;
    }
    if (typeof value === 'boolean') {
      formData.append(key, value ? '1' : '0');
    } else if (Array.isArray(value)) {
      value.forEach((item, index) => {
        if (typeof item === 'object' && item !== null && !(item instanceof File) && !(item instanceof Blob)) {
          formData.append(`${key}[${index}]`, JSON.stringify(item));
        } else {
          formData.append(`${key}[]`, item);
        }
      });
    } else if (typeof value === 'object' && !(value instanceof File) && !(value instanceof Blob)) {
      formData.append(key, JSON.stringify(value));
    } else {
      formData.append(key, value);
    }
  });

  // Append file objects
  Object.keys(files).forEach((key) => {
    const file = files[key];
    if (file instanceof File || file instanceof Blob) {
      formData.append(key, file);
    }
  });

  return formData;
}
