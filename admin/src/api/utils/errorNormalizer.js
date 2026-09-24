/**
 * Error Normalizer Utility
 * Standardizes backend HTTP errors (Laravel validation, authentication, server errors)
 * into a consistent, user-friendly error object for React components.
 */

export function normalizeError(error) {
  if (!error) {
    return {
      status: 500,
      message: 'An unexpected error occurred.',
      errors: {},
      isValidationError: false,
      isAuthError: false,
      isForbidden: false,
    };
  }

  // If error is already normalized
  if (error.__isNormalized) {
    return error;
  }

  const status = error.status || error.response?.status || 500;
  const responseData = error.response?.data || error.data || {};

  // Extract message
  let message = responseData.message || error.message || 'An unexpected error occurred.';
  if (status === 401) {
    message =
      responseData.message && responseData.message !== 'Unauthenticated.'
        ? responseData.message
        : 'Your session has expired. Please sign in again.';
  } else if (status === 403) {
    message = responseData.message || 'You do not have permission to perform this action.';
  } else if (status === 404) {
    message = responseData.message || 'The requested resource was not found.';
  } else if (status === 419) {
    message = 'Security token expired. Please refresh and try again.';
  } else if (status === 429) {
    message = 'Too many requests. Please wait a moment before trying again.';
  } else if (status >= 500) {
    message = 'A server error occurred. Please try again later.';
  }

  // Normalize Laravel validation errors: { field_name: ["Error message 1", "Error message 2"] }
  const rawErrors = responseData.errors || error.errors || {};
  const normalizedErrors = {};

  if (typeof rawErrors === 'object' && rawErrors !== null) {
    Object.keys(rawErrors).forEach((field) => {
      const fieldError = rawErrors[field];
      if (Array.isArray(fieldError)) {
        normalizedErrors[field] = fieldError.join(' ');
      } else if (typeof fieldError === 'string') {
        normalizedErrors[field] = fieldError;
      }
    });
  }

  return {
    __isNormalized: true,
    status,
    message,
    errors: normalizedErrors,
    hasErrors: Object.keys(normalizedErrors).length > 0,
    isValidationError: status === 422,
    isAuthError: status === 401 || status === 419,
    isForbidden: status === 403,
    isNotFound: status === 404,
    raw: error,
  };
}

/**
 * Extract single field error message from normalized error object.
 */
export function getFieldError(normalizedError, fieldName) {
  if (!normalizedError || !normalizedError.errors) return null;
  return normalizedError.errors[fieldName] || null;
}
