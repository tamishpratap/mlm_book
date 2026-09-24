/**
 * Canonical WhatsApp Verification Message & Deep Link Generator
 *
 * Single source of truth for the member WhatsApp verification pre-filled message.
 * Conforms strictly to the approved format:
 *
 * Hello Support Team,
 *
 * I would like to verify my WhatsApp number for my account.
 *
 * User ID: [USER_ID]
 * Name: [NAME]
 * Email: [EMAIL]
 * Mobile Number: [REGISTERED_PHONE]
 *
 * Kindly verify and link this number to my account. Please let me know if any additional information is required.
 *
 * Thank you.
 */

/**
 * Builds the canonical verification text payload.
 * @param {object} member
 * @returns {string}
 */
export const buildWhatsAppVerificationMessage = (member) => {
  const userId = member?.user_id || '';
  const name = member?.name || '';
  const email = member?.email || '';
  const phone = member?.phone || '';

  return (
    'Hello Support Team,\n\n' +
    'I would like to verify my WhatsApp number for my account.\n\n' +
    `User ID: ${userId}\n` +
    `Name: ${name}\n` +
    `Email: ${email}\n` +
    `Mobile Number: ${phone}\n\n` +
    'Kindly verify and link this number to my account. Please let me know if any additional information is required.\n\n' +
    'Thank you.'
  );
};

/**
 * Builds the canonical wa.me deep link with full URL encoding.
 * @param {string} destinationNumber
 * @param {object} member
 * @returns {string}
 */
export const buildWhatsAppVerificationUrl = (destinationNumber, member) => {
  const cleanDigits = (destinationNumber || '').replace(/\D/g, '') || '919876543210';
  const message = buildWhatsAppVerificationMessage(member);
  return `https://wa.me/${cleanDigits}?text=${encodeURIComponent(message)}`;
};

/**
 * Resolves the final WhatsApp deep link for the runtime browser action.
 * Ensures that if a server response or browser cache returns a deprecated template
 * (e.g. "Hey," or "I am here for WhatsApp verification"), it is immediately rejected
 * and overridden with the canonical approved template.
 *
 * @param {string|null} serverUrl
 * @param {string} destinationNumber
 * @param {object} member
 * @returns {string}
 */
export const resolveWhatsAppVerificationUrl = (serverUrl, destinationNumber, member) => {
  if (
    serverUrl &&
    (serverUrl.includes('Hello%20Support%20Team') ||
      serverUrl.includes('Hello+Support+Team') ||
      serverUrl.includes('Hello Support Team')) &&
    !serverUrl.includes('Hey%2C') &&
    !serverUrl.includes('Hey,') &&
    !serverUrl.includes('I%20am%20here%20for%20WhatsApp%20verification') &&
    !serverUrl.includes('I am here for WhatsApp verification')
  ) {
    return serverUrl;
  }

  return buildWhatsAppVerificationUrl(destinationNumber, member);
};

/**
 * Canonical helper to check if a member is mobile-verified.
 * Considers mobile_verified_at, is_verified, and verification_status.
 *
 * @param {object|null|undefined} member
 * @returns {boolean}
 */
export const isMemberMobileVerified = (member) => {
  if (!member) return false;
  return Boolean(
    member.is_verified ||
    member.mobile_verified_at ||
    member.verification_status === 'verified'
  );
};

export default {
  buildWhatsAppVerificationMessage,
  buildWhatsAppVerificationUrl,
  resolveWhatsAppVerificationUrl,
  isMemberMobileVerified,
};
