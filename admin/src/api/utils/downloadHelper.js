/**
 * Extract filename from Content-Disposition HTTP header.
 * Supports standard filename="..." as well as RFC 5987 filename*=UTF-8''...
 */
export function extractFilenameFromHeader(header, fallbackFilename = 'export.csv') {
  if (!header) return fallbackFilename;

  // 1. Try RFC 5987 encoded filename: filename*=UTF-8''...
  const utf8Match = header.match(/filename\*=UTF-8''([^;\n]*)/i);
  if (utf8Match && utf8Match[1]) {
    try {
      return decodeURIComponent(utf8Match[1].trim());
    } catch {
      return utf8Match[1].trim();
    }
  }

  // 2. Try standard filename="foo.csv" or filename=foo.csv
  const match = header.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i);
  if (match && match[1]) {
    const cleaned = match[1].replace(/['"]/g, '').trim();
    if (cleaned) return cleaned;
  }

  return fallbackFilename;
}

/**
 * Validate that a response blob is a genuine downloadable file (e.g. CSV)
 * and not an accidental JSON error payload or HTML error/login page.
 * Returns { isValid: boolean, error?: string }
 */
export async function validateBlobResponse(blob) {
  if (!blob || !(blob instanceof Blob)) {
    return { isValid: false, error: 'No downloadable file was received from the server.' };
  }

  if (blob.size === 0) {
    return { isValid: false, error: 'The exported file is empty (0 bytes).' };
  }

  // Check MIME type for JSON error response wrapped in Blob
  if (blob.type && blob.type.includes('application/json')) {
    try {
      const text = await blob.text();
      const json = JSON.parse(text);
      return { isValid: false, error: json.message || 'Export failed: Server returned an error.' };
    } catch {
      return { isValid: false, error: 'Export failed: Server returned an unexpected JSON response.' };
    }
  }

  // Check MIME type for HTML document (e.g. login redirect or error page)
  if (blob.type && blob.type.includes('text/html')) {
    return { isValid: false, error: 'Export failed: Server returned an HTML page. Please verify your administrative session.' };
  }

  // Inspect first 150 bytes for HTML doctype or tags
  try {
    const slice = blob.slice(0, 150);
    const text = await slice.text();
    const lower = text.toLowerCase();
    if (lower.includes('<!doctype') || lower.includes('<html') || lower.includes('<body') || lower.includes('<head')) {
      return { isValid: false, error: 'Export failed: Server returned an HTML error document instead of CSV.' };
    }
  } catch {
    // If text slice cannot be decoded, assume valid binary
  }

  return { isValid: true };
}

/**
 * Authenticated Download Helper Utility
 * Creates a Blob URL, temporary <a> tag, triggers click, and safely cleans up after download initiates.
 */
export function triggerBlobDownload(blobData, defaultFilename = 'download.csv') {
  if (!blobData) {
    throw new Error('Cannot download: No data provided.');
  }

  const blob = blobData instanceof Blob ? blobData : new Blob([blobData], { type: 'text/csv;charset=utf-8;' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.style.display = 'none';
  link.href = url;
  link.setAttribute('download', defaultFilename);

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  // Safely delay URL revocation so the browser's download manager has time to read the stream
  setTimeout(() => {
    try {
      window.URL.revokeObjectURL(url);
    } catch {
      // Ignore cleanup error
    }
  }, 1000);
}

/**
 * High-level helper: validates blob, resolves filename, and triggers download.
 */
export async function downloadBlobFromResponse(blobData, fallbackFilename = 'analytics-export.csv') {
  const validation = await validateBlobResponse(blobData);
  if (!validation.isValid) {
    throw new Error(validation.error);
  }

  const filename =
    blobData?.filename ||
    (blobData?.contentDisposition ? extractFilenameFromHeader(blobData.contentDisposition, fallbackFilename) : fallbackFilename);

  triggerBlobDownload(blobData, filename);
  return { success: true, filename };
}

export default {
  triggerBlobDownload,
  extractFilenameFromHeader,
  validateBlobResponse,
  downloadBlobFromResponse,
};
